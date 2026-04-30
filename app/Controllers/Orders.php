<?php

namespace App\Controllers;

class Orders extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // ========== HELPERS ==========

    private function _getOrder($id)
    {
        return $this->db->query('
            SELECT o.*, c.name as client_name
            FROM orders o
            LEFT JOIN client c ON c.id = o.client_id
            WHERE o.id = ?
        ', [$id])->getRowArray();
    }

    private function _groupAndSortVariations(array $variations): array
    {
        $groups = [];
        foreach ($variations as $v) {
            $groups[$v['group_name']][] = $v;
        }
        uksort($groups, function ($a, $b) use ($groups) {
            $minA = min(array_map(fn($v) => (float)$v['size'], $groups[$a]));
            $minB = min(array_map(fn($v) => (float)$v['size'], $groups[$b]));
            return $minA <=> $minB;
        });
        return $groups;
    }

    private function _getEffectiveBom($productId, $patternId)
    {
        $bom = $this->db->query('
            SELECT bom.*, pa.name as part_name
            FROM product_bill_of_material bom
            LEFT JOIN part pa ON pa.id = bom.part_id
            WHERE bom.product_id = ?
        ', [$productId])->getResultArray();

        if (!$patternId) return $bom;

        $pat = $this->db->query('SELECT is_default FROM product_pattern WHERE id = ?', [$patternId])->getRowArray();
        if (!$pat || $pat['is_default']) return $bom;

        $changes = $this->db->query('
            SELECT pc.*, pa.name as part_name
            FROM product_pattern_bom_change pc
            LEFT JOIN part pa ON pa.id = pc.part_id
            WHERE pc.product_pattern_id = ?
        ', [$patternId])->getResultArray();

        foreach ($changes as $ch) {
            if ($ch['action'] === 'add') {
                $bom[] = [
                    'part_id'         => $ch['part_id'],
                    'part_name'       => $ch['part_name'],
                    'part_pcs'        => $ch['part_pcs'],
                    'scale'           => $ch['scale'],
                    'variation_group' => $ch['variation_group'],
                    'podi_id'         => $ch['podi_id'],
                    'podi_pcs'        => $ch['podi_pcs'],
                ];
            } elseif ($ch['action'] === 'remove') {
                foreach ($bom as $k => $row) {
                    if ($row['part_id'] == $ch['part_id']) {
                        unset($bom[$k]);
                        break;
                    }
                }
            } elseif ($ch['action'] === 'replace' && $ch['replace_part_id']) {
                $newPart = $this->db->query('SELECT id, name FROM part WHERE id = ?', [$ch['replace_part_id']])->getRowArray();
                $bom[] = [
                    'part_id'         => $ch['part_id'],
                    'part_name'       => '',
                    'part_pcs'        => -1 * abs((float)$ch['part_pcs']),
                    'scale'           => $ch['scale'],
                    'variation_group' => $ch['variation_group'],
                    'podi_id'         => null,
                    'podi_pcs'        => 0,
                ];
                $bom[] = [
                    'part_id'         => $ch['replace_part_id'],
                    'part_name'       => $newPart['name'] ?? '',
                    'part_pcs'        => $ch['part_pcs'],
                    'scale'           => $ch['scale'],
                    'variation_group' => $ch['variation_group'],
                    'podi_id'         => $ch['podi_id'],
                    'podi_pcs'        => $ch['podi_pcs'],
                ];
            }
        }

        return array_values($bom);
    }


    // Recalculate and persist estimated_weight for an order
    private function _recalcOrderWeight(int $orderId): void
    {
        $items = $this->db->query(
            'SELECT oi.id, oi.product_id, oi.pattern_id FROM order_items oi WHERE oi.order_id = ?',
            [$orderId]
        )->getResultArray();

        $grand = 0.0;
        foreach ($items as $item) {
            $wmap = $this->_computeWeightMap(
                $item['product_id'],
                $item['pattern_id'],
                null,
                $orderId
            );
            $qtyRows = $this->db->query(
                'SELECT variation_id, quantity FROM order_item_qty WHERE order_item_id = ?',
                [$item['id']]
            )->getResultArray();
            foreach ($qtyRows as $q) {
                $vid = $q['variation_id'];
                $qty = (float)$q['quantity'];
                $wpp = $wmap[$vid] ?? 0.0;
                $grand += $qty * $wpp;
            }
        }

        $this->db->table('orders')->where('id', $orderId)->update(['estimated_weight' => round($grand, 4)]);
    }
    // Returns weight_per_pcs[variation_id] for a product+pattern combination
    private function _computeWeightMap($productId, $patternId, $mainPartId, $orderId = null)
    {
        // 1. Product data
        $prod = $this->db->query('
            SELECT p.main_part_id, pt.multiplication_factor, pt.variations as pt_variations, b.clasp_size
            FROM product p
            LEFT JOIN product_type pt ON pt.id = p.product_type_id
            LEFT JOIN body b ON b.id = p.body_id
            WHERE p.id = ?
        ', [$productId])->getRowArray();
        if (!$prod) return [];

        $factor    = (float)($prod['multiplication_factor'] ?? 1);
        $claspSize = (float)($prod['clasp_size'] ?? 0);
        $mainId    = (int)($prod['main_part_id'] ?? 0);

        // 2. kanni_per_inch and weight_per_kanni
        $kanniPerInch    = 0;
        $weightPerKanni  = 0;

        if ($orderId && $mainId) {
            $setup = $this->db->query('SELECT kanni_per_inch, weight_per_kanni FROM order_main_part_setup WHERE order_id = ? AND part_id = ?', [$orderId, $mainId])->getRowArray();
            if ($setup) {
                $kanniPerInch   = (float)$setup['kanni_per_inch'];
                $weightPerKanni = (float)$setup['weight_per_kanni'];
            }
        }
        if ($kanniPerInch <= 0 && $mainId) {
            $mp = $this->db->query('SELECT pcs FROM part WHERE id = ?', [$mainId])->getRowArray();
            if ($mp && isset($mp['pcs'])) $kanniPerInch = (float)$mp['pcs'];
        }
        if ($kanniPerInch <= 0) $kanniPerInch = 12.0;

        // 3. Variations for this product type
        $ptVarIds = [];
        if (!empty($prod['pt_variations'])) {
            $ptVarIds = array_filter(array_map('trim', explode(',', $prod['pt_variations'])));
        }
        if (empty($ptVarIds)) return [];

        $ph   = implode(',', array_fill(0, count($ptVarIds), '?'));
        $vars = $this->db->query("SELECT id, group_name, size FROM variation WHERE id IN ($ph)", $ptVarIds)->getResultArray();

        // 4. BOM
        $bom = $this->_getEffectiveBom($productId, $patternId);

        // Collect part IDs to load weights
        $bomPartIds = array_unique(array_filter(array_column($bom, 'part_id')));
        $partWeights = [];
        if ($bomPartIds) {
            $ph2 = implode(',', array_fill(0, count($bomPartIds), '?'));
            $pRows = $this->db->query("SELECT id, weight FROM part WHERE id IN ($ph2)", array_values($bomPartIds))->getResultArray();
            foreach ($pRows as $pr) $partWeights[$pr['id']] = (float)$pr['weight'];
        }

        // 5. Build weight_per_pcs per variation
        $weightMap = [];
        foreach ($vars as $v) $weightMap[$v['id']] = 0.0;

        foreach ($bom as $bomRow) {
            $partId   = $bomRow['part_id'];
            $partPcs  = (float)($bomRow['part_pcs'] ?? 0);
            $scale    = $bomRow['scale'] ?? '';
            $vgRaw    = trim($bomRow['variation_group'] ?? '');
            $vgList   = $vgRaw !== '' ? array_map('trim', explode(',', $vgRaw)) : [];
            $partWt   = $partWeights[$partId] ?? 0;

            foreach ($vars as $v) {
                $applies = empty($vgList) || in_array($v['group_name'], $vgList);
                if (!$applies) continue;
                $actualLen = max(0, (float)$v['size'] - $claspSize);

                $contrib = 0;
                if ($scale === 'Per Inch')  $contrib = $actualLen * $factor * $partPcs * $partWt;
                if ($scale === 'Per Pair')  $contrib = $partPcs * $partWt;
                if ($scale === 'Per Kanni') $contrib = $actualLen * $factor * $kanniPerInch * $partPcs * $partWt;

                $weightMap[$v['id']] += $contrib;
            }
        }

        // 6. Main part weight contribution per variation
        if ($mainId && $weightPerKanni > 0) {
            foreach ($vars as $v) {
                $actualLen = max(0, (float)$v['size'] - $claspSize);
                $weightMap[$v['id']] += $actualLen * $factor * $kanniPerInch * $weightPerKanni;
            }
        }

        // 7. CBOM contributions
        $cbomRows = $this->db->query('SELECT * FROM product_customize_bill_of_material WHERE product_id = ?', [$productId])->getResultArray();
        foreach ($cbomRows as $cbom) {
            $partId = $cbom['part_id'];
            $partWt = $partWeights[$partId] ?? 0;
            if (!isset($partWeights[$partId])) {
                $pw = $this->db->query('SELECT weight FROM part WHERE id = ?', [$partId])->getRowArray();
                $partWt = $pw ? (float)$pw['weight'] : 0;
            }
            $cbomQtys = $this->db->query('SELECT variation_id, part_quantity FROM product_customize_bill_of_material_quantity WHERE product_customize_bill_of_material_id = ?', [$cbom['id']])->getResultArray();
            foreach ($cbomQtys as $cq) {
                $vid = $cq['variation_id'];
                if (isset($weightMap[$vid])) {
                    $weightMap[$vid] += (float)($cq['part_quantity'] ?? 0) * $partWt;
                }
            }
        }

        return $weightMap;
    }

    // Returns [order_item_id => [variation_id => avg_touch%]]
    // avg_touch = (Σ group_weight × group_touch%) / total_weight × 100  (1 pc basis)
    private function _computeVariationTouchMap(int $orderId, array $savedTouch): array
    {
        if (empty($savedTouch)) return [];

        $kanniMap = [];
        $setup = $this->db->query('SELECT * FROM order_main_part_setup WHERE order_id = ?', [$orderId])->getResultArray();
        foreach ($setup as $s) {
            $kanniMap[$s['part_id']] = [
                'kanni_per_inch'   => (float)$s['kanni_per_inch'],
                'weight_per_kanni' => (float)$s['weight_per_kanni'],
            ];
        }

        $rawItems = $this->db->query('
            SELECT oi.id, oi.product_id, oi.pattern_id,
                   p.main_part_id, pt.variations as pt_variations,
                   pt.multiplication_factor, b.clasp_size
            FROM order_items oi
            JOIN product p ON p.id = oi.product_id
            LEFT JOIN product_type pt ON pt.id = p.product_type_id
            LEFT JOIN body b ON b.id = p.body_id
            WHERE oi.order_id = ?
            ORDER BY oi.sort_order, oi.id
        ', [$orderId])->getResultArray();

        $result = [];

        foreach ($rawItems as $item) {
            $itemId    = (int)$item['id'];
            $factor    = (float)($item['multiplication_factor'] ?? 1);
            $claspSize = (float)($item['clasp_size'] ?? 0);
            $mainId    = (int)($item['main_part_id'] ?? 0);

            $ptVarIds = [];
            if (!empty($item['pt_variations'])) {
                $ptVarIds = array_values(array_filter(array_map('trim', explode(',', $item['pt_variations']))));
            }
            if (empty($ptVarIds)) continue;

            $ph   = implode(',', array_fill(0, count($ptVarIds), '?'));
            $vars = $this->db->query(
                "SELECT id, group_name, name, size FROM variation WHERE id IN ($ph) ORDER BY group_name, size+0",
                $ptVarIds
            )->getResultArray();
            if (empty($vars)) continue;

            $bom = $this->_getEffectiveBom($item['product_id'], $item['pattern_id']);

            // Load podi weights from BOM rows
            $podiWeights = [];
            $podiIdsInBom = array_values(array_unique(array_filter(array_column($bom, 'podi_id'))));
            if ($podiIdsInBom) {
                $ph3   = implode(',', array_fill(0, count($podiIdsInBom), '?'));
                $podiRows = $this->db->query("SELECT id, weight FROM podi WHERE id IN ($ph3)", $podiIdsInBom)->getResultArray();
                foreach ($podiRows as $pr) $podiWeights[$pr['id']] = (float)$pr['weight'];
            }

            $bomPartIds = array_unique(array_filter(array_column($bom, 'part_id')));
            if ($mainId) $bomPartIds[] = $mainId;
            $bomPartIds = array_values(array_unique($bomPartIds));

            $partGroupMap = [];
            if ($bomPartIds) {
                $ph2   = implode(',', array_fill(0, count($bomPartIds), '?'));
                $pRows = $this->db->query("
                    SELECT pa.id, pa.weight, dg.name as group_name
                    FROM part pa
                    LEFT JOIN department d ON d.id = pa.department_id
                    LEFT JOIN department_group dg ON dg.id = d.department_group_id
                    WHERE pa.id IN ($ph2)
                ", array_values($bomPartIds))->getResultArray();
                foreach ($pRows as $pr) {
                    $partGroupMap[$pr['id']] = [
                        'weight'     => (float)$pr['weight'],
                        'group_name' => $pr['group_name'] ?? 'Unassigned',
                    ];
                }
            }

            $kanniPerInch   = isset($kanniMap[$mainId]) ? $kanniMap[$mainId]['kanni_per_inch']   : 0;
            $weightPerKanni = isset($kanniMap[$mainId]) ? $kanniMap[$mainId]['weight_per_kanni']  : 0;
            if ($kanniPerInch <= 0 && $mainId) {
                $mp = $this->db->query('SELECT pcs FROM part WHERE id = ?', [$mainId])->getRowArray();
                if ($mp) $kanniPerInch = (float)($mp['pcs'] ?? 0);
            }
            if ($kanniPerInch <= 0) $kanniPerInch = 12.0;

            $cbomRows = $this->db->query(
                'SELECT * FROM product_customize_bill_of_material WHERE product_id = ?',
                [$item['product_id']]
            )->getResultArray();
            $cbomQtyMap = [];
            foreach ($cbomRows as $cbom) {
                $rows = $this->db->query(
                    'SELECT variation_id, part_quantity FROM product_customize_bill_of_material_quantity WHERE product_customize_bill_of_material_id = ?',
                    [$cbom['id']]
                )->getResultArray();
                foreach ($rows as $r) {
                    $cbomQtyMap[$cbom['id']][$r['variation_id']] = (float)$r['part_quantity'];
                }
            }

            $result[$itemId] = [];

            foreach ($vars as $v) {
                $vid       = (int)$v['id'];
                $actualLen = max(0, (float)$v['size'] - $claspSize);
                $groupWt   = [];

                foreach ($bom as $bomRow) {
                    $partId  = (int)$bomRow['part_id'];
                    $partPcs = (float)($bomRow['part_pcs'] ?? 0);
                    $scale   = $bomRow['scale'] ?? '';
                    $vgRaw   = trim($bomRow['variation_group'] ?? '');
                    $vgList  = $vgRaw !== '' ? array_map('trim', explode(',', $vgRaw)) : [];

                    $applies = empty($vgList) || in_array($v['group_name'], $vgList);
                    if (!$applies) continue;

                    if ($partId === $mainId && $kanniPerInch > 0 && $weightPerKanni > 0) continue;

                    $partInfo = $partGroupMap[$partId] ?? null;
                    if (!$partInfo) continue;

                    $contrib = 0;
                    if ($scale === 'Per Inch')  $contrib = $actualLen * $factor * $partPcs * $partInfo['weight'];
                    if ($scale === 'Per Pair')  $contrib = $partPcs * $partInfo['weight'];
                    if ($scale === 'Per Kanni') $contrib = $actualLen * $factor * $kanniPerInch * $partPcs * $partInfo['weight'];

                    $gn = $partInfo['group_name'];
                    $groupWt[$gn] = ($groupWt[$gn] ?? 0) + $contrib;

                    // Podi contribution for this BOM row (same applicability as part)
                    $podiId  = $bomRow['podi_id'] ?? null;
                    $podiPcs = (float)($bomRow['podi_pcs'] ?? 0);
                    if ($podiId && $podiPcs > 0 && isset($podiWeights[$podiId])) {
                        $groupWt['Podi'] = ($groupWt['Podi'] ?? 0) + $podiPcs * $podiWeights[$podiId];
                    }
                }

                if ($mainId && $kanniPerInch > 0 && $weightPerKanni > 0) {
                    $mainContrib = $actualLen * $factor * $kanniPerInch * $weightPerKanni;
                    $mainGn      = $partGroupMap[$mainId]['group_name'] ?? 'Unassigned';
                    $groupWt[$mainGn] = ($groupWt[$mainGn] ?? 0) + $mainContrib;
                }

                foreach ($cbomRows as $cbom) {
                    $partId   = (int)$cbom['part_id'];
                    $partInfo = $partGroupMap[$partId] ?? null;
                    if (!$partInfo) continue;
                    $qty = $cbomQtyMap[$cbom['id']][$vid] ?? 0;
                    if ($qty <= 0) continue;
                    $gn = $partInfo['group_name'];
                    $groupWt[$gn] = ($groupWt[$gn] ?? 0) + $qty * $partInfo['weight'];
                }

                $totalWt = array_sum($groupWt);
                if ($totalWt <= 0) {
                    $result[$itemId][$vid] = 0.0;
                    continue;
                }
                $pure = 0;
                foreach ($groupWt as $gn => $wt) {
                    $pure += $wt * (float)($savedTouch[$gn] ?? 0) / 100;
                }
                $result[$itemId][$vid] = round($pure / $totalWt * 100, 2);
            }
        }

        return $result;
    }

    private function _calculatePartRequirements($orderId, $setupOverride = [], $filterItemId = null)
    {
        $kanniMap = [];
        if (!empty($setupOverride)) {
            foreach ($setupOverride as $partId => $vals) {
                $kanniMap[$partId] = [
                    'kanni_per_inch'   => (float)$vals['kanni_per_inch'],
                    'weight_per_kanni' => (float)$vals['weight_per_kanni'],
                ];
            }
        } else {
            $setup = $this->db->query('SELECT * FROM order_main_part_setup WHERE order_id = ?', [$orderId])->getResultArray();
            foreach ($setup as $s) {
                $kanniMap[$s['part_id']] = [
                    'kanni_per_inch'   => (float)$s['kanni_per_inch'],
                    'weight_per_kanni' => (float)$s['weight_per_kanni'],
                ];
            }
        }

        $items = $this->db->query('
            SELECT oi.*, p.product_type_id, p.main_part_id,
                   pt.multiplication_factor, pt.variations as pt_variations,
                   b.clasp_size
            FROM order_items oi
            JOIN product p ON p.id = oi.product_id
            LEFT JOIN product_type pt ON pt.id = p.product_type_id
            LEFT JOIN body b ON b.id = p.body_id
            WHERE oi.order_id = ?
        ', [$orderId])->getResultArray();

        $aggregated = [];

        foreach ($items as $item) {
            if ($filterItemId !== null && (int)$item['id'] !== (int)$filterItemId) continue;
            $factor   = (float)($item['multiplication_factor'] ?? 1);
            $claspSize = (float)($item['clasp_size'] ?? 0);

            $ptVarIds = [];
            if (!empty($item['pt_variations'])) {
                $ptVarIds = array_values(array_filter(array_map('trim', explode(',', $item['pt_variations']))));
            }
            if (empty($ptVarIds)) continue;

            $ph = implode(',', array_fill(0, count($ptVarIds), '?'));
            $variations = $this->db->query(
                "SELECT id, group_name, name, size FROM variation WHERE id IN ($ph) ORDER BY group_name, size+0",
                $ptVarIds
            )->getResultArray();

            $qtyRows = $this->db->query('SELECT variation_id, quantity FROM order_item_qty WHERE order_item_id = ?', [$item['id']])->getResultArray();
            $qtyMap = [];
            foreach ($qtyRows as $q) $qtyMap[$q['variation_id']] = (float)$q['quantity'];

            $varStats = [];
            foreach ($variations as $v) {
                $qty = $qtyMap[$v['id']] ?? 0;
                if ($qty <= 0) continue;
                $actualLen = (float)$v['size'] - $claspSize;
                $varStats[$v['id']] = [
                    'group_name'   => $v['group_name'],
                    'total_length' => $actualLen * $qty * $factor,
                    'raw_pcs'      => $qty,
                ];
            }

            $mainPartId        = $item['main_part_id'];
            $mainKanniPerInch  = isset($kanniMap[$mainPartId]) ? $kanniMap[$mainPartId]['kanni_per_inch'] : null;
            $kanniPerInch      = $mainKanniPerInch ?? 12.0;

            // Main Part Recompute — mirrors PATH 1 in partCalcDetail
            if ($mainPartId && $mainKanniPerInch !== null) {
                $sumLength = 0;
                foreach ($varStats as $vs) $sumLength += $vs['total_length'];
                if ($sumLength > 0) {
                    $req = $sumLength * $mainKanniPerInch;
                    if (!isset($aggregated[$mainPartId])) {
                        $aggregated[$mainPartId] = ['part_pcs' => 0, 'podi_id' => null, 'podi_pcs' => 0, 'sum_length' => 0];
                    }
                    $aggregated[$mainPartId]['part_pcs']   += $req;
                    $aggregated[$mainPartId]['sum_length']  = ($aggregated[$mainPartId]['sum_length'] ?? 0) + $sumLength;
                }
            }

            $effectiveBom = $this->_getEffectiveBom($item['product_id'], $item['pattern_id']);

            foreach ($effectiveBom as $bomRow) {
                $partId  = $bomRow['part_id'];
                // Skip main part — already handled by recompute above
                if ((int)$partId === (int)$mainPartId && $mainKanniPerInch !== null) continue;
                $partPcs = (float)($bomRow['part_pcs'] ?? 0);
                $scale   = $bomRow['scale'] ?? '';
                $vgRaw   = trim($bomRow['variation_group'] ?? '');
                $vgList  = $vgRaw !== '' ? array_map('trim', explode(',', $vgRaw)) : [];
                $podiId  = $bomRow['podi_id'] ?? null;
                $podiPcs = (float)($bomRow['podi_pcs'] ?? 0);

                $sumLength = 0;
                $sumRaw    = 0;
                foreach ($varStats as $vid => $vs) {
                    $applies = empty($vgList) || in_array($vs['group_name'], $vgList);
                    if ($applies) {
                        $sumLength += $vs['total_length'];
                        $sumRaw    += $vs['raw_pcs'];
                    }
                }

                $req = 0;
                if ($scale === 'Per Inch')  $req = $sumLength * $partPcs;
                if ($scale === 'Per Pair')  $req = $sumRaw    * $partPcs;

                if ($scale === 'Per Kanni') {
                    $req = $sumLength * $kanniPerInch * $partPcs;
                    if (!isset($aggregated[$partId])) {
                        $aggregated[$partId] = ['part_pcs' => 0, 'podi_id' => $podiId, 'podi_pcs' => 0, 'sum_length' => 0];
                    }
                    $aggregated[$partId]['part_pcs']   += $req;
                    $aggregated[$partId]['part_pcs']    = max(0, $aggregated[$partId]['part_pcs']);
                    $aggregated[$partId]['sum_length']  = ($aggregated[$partId]['sum_length'] ?? 0) + $sumLength;
                    if ($podiId) {
                        $aggregated[$partId]['podi_id']   = $podiId;
                        $aggregated[$partId]['podi_pcs'] += $sumLength > 0 ? $podiPcs * $sumLength : 0;
                    }
                    continue;
                }

                if ($req == 0) continue;

                if (!isset($aggregated[$partId])) {
                    $aggregated[$partId] = ['part_pcs' => 0, 'podi_id' => $podiId, 'podi_pcs' => 0, 'sum_length' => 0];
                }
                $aggregated[$partId]['part_pcs'] += $req;
                $aggregated[$partId]['part_pcs']  = max(0, $aggregated[$partId]['part_pcs']);
                if ($podiId) {
                    $aggregated[$partId]['podi_id']   = $podiId;
                    $aggregated[$partId]['podi_pcs'] += $sumLength > 0 ? $podiPcs * ($scale === 'Per Pair' ? $sumRaw : $sumLength) : 0;
                }
            }

            $cbomRows = $this->db->query('SELECT * FROM product_customize_bill_of_material WHERE product_id = ?', [$item['product_id']])->getResultArray();

            // Build cbomOverride map from pattern CBOM changes: [part_id][variation_id] = override row
            $cbomOverride = [];
            if (!empty($item['pattern_id'])) {
                $patCbomChanges = $this->db->query(
                    'SELECT * FROM product_pattern_cbom_change WHERE product_pattern_id = ?',
                    [$item['pattern_id']]
                )->getResultArray();
                foreach ($patCbomChanges as $pcc) {
                    $cbomOverride[$pcc['part_id']][$pcc['variation_id']] = $pcc;
                }
            }

            foreach ($cbomRows as $cbom) {
                $partId  = $cbom['part_id'];
                // Skip main part — already handled by recompute above
                if ((int)$partId === (int)$mainPartId && $mainKanniPerInch !== null) continue;
                $podiId  = $cbom['podi_id'] ?? null;
                $cbomQtys = $this->db->query('SELECT * FROM product_customize_bill_of_material_quantity WHERE product_customize_bill_of_material_id = ?', [$cbom['id']])->getResultArray();
                $partReq = 0;
                $podiReq = 0;
                foreach ($cbomQtys as $cq) {
                    $vid = (int)$cq['variation_id'];
                    $orderQty = $qtyMap[$cq['variation_id']] ?? 0;

                    // Apply pattern CBOM override for this part+variation
                    if (isset($cbomOverride[$partId][$vid])) {
                        $ov = $cbomOverride[$partId][$vid];
                        if ($ov['action'] === 'remove') continue; // skip this part for this size
                        if ($ov['action'] === 'replace') {
                            // Replace: skip original, add replacement below
                            // (handled in the add-overrides pass after this loop)
                            continue;
                        }
                        // action = add: override the quantity
                        $cq['part_quantity']  = (float)$ov['quantity'];
                        $cq['podi_id']        = $ov['podi_id'] ?? $cq['podi_id'];
                        $cq['podi_quantity']  = isset($ov['podi_qty']) ? (float)$ov['podi_qty'] : ($cq['podi_quantity'] ?? 0);
                    }

                    $partReq += $orderQty * (float)($cq['part_quantity'] ?? 0);
                    $podiReq += $orderQty * (float)($cq['podi_quantity'] ?? 0);
                }
                if ($partReq <= 0) continue;
                if (!isset($aggregated[$partId])) {
                    $aggregated[$partId] = ['part_pcs' => 0, 'podi_id' => $podiId, 'podi_pcs' => 0];
                }
                $aggregated[$partId]['part_pcs'] += $partReq;
                if ($podiId) {
                    $aggregated[$partId]['podi_id']   = $podiId;
                    $aggregated[$partId]['podi_pcs'] += $podiReq;
                }
            }

            // Apply pattern CBOM overrides for ADD and REPLACE (new entries not in base CBOM)
            foreach ($cbomOverride as $ovPartId => $varOverrides) {
                // Skip main part — already handled by recompute above
                if ((int)$ovPartId === (int)$mainPartId && $mainKanniPerInch !== null) continue;
                foreach ($varOverrides as $ovVarId => $ov) {
                    if (!isset($qtyMap[$ovVarId])) continue;
                    $targetPartId = $ov['action'] === 'replace' ? (int)$ov['replace_part_id'] : $ovPartId;
                    if (!$targetPartId) continue;
                    if ($ov['action'] === 'remove') continue; // already handled above
                    $ovQty = (float)$ov['quantity'];
                    if ($ovQty <= 0) continue;
                    $orderQty = $qtyMap[$ovVarId] ?? 0;
                    $partName = $this->db->query('SELECT name FROM part WHERE id = ?', [$targetPartId])->getRowArray()['name'] ?? "Part #{$targetPartId}";
                    if (!isset($aggregated[$targetPartId])) {
                        $aggregated[$targetPartId] = ['part_pcs' => 0, 'podi_id' => null, 'podi_pcs' => 0];
                    }
                    $aggregated[$targetPartId]['part_pcs'] += $orderQty * $ovQty;
                    if (!empty($ov['podi_id'])) {
                        $aggregated[$targetPartId]['podi_id']   = $ov['podi_id'];
                        $aggregated[$targetPartId]['podi_pcs'] += $orderQty * (float)($ov['podi_qty'] ?? 0);
                    }
                }
            }
        }

        return $aggregated;
    }

    // ========== INDEX ==========

    public function index()
    {
        $statusFilter = $this->request->getGet('status') ?? '';
        $clientFilter = (int)($this->request->getGet('client_id') ?? 0);
        $sql    = 'SELECT o.*, c.name as client_name, COUNT(oi.id) as item_count
                   FROM orders o
                   LEFT JOIN client c ON c.id = o.client_id
                   LEFT JOIN order_items oi ON oi.order_id = o.id
                   WHERE 1=1';
        $params = [];
        if ($statusFilter) { $sql .= ' AND o.status = ?'; $params[] = $statusFilter; }
        if ($clientFilter) { $sql .= ' AND o.client_id = ?'; $params[] = $clientFilter; }
        $sql .= ' GROUP BY o.id ORDER BY o.created_at DESC';

        return view('orders/index', [
            'title'        => 'Orders',
            'items'        => $this->db->query($sql, $params)->getResultArray(),
            'statusFilter' => $statusFilter,
            'clientFilter' => $clientFilter,
            'clients'      => $this->db->query('SELECT id, name FROM client ORDER BY name')->getResultArray(),
        ]);
    }

    // ========== CREATE / STORE ==========

    public function create()
    {
        return view('orders/form', [
            'title'   => 'Create Order',
            'clients' => $this->db->query('SELECT id, name FROM client ORDER BY name')->getResultArray(),
        ]);
    }

    public function store()
    {
        $d = $this->request->getPost();
        $this->db->table('orders')->insert([
            'title'      => trim($d['title'] ?? ''),
            'client_id'  => $d['client_id'] ?: null,
            'notes'      => $d['notes'] ?? '',
            'status'     => 'draft',
            'created_by' => $this->currentUser(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = $this->db->insertID();
        $this->db->table('orders')->where('id', $id)->update(['order_number' => 'ORD-' . str_pad($id, 3, '0', STR_PAD_LEFT)]);
        return redirect()->to('orders/view/' . $id)->with('success', 'Order created');
    }

    // ========== EDIT / UPDATE ==========

    public function edit($id)
    {
        $order = $this->_getOrder($id);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');
        if ($order['status'] !== 'draft') return redirect()->to('orders/view/' . $id)->with('info', 'Only the order header (title/client/notes) can be edited for Draft orders. To change products or quantities, use the panels below.');

        return view('orders/form', [
            'title'   => 'Edit Order',
            'item'    => $order,
            'clients' => $this->db->query('SELECT id, name FROM client ORDER BY name')->getResultArray(),
        ]);
    }

    public function update($id)
    {
        $order = $this->_getOrder($id);
        if (!$order || $order['status'] !== 'draft') return redirect()->to('orders')->with('error', 'Cannot edit');
        $d = $this->request->getPost();
        $this->db->table('orders')->where('id', $id)->update([
            'title'     => trim($d['title'] ?? ''),
            'client_id' => $d['client_id'] ?: null,
            'notes'     => $d['notes'] ?? '',
        ]);
        return redirect()->to('orders/view/' . $id)->with('success', 'Order updated');
    }

    // ========== DELETE ==========

    public function delete($id)
    {
        $order = $this->_getOrder($id);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');

        $this->db->transStart();
        $itemIds = array_column(
            $this->db->query('SELECT id FROM order_items WHERE order_id = ?', [$id])->getResultArray(),
            'id'
        );
        if ($itemIds) {
            $ph = implode(',', array_fill(0, count($itemIds), '?'));
            $this->db->query("DELETE FROM order_item_qty WHERE order_item_id IN ($ph)", $itemIds);
        }
        $this->db->query('DELETE FROM order_items WHERE order_id = ?', [$id]);
        $this->db->query('DELETE FROM order_main_part_setup WHERE order_id = ?', [$id]);
        $this->db->query('DELETE FROM orders WHERE id = ?', [$id]);
        $this->db->transComplete();

        return redirect()->to('orders')->with('success', 'Order deleted');
    }

    // ========== VIEW ==========

    public function view($id)
    {
        $order = $this->_getOrder($id);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');

        $rawItems = $this->db->query('
            SELECT oi.*, p.name as product_name, p.sku, p.image as product_image,
                   p.product_type_id, p.main_part_id,
                   pt.name as type_name, pt.variations as pt_variations, pt.multiplication_factor,
                   b.clasp_size, b.name as body_name,
                   pp.name as pattern_name,
                   s.name as stamp_name
            FROM order_items oi
            JOIN product p ON p.id = oi.product_id
            LEFT JOIN product_type pt ON pt.id = p.product_type_id
            LEFT JOIN body b ON b.id = p.body_id
            LEFT JOIN product_pattern pp ON pp.id = oi.pattern_id
            LEFT JOIN stamp s ON s.id = oi.stamp_id
            WHERE oi.order_id = ?
            ORDER BY oi.sort_order, oi.id
        ', [$id])->getResultArray();

        $items = [];
        $prevQtyMap = [];
        $prevTypeId = null;
        foreach ($rawItems as $item) {
            $varIds = [];
            if (!empty($item['pt_variations'])) {
                $varIds = array_filter(array_map('trim', explode(',', $item['pt_variations'])));
            }
            $variations = [];
            if ($varIds) {
                $ph = implode(',', array_fill(0, count($varIds), '?'));
                $variations = $this->db->query(
                    "SELECT * FROM variation WHERE id IN ($ph) ORDER BY group_name, size+0", $varIds
                )->getResultArray();
            }

            $qtyRows = $this->db->query('SELECT variation_id, quantity FROM order_item_qty WHERE order_item_id = ?', [$item['id']])->getResultArray();
            $qtyMap = [];
            foreach ($qtyRows as $q) $qtyMap[$q['variation_id']] = $q['quantity'];

            $item['variations']      = $variations;
            $item['variation_groups'] = $this->_groupAndSortVariations($variations);
            $item['qty_map']         = $qtyMap;
            $item['patterns']        = $this->db->query('SELECT id, name, tamil_name, is_default FROM product_pattern WHERE product_id = ? ORDER BY is_default DESC, name', [$item['product_id']])->getResultArray();
            $item['same_type_prev']  = ($item['product_type_id'] === $prevTypeId);
            $item['prev_qty_json']   = ($item['same_type_prev'] && $prevQtyMap) ? json_encode($prevQtyMap) : '{}';
            $item['weight_map']      = $this->_computeWeightMap($item['product_id'], $item['pattern_id'], $item['main_part_id'], $id);
            $items[] = $item;

            $prevQtyMap = $qtyMap;
            $prevTypeId = $item['product_type_id'];
        }

        // Load variation touch map if touch values are saved
        $savedTouch = [];
        $touchRows  = $this->db->query('SELECT group_name, touch_value FROM order_touch WHERE order_id = ?', [$id])->getResultArray();
        foreach ($touchRows as $t) $savedTouch[$t['group_name']] = (float)$t['touch_value'];
        $variationTouchMap = $this->_computeVariationTouchMap((int)$id, $savedTouch);

        return view('orders/view', [
            'title'             => $order['title'],
            'order'             => $order,
            'items'             => $items,
            'canEdit'           => in_array($order['status'], ['draft', 'confirmed']),
            'stamps'            => $this->db->query('SELECT id, name FROM stamp ORDER BY name')->getResultArray(),
            'productTypes'      => $this->db->query('SELECT id, name FROM product_type ORDER BY name')->getResultArray(),
            'variationTouchMap' => $variationTouchMap,
        ]);
    }

    // ========== ADD ITEM ==========

    public function addItem($orderId)
    {
        $order = $this->_getOrder($orderId);
        if (!$order || !in_array($order['status'], ['draft', 'confirmed'])) {
            return redirect()->to('orders/view/' . $orderId)->with('error', 'Order is not editable');
        }

        $d        = $this->request->getPost();
        $products = $d['products'] ?? [];
        if (empty($products)) return redirect()->to('orders/view/' . $orderId)->with('error', 'No products selected');

        $maxSort = (int)($this->db->query('SELECT MAX(sort_order) as m FROM order_items WHERE order_id = ?', [$orderId])->getRowArray()['m'] ?? 0);

        foreach ($products as $entry) {
            $productId = (int)($entry['product_id'] ?? 0);
            if (!$productId) continue;
            $maxSort++;
            $this->db->table('order_items')->insert([
                'order_id'   => $orderId,
                'product_id' => $productId,
                'pattern_id' => $entry['pattern_id'] ?: null,
                'stamp_id'   => $entry['stamp_id'] ?: null,
                'sort_order' => $maxSort,
                'created_by' => $this->currentUser(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $itemId = $this->db->insertID();
            $qtys = $entry['qty'] ?? [];
            foreach ($qtys as $varId => $qty) {
                $qty = (int)$qty;
                if ($qty > 0) {
                    $this->db->table('order_item_qty')->insert([
                        'order_item_id' => $itemId,
                        'variation_id'  => (int)$varId,
                        'quantity'      => $qty,
                        'created_by'    => $this->currentUser(),
                        'created_at'    => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        $this->_recalcOrderWeight($orderId);
        return redirect()->to('orders/view/' . $orderId)->with('success', count($products) . ' product(s) added to order');
    }

    // ========== REMOVE ITEM ==========

    public function removeItem($itemId)
    {
        $item = $this->db->query('SELECT * FROM order_items WHERE id = ?', [$itemId])->getRowArray();
        if (!$item) return redirect()->to('orders')->with('error', 'Not found');

        $order = $this->_getOrder($item['order_id']);
        if (!$order || !in_array($order['status'], ['draft', 'confirmed'])) {
            return redirect()->to('orders/view/' . $item['order_id'])->with('error', 'Order is not editable');
        }

        $this->db->query('DELETE FROM order_item_qty WHERE order_item_id = ?', [$itemId]);
        $this->db->query('DELETE FROM order_items WHERE id = ?', [$itemId]);

        $this->_recalcOrderWeight((int)$item['order_id']);
        return redirect()->to('orders/view/' . $item['order_id'])->with('success', 'Item removed');
    }

    // ========== SAVE ITEM QTY ==========

    public function saveItemQtyAjax($itemId)
    {
        $item = $this->db->query('SELECT * FROM order_items WHERE id = ?', [$itemId])->getRowArray();
        if (!$item) return $this->response->setJSON(['success' => false, 'error' => 'Not found']);

        $order = $this->_getOrder($item['order_id']);
        if (!$order || !in_array($order['status'], ['draft', 'confirmed'])) {
            return $this->response->setJSON(['success' => false, 'error' => 'Not editable']);
        }

        $d = $this->request->getPost();
        $this->db->table('order_items')->where('id', $itemId)->update([
            'pattern_id' => $d['pattern_id'] ?: null,
            'stamp_id'   => $d['stamp_id'] ?: null,
        ]);
        $this->db->query('DELETE FROM order_item_qty WHERE order_item_id = ?', [$itemId]);
        foreach (($d['qty'] ?? []) as $varId => $qty) {
            if ((int)$qty > 0) {
                $this->db->table('order_item_qty')->insert([
                    'order_item_id' => $itemId,
                    'variation_id'  => (int)$varId,
                    'quantity'      => (int)$qty,
                    'created_by'    => $this->currentUser(),
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }
        return $this->response->setJSON(['success' => true]);
    }

    public function saveItemQty($itemId)
    {
        $item = $this->db->query('SELECT * FROM order_items WHERE id = ?', [$itemId])->getRowArray();
        if (!$item) return redirect()->to('orders')->with('error', 'Not found');

        $order = $this->_getOrder($item['order_id']);
        if (!$order || !in_array($order['status'], ['draft', 'confirmed'])) {
            return redirect()->to('orders/view/' . $item['order_id'])->with('error', 'Order is not editable');
        }

        $d = $this->request->getPost();
        $this->db->table('order_items')->where('id', $itemId)->update([
            'pattern_id' => $d['pattern_id'] ?: null,
            'stamp_id'   => $d['stamp_id'] ?: null,
        ]);

        $this->db->query('DELETE FROM order_item_qty WHERE order_item_id = ?', [$itemId]);
        $qtys = $d['qty'] ?? [];
        foreach ($qtys as $varId => $qty) {
            $qty = (int)$qty;
            if ($qty > 0) {
                $this->db->table('order_item_qty')->insert([
                    'order_item_id' => $itemId,
                    'variation_id'  => (int)$varId,
                    'quantity'      => $qty,
                    'created_by'    => $this->currentUser(),
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->_recalcOrderWeight((int)$item['order_id']);
        return redirect()->to('orders/view/' . $item['order_id'])->with('success', 'Quantities saved');
    }

    // ========== UPDATE STATUS ==========

    public function updateStatus($id, $status)
    {
        $order = $this->_getOrder($id);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');

        $allowed = [
            'confirmed'  => 'production',
            'production' => 'completed',
        ];
        if (!isset($allowed[$order['status']]) || $allowed[$order['status']] !== $status) {
            return redirect()->to('orders/view/' . $id)->with('error', 'Invalid status transition');
        }

        $this->db->table('orders')->where('id', $id)->update(['status' => $status]);
        return redirect()->to('orders/view/' . $id)->with('success', 'Status updated to ' . ucfirst($status));
    }

    // ========== PREVIEW ==========

    public function preview($orderId)
    {
        $order = $this->_getOrder($orderId);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');

        $rawItems = $this->db->query('
            SELECT oi.*, p.name as product_name, pp.name as pattern_name, s.name as stamp_name,
                   pt.variations as pt_variations
            FROM order_items oi
            JOIN product p ON p.id = oi.product_id
            LEFT JOIN product_type pt ON pt.id = p.product_type_id
            LEFT JOIN product_pattern pp ON pp.id = oi.pattern_id
            LEFT JOIN stamp s ON s.id = oi.stamp_id
            WHERE oi.order_id = ?
            ORDER BY oi.sort_order, oi.id
        ', [$orderId])->getResultArray();

        $items = [];
        foreach ($rawItems as $item) {
            $varIds = [];
            if (!empty($item['pt_variations'])) {
                $varIds = array_filter(array_map('trim', explode(',', $item['pt_variations'])));
            }
            $variations = [];
            if ($varIds) {
                $ph = implode(',', array_fill(0, count($varIds), '?'));
                $variations = $this->db->query("SELECT * FROM variation WHERE id IN ($ph) ORDER BY group_name, size+0", $varIds)->getResultArray();
            }
            $qtyRows = $this->db->query('SELECT variation_id, quantity FROM order_item_qty WHERE order_item_id = ? AND quantity > 0', [$item['id']])->getResultArray();
            $qtyMap = [];
            foreach ($qtyRows as $q) $qtyMap[$q['variation_id']] = $q['quantity'];

            $item['variations'] = $variations;
            $item['qty_map']    = $qtyMap;
            $item['has_qty']    = count($qtyMap) > 0;
            $items[] = $item;
        }

        return view('orders/preview', [
            'title' => 'Preview: ' . $order['title'],
            'order' => $order,
            'items' => $items,
        ]);
    }

    // ========== CONFIRM ==========

    public function confirm($orderId)
    {
        $order = $this->_getOrder($orderId);
        if (!$order || $order['status'] !== 'draft') return redirect()->to('orders/view/' . $orderId)->with('error', 'Only draft orders can be confirmed');

        $mainParts = $this->db->query('
            SELECT DISTINCT p.main_part_id,
                   pa.pcs    AS default_kanni,
                   pa.weight AS default_weight
            FROM order_items oi
            JOIN product p  ON p.id  = oi.product_id
            JOIN part    pa ON pa.id = p.main_part_id
            WHERE oi.order_id = ? AND p.main_part_id IS NOT NULL
        ', [$orderId])->getResultArray();

        foreach ($mainParts as $mp) {
            $this->db->query(
                'INSERT IGNORE INTO order_main_part_setup (order_id, part_id, kanni_per_inch, weight_per_kanni) VALUES (?, ?, ?, ?)',
                [$orderId, $mp['main_part_id'], (float)($mp['default_kanni'] ?? 12), (float)($mp['default_weight'] ?? 0)]
            );
        }

        $this->db->table('orders')->where('id', $orderId)->update(['status' => 'confirmed']);
        return redirect()->to('orders/mainPartSetup/' . $orderId)->with('success', 'Order confirmed! Please review main part setup before viewing requirements.');
    }

    // ========== MAIN PART SETUP ==========

    public function mainPartSetup($id)
    {
        $order = $this->_getOrder($id);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');
        if ($order['status'] === 'draft') return redirect()->to('orders/view/' . $id)->with('error', 'Confirm order first');

        $setup = $this->db->query('
            SELECT omps.*, pa.name as part_name
            FROM order_main_part_setup omps
            JOIN part pa ON pa.id = omps.part_id
            WHERE omps.order_id = ?
              AND EXISTS (
                  SELECT 1 FROM order_items oi
                  JOIN product p ON p.id = oi.product_id
                  WHERE oi.order_id = omps.order_id
                    AND p.main_part_id = omps.part_id
              )
        ', [$id])->getResultArray();

        return view('orders/main_part_setup', [
            'title' => 'Main Part Setup: ' . $order['title'],
            'order' => $order,
            'setup' => $setup,
        ]);
    }

    public function saveMainPartSetup($id)
    {
        $order = $this->_getOrder($id);
        if (!$order || $order['status'] === 'draft') return redirect()->to('orders/view/' . $id)->with('error', 'Cannot edit setup');

        $d = $this->request->getPost();
        $partIds   = $d['part_id'] ?? [];
        $kannis    = $d['kanni_per_inch'] ?? [];
        $weights   = $d['weight_per_kanni'] ?? [];

        foreach ($partIds as $i => $partId) {
            $this->db->table('order_main_part_setup')
                ->where('order_id', $id)
                ->where('part_id', $partId)
                ->update([
                    'kanni_per_inch'   => (float)($kannis[$i] ?? 12),
                    'weight_per_kanni' => (float)($weights[$i] ?? 0),
                ]);
        }

        return redirect()->to('orders/partRequirements/' . $id)->with('success', 'Setup saved');
    }

    // ========== CALCULATION DETAIL (AJAX) ==========

    public function partCalcDetail($orderId, $partId, $itemId = 0)
    {
        $order = $this->_getOrder($orderId);
        if (!$order) return $this->response->setJSON(['error' => 'Order not found']);

        $partId = (int)$partId;
        $itemId = (int)$itemId;

        $setupRows = $this->db->query('SELECT part_id, kanni_per_inch, weight_per_kanni FROM order_main_part_setup WHERE order_id = ?', [$orderId])->getResultArray();
        $mainSetup = [];
        $kanniMap  = [];
        foreach ($setupRows as $s) {
            $mainSetup[$s['part_id']] = $s;
            $kanniMap[$s['part_id']]  = ['kanni_per_inch' => (float)$s['kanni_per_inch'], 'weight_per_kanni' => (float)$s['weight_per_kanni']];
        }

        $isMainPart        = isset($mainSetup[$partId]);
        $kanniPerInchSetup = $isMainPart ? (float)$mainSetup[$partId]['kanni_per_inch'] : 0;

        $items = $this->db->query('
            SELECT oi.id, oi.product_id, oi.pattern_id,
                   p.name as product_name, p.sku, p.main_part_id,
                   pat.name as pattern_name,
                   pt.multiplication_factor, pt.variations as pt_variations,
                   b.clasp_size
            FROM order_items oi
            JOIN product p ON p.id = oi.product_id
            LEFT JOIN product_pattern pat ON pat.id = oi.pattern_id
            LEFT JOIN product_type pt ON pt.id = p.product_type_id
            LEFT JOIN body b ON b.id = p.body_id
            WHERE oi.order_id = ?
        ', [$orderId])->getResultArray();

        $blocks = [];

        foreach ($items as $item) {
            if ($itemId > 0 && (int)$item['id'] !== $itemId) continue;
            $factor       = (float)($item['multiplication_factor'] ?? 1);
            $claspSize    = (float)($item['clasp_size'] ?? 0);
            $productLabel = $item['product_name'] . ($item['pattern_name'] ? ' â€” ' . $item['pattern_name'] : '');

            $ptVarIds = [];
            if (!empty($item['pt_variations'])) {
                $ptVarIds = array_values(array_filter(array_map('trim', explode(',', $item['pt_variations']))));
            }

            $qtyRows = $this->db->query('SELECT variation_id, quantity FROM order_item_qty WHERE order_item_id = ?', [$item['id']])->getResultArray();
            $qtyMap  = [];
            foreach ($qtyRows as $q) $qtyMap[$q['variation_id']] = (float)$q['quantity'];

            $variations = [];
            if ($ptVarIds) {
                $ph         = implode(',', array_fill(0, count($ptVarIds), '?'));
                $variations = $this->db->query(
                    "SELECT id, group_name, name, size FROM variation WHERE id IN ($ph) ORDER BY group_name, size+0", $ptVarIds
                )->getResultArray();
            }

            $varStats = [];
            foreach ($variations as $v) {
                $qty = $qtyMap[$v['id']] ?? 0;
                if ($qty <= 0) continue;
                $actualLen              = max(0, (float)$v['size'] - $claspSize);
                $varStats[$v['id']] = [
                    'name'         => $v['name'],
                    'size'         => (float)$v['size'],
                    'group_name'   => $v['group_name'],
                    'qty'          => $qty,
                    'actual_len'   => $actualLen,
                    'total_length' => $actualLen * $qty * $factor,
                    'raw_pcs'      => $qty,
                ];
            }

            $kanniPerInch = isset($kanniMap[$item['main_part_id']]) ? $kanniMap[$item['main_part_id']]['kanni_per_inch'] : 12.0;

            // PATH 1: Main Part Recompute
            if ($isMainPart && (int)$item['main_part_id'] === $partId) {
                $rows   = [];
                $sumLen = 0;
                foreach ($varStats as $vs) {
                    $rows[] = ['variation' => $vs['name'], 'size' => $vs['size'], 'clasp' => $claspSize, 'actual' => $vs['actual_len'], 'qty' => $vs['qty'], 'factor' => $factor, 'length' => $vs['total_length']];
                    $sumLen += $vs['total_length'];
                }
                if ($rows) {
                    $blocks[] = ['product_id' => (int)$item['product_id'], 'sku' => $item['sku'] ?? '', 'product' => $productLabel, 'source' => 'Main Part (Recompute)', 'scale' => 'Per Kanni', 'bom_pcs' => 1, 'kanni_per_inch' => $kanniPerInchSetup, 'clasp_size' => $claspSize, 'factor' => $factor, 'rows' => $rows, 'sum_length' => $sumLen, 'contribution' => $sumLen * $kanniPerInchSetup];
                }
                continue;
            }

            // PATH 2: Logic BOM
            $effectiveBom = $this->_getEffectiveBom($item['product_id'], $item['pattern_id']);
            foreach ($effectiveBom as $bomRow) {
                if ((int)$bomRow['part_id'] !== $partId) continue;
                $scale   = $bomRow['scale'] ?? '';
                $partPcs = (float)($bomRow['part_pcs'] ?? 0);
                $vgRaw   = trim($bomRow['variation_group'] ?? '');
                $vgList  = $vgRaw !== '' ? array_map('trim', explode(',', $vgRaw)) : [];
                $rows    = [];
                $sumLen  = 0;
                $sumRaw  = 0;
                foreach ($varStats as $vs) {
                    $applies = empty($vgList) || in_array($vs['group_name'], $vgList);
                    if (!$applies) continue;
                    if ($scale === 'Per Inch' || $scale === 'Per Kanni') {
                        $rows[] = ['variation' => $vs['name'], 'size' => $vs['size'], 'clasp' => $claspSize, 'actual' => $vs['actual_len'], 'qty' => $vs['qty'], 'factor' => $factor, 'length' => $vs['total_length']];
                        $sumLen += $vs['total_length'];
                    } elseif ($scale === 'Per Pair') {
                        $rows[] = ['variation' => $vs['name'], 'qty' => $vs['qty'], 'raw_pcs' => $vs['raw_pcs']];
                        $sumRaw += $vs['raw_pcs'];
                    }
                }
                $contrib = 0;
                if ($scale === 'Per Inch')  $contrib = $sumLen * $partPcs;
                if ($scale === 'Per Pair')  $contrib = $sumRaw * $partPcs;
                if ($scale === 'Per Kanni') $contrib = $sumLen * $kanniPerInch * $partPcs;
                if (empty($rows)) continue;
                $blocks[] = ['product_id' => (int)$item['product_id'], 'sku' => $item['sku'] ?? '', 'product' => $productLabel, 'source' => 'BOM â€” ' . $scale, 'scale' => $scale, 'bom_pcs' => $partPcs, 'kanni_per_inch' => $kanniPerInch, 'clasp_size' => $claspSize, 'factor' => $factor, 'vg_filter' => $vgRaw ?: '(all groups)', 'rows' => $rows, 'sum_length' => $sumLen, 'sum_raw' => $sumRaw, 'contribution' => $contrib];
            }

            // PATH 3: CBOM
            $cbomRows = $this->db->query('SELECT * FROM product_customize_bill_of_material WHERE product_id = ? AND part_id = ?', [$item['product_id'], $partId])->getResultArray();
            foreach ($cbomRows as $cbom) {
                $cbomQtys = $this->db->query('SELECT pcbmq.*, v.name as var_name, v.size as var_size FROM product_customize_bill_of_material_quantity pcbmq JOIN variation v ON v.id = pcbmq.variation_id WHERE pcbmq.product_customize_bill_of_material_id = ?', [$cbom['id']])->getResultArray();
                $rows  = [];
                $total = 0;
                foreach ($cbomQtys as $cq) {
                    $orderQty = $qtyMap[$cq['variation_id']] ?? 0;
                    $cbomPcs  = (float)($cq['part_quantity'] ?? 0);
                    $contrib  = $orderQty * $cbomPcs;
                    if ($cbomPcs <= 0 && $orderQty <= 0) continue;
                    $rows[] = ['variation' => $cq['var_name'], 'size' => $cq['var_size'], 'order_qty' => $orderQty, 'cbom_pcs' => $cbomPcs, 'contrib' => $contrib];
                    $total  += $contrib;
                }
                if ($total > 0) {
                    $blocks[] = ['product_id' => (int)$item['product_id'], 'sku' => $item['sku'] ?? '', 'product' => $productLabel, 'source' => 'Custom BOM', 'scale' => 'CBOM', 'rows' => $rows, 'contribution' => $total];
                }
            }
        }

        return $this->response->setJSON(['part_id' => $partId, 'blocks' => $blocks, 'grand_total' => array_sum(array_column($blocks, 'contribution'))]);
    }

    // ========== PART REQUIREMENTS ==========

    public function combinedMainPartSetup()
    {
        $orderIds = $this->request->getGet('order_ids');
        if (empty($orderIds) || count($orderIds) < 2) {
            return redirect()->to('orders')->with('error', 'Select at least 2 orders');
        }
        $orderIds = array_map('intval', $orderIds);

        $ph = implode(',', array_fill(0, count($orderIds), '?'));
        $orders = $this->db->query("SELECT o.id, o.order_number, o.title, c.name as client_name FROM orders o LEFT JOIN client c ON c.id = o.client_id WHERE o.id IN ($ph)", $orderIds)->getResultArray();
        if (count($orders) < 2) {
            return redirect()->to('orders')->with('error', 'Could not find selected orders');
        }

        // Index orders by id for easy lookup
        $ordersById = [];
        foreach ($orders as $o) $ordersById[$o['id']] = $o;

        // Build per-order setup rows: each order shows only its own main parts
        $setupRows = [];
        foreach ($orderIds as $oid) {
            $rows = $this->db->query('
                SELECT omps.part_id, omps.kanni_per_inch, omps.weight_per_kanni, pa.name as part_name
                FROM order_main_part_setup omps
                JOIN part pa ON pa.id = omps.part_id
                WHERE omps.order_id = ?
                  AND EXISTS (
                      SELECT 1 FROM order_items oi
                      JOIN product p ON p.id = oi.product_id
                      WHERE oi.order_id = omps.order_id
                        AND p.main_part_id = omps.part_id
                  )
                ORDER BY pa.name
            ', [$oid])->getResultArray();

            if (empty($rows)) {
                // Try to find main parts from order items even if setup not saved
                $partRows = $this->db->query('
                    SELECT DISTINCT pa.id as part_id, pa.name as part_name, pa.pcs as default_kanni, pa.weight as default_weight
                    FROM order_items oi
                    JOIN product p ON p.id = oi.product_id
                    JOIN part pa ON pa.id = p.main_part_id
                    WHERE oi.order_id = ? AND p.main_part_id IS NOT NULL
                    ORDER BY pa.name
                ', [$oid])->getResultArray();
                foreach ($partRows as $pr) {
                    $setupRows[] = [
                        'order_id'         => $oid,
                        'order_label'      => $ordersById[$oid]['order_number'] ?: ('#'.$oid),
                        'part_id'          => $pr['part_id'],
                        'part_name'        => $pr['part_name'],
                        'kanni_per_inch'   => (float)($pr['default_kanni'] ?? 12),
                        'weight_per_kanni' => (float)($pr['default_weight'] ?? 0),
                        'no_setup'         => true,
                    ];
                }
            } else {
                foreach ($rows as $r) {
                    $setupRows[] = [
                        'order_id'         => $oid,
                        'order_label'      => $ordersById[$oid]['order_number'] ?: ('#'.$oid),
                        'part_id'          => $r['part_id'],
                        'part_name'        => $r['part_name'],
                        'kanni_per_inch'   => (float)$r['kanni_per_inch'],
                        'weight_per_kanni' => (float)$r['weight_per_kanni'],
                        'no_setup'         => false,
                    ];
                }
            }
        }

        return view('orders/combined_main_part_setup', [
            'title'     => 'Combined Part Requirements Setup',
            'orders'    => $orders,
            'orderIds'  => $orderIds,
            'setupRows' => $setupRows,
        ]);
    }

    public function combinedPartRequirements()
    {
        $orderIds    = $this->request->getPost('order_ids');
        $rowOrderIds = $this->request->getPost('row_order_id');
        $rowPartIds  = $this->request->getPost('row_part_id');
        $rowKannis   = $this->request->getPost('row_kanni');
        $rowWeights  = $this->request->getPost('row_weight');
        $saveBack    = $this->request->getPost('save_to_orders');

        if (empty($orderIds) || count($orderIds) < 2) {
            return redirect()->to('orders')->with('error', 'Select at least 2 orders');
        }
        $orderIds = array_map('intval', $orderIds);

        // Build per-order setup override: $perOrderSetup[order_id][part_id] = [kanni, weight]
        $perOrderSetup = [];
        if ($rowOrderIds) {
            foreach ($rowOrderIds as $idx => $oid) {
                $pid = (int)($rowPartIds[$idx] ?? 0);
                if (!$pid) continue;
                $perOrderSetup[(int)$oid][$pid] = [
                    'kanni_per_inch'   => (float)($rowKannis[$idx] ?? 12),
                    'weight_per_kanni' => (float)($rowWeights[$idx] ?? 0),
                ];
            }
        }

        // Optionally save back to each order's main part setup
        if ($saveBack) {
            foreach ($perOrderSetup as $oid => $partMap) {
                foreach ($partMap as $mpId => $vals) {
                    $exists = $this->db->query('SELECT id FROM order_main_part_setup WHERE order_id = ? AND part_id = ?', [$oid, $mpId])->getRowArray();
                    if ($exists) {
                        $this->db->query('UPDATE order_main_part_setup SET kanni_per_inch = ?, weight_per_kanni = ? WHERE order_id = ? AND part_id = ?',
                            [$vals['kanni_per_inch'], $vals['weight_per_kanni'], $oid, $mpId]);
                    }
                }
            }
        }

        $combined = [];
        foreach ($orderIds as $oid) {
            $override   = $perOrderSetup[$oid] ?? [];
            $aggregated = $this->_calculatePartRequirements($oid, $override);

            // For main parts: recompute using this order's override or saved setup
            $mainSetupRows = $this->db->query('SELECT part_id, kanni_per_inch, weight_per_kanni FROM order_main_part_setup WHERE order_id = ?', [$oid])->getResultArray();
            $mainSetupMap  = [];
            foreach ($mainSetupRows as $ms) $mainSetupMap[$ms['part_id']] = $ms;

            // Apply override on top
            foreach ($override as $mpId => $vals) {
                $mainSetupMap[$mpId] = ['part_id' => $mpId, 'kanni_per_inch' => $vals['kanni_per_inch'], 'weight_per_kanni' => $vals['weight_per_kanni']];
            }

            foreach ($mainSetupMap as $mpId => $mpData) {
                if (!isset($aggregated[$mpId])) {
                    $aggregated[$mpId] = ['part_pcs' => 0, 'podi_id' => null, 'podi_pcs' => 0, 'sum_length' => 0];
                }
            }

            foreach ($mainSetupMap as $mpId => $mpData) {
                $kpi = (float)$mpData['kanni_per_inch'];
                if ($kpi <= 0) continue;
                $ois = $this->db->query('
                    SELECT oi.id, pt.multiplication_factor, b.clasp_size
                    FROM order_items oi
                    JOIN product p ON p.id = oi.product_id
                    LEFT JOIN product_type pt ON pt.id = p.product_type_id
                    LEFT JOIN body b ON b.id = p.body_id
                    WHERE oi.order_id = ? AND p.main_part_id = ?
                ', [$oid, $mpId])->getResultArray();
                $totalLength = 0;
                foreach ($ois as $oi) {
                    $factor    = (float)($oi['multiplication_factor'] ?? 1);
                    $claspSize = (float)($oi['clasp_size'] ?? 0);
                    $qtys = $this->db->query('
                        SELECT oiq.quantity, v.size FROM order_item_qty oiq
                        JOIN variation v ON v.id = oiq.variation_id
                        WHERE oiq.order_item_id = ? AND oiq.quantity > 0
                    ', [$oi['id']])->getResultArray();
                    foreach ($qtys as $q) {
                        $totalLength += max(0, (float)$q['size'] - $claspSize) * (float)$q['quantity'] * $factor;
                    }
                }
                $aggregated[$mpId]['sum_length'] = $totalLength;
                $aggregated[$mpId]['part_pcs']   = ($aggregated[$mpId]['part_pcs'] ?? 0) + $totalLength * $kpi;
            }

            foreach ($aggregated as $pid => $data) {
                if (!isset($combined[$pid])) {
                    $combined[$pid] = ['part_pcs' => 0, 'podi_id' => $data['podi_id'], 'podi_pcs' => 0, 'sum_length' => 0];
                }
                $combined[$pid]['part_pcs']   += $data['part_pcs'];
                $combined[$pid]['podi_pcs']   += $data['podi_pcs'] ?? 0;
                $combined[$pid]['sum_length'] += $data['sum_length'] ?? 0;
                if (empty($combined[$pid]['podi_id']) && !empty($data['podi_id'])) {
                    $combined[$pid]['podi_id'] = $data['podi_id'];
                }
            }
        }

        $ph = implode(',', array_fill(0, count($orderIds), '?'));
        $orders = $this->db->query("SELECT o.id, o.order_number, o.title, c.name as client_name FROM orders o LEFT JOIN client c ON c.id = o.client_id WHERE o.id IN ($ph)", $orderIds)->getResultArray();

        $allPartIds = array_keys($combined);
        $parts = []; $podies = [];
        if ($allPartIds) {
            $ph2 = implode(',', array_fill(0, count($allPartIds), '?'));
            $pRows = $this->db->query("
                SELECT pa.id, pa.name, pa.weight, pa.gatti, pa.is_main_part, d.name as dept_name
                FROM part pa LEFT JOIN department d ON d.id = pa.department_id
                WHERE pa.id IN ($ph2)
            ", $allPartIds)->getResultArray();
            foreach ($pRows as $p) $parts[$p['id']] = $p;

            uksort($combined, function($a, $b) use ($parts) {
                $dA = $parts[$a]['dept_name'] ?? 'zzz';
                $dB = $parts[$b]['dept_name'] ?? 'zzz';
                if ($dA !== $dB) return strcmp($dA, $dB);
                return strcmp($parts[$a]['name'] ?? '', $parts[$b]['name'] ?? '');
            });

            $podiIds = array_values(array_filter(array_unique(array_column($combined, 'podi_id'))));
            if ($podiIds) {
                $ph3 = implode(',', array_fill(0, count($podiIds), '?'));
                $poRows = $this->db->query("SELECT id, name, weight FROM podi WHERE id IN ($ph3)", array_values($podiIds))->getResultArray();
                foreach ($poRows as $po) $podies[$po['id']] = $po;
            }
        }

        // Build mainSetup for view (use overrides or saved values — per-part aggregated weight for display)
        $mainSetupForView = [];
        foreach ($perOrderSetup as $oid => $partMap) {
            foreach ($partMap as $mpId => $vals) {
                if (!isset($mainSetupForView[$mpId])) {
                    $mainSetupForView[$mpId] = $vals;
                }
            }
        }
        // Also pull from DB for any not overridden
        foreach ($orderIds as $oid) {
            $rows = $this->db->query('SELECT part_id, weight_per_kanni FROM order_main_part_setup WHERE order_id = ?', [$oid])->getResultArray();
            foreach ($rows as $r) {
                if (!isset($mainSetupForView[$r['part_id']])) {
                    $mainSetupForView[$r['part_id']] = ['kanni_per_inch' => 0, 'weight_per_kanni' => (float)$r['weight_per_kanni']];
                }
            }
        }

        $totalProducts = 0;
        foreach ($orderIds as $oid) {
            $cnt = $this->db->query('SELECT COUNT(*) as c FROM order_items WHERE order_id = ?', [$oid])->getRowArray();
            $totalProducts += (int)($cnt['c'] ?? 0);
        }

        return view('orders/combined_part_requirements', [
            'title'         => 'Combined Part Requirements',
            'orders'        => $orders,
            'combined'      => $combined,
            'parts'         => $parts,
            'podies'        => $podies,
            'mainSetup'     => $mainSetupForView,
            'totalProducts' => $totalProducts,
        ]);
    }


    public function productPartRequirements($orderId, $itemId)
    {
        $order = $this->_getOrder($orderId);
        if (!$order) return redirect()->to('orders')->with('error', 'Order not found');

        $item = $this->db->query("
            SELECT oi.*, p.name as product_name, p.sku,
                   COALESCE(pn.name, 'Default') as pattern_name
            FROM order_items oi
            JOIN product p ON p.id = oi.product_id
            LEFT JOIN product_pattern pp ON pp.id = oi.pattern_id
            LEFT JOIN pattern_name pn ON pn.id = pp.pattern_name_id
            WHERE oi.id = ? AND oi.order_id = ?
        ", [$itemId, $orderId])->getRowArray();
        if (!$item) return redirect()->to('orders/view/' . $orderId)->with('error', 'Item not found');

        $aggregated = $this->_calculatePartRequirements($orderId, [], (int)$itemId);

        $mainSetup = [];
        $setupRows = $this->db->query('SELECT part_id, kanni_per_inch, weight_per_kanni FROM order_main_part_setup WHERE order_id = ?', [$orderId])->getResultArray();
        foreach ($setupRows as $s) $mainSetup[$s['part_id']] = $s;

        $partIds = array_values(array_keys($aggregated));
        $parts = $podies = [];
        if ($partIds) {
            $ph = implode(',', array_fill(0, count($partIds), '?'));
            $pRows = $this->db->query("SELECT pa.id, pa.name, pa.weight, pa.gatti, pa.is_main_part, d.name as dept_name FROM part pa LEFT JOIN department d ON d.id = pa.department_id WHERE pa.id IN ($ph)", $partIds)->getResultArray();
            foreach ($pRows as $p) $parts[$p['id']] = $p;
            $podiIds = array_values(array_values(array_filter(array_unique(array_column($aggregated, 'podi_id')))));
            if ($podiIds) {
                $ph2 = implode(',', array_fill(0, count($podiIds), '?'));
                $podiRows = $this->db->query("SELECT * FROM podi WHERE id IN ($ph2)", $podiIds)->getResultArray();
                foreach ($podiRows as $po) $podies[$po['id']] = $po;
            }
            uksort($aggregated, function ($a, $b) use ($parts) {
                $dA = $parts[$a]['dept_name'] ?? 'zzz';
                $dB = $parts[$b]['dept_name'] ?? 'zzz';
                if ($dA !== $dB) return strcmp($dA, $dB);
                return strcmp($parts[$a]['name'] ?? '', $parts[$b]['name'] ?? '');
            });
        }

        return view('orders/part_requirements_single', [
            'title'      => 'Part Req: ' . ($item['product_name'] ?? ''),
            'order'      => $order,
            'item'       => $item,
            'aggregated' => $aggregated,
            'parts'      => $parts,
            'podies'     => $podies,
            'mainSetup'  => $mainSetup,
        ]);
    }

    public function productPartRequirementsPdf($orderId, $itemId)
    {
        $order = $this->_getOrder($orderId);
        if (!$order) return redirect()->to('orders')->with('error', 'Order not found');

        $item = $this->db->query("
            SELECT oi.*, p.name as product_name, p.sku,
                   COALESCE(pn.name, 'Default') as pattern_name
            FROM order_items oi
            JOIN product p ON p.id = oi.product_id
            LEFT JOIN product_pattern pp ON pp.id = oi.pattern_id
            LEFT JOIN pattern_name pn ON pn.id = pp.pattern_name_id
            WHERE oi.id = ? AND oi.order_id = ?
        ", [$itemId, $orderId])->getRowArray();
        if (!$item) return redirect()->to('orders/view/' . $orderId)->with('error', 'Item not found');

        $aggregated = $this->_calculatePartRequirements($orderId, [], (int)$itemId);

        $mainSetup = [];
        $setupRows = $this->db->query('SELECT part_id, kanni_per_inch, weight_per_kanni FROM order_main_part_setup WHERE order_id = ?', [$orderId])->getResultArray();
        foreach ($setupRows as $s) $mainSetup[$s['part_id']] = $s;

        $partIds = array_keys($aggregated);
        $parts = $podies = [];
        if ($partIds) {
            $ph = implode(',', array_fill(0, count($partIds), '?'));
            $pRows = $this->db->query("SELECT pa.id, pa.name, pa.tamil_name, pa.weight, pa.gatti, pa.is_main_part, d.name as dept_name FROM part pa LEFT JOIN department d ON d.id = pa.department_id WHERE pa.id IN ($ph)", $partIds)->getResultArray();
            foreach ($pRows as $p) $parts[$p['id']] = $p;
            $podiIds = array_values(array_filter(array_unique(array_column($aggregated, 'podi_id'))));
            if ($podiIds) {
                $ph2 = implode(',', array_fill(0, count($podiIds), '?'));
                $podiRows = $this->db->query("SELECT * FROM podi WHERE id IN ($ph2)", $podiIds)->getResultArray();
                foreach ($podiRows as $po) $podies[$po['id']] = $po;
            }
            uksort($aggregated, function ($a, $b) use ($parts) {
                $dA = $parts[$a]['dept_name'] ?? 'zzz';
                $dB = $parts[$b]['dept_name'] ?? 'zzz';
                if ($dA !== $dB) return strcmp($dA, $dB);
                return strcmp($parts[$a]['name'] ?? '', $parts[$b]['name'] ?? '');
            });
        }

        $orderNum = $order['order_number'] ?: ('#' . $order['id']);
        $css = 'body{font-family:latha;font-size:12px;color:#222;}h1{font-size:15px;margin:0 0 6px 0;border-bottom:2px solid #333;padding-bottom:4px;}.hdr-table{width:100%;margin-bottom:10px;font-size:12px;}.hdr-table td{padding:2px 6px;vertical-align:top;}.hdr-label{font-weight:bold;color:#555;width:80px;}table{border-collapse:collapse;width:100%;font-size:11px;margin-bottom:14px;}thead th{background:#dce8f5;padding:4px 8px;text-align:center;border:1px solid #aaa;font-weight:bold;}tbody td{padding:4px 8px;border:1px solid #ccc;}.num{text-align:right;}.dept-row td{background:#e8f0fe;font-weight:bold;font-size:11px;padding:3px 8px;}tfoot td{background:#f0f4f8;font-weight:bold;border:1px solid #aaa;padding:4px 8px;}';

        $html  = '<html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>';
        $html .= '<h1>Part Requirements</h1>';
        $html .= '<table class="hdr-table"><tr>';
        $html .= '<td><span class="hdr-label">Order:</span> <strong>' . htmlspecialchars($orderNum) . '</strong></td>';
        $html .= '<td><span class="hdr-label">Date:</span> ' . date('d M Y', strtotime($order['created_at'])) . '</td>';
        $html .= '</tr><tr>';
        $html .= '<td><span class="hdr-label">Product:</span> <strong>' . htmlspecialchars($item['product_name'] ?? '') . '</strong></td>';
        $html .= '<td><span class="hdr-label">Pattern:</span> ' . htmlspecialchars($item['pattern_name'] ?? 'Default') . '</td>';
        $html .= '</tr></table>';
        $html .= '<table><thead><tr><th>#</th><th style="text-align:left;">Part Name</th><th>Total Pcs</th><th>Weight/pc (g)</th><th>Est. Weight (g)</th><th>Gatti Req (g)</th></tr></thead><tbody>';

        $totalWt = 0; $totalGatti = 0; $i = 1; $currentDept = null;
        foreach ($aggregated as $partId => $data) {
            $part     = $parts[$partId] ?? null;
            $tName    = trim($part['tamil_name'] ?? '');
            $pName    = $tName !== '' ? $tName : ($part ? $part['name'] : 'Part #' . $partId);
            $deptName = $part['dept_name'] ?? 'â';
            $isMain   = !empty($part['is_main_part']);
            $gattiPkg = (float)($part['gatti'] ?? 0);
            $wpp      = ($isMain && isset($mainSetup[$partId])) ? (float)$mainSetup[$partId]['weight_per_kanni'] : (float)($part['weight'] ?? 0);
            $pcs      = round($data['part_pcs'], 2);
            $wt       = round($pcs * $wpp, 4);
            $gattiReq = $gattiPkg > 0 ? round($wt * $gattiPkg / 1000, 4) : 0;
            $totalWt += $wt; $totalGatti += $gattiReq;
            if ($deptName !== $currentDept) { $currentDept = $deptName; $html .= '<tr class="dept-row"><td colspan="6">' . htmlspecialchars($deptName) . '</td></tr>'; }
            $html .= '<tr><td class="num">' . $i++ . '</td><td>' . htmlspecialchars($pName) . ($isMain ? ' *' : '') . '</td><td class="num">' . $pcs . '</td><td class="num">' . $wpp . '</td><td class="num">' . ($wt ?: 'â') . '</td><td class="num">' . ($gattiReq ?: 'â') . '</td></tr>';
        }
        $html .= '</tbody><tfoot><tr><td colspan="4" style="text-align:right;">TOTAL</td><td class="num">' . round($totalWt, 4) . '</td><td class="num">' . round($totalGatti, 4) . '</td></tr></tfoot></table></body></html>';

        return $this->response->setHeader('Content-Type', 'text/html; charset=utf-8')->setBody($html);
    }

    public function partRequirements($id)
    {
        $order = $this->_getOrder($id);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');
        if ($order['status'] === 'draft') return redirect()->to('orders/view/' . $id)->with('error', 'Confirm order first');

        $aggregated = $this->_calculatePartRequirements($id);

        $mainSetup = [];
        $setupRows = $this->db->query('SELECT omps.part_id, omps.kanni_per_inch, omps.weight_per_kanni FROM order_main_part_setup omps WHERE omps.order_id = ? AND EXISTS (SELECT 1 FROM order_items oi JOIN product p ON p.id = oi.product_id WHERE oi.order_id = omps.order_id AND p.main_part_id = omps.part_id)', [$id])->getResultArray();
        foreach ($setupRows as $s) $mainSetup[$s['part_id']] = $s;

        // Always include main parts from setup even when BOM calc gave req=0
        foreach ($mainSetup as $mpId => $mpData) {
            if (!isset($aggregated[$mpId])) {
                $aggregated[$mpId] = ['part_pcs' => 0, 'podi_id' => null, 'podi_pcs' => 0, 'sum_length' => 0];
            }
        }

        // Always compute kanni from order dimensions for main parts (DEFAULT), then add BOM pcs on top
        foreach ($mainSetup as $mpId => $mpData) {
            $kanniPerInch = (float)$mpData['kanni_per_inch'];
            if ($kanniPerInch <= 0) continue;

            $ois = $this->db->query('
                SELECT oi.id, pt.multiplication_factor, b.clasp_size
                FROM order_items oi
                JOIN product p            ON p.id  = oi.product_id
                LEFT JOIN product_type pt ON pt.id = p.product_type_id
                LEFT JOIN body b          ON b.id  = p.body_id
                WHERE oi.order_id = ? AND p.main_part_id = ?
            ', [$id, $mpId])->getResultArray();

            $totalLength = 0;
            foreach ($ois as $oi) {
                $factor    = (float)($oi['multiplication_factor'] ?? 1);
                $claspSize = (float)($oi['clasp_size'] ?? 0);
                $qtys = $this->db->query('
                    SELECT oiq.quantity, v.size
                    FROM order_item_qty oiq
                    JOIN variation v ON v.id = oiq.variation_id
                    WHERE oiq.order_item_id = ? AND oiq.quantity > 0
                ', [$oi['id']])->getResultArray();
                foreach ($qtys as $q) {
                    $totalLength += max(0, (float)$q['size'] - $claspSize) * (float)$q['quantity'] * $factor;
                }
            }

            $aggregated[$mpId]['sum_length']  = $totalLength;
            $aggregated[$mpId]['part_pcs']    = $totalLength * $kanniPerInch;
        }

        $partIds = array_keys($aggregated);
        $parts   = [];
        $podies  = [];
        if ($partIds) {
            $ph    = implode(',', array_fill(0, count($partIds), '?'));
            $pRows = $this->db->query("
                SELECT pa.id, pa.name, pa.weight, pa.gatti, pa.is_main_part,
                       d.name as dept_name
                FROM part pa
                LEFT JOIN department d ON d.id = pa.department_id
                WHERE pa.id IN ($ph)
            ", $partIds)->getResultArray();
            foreach ($pRows as $p) $parts[$p['id']] = $p;

            uksort($aggregated, function ($a, $b) use ($parts) {
                $dA = $parts[$a]['dept_name'] ?? 'zzz';
                $dB = $parts[$b]['dept_name'] ?? 'zzz';
                if ($dA !== $dB) return strcmp($dA, $dB);
                return strcmp($parts[$a]['name'] ?? '', $parts[$b]['name'] ?? '');
            });

            $podiIds = array_values(array_filter(array_unique(array_column($aggregated, 'podi_id'))));
            if ($podiIds) {
                $ph2    = implode(',', array_fill(0, count($podiIds), '?'));
                $poRows = $this->db->query("SELECT id, name, weight FROM podi WHERE id IN ($ph2)", array_values($podiIds))->getResultArray();
                foreach ($poRows as $po) $podies[$po['id']] = $po;
            }
        }

        return view('orders/part_requirements', [
            'title'      => 'Part Requirements: ' . $order['title'],
            'order'      => $order,
            'aggregated' => $aggregated,
            'parts'      => $parts,
            'podies'     => $podies,
            'mainSetup'  => $mainSetup,
        ]);
    }

    public function partRequirementsPdf($id)
    {
        $order = $this->_getOrder($id);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');
        if ($order['status'] === 'draft') return redirect()->to('orders/view/' . $id)->with('error', 'Confirm order first');

        $aggregated = $this->_calculatePartRequirements($id);

        $mainSetup = [];
        $setupRows = $this->db->query('SELECT part_id, kanni_per_inch, weight_per_kanni FROM order_main_part_setup WHERE order_id = ?', [$id])->getResultArray();
        foreach ($setupRows as $s) $mainSetup[$s['part_id']] = $s;

        foreach ($mainSetup as $mpId => $mpData) {
            if (!isset($aggregated[$mpId])) {
                $aggregated[$mpId] = ['part_pcs' => 0, 'podi_id' => null, 'podi_pcs' => 0, 'sum_length' => 0];
            }
        }

        foreach ($mainSetup as $mpId => $mpData) {
            $kanniPerInch = (float)$mpData['kanni_per_inch'];
            if ($kanniPerInch <= 0) continue;

            $ois = $this->db->query('
                SELECT oi.id, pt.multiplication_factor, b.clasp_size
                FROM order_items oi
                JOIN product p            ON p.id  = oi.product_id
                LEFT JOIN product_type pt ON pt.id = p.product_type_id
                LEFT JOIN body b          ON b.id  = p.body_id
                WHERE oi.order_id = ? AND p.main_part_id = ?
            ', [$id, $mpId])->getResultArray();

            $totalLength = 0;
            foreach ($ois as $oi) {
                $factor    = (float)($oi['multiplication_factor'] ?? 1);
                $claspSize = (float)($oi['clasp_size'] ?? 0);
                $qtys = $this->db->query('
                    SELECT oiq.quantity, v.size
                    FROM order_item_qty oiq
                    JOIN variation v ON v.id = oiq.variation_id
                    WHERE oiq.order_item_id = ? AND oiq.quantity > 0
                ', [$oi['id']])->getResultArray();
                foreach ($qtys as $q) {
                    $totalLength += max(0, (float)$q['size'] - $claspSize) * (float)$q['quantity'] * $factor;
                }
            }

            $aggregated[$mpId]['sum_length'] = $totalLength;
            $aggregated[$mpId]['part_pcs']   = $totalLength * $kanniPerInch;
        }

        $partIds = array_keys($aggregated);
        $parts   = [];
        $podies  = [];
        if ($partIds) {
            $ph    = implode(',', array_fill(0, count($partIds), '?'));
            $pRows = $this->db->query("
                SELECT pa.id, pa.name, pa.weight, pa.gatti, pa.is_main_part,
                       d.name as dept_name
                FROM part pa
                LEFT JOIN department d ON d.id = pa.department_id
                WHERE pa.id IN ($ph)
            ", $partIds)->getResultArray();
            foreach ($pRows as $p) $parts[$p['id']] = $p;

            uksort($aggregated, function ($a, $b) use ($parts) {
                $dA = $parts[$a]['dept_name'] ?? 'zzz';
                $dB = $parts[$b]['dept_name'] ?? 'zzz';
                if ($dA !== $dB) return strcmp($dA, $dB);
                return strcmp($parts[$a]['name'] ?? '', $parts[$b]['name'] ?? '');
            });

            $podiIds = array_values(array_filter(array_unique(array_column($aggregated, 'podi_id'))));
            if ($podiIds) {
                $ph2    = implode(',', array_fill(0, count($podiIds), '?'));
                $poRows = $this->db->query("SELECT id, name, weight FROM podi WHERE id IN ($ph2)", array_values($podiIds))->getResultArray();
                foreach ($poRows as $po) $podies[$po['id']] = $po;
            }
        }

        // Fetch tamil_name for parts
        if ($partIds) {
            $ph = implode(',', array_fill(0, count($partIds), '?'));
            $tRows = $this->db->query("SELECT id, tamil_name FROM part WHERE id IN ($ph)", $partIds)->getResultArray();
            foreach ($tRows as $t) {
                if (isset($parts[$t['id']])) $parts[$t['id']]['tamil_name'] = $t['tamil_name'];
            }
        }

        // Build podi summary: group podi_pcs by podi_id
        $podiSummary = [];
        foreach ($aggregated as $partId => $data) {
            $pid = $data['podi_id'] ?? null;
            if ($pid && ($data['podi_pcs'] ?? 0) > 0) {
                $podiSummary[$pid] = ($podiSummary[$pid] ?? 0) + (float)$data['podi_pcs'];
            }
        }

        $css = '
            body { font-family: latha; font-size: 12px; color: #222; }
            h1 { font-size: 16px; margin: 0 0 6px 0; border-bottom: 2px solid #333; padding-bottom: 4px; }
            .hdr-table { width: 100%; margin-bottom: 10px; font-size: 12px; }
            .hdr-table td { padding: 2px 6px; vertical-align: top; }
            .hdr-label { font-weight: bold; color: #555; width: 80px; }
            table { border-collapse: collapse; width: 100%; font-size: 11px; margin-bottom: 14px; }
            thead th { background: #dce8f5; padding: 4px 8px; text-align: center; border: 1px solid #aaa; font-weight: bold; }
            tbody td { padding: 4px 8px; border: 1px solid #ccc; }
            .num { text-align: right; }
            .dept-row td { background: #e8f0fe; font-weight: bold; font-size: 11px; padding: 3px 8px; }
            tfoot td { background: #f0f4f8; font-weight: bold; border: 1px solid #aaa; padding: 4px 8px; }
            .section-title { font-size: 13px; font-weight: bold; margin: 10px 0 4px 0; border-left: 3px solid #4a90d9; padding-left: 6px; }
        ';

        $orderNum = $order['order_number'] ?: ('#' . $order['id']);

        $html  = '<html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>';
        $html .= '<h1>Part Requirements</h1>';
        $html .= '<table class="hdr-table"><tr>';
        $html .= '<td><span class="hdr-label">Order No:</span> <strong>' . htmlspecialchars($orderNum) . '</strong></td>';
        $html .= '<td><span class="hdr-label">Date:</span> ' . date('d M Y', strtotime($order['created_at'])) . '</td>';
        $html .= '</tr><tr>';
        $html .= '<td colspan="2"><span class="hdr-label">Title:</span> ' . htmlspecialchars($order['title']) . '</td>';
        $html .= '</tr>';
        if (!empty($order['client_name'])) {
            $html .= '<tr><td colspan="2"><span class="hdr-label">Client:</span> ' . htmlspecialchars($order['client_name']) . '</td></tr>';
        }
        if (!empty($order['notes'])) {
            $html .= '<tr><td colspan="2"><span class="hdr-label">Notes:</span> ' . htmlspecialchars($order['notes']) . '</td></tr>';
        }
        $html .= '</table>';

        $html .= '<table><thead><tr>
            <th>#</th>
            <th style="text-align:left;">Part Name</th>
            <th>Total Pcs</th>
            <th>Weight/pc (g)</th>
            <th>Est. Weight (g)</th>
            <th>Gatti Req (g)</th>
        </tr></thead><tbody>';

        $totalWt = 0; $totalGatti = 0; $i = 1; $currentDept = null;
        foreach ($aggregated as $partId => $data) {
            $part     = $parts[$partId] ?? null;
            $tamilName = trim($part['tamil_name'] ?? '');
            $pName    = $tamilName !== '' ? $tamilName : ($part ? $part['name'] : '(Part #' . $partId . ')');
            $deptName = $part['dept_name'] ?? 'â€”';
            $isMain   = !empty($part['is_main_part']);
            $gattiPkg = (float)($part['gatti'] ?? 0);

            if ($isMain && isset($mainSetup[$partId])) {
                $wpp = (float)$mainSetup[$partId]['weight_per_kanni'];
            } else {
                $wpp = (float)($part['weight'] ?? 0);
            }

            $pcs      = round($data['part_pcs'], 2);
            $wt       = round($pcs * $wpp, 4);
            $gattiReq = $gattiPkg > 0 ? round($wt * $gattiPkg / 1000, 4) : 0;
            $totalWt    += $wt;
            $totalGatti += $gattiReq;

            if ($deptName !== $currentDept) {
                $currentDept = $deptName;
                $html .= '<tr class="dept-row"><td colspan="6">' . htmlspecialchars($deptName) . '</td></tr>';
            }

            $html .= '<tr>';
            $html .= '<td class="num">' . $i++ . '</td>';
            $html .= '<td>' . htmlspecialchars($pName) . ($isMain ? ' *' : '') . '</td>';
            $html .= '<td class="num">' . $pcs . '</td>';
            $html .= '<td class="num">' . $wpp . '</td>';
            $html .= '<td class="num">' . $wt . '</td>';
            $html .= '<td class="num">' . ($gattiReq ?: 'â€”') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody><tfoot><tr>';
        $html .= '<td colspan="4" style="text-align:right;">TOTAL</td>';
        $html .= '<td class="num">' . round($totalWt, 4) . '</td>';
        $html .= '<td class="num">' . round($totalGatti, 4) . '</td>';
        $html .= '</tr></tfoot></table>';

        // Podi summary section
        if (!empty($podiSummary)) {
            $html .= '<div class="section-title">Podi Requirements</div>';
            $html .= '<table><thead><tr>
                <th style="text-align:left;">Podi Name</th>
                <th>Total Units</th>
                <th>Weight/unit (g)</th>
                <th>Total Weight (g)</th>
            </tr></thead><tbody>';

            $totalPodiWt = 0;
            foreach ($podiSummary as $pid => $totalUnits) {
                $podi = $podies[$pid] ?? null;
                if (!$podi) continue;
                $podiWt = round($totalUnits * (float)$podi['weight'], 4);
                $totalPodiWt += $podiWt;
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($podi['name']) . '</td>';
                $html .= '<td class="num">' . round($totalUnits, 2) . '</td>';
                $html .= '<td class="num">' . $podi['weight'] . '</td>';
                $html .= '<td class="num">' . $podiWt . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody><tfoot><tr>';
            $html .= '<td colspan="3" style="text-align:right;">TOTAL PODI WEIGHT</td>';
            $html .= '<td class="num">' . round($totalPodiWt, 4) . '</td>';
            $html .= '</tr></tfoot></table>';
        }

        $html .= '</body></html>';

        \App\Services\PdfService::make($html, 'part-requirements-' . $id . '.pdf');
    }

    // ========== UPDATE MASTER WEIGHTS ==========

    public function updateMasterWeights($orderId)
    {
        $order = $this->_getOrder($orderId);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');

        $d           = $this->request->getPost();
        $partWeights = $d['part_weight'] ?? [];
        $podiWeights = $d['podi_weight'] ?? [];

        foreach ($partWeights as $partId => $wt) {
            $wt = max(0, (float)$wt);
            $this->db->table('part')->where('id', (int)$partId)->update(['weight' => $wt]);
        }
        foreach ($podiWeights as $podiId => $wt) {
            $wt = max(0, (float)$wt);
            $this->db->table('podi')->where('id', (int)$podiId)->update(['weight' => $wt]);
        }

        return redirect()->to('orders/partRequirements/' . $orderId)
                         ->with('success', 'Master weights updated. Requirements recalculated.');
    }

    // ========== ORDER SHEET ==========

    public function orderSheet($id)
    {
        $order = $this->_getOrder($id);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');
        if ($order['status'] === 'draft') return redirect()->to('orders/view/' . $id)->with('error', 'Confirm order first');

        $rawItems = $this->db->query('
            SELECT oi.*, p.name as product_name, p.sku,
                   pt.variations as pt_variations, pt.multiplication_factor,
                   b.clasp_size,
                   pp.name as pattern_name, pp.tamil_name as pattern_tamil_name, pp.pattern_code,
                   s.name as stamp_name
            FROM order_items oi
            JOIN product p ON p.id = oi.product_id
            LEFT JOIN product_type pt ON pt.id = p.product_type_id
            LEFT JOIN body b ON b.id = p.body_id
            LEFT JOIN product_pattern pp ON pp.id = oi.pattern_id
            LEFT JOIN stamp s ON s.id = oi.stamp_id
            WHERE oi.order_id = ?
            ORDER BY oi.sort_order, oi.id
        ', [$id])->getResultArray();

        $items = [];
        foreach ($rawItems as $item) {
            $varIds = [];
            if (!empty($item['pt_variations'])) {
                $varIds = array_values(array_filter(array_map('trim', explode(',', $item['pt_variations']))));
            }
            $variations = [];
            if ($varIds) {
                $ph = implode(',', array_fill(0, count($varIds), '?'));
                $variations = $this->db->query("SELECT * FROM variation WHERE id IN ($ph) ORDER BY group_name, size+0", $varIds)->getResultArray();
            }
            $qtyRows = $this->db->query('SELECT variation_id, quantity FROM order_item_qty WHERE order_item_id = ? AND quantity > 0', [$item['id']])->getResultArray();
            $qtyMap = [];
            foreach ($qtyRows as $q) $qtyMap[$q['variation_id']] = $q['quantity'];

            $item['variations'] = $variations;
            $item['qty_map']    = $qtyMap;
            $items[] = $item;
        }

        return view('orders/order_sheet', [
            'title' => 'Order Sheet: ' . $order['title'],
            'order' => $order,
            'items' => $items,
        ]);
    }

    public function orderSheetPdf($id)
    {
        $order = $this->_getOrder($id);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');
        if ($order['status'] === 'draft') return redirect()->to('orders/view/' . $id)->with('error', 'Confirm order first');

        $rawItems = $this->db->query('
            SELECT oi.*, p.name as product_name, p.sku, p.main_part_id,
                   pt.variations as pt_variations, pt.multiplication_factor,
                   b.clasp_size,
                   pp.name as pattern_name, pp.tamil_name as pattern_tamil_name, pp.pattern_code,
                   s.name as stamp_name
            FROM order_items oi
            JOIN product p ON p.id = oi.product_id
            LEFT JOIN product_type pt ON pt.id = p.product_type_id
            LEFT JOIN body b ON b.id = p.body_id
            LEFT JOIN product_pattern pp ON pp.id = oi.pattern_id
            LEFT JOIN stamp s ON s.id = oi.stamp_id
            WHERE oi.order_id = ?
            ORDER BY oi.sort_order, oi.id
        ', [$id])->getResultArray();

        $items = [];
        foreach ($rawItems as $item) {
            $varIds = [];
            if (!empty($item['pt_variations'])) {
                $varIds = array_values(array_filter(array_map('trim', explode(',', $item['pt_variations']))));
            }
            $variations = [];
            if ($varIds) {
                $ph = implode(',', array_fill(0, count($varIds), '?'));
                $variations = $this->db->query("SELECT * FROM variation WHERE id IN ($ph) ORDER BY group_name, size+0", $varIds)->getResultArray();
            }
            $qtyRows = $this->db->query('SELECT variation_id, quantity FROM order_item_qty WHERE order_item_id = ? AND quantity > 0', [$item['id']])->getResultArray();
            $qtyMap = [];
            foreach ($qtyRows as $q) $qtyMap[$q['variation_id']] = $q['quantity'];
            $item['variations'] = $variations;
            $item['qty_map']    = $qtyMap;
            $items[] = $item;
        }

        // Pre-pass: collect header data (stamps + estimated weight)
        $headerStamps   = [];
        $totalEstWeight = 0.0;
        foreach ($items as $_item) {
            if (!empty($_item['stamp_name']) && !in_array($_item['stamp_name'], $headerStamps)) {
                $headerStamps[] = $_item['stamp_name'];
            }
            if (!empty($_item['qty_map'])) {
                $wmap = $this->_computeWeightMap(
                    $_item['product_id'], $_item['pattern_id'], (int)($_item['main_part_id'] ?? 0), $id
                );
                foreach ($_item['qty_map'] as $vid => $qty) {
                    $totalEstWeight += (float)($wmap[$vid] ?? 0) * (int)$qty;
                }
            }
        }

        $css = '
            body { font-family: latha; font-size: 12px; color: #222; }
            h2 { font-size: 15px; margin: 0 0 4px 0; }
            .meta { font-size: 11px; color: #555; margin-bottom: 10px; }
            .product-block { page-break-inside: avoid; break-inside: avoid; margin-bottom: 14px; border: 1px solid #ccc; border-radius: 4px; overflow: hidden; }
            table { page-break-inside: avoid; break-inside: avoid; }
            .product-header { background: #f0f4f8; padding: 6px 10px; font-weight: bold; font-size: 12px; }
            table { border-collapse: collapse; width: 100%; font-size: 11px; }
            th { background: #dce8f5; padding: 4px 8px; text-align: center; border: 1px solid #bbb; }
            td { padding: 4px 8px; text-align: center; border: 1px solid #ccc; }
            td.lbl { text-align: left; background: #f9f9f9; font-weight: bold; }
            .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 10px; }
            .badge-info { background: #d1ecf1; color: #0c5460; }
            .badge-warn { background: #fff3cd; color: #856404; }
            .stamp-label { font-size: 15px; font-weight: bold; color: #333; }
            .g-even { background: #fde8c8; }
            .g-odd  { background: #d4edda; }
        ';

        $html = '<html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>';
        // 3-column header: Left = stamps + date | Centre = title | Right = order# + est weight
        $html .= '<table width="100%" style="margin-bottom:10px;border-bottom:1px solid #ccc;padding-bottom:6px;"><tr>';
        // Left column
        $html .= '<td width="33%" style="vertical-align:top;">';
        if (!empty($headerStamps)) {
            $html .= '<div style="font-size:13px;font-weight:bold;">சீல் : ' . htmlspecialchars(implode(', ', $headerStamps)) . '</div>';
        }
        $html .= '<div style="font-size:11px;color:#555;margin-top:2px;">' . date('d M Y', strtotime($order['created_at'])) . '</div>';
        $html .= '</td>';
        // Centre column
        $html .= '<td width="34%" style="text-align:center;vertical-align:middle;">';
        $html .= '<div style="font-size:16px;font-weight:bold;">' . htmlspecialchars($order['title']) . '</div>';
        if ($order['client_name']) $html .= '<div style="font-size:11px;color:#555;margin-top:2px;">' . htmlspecialchars($order['client_name']) . '</div>';
        $html .= '</td>';
        // Right column
        $html .= '<td width="33%" style="text-align:right;vertical-align:top;">';
        $html .= '<div style="font-size:13px;font-weight:bold;">Order #' . (int)$order['id'] . '</div>';
        $html .= '<div style="font-size:11px;color:#555;margin-top:2px;">Est. Wt: ' . number_format($totalEstWeight, 2) . ' g</div>';
        $html .= '</td>';
        $html .= '</tr></table>';

        if (empty($items)) {
            $html .= '<p>No items in this order.</p>';
        } else {
            $allVarCols = [];
            $colTotals  = [];
            $grandTotal = 0;

            foreach ($items as $idx => $item) {
                $varsByGroup = [];
                foreach ($item['variations'] as $v) $varsByGroup[$v['group_name']][] = $v;

                $displayName = !empty($item['pattern_tamil_name'])
                    ? $item['pattern_tamil_name']
                    : (!empty($item['pattern_name']) ? $item['pattern_name'] : $item['product_name']);

                // Build active groups with sort key and totals
                $activeGroups = [];
                foreach ($varsByGroup as $gName => $vars) {
                    $active = array_values(array_filter($vars, fn($v) => ($item['qty_map'][$v['id']] ?? 0) > 0));
                    if (!empty($active)) {
                        $groupLabel  = !empty($active[0]['group_tamil_name']) ? $active[0]['group_tamil_name'] : $gName;
                        $groupTotal  = array_sum(array_map(fn($v) => (int)($item['qty_map'][$v['id']] ?? 0), $active));
                        $minSize     = min(array_map(fn($v) => (float)($v['size'] ?? 0), $active));
                        $activeGroups[] = ['label' => $groupLabel, 'vars' => $active, 'total' => $groupTotal, 'min_size' => $minSize];
                    }
                }
                // Sort groups: smallest min variation size first
                usort($activeGroups, fn($a, $b) => $a['min_size'] <=> $b['min_size']);

                $productTotal = array_sum(array_column($activeGroups, 'total'));

                // Accumulate for summary
                foreach ($activeGroups as $g) {
                    foreach ($g['vars'] as $v) {
                        $qty = (int)($item['qty_map'][$v['id']] ?? 0);
                        if (!isset($allVarCols[$v['id']])) {
                            $allVarCols[$v['id']] = [
                                'name'           => $v['name'],
                                'size'           => (float)($v['size'] ?? 0),
                                'group_label'    => $g['label'],
                                'group_min_size' => $g['min_size'],
                            ];
                            $colTotals[$v['id']] = 0;
                        }
                        $colTotals[$v['id']] += $qty;
                        $grandTotal           += $qty;
                    }
                }

                $html .= '<div class="product-block">';
                $html .= '<div class="product-header">' . ($idx + 1) . '. ' . htmlspecialchars($displayName);
                if (!empty($item['pattern_code'])) $html .= ' <span style="font-size:10px;color:#777;font-weight:normal;">[' . htmlspecialchars($item['pattern_code']) . ']</span>';
                if ($item['stamp_name']) $html .= ' <span class="stamp-label">சீல் : ' . htmlspecialchars($item['stamp_name']) . '</span>';
                if ($productTotal > 0) $html .= ' <span style="font-weight:normal;font-size:10px;color:#555;">— ' . $productTotal . ' pcs</span>';
                $html .= '</div>';

                if (!empty($activeGroups)) {
                    $html .= '<table><thead>';
                    // Row 1: group headers with total pcs, alternating colour
                    $html .= '<tr>';
                    foreach ($activeGroups as $gIdx => $g) {
                        $cls = ($gIdx % 2 === 0) ? 'g-even' : 'g-odd';
                        $html .= '<th colspan="' . count($g['vars']) . '" class="' . $cls . '" style="text-align:center;">'
                               . htmlspecialchars($g['label']) . ' (' . $g['total'] . ' pcs)</th>';
                    }
                    $html .= '</tr>';
                    // Row 2: variation names only, same alternating colour
                    $html .= '<tr>';
                    foreach ($activeGroups as $gIdx => $g) {
                        $cls = ($gIdx % 2 === 0) ? 'g-even' : 'g-odd';
                        foreach ($g['vars'] as $v) {
                            $html .= '<th class="' . $cls . '">' . htmlspecialchars($v['name']) . '</th>';
                        }
                    }
                    $html .= '</tr></thead><tbody><tr>';
                    // Row 3: quantities
                    foreach ($activeGroups as $g) {
                        foreach ($g['vars'] as $v) {
                            $html .= '<td>' . (int)($item['qty_map'][$v['id']] ?? 0) . '</td>';
                        }
                    }
                    $html .= '</tr></tbody></table>';
                } else {
                    $html .= '<p style="padding:6px 10px;color:#888;font-size:11px;">No quantities entered</p>';
                }
                $html .= '</div>';
            }
            // Build summary section
            if (!empty($allVarCols)) {
                uasort($allVarCols, function($a, $b) {
                    if ($a['group_min_size'] !== $b['group_min_size'])
                        return $a['group_min_size'] <=> $b['group_min_size'];
                    return $a['size'] <=> $b['size'];
                });
                $varIds = array_keys($allVarCols);

                // Build group spans
                $headerGroups = [];
                $prevLabel    = null;
                foreach ($allVarCols as $vid => $vc) {
                    if ($vc['group_label'] !== $prevLabel) {
                        $headerGroups[] = ['label' => $vc['group_label'], 'count' => 0, 'total' => 0];
                        $prevLabel = $vc['group_label'];
                    }
                    $headerGroups[count($headerGroups)-1]['count']++;
                    $headerGroups[count($headerGroups)-1]['total'] += $colTotals[$vid];
                }

                $html .= '<div style="page-break-before:always;"></div>';
                $html .= '<h3 style="margin:0 0 6px 0;">Order Summary &mdash; ' . $grandTotal . ' pcs</h3>';
                $html .= '<table><thead>';

                // Row 1: group headers with group total
                $html .= '<tr>';
                foreach ($headerGroups as $gIdx => $g) {
                    $cls = ($gIdx % 2 === 0) ? 'g-even' : 'g-odd';
                    $html .= '<th colspan="' . $g['count'] . '" class="' . $cls . '">'
                           . htmlspecialchars($g['label']) . ' (' . $g['total'] . ' pcs)</th>';
                }
                $html .= '<th>Total</th></tr>';

                // Row 2: variation names
                $html .= '<tr>';
                $gIdx2 = 0; $prevLabel2 = null;
                foreach ($allVarCols as $vid => $vc) {
                    if ($vc['group_label'] !== $prevLabel2) { $gIdx2++; $prevLabel2 = $vc['group_label']; }
                    $cls = (($gIdx2 - 1) % 2 === 0) ? 'g-even' : 'g-odd';
                    $html .= '<th class="' . $cls . '">' . htmlspecialchars($vc['name']) . '</th>';
                }
                $html .= '<th></th></tr></thead>';

                // Single data row: totals only
                $html .= '<tbody><tr>';
                foreach ($varIds as $vid) {
                    $v = $colTotals[$vid];
                    $html .= '<td>' . ($v > 0 ? $v : '&mdash;') . '</td>';
                }
                $html .= '<td><strong>' . $grandTotal . '</strong></td></tr></tbody></table>';
            }
        }

        $html .= '</body></html>';
        \App\Services\PdfService::make($html, 'order-sheet-' . $id . '.pdf');
    }

    public function orderSheetSlipPdf($id)
    {
        $order = $this->_getOrder($id);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');
        if ($order['status'] === 'draft') return redirect()->to('orders/view/' . $id)->with('error', 'Confirm order first');

        $rawItems = $this->db->query('
            SELECT oi.*, p.name as product_name, p.sku, p.main_part_id, p.image as product_image,
                   pt.variations as pt_variations, pt.multiplication_factor,
                   b.clasp_size,
                   pp.name as pattern_name, pp.tamil_name as pattern_tamil_name, pp.pattern_code,
                   s.name as stamp_name
            FROM order_items oi
            JOIN product p ON p.id = oi.product_id
            LEFT JOIN product_type pt ON pt.id = p.product_type_id
            LEFT JOIN body b ON b.id = p.body_id
            LEFT JOIN product_pattern pp ON pp.id = oi.pattern_id
            LEFT JOIN stamp s ON s.id = oi.stamp_id
            WHERE oi.order_id = ?
            ORDER BY oi.sort_order, oi.id
        ', [$id])->getResultArray();

        $items = [];
        foreach ($rawItems as $item) {
            $varIds = [];
            if (!empty($item['pt_variations'])) {
                $varIds = array_values(array_filter(array_map('trim', explode(',', $item['pt_variations']))));
            }
            $variations = [];
            if ($varIds) {
                $ph = implode(',', array_fill(0, count($varIds), '?'));
                $variations = $this->db->query("SELECT * FROM variation WHERE id IN ($ph) ORDER BY group_name, size+0", $varIds)->getResultArray();
            }
            $qtyRows = $this->db->query('SELECT variation_id, quantity FROM order_item_qty WHERE order_item_id = ? AND quantity > 0', [$item['id']])->getResultArray();
            $qtyMap = [];
            foreach ($qtyRows as $q) $qtyMap[$q['variation_id']] = $q['quantity'];
            $item['variations'] = $variations;
            $item['qty_map']    = $qtyMap;
            $items[] = $item;
        }

        $css = '
            body { font-family: latha; font-size: 11px; color: #222; margin: 0; padding: 0; }
            .slip { height: 96mm; overflow: hidden; box-sizing: border-box; padding: 3mm 4mm; }
            .cut { border-top: 1px dashed #aaa; margin: 0; }
            .slip-header { width: 100%; border-collapse: collapse; margin-bottom: 2mm; border-bottom: 1px solid #ddd; padding-bottom: 1mm; }
            .slip-left   { width: 33%; vertical-align: top; text-align: left; }
            .slip-centre { width: 34%; vertical-align: middle; text-align: center; }
            .slip-right  { width: 33%; vertical-align: top; text-align: right; }
            .slip-code-big { font-size: 13px; font-weight: bold; }
            .slip-pattern-name { font-size: 13px; font-weight: bold; text-align: center; margin: 2mm 0 1mm 0; color: #111; }
            .slip-serial-no { font-size: 13px; font-weight: 900; color: #000; margin-right: 2px; }
            .slip-date   { font-size: 9px; color: #666; margin-top: 1mm; }
            .slip-stamp  { font-size: 14px; font-weight: bold; color: #222; }
            .slip-order-no { font-size: 12px; font-weight: bold; }
            .slip-weight { font-size: 10px; color: #555; margin-top: 1mm; }
            .slip-pcs    { font-size: 10px; color: #555; }
            .slip-img { width: 100%; max-height: 40mm; object-fit: contain; display: block; margin-bottom: 2mm; }
            .slip-page-no { font-size: 9px; color: #999; text-align: right; margin-top: 1mm; margin-bottom: 1mm; }
            table { width: 100%; border-collapse: collapse; font-size: 10px; }
            th, td { border: 2px solid #444; padding: 1mm 1.5mm; text-align: center; }
            th { font-weight: bold; }
            .g-even { background: #fde8c8; }
            .g-odd  { background: #d4edda; }
        ';

        $html = '<html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>';

        $allVarCols = [];
        $colTotals  = [];
        $grandTotal = 0;
        $totalItems = count($items);

        foreach ($items as $idx => $item) {
            $varsByGroup = [];
            foreach ($item['variations'] as $v) $varsByGroup[$v['group_name']][] = $v;

            $displayName = !empty($item['pattern_tamil_name'])
                ? $item['pattern_tamil_name']
                : (!empty($item['pattern_name']) ? $item['pattern_name'] : $item['product_name']);

            $activeGroups = [];
            foreach ($varsByGroup as $gName => $vars) {
                $active = array_values(array_filter($vars, fn($v) => ($item['qty_map'][$v['id']] ?? 0) > 0));
                if (!empty($active)) {
                    $groupLabel  = !empty($active[0]['group_tamil_name']) ? $active[0]['group_tamil_name'] : $gName;
                    $groupTotal  = array_sum(array_map(fn($v) => (int)($item['qty_map'][$v['id']] ?? 0), $active));
                    $minSize     = min(array_map(fn($v) => (float)($v['size'] ?? 0), $active));
                    $activeGroups[] = ['label' => $groupLabel, 'vars' => $active, 'total' => $groupTotal, 'min_size' => $minSize];
                }
            }
            usort($activeGroups, fn($a, $b) => $a['min_size'] <=> $b['min_size']);

            foreach ($activeGroups as $g) {
                foreach ($g['vars'] as $v) {
                    $qty = (int)($item['qty_map'][$v['id']] ?? 0);
                    if (!isset($allVarCols[$v['id']])) {
                        $allVarCols[$v['id']] = ['name' => $v['name'], 'size' => (float)($v['size'] ?? 0), 'group_label' => $g['label'], 'group_min_size' => $g['min_size']];
                        $colTotals[$v['id']] = 0;
                    }
                    $colTotals[$v['id']] += $qty;
                    $grandTotal           += $qty;
                }
            }

            $productTotal = array_sum(array_column($activeGroups, 'total'));
            $productEstWeight = 0.0;
            $wmapSlip = $this->_computeWeightMap($item['product_id'], $item['pattern_id'], (int)($item['main_part_id'] ?? 0), $id);
            foreach ($item['qty_map'] as $vid => $qty) {
                $productEstWeight += (float)($wmapSlip[$vid] ?? 0) * (int)$qty;
            }

            $html .= '<div class="slip">';
            $html .= '<table class="slip-header"><tr>';
            $html .= '<td class="slip-left"><div class="slip-code-big">' . htmlspecialchars($item['pattern_code'] ?? '') . '</div>';
            $html .= '<div class="slip-date">' . date('d M Y', strtotime($order['created_at'])) . '</div></td>';
            $html .= '<td class="slip-centre">';
            if (!empty($item['stamp_name'])) $html .= '<div class="slip-stamp">சீல் : ' . htmlspecialchars($item['stamp_name']) . '</div>';
            $html .= '</td>';
            $html .= '<td class="slip-right"><div class="slip-order-no">Order #' . (int)$order['id'] . '</div>';
            $html .= '<div class="slip-weight">' . number_format($productEstWeight, 2) . ' g</div>';
            $html .= '<div class="slip-pcs">' . $productTotal . ' pcs</div></td>';
            $html .= '</tr></table>';

            $html .= '<div class="slip-pattern-name"><span class="slip-serial-no">#' . ($idx + 1) . '</span> ' . htmlspecialchars($displayName) . '</div>';

            if (!empty($item['product_image'])) {
                $imgPath = FCPATH . 'uploads/products/' . $item['product_image'];
                if (file_exists($imgPath)) $html .= '<img src="' . $imgPath . '" class="slip-img" alt="">';
            }

            if (!empty($activeGroups)) {
                $html .= '<table><thead><tr>';
                foreach ($activeGroups as $gIdx => $g) {
                    $cls = ($gIdx % 2 === 0) ? 'g-even' : 'g-odd';
                    $html .= '<th colspan="' . count($g['vars']) . '" class="' . $cls . '">' . htmlspecialchars($g['label']) . ' (' . $g['total'] . ' pcs)</th>';
                }
                $html .= '</tr><tr>';
                foreach ($activeGroups as $gIdx => $g) {
                    $cls = ($gIdx % 2 === 0) ? 'g-even' : 'g-odd';
                    foreach ($g['vars'] as $v) $html .= '<th class="' . $cls . '">' . htmlspecialchars($v['name']) . '</th>';
                }
                $html .= '</tr></thead><tbody><tr>';
                foreach ($activeGroups as $g) {
                    foreach ($g['vars'] as $v) $html .= '<td>' . (int)($item['qty_map'][$v['id']] ?? 0) . '</td>';
                }
                $html .= '</tr></tbody></table>';
            }

            $html .= '<div class="slip-page-no">Page ' . ($idx + 1) . ' of ' . $totalItems . '</div>';
            $html .= '</div>';

            if ($idx < $totalItems - 1) {
                $html .= '<div class="cut"></div>';
                $html .= '<div style="page-break-after:always;"></div>';
            }
        }

        if (!empty($allVarCols)) {
            uasort($allVarCols, function($a, $b) {
                if ($a['group_min_size'] !== $b['group_min_size']) return $a['group_min_size'] <=> $b['group_min_size'];
                return $a['size'] <=> $b['size'];
            });
            $varIds = array_keys($allVarCols);
            $headerGroups = []; $prevLabel = null;
            foreach ($allVarCols as $vid => $vc) {
                if ($vc['group_label'] !== $prevLabel) { $headerGroups[] = ['label' => $vc['group_label'], 'count' => 0, 'total' => 0]; $prevLabel = $vc['group_label']; }
                $headerGroups[count($headerGroups)-1]['count']++;
                $headerGroups[count($headerGroups)-1]['total'] += $colTotals[$vid];
            }
            $html .= '<div style="page-break-before:always;"></div>';
            $html .= '<h3 style="margin:0 0 6px 0;font-family:latha;">Order Summary &mdash; ' . $grandTotal . ' pcs</h3>';
            $html .= '<table><thead><tr>';
            foreach ($headerGroups as $gIdx => $g) {
                $cls = ($gIdx % 2 === 0) ? 'g-even' : 'g-odd';
                $html .= '<th colspan="' . $g['count'] . '" class="' . $cls . '">' . htmlspecialchars($g['label']) . ' (' . $g['total'] . ' pcs)</th>';
            }
            $html .= '<th>Total</th></tr><tr>';
            $gIdx2 = 0; $prevLabel2 = null;
            foreach ($allVarCols as $vid => $vc) {
                if ($vc['group_label'] !== $prevLabel2) { $gIdx2++; $prevLabel2 = $vc['group_label']; }
                $cls = (($gIdx2 - 1) % 2 === 0) ? 'g-even' : 'g-odd';
                $html .= '<th class="' . $cls . '">' . htmlspecialchars($vc['name']) . '</th>';
            }
            $html .= '<th></th></tr></thead><tbody><tr>';
            foreach ($varIds as $vid) { $v = $colTotals[$vid]; $html .= '<td>' . ($v > 0 ? $v : '&mdash;') . '</td>'; }
            $html .= '<td><strong>' . $grandTotal . '</strong></td></tr></tbody></table>';
        }

        $html .= '</body></html>';
        \App\Services\PdfService::makeA5Portrait($html, 'order-slip-' . $id . '.pdf');
    }

    public function orderSheetSlipWithPartsPdf($id)
    {
        $order = $this->_getOrder($id);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');
        if ($order['status'] === 'draft') return redirect()->to('orders/view/' . $id)->with('error', 'Confirm order first');

        $rawItems = $this->db->query('
            SELECT oi.*, p.name as product_name, p.sku, p.main_part_id, p.image as product_image,
                   pt.variations as pt_variations, pt.multiplication_factor,
                   b.clasp_size,
                   pp.name as pattern_name, pp.tamil_name as pattern_tamil_name, pp.pattern_code,
                   s.name as stamp_name
            FROM order_items oi
            JOIN product p ON p.id = oi.product_id
            LEFT JOIN product_type pt ON pt.id = p.product_type_id
            LEFT JOIN body b ON b.id = p.body_id
            LEFT JOIN product_pattern pp ON pp.id = oi.pattern_id
            LEFT JOIN stamp s ON s.id = oi.stamp_id
            WHERE oi.order_id = ?
            ORDER BY oi.sort_order, oi.id
        ', [$id])->getResultArray();

        $items = [];
        foreach ($rawItems as $item) {
            $varIds = [];
            if (!empty($item['pt_variations'])) {
                $varIds = array_values(array_filter(array_map('trim', explode(',', $item['pt_variations']))));
            }
            $variations = [];
            if ($varIds) {
                $ph = implode(',', array_fill(0, count($varIds), '?'));
                $variations = $this->db->query("SELECT * FROM variation WHERE id IN ($ph) ORDER BY group_name, size+0", $varIds)->getResultArray();
            }
            $qtyRows = $this->db->query('SELECT variation_id, quantity FROM order_item_qty WHERE order_item_id = ? AND quantity > 0', [$item['id']])->getResultArray();
            $qtyMap = [];
            foreach ($qtyRows as $q) $qtyMap[$q['variation_id']] = $q['quantity'];
            $item['variations'] = $variations;
            $item['qty_map']    = $qtyMap;
            $items[] = $item;
        }

        $css = '
            body { font-family: latha; font-size: 11px; color: #222; margin: 0; padding: 0; }
            .slip { overflow: hidden; box-sizing: border-box; padding: 3mm 4mm; }
            .slip-header { width: 100%; border-collapse: collapse; margin-bottom: 2mm; border-bottom: 1px solid #ddd; padding-bottom: 1mm; }
            .slip-left   { width: 33%; vertical-align: top; text-align: left; }
            .slip-centre { width: 34%; vertical-align: middle; text-align: center; }
            .slip-right  { width: 33%; vertical-align: top; text-align: right; }
            .slip-code-big { font-size: 13px; font-weight: bold; }
            .slip-pattern-name { font-size: 13px; font-weight: bold; text-align: center; margin: 2mm 0 1mm 0; color: #111; }
            .slip-serial-no { font-size: 13px; font-weight: 900; color: #000; margin-right: 2px; }
            .slip-date   { font-size: 9px; color: #666; margin-top: 1mm; }
            .slip-stamp  { font-size: 14px; font-weight: bold; color: #222; }
            .slip-order-no { font-size: 12px; font-weight: bold; }
            .slip-weight { font-size: 10px; color: #555; margin-top: 1mm; }
            .slip-pcs    { font-size: 10px; color: #555; }
            .slip-img { width: 100%; max-height: 40mm; object-fit: contain; display: block; margin-bottom: 2mm; }
            .slip-page-no { font-size: 9px; color: #999; text-align: right; margin-top: 1mm; margin-bottom: 1mm; }
            table { width: 100%; border-collapse: collapse; font-size: 10px; }
            th, td { border: 2px solid #444; padding: 1mm 1.5mm; text-align: center; }
            th { font-weight: bold; }
            .g-even { background: #fde8c8; }
            .g-odd  { background: #d4edda; }
            .parts-title { font-size: 13px; font-weight: bold; text-align: center; margin: 3mm 0 2mm 0; color: #111; }
            .parts-table { width: 100%; border-collapse: collapse; font-size: 10px; }
            .parts-table th { background: #333; color: #fff; padding: 1.5mm 2mm; text-align: left; border: 1px solid #333; }
            .parts-table td { padding: 1mm 2mm; border: 1px solid #ccc; text-align: left; }
            .parts-table tr:nth-child(even) td { background: #f7f7f7; }
        ';

        $html = '<html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>';

        $allVarCols = [];
        $colTotals  = [];
        $grandTotal = 0;
        $totalItems = count($items);

        foreach ($items as $idx => $item) {
            $varsByGroup = [];
            foreach ($item['variations'] as $v) $varsByGroup[$v['group_name']][] = $v;

            $displayName = !empty($item['pattern_tamil_name'])
                ? $item['pattern_tamil_name']
                : (!empty($item['pattern_name']) ? $item['pattern_name'] : $item['product_name']);

            $activeGroups = [];
            foreach ($varsByGroup as $gName => $vars) {
                $active = array_values(array_filter($vars, fn($v) => ($item['qty_map'][$v['id']] ?? 0) > 0));
                if (!empty($active)) {
                    $groupLabel  = !empty($active[0]['group_tamil_name']) ? $active[0]['group_tamil_name'] : $gName;
                    $groupTotal  = array_sum(array_map(fn($v) => (int)($item['qty_map'][$v['id']] ?? 0), $active));
                    $minSize     = min(array_map(fn($v) => (float)($v['size'] ?? 0), $active));
                    $activeGroups[] = ['label' => $groupLabel, 'vars' => $active, 'total' => $groupTotal, 'min_size' => $minSize];
                }
            }
            usort($activeGroups, fn($a, $b) => $a['min_size'] <=> $b['min_size']);

            foreach ($activeGroups as $g) {
                foreach ($g['vars'] as $v) {
                    $qty = (int)($item['qty_map'][$v['id']] ?? 0);
                    if (!isset($allVarCols[$v['id']])) {
                        $allVarCols[$v['id']] = ['name' => $v['name'], 'size' => (float)($v['size'] ?? 0), 'group_label' => $g['label'], 'group_min_size' => $g['min_size']];
                        $colTotals[$v['id']] = 0;
                    }
                    $colTotals[$v['id']] += $qty;
                    $grandTotal           += $qty;
                }
            }

            $productTotal = array_sum(array_column($activeGroups, 'total'));
            $productEstWeight = 0.0;
            $wmapSlip = $this->_computeWeightMap($item['product_id'], $item['pattern_id'], (int)($item['main_part_id'] ?? 0), $id);
            foreach ($item['qty_map'] as $vid => $qty) {
                $productEstWeight += (float)($wmapSlip[$vid] ?? 0) * (int)$qty;
            }

            $html .= '<div class="slip">';
            $html .= '<table class="slip-header"><tr>';
            $html .= '<td class="slip-left"><div class="slip-code-big">' . htmlspecialchars($item['pattern_code'] ?? '') . '</div>';
            $html .= '<div class="slip-date">' . date('d M Y', strtotime($order['created_at'])) . '</div></td>';
            $html .= '<td class="slip-centre">';
            if (!empty($item['stamp_name'])) $html .= '<div class="slip-stamp">சீல் : ' . htmlspecialchars($item['stamp_name']) . '</div>';
            $html .= '</td>';
            $html .= '<td class="slip-right"><div class="slip-order-no">Order #' . (int)$order['id'] . '</div>';
            $html .= '<div class="slip-weight">' . number_format($productEstWeight, 2) . ' g</div>';
            $html .= '<div class="slip-pcs">' . $productTotal . ' pcs</div></td>';
            $html .= '</tr></table>';

            $html .= '<div class="slip-pattern-name"><span class="slip-serial-no">#' . ($idx + 1) . '</span> ' . htmlspecialchars($displayName) . '</div>';

            if (!empty($item['product_image'])) {
                $imgPath = FCPATH . 'uploads/products/' . $item['product_image'];
                if (file_exists($imgPath)) $html .= '<img src="' . $imgPath . '" class="slip-img" alt="">';
            }

            if (!empty($activeGroups)) {
                $html .= '<table><thead><tr>';
                foreach ($activeGroups as $gIdx => $g) {
                    $cls = ($gIdx % 2 === 0) ? 'g-even' : 'g-odd';
                    $html .= '<th colspan="' . count($g['vars']) . '" class="' . $cls . '">' . htmlspecialchars($g['label']) . ' (' . $g['total'] . ' pcs)</th>';
                }
                $html .= '</tr><tr>';
                foreach ($activeGroups as $gIdx => $g) {
                    $cls = ($gIdx % 2 === 0) ? 'g-even' : 'g-odd';
                    foreach ($g['vars'] as $v) $html .= '<th class="' . $cls . '">' . htmlspecialchars($v['name']) . '</th>';
                }
                $html .= '</tr></thead><tbody><tr>';
                foreach ($activeGroups as $g) {
                    foreach ($g['vars'] as $v) $html .= '<td>' . (int)($item['qty_map'][$v['id']] ?? 0) . '</td>';
                }
                $html .= '</tr></tbody></table>';
            }

            $html .= '<div class="slip-page-no">Page ' . ($idx + 1) . ' of ' . $totalItems . '</div>';

            $partAggregated = $this->_calculatePartRequirements($id, [], $item['id']);
            $partIds2 = array_keys($partAggregated);
            $partsInfo = [];
            if ($partIds2) {
                $ph2 = implode(',', array_fill(0, count($partIds2), '?'));
                $pRows = $this->db->query("SELECT id, name, tamil_name, weight FROM part WHERE id IN ($ph2)", $partIds2)->getResultArray();
                foreach ($pRows as $p) $partsInfo[$p['id']] = $p;
            }

            $partRows = [];
            foreach ($partAggregated as $partId => $data) {
                $pInfo  = $partsInfo[$partId] ?? [];
                $tName  = trim($pInfo['tamil_name'] ?? '');
                $pName  = $tName !== '' ? $tName : ($pInfo['name'] ?? 'Part #' . $partId);
                $pcs    = (float)($data['part_pcs'] ?? 0);
                $wt     = $pcs * (float)($pInfo['weight'] ?? 0);
                $partRows[] = ['name' => $pName, 'pcs' => $pcs, 'wt' => $wt];
            }

            $html .= '<div class="parts-title">தேவையான பொருள்கள்</div>';

            $renderPartsTable = function(array $rows) use (&$html) {
                $html .= '<table class="parts-table" style="width:100%;"><thead><tr>';
                $html .= '<th>பொருள்</th><th>எண்ணிக்கை</th><th>எடை</th>';
                $html .= '</tr></thead><tbody>';
                if (empty($rows)) {
                    $html .= '<tr><td colspan="3" style="text-align:center;color:#999;">&mdash;</td></tr>';
                } else {
                    foreach ($rows as $row) {
                        $html .= '<tr><td>' . htmlspecialchars($row['name']) . '</td>';
                        $html .= '<td>' . number_format($row['pcs'], 2) . ' pcs</td>';
                        $html .= '<td>' . number_format($row['wt'], 4) . ' g</td></tr>';
                    }
                }
                $html .= '</tbody></table>';
            };

            if (count($partRows) > 5) {
                $half  = (int)ceil(count($partRows) / 2);
                $left  = array_slice($partRows, 0, $half);
                $right = array_slice($partRows, $half);
                $html .= '<table style="width:100%;border-collapse:collapse;"><tr>';
                $html .= '<td style="width:50%;vertical-align:top;padding-right:2mm;">';
                $renderPartsTable($left);
                $html .= '</td><td style="width:50%;vertical-align:top;padding-left:2mm;">';
                $renderPartsTable($right);
                $html .= '</td></tr></table>';
            } else {
                $renderPartsTable($partRows);
            }

            $html .= '</div>';

            if ($idx < $totalItems - 1) $html .= '<div style="page-break-after:always;"></div>';
        }

        if (!empty($allVarCols)) {
            uasort($allVarCols, function($a, $b) {
                if ($a['group_min_size'] !== $b['group_min_size']) return $a['group_min_size'] <=> $b['group_min_size'];
                return $a['size'] <=> $b['size'];
            });
            $varIds = array_keys($allVarCols);
            $headerGroups = []; $prevLabel = null;
            foreach ($allVarCols as $vid => $vc) {
                if ($vc['group_label'] !== $prevLabel) { $headerGroups[] = ['label' => $vc['group_label'], 'count' => 0, 'total' => 0]; $prevLabel = $vc['group_label']; }
                $headerGroups[count($headerGroups)-1]['count']++;
                $headerGroups[count($headerGroups)-1]['total'] += $colTotals[$vid];
            }
            $html .= '<div style="page-break-before:always;"></div>';
            $html .= '<h3 style="margin:0 0 6px 0;font-family:latha;">Order Summary &mdash; ' . $grandTotal . ' pcs</h3>';
            $html .= '<table><thead><tr>';
            foreach ($headerGroups as $gIdx => $g) {
                $cls = ($gIdx % 2 === 0) ? 'g-even' : 'g-odd';
                $html .= '<th colspan="' . $g['count'] . '" class="' . $cls . '">' . htmlspecialchars($g['label']) . ' (' . $g['total'] . ' pcs)</th>';
            }
            $html .= '<th>Total</th></tr><tr>';
            $gIdx2 = 0; $prevLabel2 = null;
            foreach ($allVarCols as $vid => $vc) {
                if ($vc['group_label'] !== $prevLabel2) { $gIdx2++; $prevLabel2 = $vc['group_label']; }
                $cls = (($gIdx2 - 1) % 2 === 0) ? 'g-even' : 'g-odd';
                $html .= '<th class="' . $cls . '">' . htmlspecialchars($vc['name']) . '</th>';
            }
            $html .= '<th></th></tr></thead><tbody><tr>';
            foreach ($varIds as $vid) { $v = $colTotals[$vid]; $html .= '<td>' . ($v > 0 ? $v : '&mdash;') . '</td>'; }
            $html .= '<td><strong>' . $grandTotal . '</strong></td></tr></tbody></table>';
        }

        $html .= '</body></html>';
        \App\Services\PdfService::makeA5Portrait($html, 'order-slip-parts-' . $id . '.pdf');
    }

    // ========== AJAX ==========

    public function searchProducts()
    {
        $q      = $this->request->getPost('q') ?? '';
        $typeId = $this->request->getPost('product_type_id') ?? '';

        $sql    = 'SELECT p.id, p.name, p.sku, p.image, pt.name as type_name, b.name as body_name FROM product p LEFT JOIN product_type pt ON pt.id = p.product_type_id LEFT JOIN body b ON b.id = p.body_id WHERE (p.name LIKE ? OR p.sku LIKE ?)';
        $params = ['%' . $q . '%', '%' . $q . '%'];

        if ($typeId) { $sql .= ' AND p.product_type_id = ?'; $params[] = $typeId; }
        $sql .= ' ORDER BY p.name LIMIT 30';

        return $this->response->setJSON(['products' => $this->db->query($sql, $params)->getResultArray()]);
    }

    public function getProductPatterns()
    {
        $productId = $this->request->getPost('product_id');
        $rows = $this->db->query('SELECT id, name, tamil_name, is_default FROM product_pattern WHERE product_id = ? ORDER BY is_default DESC, name', [$productId])->getResultArray();
        return $this->response->setJSON(['patterns' => $rows]);
    }

    public function getProductVariations()
    {
        $productId = $this->request->getPost('product_id');
        $pt = $this->db->query('SELECT pt.variations FROM product p JOIN product_type pt ON pt.id = p.product_type_id WHERE p.id = ?', [$productId])->getRowArray();

        if (!$pt || empty($pt['variations'])) return $this->response->setJSON(['variations' => [], 'groups' => []]);

        $vids = array_filter(array_map('trim', explode(',', $pt['variations'])));
        if (empty($vids)) return $this->response->setJSON(['variations' => [], 'groups' => []]);

        $ph   = implode(',', array_fill(0, count($vids), '?'));
        $vars = $this->db->query("SELECT id, group_name, name, size FROM variation WHERE id IN ($ph) ORDER BY group_name, size+0", $vids)->getResultArray();

        $groupedSorted = $this->_groupAndSortVariations($vars);

        return $this->response->setJSON(['variations' => $vars, 'groups' => $groupedSorted]);
    }

    public function getProductWeightData()
    {
        $productId = (int)$this->request->getPost('product_id');
        $patternId = $this->request->getPost('pattern_id') ?: null;
        $orderId   = $this->request->getPost('order_id')   ?: null;

        if (!$productId) return $this->response->setJSON(['weight_map' => []]);

        $prod = $this->db->query('SELECT main_part_id FROM product WHERE id = ?', [$productId])->getRowArray();
        $mainPartId = $prod ? (int)$prod['main_part_id'] : 0;

        $weightMap = $this->_computeWeightMap($productId, $patternId, $mainPartId, $orderId);

        return $this->response->setJSON(['weight_map' => $weightMap]);
    }

    public function touchAnalysis($orderId)
    {
        $order = $this->_getOrder($orderId);
        if (!$order) return redirect()->to('orders')->with('error', 'Not found');
        if ($order['status'] === 'draft') return redirect()->to('orders/view/' . $orderId)->with('error', 'Confirm order first');

        $aggregated = $this->_calculatePartRequirements($orderId);

        // --- same inject + recompute as partRequirements() ---
        $mainSetup = [];
        $setupRows = $this->db->query('SELECT part_id, kanni_per_inch, weight_per_kanni FROM order_main_part_setup WHERE order_id = ?', [$orderId])->getResultArray();
        foreach ($setupRows as $s) $mainSetup[$s['part_id']] = $s;

        foreach ($mainSetup as $mpId => $mpData) {
            if (!isset($aggregated[$mpId])) {
                $aggregated[$mpId] = ['part_pcs' => 0, 'podi_id' => null, 'podi_pcs' => 0, 'sum_length' => 0];
            }
        }

        // Always compute kanni from order dimensions for main parts (DEFAULT), then add BOM pcs on top
        foreach ($mainSetup as $mpId => $mpData) {
            $kanniPerInch = (float)$mpData['kanni_per_inch'];
            if ($kanniPerInch <= 0) continue;

            $ois = $this->db->query('
                SELECT oi.id, pt.multiplication_factor, b.clasp_size
                FROM order_items oi
                JOIN product p            ON p.id  = oi.product_id
                LEFT JOIN product_type pt ON pt.id = p.product_type_id
                LEFT JOIN body b          ON b.id  = p.body_id
                WHERE oi.order_id = ? AND p.main_part_id = ?
            ', [$orderId, $mpId])->getResultArray();

            $totalLength = 0;
            foreach ($ois as $oi) {
                $factor    = (float)($oi['multiplication_factor'] ?? 1);
                $claspSize = (float)($oi['clasp_size'] ?? 0);
                $qtys = $this->db->query('
                    SELECT oiq.quantity, v.size
                    FROM order_item_qty oiq
                    JOIN variation v ON v.id = oiq.variation_id
                    WHERE oiq.order_item_id = ? AND oiq.quantity > 0
                ', [$oi['id']])->getResultArray();
                foreach ($qtys as $q) {
                    $totalLength += max(0, (float)$q['size'] - $claspSize) * (float)$q['quantity'] * $factor;
                }
            }
            $aggregated[$mpId]['sum_length']  = $totalLength;
            $aggregated[$mpId]['part_pcs']    = $totalLength * $kanniPerInch;
        }
        // --- end inject + recompute ---

        $partIds       = array_keys($aggregated);
        $groupData     = [];
        $podiGroupData = [];

        if ($partIds) {
            $ph   = implode(',', array_fill(0, count($partIds), '?'));
            $rows = $this->db->query("
                SELECT pa.id as part_id, pa.weight, pa.is_main_part, dg.name as group_name
                FROM part pa
                LEFT JOIN department d ON d.id = pa.department_id
                LEFT JOIN department_group dg ON dg.id = d.department_group_id
                WHERE pa.id IN ($ph)
            ", $partIds)->getResultArray();

            $podiIds = array_values(array_filter(array_unique(array_column($aggregated, 'podi_id'))));
            $podiWeightMap = [];
            if ($podiIds) {
                $ph2      = implode(',', array_fill(0, count($podiIds), '?'));
                $podiRows = $this->db->query("SELECT id, weight FROM podi WHERE id IN ($ph2)", array_values($podiIds))->getResultArray();
                foreach ($podiRows as $pr) $podiWeightMap[$pr['id']] = (float)$pr['weight'];
            }

            foreach ($rows as $row) {
                $partId    = $row['part_id'];
                $groupName = $row['group_name'] ?? 'Unassigned';
                $pcs       = (float)($aggregated[$partId]['part_pcs'] ?? 0);

                // Main parts: use weight_per_kanni from setup, not part.weight
                $isMain = !empty($row['is_main_part']);
                $wpp    = ($isMain && isset($mainSetup[$partId]))
                          ? (float)$mainSetup[$partId]['weight_per_kanni']
                          : (float)($row['weight'] ?? 0);

                $estWt = $pcs * $wpp;
                if (!isset($groupData[$groupName])) $groupData[$groupName] = 0;
                $groupData[$groupName] += $estWt;

                $podiId  = $aggregated[$partId]['podi_id'] ?? null;
                $podiPcs = (float)($aggregated[$partId]['podi_pcs'] ?? 0);
                if ($podiId && $podiPcs > 0) {
                    $podiWt = $podiWeightMap[$podiId] ?? 0;
                    if (!isset($podiGroupData[$groupName])) $podiGroupData[$groupName] = 0;
                    $podiGroupData[$groupName] += $podiPcs * $podiWt;
                }
            }
        }

        $savedTouch = [];
        $touchRows  = $this->db->query('SELECT group_name, touch_value FROM order_touch WHERE order_id = ?', [$orderId])->getResultArray();
        foreach ($touchRows as $t) $savedTouch[$t['group_name']] = (float)$t['touch_value'];

        return view('orders/touch_analysis', [
            'title'         => 'Touch Analysis: ' . $order['title'],
            'order'         => $order,
            'groupData'     => $groupData,
            'podiGroupData' => $podiGroupData,
            'savedTouch'    => $savedTouch,
        ]);
    }

    public function saveTouchAnalysis($orderId)
    {
        $order = $this->_getOrder($orderId);
        if (!$order || $order['status'] === 'draft') return redirect()->to('orders')->with('error', 'Not found');

        $d          = $this->request->getPost();
        $groupNames = $d['group_name'] ?? [];
        $touches    = $d['touch_value'] ?? [];

        foreach ($groupNames as $i => $gn) {
            $tv = max(0, min(100, (float)($touches[$i] ?? 0)));
            $existing = $this->db->query('SELECT id FROM order_touch WHERE order_id = ? AND group_name = ?', [$orderId, $gn])->getRowArray();
            if ($existing) {
                $this->db->table('order_touch')->where('id', $existing['id'])->update(['touch_value' => $tv]);
            } else {
                $this->db->table('order_touch')->insert(['order_id' => $orderId, 'group_name' => $gn, 'touch_value' => $tv, 'created_by' => $this->currentUser(), 'created_at' => date('Y-m-d H:i:s')]);
            }
        }

        return redirect()->to('orders/touchAnalysis/' . $orderId)->with('success', 'Touch values saved');
    }


    // ========== SPLIT ORDER ==========

    public function splitOrder($id)
    {
        $order = $this->_getOrder($id);
        if (!$order) return redirect()->to('orders')->with('error', 'Order not found');

        $items = $this->db->query(
            "SELECT oi.id, oi.product_id, oi.pattern_id,
                    p.name as product_name, p.sku,
                    COALESCE(pn.name, 'Default') as pattern_name,
                    (SELECT SUM(oiq.quantity) FROM order_item_qty oiq WHERE oiq.order_item_id = oi.id) as total_qty
             FROM order_items oi
             LEFT JOIN product p ON p.id = oi.product_id
             LEFT JOIN product_pattern pp ON pp.id = oi.pattern_id
             LEFT JOIN pattern_name pn ON pn.id = pp.pattern_name_id
             WHERE oi.order_id = ?
             ORDER BY oi.id",
            [$id]
        )->getResultArray();

        return view('orders/split', [
            'title' => 'Split Order â€” ' . $order['order_number'],
            'order' => $order,
            'items' => $items,
            'clients' => $this->db->query('SELECT id, name FROM client ORDER BY name')->getResultArray(),
        ]);
    }

    public function doSplit($id)
    {
        $order = $this->_getOrder($id);
        if (!$order) return redirect()->to('orders')->with('error', 'Order not found');

        $selectedIds = $this->request->getPost('selected_ids') ?? [];
        $splitMode   = $this->request->getPost('split_mode') ?? 'A';
        $newTitle    = trim($this->request->getPost('new_title') ?? ($order['title'] . ' (Split)'));
        $newClientId = $this->request->getPost('new_client_id') ?: null;

        $allItems = $this->db->query('SELECT id FROM order_items WHERE order_id = ?', [$id])->getResultArray();
        $allIds   = array_column($allItems, 'id');

        if (empty($selectedIds)) {
            return redirect()->to('orders/split/' . $id)->with('error', 'Select at least one product to split');
        }
        if (count($selectedIds) >= count($allIds)) {
            return redirect()->to('orders/split/' . $id)->with('error', 'Cannot move all products â€” at least one must remain');
        }

        $remainingIds = array_diff($allIds, $selectedIds);
        $newOrderId   = $this->_createOrderCopy($order, $newTitle, $newClientId, $selectedIds);

        if ($splitMode === 'B') {
            // Create second order with remaining items
            $title2     = trim($this->request->getPost('title2') ?? ($order['title'] . ' (Remainder)'));
            $clientId2  = $this->request->getPost('client_id2') ?: ($order['client_id'] ?? null);
            $newOrderId2 = $this->_createOrderCopy($order, $title2, $clientId2, array_values($remainingIds));
            // Close original
            $this->db->table('orders')->where('id', $id)->update(['status' => 'closed']);
            $this->db->table('order_touch')->where('order_id', $id)->delete();
        } else {
            // Option A: remove selected from original, reset original to draft
            foreach ($selectedIds as $itemId) {
                $this->db->table('order_item_qty')->where('order_item_id', $itemId)->delete();
                $this->db->table('order_items')->where('id', $itemId)->delete();
            }
            $this->db->table('orders')->where('id', $id)->update(['status' => 'draft']);
            $this->db->table('order_touch')->where('order_id', $id)->delete();
        }

        $this->db->table('order_touch')->where('order_id', $newOrderId)->delete();

        return redirect()->to('orders/view/' . $newOrderId)->with('success', 'Order split successfully. Regenerate Part Requirements.');
    }

    // ========== MERGE ORDERS ==========

    public function mergePreview()
    {
        $orderIds = $this->request->getGet('order_ids') ?? [];
        if (count($orderIds) < 2) return redirect()->to('orders')->with('error', 'Select at least 2 orders to merge');

        $orders = [];
        foreach ($orderIds as $oid) {
            $o = $this->_getOrder((int)$oid);
            if ($o) {
                $o['items'] = $this->db->query(
                    "SELECT oi.id, p.name as product_name, p.sku,
                            COALESCE(pn.name, 'Default') as pattern_name,
                            (SELECT SUM(oiq.quantity) FROM order_item_qty oiq WHERE oiq.order_item_id = oi.id) as total_qty
                     FROM order_items oi
                     LEFT JOIN product p ON p.id = oi.product_id
                     LEFT JOIN product_pattern pp ON pp.id = oi.pattern_id
                     LEFT JOIN pattern_name pn ON pn.id = pp.pattern_name_id
                     WHERE oi.order_id = ?",
                    [(int)$oid]
                )->getResultArray();
                $orders[] = $o;
            }
        }

        return view('orders/merge', [
            'title'    => 'Merge Orders',
            'orders'   => $orders,
            'orderIds' => $orderIds,
            'clients'  => $this->db->query('SELECT id, name FROM client ORDER BY name')->getResultArray(),
        ]);
    }

    public function doMerge()
    {
        $orderIds  = $this->request->getPost('order_ids') ?? [];
        $newTitle  = trim($this->request->getPost('new_title') ?? 'Merged Order');
        $newClient = $this->request->getPost('new_client_id') ?: null;

        if (count($orderIds) < 2) return redirect()->to('orders')->with('error', 'Need at least 2 orders to merge');

        // Create new blank order
        $this->db->table('orders')->insert([
            'title'      => $newTitle,
            'client_id'  => $newClient,
            'status'     => 'draft',
            'notes'      => 'Merged from: ' . implode(', ', array_map(fn($oid) => 'ORD-' . str_pad($oid, 3, '0', STR_PAD_LEFT), $orderIds)),
            'created_by' => $this->currentUser(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $newId = $this->db->insertID();
        $this->db->table('orders')->where('id', $newId)->update(['order_number' => 'ORD-' . str_pad($newId, 3, '0', STR_PAD_LEFT)]);

        // Copy items from all source orders
        foreach ($orderIds as $oid) {
            $items = $this->db->query('SELECT * FROM order_items WHERE order_id = ?', [(int)$oid])->getResultArray();
            foreach ($items as $item) {
                $this->db->table('order_items')->insert([
                    'order_id'   => $newId,
                    'product_id' => $item['product_id'],
                    'pattern_id' => $item['pattern_id'],
                    'created_by' => $this->currentUser(),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $newItemId = $this->db->insertID();

                $qtys = $this->db->query('SELECT * FROM order_item_qty WHERE order_item_id = ?', [$item['id']])->getResultArray();
                foreach ($qtys as $q) {
                    $this->db->table('order_item_qty')->insert([
                        'order_item_id' => $newItemId,
                        'variation_id'  => $q['variation_id'],
                        'quantity'      => $q['quantity'],
                        'created_by'    => $this->currentUser(),
                        'created_at'    => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            // Copy main_part_setup (first-found wins)
            $setups = $this->db->query('SELECT * FROM order_main_part_setup WHERE order_id = ?', [(int)$oid])->getResultArray();
            foreach ($setups as $s) {
                $exists = $this->db->query(
                    'SELECT id FROM order_main_part_setup WHERE order_id = ? AND part_id = ?',
                    [$newId, $s['part_id']]
                )->getRowArray();
                if (!$exists) {
                    $this->db->table('order_main_part_setup')->insert([
                        'order_id'        => $newId,
                        'part_id'         => $s['part_id'],
                        'kanni_per_inch'  => $s['kanni_per_inch'],
                        'weight_per_kanni'=> $s['weight_per_kanni'],
                        'created_by'      => $this->currentUser(),
                        'created_at'      => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            // Close source order
            $this->db->table('orders')->where('id', (int)$oid)->update(['status' => 'closed']);
            $this->db->table('order_touch')->where('order_id', (int)$oid)->delete();
        }

        return redirect()->to('orders/view/' . $newId)->with('success', 'Orders merged successfully. Regenerate Part Requirements.');
    }

    // ========== HELPER: copy items to a new order ==========

    private function _createOrderCopy($sourceOrder, $newTitle, $newClientId, $itemIds)
    {
        $this->db->table('orders')->insert([
            'title'      => $newTitle,
            'client_id'  => $newClientId ?? ($sourceOrder['client_id'] ?? null),
            'notes'      => $sourceOrder['notes'] ?? '',
            'status'     => 'draft',
            'created_by' => $this->currentUser(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $newId = $this->db->insertID();
        $this->db->table('orders')->where('id', $newId)->update(['order_number' => 'ORD-' . str_pad($newId, 3, '0', STR_PAD_LEFT)]);

        foreach ($itemIds as $itemId) {
            $item = $this->db->query('SELECT * FROM order_items WHERE id = ?', [(int)$itemId])->getRowArray();
            if (!$item) continue;
            $this->db->table('order_items')->insert([
                'order_id'   => $newId,
                'product_id' => $item['product_id'],
                'pattern_id' => $item['pattern_id'],
                'created_by' => $this->currentUser(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $newItemId = $this->db->insertID();
            $qtys = $this->db->query('SELECT * FROM order_item_qty WHERE order_item_id = ?', [(int)$itemId])->getResultArray();
            foreach ($qtys as $q) {
                $this->db->table('order_item_qty')->insert([
                    'order_item_id' => $newItemId,
                    'variation_id'  => $q['variation_id'],
                    'quantity'      => $q['quantity'],
                    'created_by'    => $this->currentUser(),
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Copy main_part_setup
        $setups = $this->db->query('SELECT * FROM order_main_part_setup WHERE order_id = ?', [(int)$sourceOrder['id']])->getResultArray();
        foreach ($setups as $s) {
            $this->db->table('order_main_part_setup')->insert([
                'order_id'         => $newId,
                'part_id'          => $s['part_id'],
                'kanni_per_inch'   => $s['kanni_per_inch'],
                'weight_per_kanni' => $s['weight_per_kanni'],
                'created_by'       => $this->currentUser(),
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        return $newId;
    }

    public function fromLowStock()
    {
        $rows = $this->db->query("
            SELECT ps.product_id, ps.pattern_id, ps.variation_id,
                   ps.qty, ps.min_qty,
                   (ps.min_qty - ps.qty) AS shortage
            FROM product_stock ps
            WHERE ps.min_qty > 0 AND ps.qty < ps.min_qty
            GROUP BY ps.product_id, ps.pattern_id, ps.variation_id
        ")->getResultArray();

        if (empty($rows)) {
            return redirect()->to('stock/low-stock')->with('error', 'No low stock items found.');
        }

        $this->db->table('orders')->insert([
            'title'      => 'Restock - ' . date('d/m/Y'),
            'status'     => 'draft',
            'notes'      => 'Auto-generated from Low Stock alert',
            'created_by' => $this->currentUser(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $orderId = $this->db->insertID();
        $this->db->table('orders')->where('id', $orderId)->update(['order_number' => 'ORD-' . str_pad($orderId, 3, '0', STR_PAD_LEFT)]);

        // Group items by product+pattern to create order_items rows
        $grouped = [];
        foreach ($rows as $row) {
            $key = $row['product_id'] . '_' . $row['pattern_id'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['product_id' => $row['product_id'], 'pattern_id' => $row['pattern_id'], 'variations' => []];
            }
            $shortage = (int)$row['shortage'];
            if ($shortage < 1) continue;
            $grouped[$key]['variations'][] = ['variation_id' => $row['variation_id'], 'qty' => $shortage];
        }

        foreach ($grouped as $g) {
            $this->db->table('order_items')->insert([
                'order_id'   => $orderId,
                'product_id' => $g['product_id'],
                'pattern_id' => $g['pattern_id'],
                'created_by' => $this->currentUser(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $orderItemId = $this->db->insertID();

            foreach ($g['variations'] as $v) {
                $this->db->table('order_item_qty')->insert([
                    'order_item_id' => $orderItemId,
                    'variation_id'  => $v['variation_id'],
                    'quantity'      => $v['qty'],
                    'created_by'    => $this->currentUser(),
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return redirect()->to('orders/view/' . $orderId)
            ->with('success', count($rows) . ' low stock item(s) added as draft order. Fill in client details and confirm.');
    }
}

