<?php
namespace App\Controllers;

class PartOrder extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $karigarFilter = $this->request->getGet('karigar') ?? '';
        $statusFilter  = $this->request->getGet('status') ?? '';

        $builder = $db->table('part_order po')
            ->select('po.*, k.name as karigar_name')
            ->join('karigar k', 'k.id = po.karigar_id', 'left')
            ->orderBy('po.created_at', 'DESC');
        if ($karigarFilter) $builder->where('po.karigar_id', $karigarFilter);
        if ($statusFilter)  $builder->where('po.status', $statusFilter);

        $items    = $builder->get()->getResultArray();
        $karigars = $db->query('SELECT id, name FROM karigar ORDER BY name')->getResultArray();

        return view('part_orders/index', [
            'title'         => 'Part Orders (PARTORD)',
            'items'         => $items,
            'karigars'      => $karigars,
            'karigarFilter' => $karigarFilter,
            'statusFilter'  => $statusFilter,
        ]);
    }

    public function create()
    {
        $db          = \Config\Database::connect();
        $karigars    = $db->query('SELECT k.id, k.name, k.department_id, d.name as dept_name FROM karigar k LEFT JOIN department d ON d.id = k.department_id ORDER BY d.name, k.name')->getResultArray();
        $departments = $db->query('SELECT id, name FROM department ORDER BY name')->getResultArray();
        $orders      = $db->query('SELECT id, order_number, title FROM orders ORDER BY id DESC LIMIT 50')->getResultArray();
        return view('part_orders/form', [
            'title'       => 'Create Part Order',
            'karigars'    => $karigars,
            'departments' => $departments,
            'orders'      => $orders,
            'nextNum'     => $this->_nextOrderNumber(),
        ]);
    }

    public function store()
    {
        $db        = \Config\Database::connect();
        $karigarId = $this->request->getPost('karigar_id');

        $db->table('part_order')->insert([
            'order_number'     => $this->_nextOrderNumber(),
            'karigar_id'       => $karigarId,
            'client_order_id'  => $this->request->getPost('client_order_id') ?: null,
            'status'           => 'draft',
            'cash_rate_per_kg' => 0,
            'fine_pct'         => 0,
            'notes'            => $this->request->getPost('notes'),
        ]);
        return redirect()->to('part-orders/view/' . $db->insertID())->with('success', 'Part order created');
    }

    public function view($id)
    {
        $db  = \Config\Database::connect();
        $po  = $db->query('SELECT po.*, k.name as karigar_name, d.name as dept_name FROM part_order po LEFT JOIN karigar k ON k.id = po.karigar_id LEFT JOIN department d ON d.id = k.department_id WHERE po.id = ?', [$id])->getRowArray();
        if (!$po) return redirect()->to('part-orders')->with('error', 'Not found');

        // Reset overrides if requested
        if ($this->request->getGet('reset') == 1) {
            $db->table('part_order_charge_override')->where('part_order_id', $id)->delete();
            return redirect()->to('part-orders/view/'.$id)->with('success', 'Charge overrides reset to auto');
        }

        $issues   = $db->query('SELECT poi.*, gs.touch_pct as gatti_touch, gs.batch_number as gatti_batch, mj.job_number, s.name as stamp_name, p.name as issued_part_name, pb.batch_number as issued_part_batch FROM part_order_issue poi LEFT JOIN gatti_stock gs ON gs.id = poi.gatti_stock_id LEFT JOIN melt_job mj ON mj.id = gs.melt_job_id LEFT JOIN stamp s ON s.id = poi.stamp_id LEFT JOIN part p ON p.id = poi.part_id LEFT JOIN part_batch pb ON pb.id = poi.part_batch_id WHERE poi.part_order_id = ?', [$id])->getResultArray();
        $receives = $db->query('SELECT por.*, p.name as part_name, bt.name as byprod_name, s.name as stamp_name FROM part_order_receive por LEFT JOIN part p ON p.id = por.part_id LEFT JOIN byproduct_type bt ON bt.id = por.byproduct_type_id LEFT JOIN stamp s ON s.id = por.stamp_id WHERE por.part_order_id = ?', [$id])->getResultArray();

        $totalIssuedFine   = 0;
        $totalIssuedWeight = 0;
        foreach ($issues as $i) {
            $totalIssuedFine   += $i['weight_g'] * $i['touch_pct'] / 100;
            $totalIssuedWeight += $i['weight_g'];
        }

        $totalRecvFine    = 0;
        $totalRecvWeight  = 0;
        $totalPartsWeight = 0;
        foreach ($receives as $r) {
            $totalRecvFine   += $r['weight_g'] * $r['touch_pct'] / 100;
            $totalRecvWeight += $r['weight_g'];
            if ($r['receive_type'] === 'part') $totalPartsWeight += $r['weight_g'];
        }

        $fineDiff = $totalIssuedFine - $totalRecvFine;

        // Auto-compute charge breakdown
        $charges = $this->_computeChargeBreakdown($po['karigar_id'], $issues, $receives);

        // Load saved overrides
        $overrideRows = $db->query('SELECT * FROM part_order_charge_override WHERE part_order_id = ?', [$id])->getResultArray();
        $overrideMap  = array_column($overrideRows, null, 'rule_id'); // keyed by rule_id (null key = manual)
        $hasOverrides = !empty($overrideRows);

        // Apply overrides to chargeBreakdown for display pre-fill
        $breakdown = $charges['breakdown'];
        foreach ($breakdown as &$cb) {
            $ruleId = $cb['rule']['id'];
            if (isset($overrideMap[$ruleId])) {
                $ov = $overrideMap[$ruleId];
                $cb['ov_weight']   = $ov['weight_g'];
                $cb['ov_fine_pct'] = $ov['fine_pct'];
                $cb['ov_cash']     = $ov['cash_rate_per_kg'];
                $cb['ov_fine']     = $ov['weight_g'] * $ov['fine_pct'] / 100;
                $cb['ov_cash_amt'] = $ov['weight_g'] / 1000 * $ov['cash_rate_per_kg'];
            } else {
                $cb['ov_weight']   = $cb['weight'];
                $cb['ov_fine_pct'] = $cb['rule']['fine_pct'];
                $cb['ov_cash']     = $cb['rule']['cash_rate_per_kg'];
                $cb['ov_fine']     = $cb['fine'];
                $cb['ov_cash_amt'] = $cb['cash'];
            }
        }
        unset($cb);

        // Append manually added override rows (rule_id = null) — not part of karigar rules
        foreach ($overrideRows as $ov) {
            if ($ov['rule_id'] !== null) continue; // already handled above via rule-based breakdown
            $breakdown[] = [
                'rule'        => ['id' => null, 'basis' => $ov['basis_label'], 'notes' => '', 'fine_pct' => $ov['fine_pct'], 'cash_rate_per_kg' => $ov['cash_rate_per_kg']],
                'weight'      => $ov['weight_g'],
                'fine'        => $ov['weight_g'] * $ov['fine_pct'] / 100,
                'cash'        => $ov['weight_g'] / 1000 * $ov['cash_rate_per_kg'],
                'ov_weight'   => $ov['weight_g'],
                'ov_fine_pct' => $ov['fine_pct'],
                'ov_cash'     => $ov['cash_rate_per_kg'],
                'ov_fine'     => $ov['weight_g'] * $ov['fine_pct'] / 100,
                'ov_cash_amt' => $ov['weight_g'] / 1000 * $ov['cash_rate_per_kg'],
            ];
        }

        // Determine effective mcFine / mcCash
        if ($hasOverrides) {
            $mcFine = array_sum(array_column($overrideRows, null, null) ? array_map(fn($r) => $r['weight_g'] * $r['fine_pct'] / 100, $overrideRows) : [0]);
            $mcCash = array_sum(array_map(fn($r) => $r['weight_g'] / 1000 * $r['cash_rate_per_kg'], $overrideRows));
        } elseif ($charges['hasRules']) {
            $mcFine = $charges['totalFine'];
            $mcCash = $charges['totalCash'];
        } else {
            $mcFine = $totalPartsWeight * $po['fine_pct'] / 100;
            $mcCash = $totalPartsWeight / 1000 * $po['cash_rate_per_kg'];
        }
        $netFine = $fineDiff - $mcFine;

        $gattiStock  = $db->query('SELECT gs.id, gs.batch_number, gs.weight_g, gs.touch_pct, gs.qty_issued_g, mj.job_number, s.name as stamp_name FROM gatti_stock gs LEFT JOIN melt_job mj ON mj.id = gs.melt_job_id LEFT JOIN stamp s ON s.id = gs.stamp_id WHERE (gs.weight_g - gs.qty_issued_g) > 0 ORDER BY gs.created_at DESC')->getResultArray();
        $partBatches = $db->query('SELECT pb.id, pb.batch_number, pb.part_id, pb.touch_pct, pb.weight_in_stock_g, pb.qty_in_stock, p.name as part_name FROM part_batch pb LEFT JOIN part p ON p.id = pb.part_id WHERE pb.weight_in_stock_g > 0 ORDER BY p.name, pb.batch_number')->getResultArray();
        $stamps      = $db->query('SELECT id, name FROM stamp ORDER BY name')->getResultArray();
        $parts       = $db->query('SELECT id, name, gatti FROM part ORDER BY name')->getResultArray();
        $byprods     = $db->query('SELECT id, name FROM byproduct_type ORDER BY name')->getResultArray();
        $pendingReceives = [];
        if ($this->tableExists('pending_receive_entry')) {
            $pendingReceives = $db->query('
                SELECT pre.*, p.name AS part_name, s.name AS stamp_name
                FROM pending_receive_entry pre
                LEFT JOIN part p ON p.id = pre.part_id
                LEFT JOIN stamp s ON s.id = pre.stamp_id
                WHERE pre.status = ?
                ORDER BY pre.created_at DESC, pre.id DESC
            ', ['pending'])->getResultArray();
        }

        // Manufacturing Allocation Plan
        $allocations = $db->query('
            SELECT a.*, p.name AS part_name
            FROM part_order_allocation a
            LEFT JOIN part p ON p.id = a.part_id
            WHERE a.part_order_id = ?
            ORDER BY a.id
        ', [$id])->getResultArray();
        $totalAllocatedWeight = array_sum(array_column($allocations, 'allocated_weight_g'));
        $remainingBalance     = $totalIssuedWeight - $totalAllocatedWeight;
        $defaultTouchPct      = ($totalIssuedWeight > 0)
            ? round(($totalIssuedFine / $totalIssuedWeight) * 100, 4) + 5
            : 5;

        return view('part_orders/view', [
            'title'               => $po['order_number'],
            'po'                  => $po,
            'issues'              => $issues,
            'receives'            => $receives,
            'gattiStock'          => $gattiStock,
            'partBatches'         => $partBatches,
            'stamps'              => $stamps,
            'parts'               => $parts,
            'byprods'             => $byprods,
            'pendingReceives'     => $pendingReceives,
            'totalIssuedFine'     => $totalIssuedFine,
            'totalIssuedWeight'   => $totalIssuedWeight,
            'totalRecvFine'       => $totalRecvFine,
            'totalRecvWeight'     => $totalRecvWeight,
            'totalPartsWeight'    => $totalPartsWeight,
            'fineDiff'            => $fineDiff,
            'mcFine'              => $mcFine,
            'mcCash'              => $mcCash,
            'netFine'             => $netFine,
            'chargeBreakdown'     => $breakdown,
            'hasChargeRules'      => $charges['hasRules'],
            'hasOverrides'        => $hasOverrides,
            'allocations'         => $allocations,
            'totalAllocatedWeight'=> $totalAllocatedWeight,
            'remainingBalance'    => $remainingBalance,
            'defaultTouchPct'     => $defaultTouchPct,
        ]);
    }

    public function saveChargeOverrides($poId)
    {
        $db = \Config\Database::connect();
        $po = $db->query('SELECT * FROM part_order WHERE id = ?', [$poId])->getRowArray();
        if (!$po || $po['status'] === 'posted') {
            return redirect()->to('part-orders/view/'.$poId)->with('error', 'Cannot modify a posted order');
        }

        $ruleIds    = $this->request->getPost('rule_id')        ?? [];
        $labels     = $this->request->getPost('basis_label')    ?? [];
        $weights    = $this->request->getPost('weight_g')       ?? [];
        $fines      = $this->request->getPost('fine_pct')       ?? [];
        $cashRates  = $this->request->getPost('cash_rate_per_kg') ?? [];

        $db->table('part_order_charge_override')->where('part_order_id', $poId)->delete();

        foreach ($ruleIds as $i => $ruleId) {
            $db->table('part_order_charge_override')->insert([
                'part_order_id'    => $poId,
                'rule_id'          => $ruleId ?: null,
                'basis_label'      => $labels[$i] ?? '',
                'weight_g'         => (float)($weights[$i] ?? 0),
                'fine_pct'         => (float)($fines[$i] ?? 0),
                'cash_rate_per_kg' => (float)($cashRates[$i] ?? 0),
            ]);
        }

        return redirect()->to('part-orders/view/'.$poId)->with('success', 'Charge overrides saved');
    }

    public function resetChargeOverrides($poId)
    {
        $db = \Config\Database::connect();
        $db->table('part_order_charge_override')->where('part_order_id', $poId)->delete();
        return redirect()->to('part-orders/view/'.$poId)->with('success', 'Charge overrides reset to auto');
    }

    public function updateNotes($id)
    {
        $db = \Config\Database::connect();
        $po = $db->query('SELECT * FROM part_order WHERE id = ?', [$id])->getRowArray();
        if (!$po || $po['status'] === 'posted') {
            return redirect()->to('part-orders/view/'.$id)->with('error', 'Cannot modify a posted order');
        }
        $db->table('part_order')->where('id', $id)->update(['notes' => $this->request->getPost('notes')]);
        return redirect()->to('part-orders/view/'.$id)->with('success', 'Notes saved');
    }

    public function saveAllocation($poId)
    {
        $db      = \Config\Database::connect();
        $partId  = $this->request->getPost('part_id') ?: null;
        $manual  = trim($this->request->getPost('manual_label') ?? '');
        $weight  = (float)$this->request->getPost('allocated_weight_g');
        $touch   = (float)$this->request->getPost('touch_pct');
        $gattiKg = (float)($this->request->getPost('gatti_per_kg') ?: 0) ?: null;
        $allocId = (int)$this->request->getPost('allocation_id');

        if (!$partId && !$manual) {
            return redirect()->to('part-orders/view/'.$poId)->with('error', 'Select a part or enter a manual label');
        }
        if ($weight <= 0) {
            return redirect()->to('part-orders/view/'.$poId)->with('error', 'Weight must be greater than 0');
        }

        if ($partId) {
            $part    = $db->query('SELECT gatti FROM part WHERE id = ?', [$partId])->getRowArray();
            $gattiKg = $part ? (float)$part['gatti'] : $gattiKg;
            $manual  = null;
        }

        $data = [
            'part_order_id'      => $poId,
            'part_id'            => $partId,
            'manual_label'       => $manual ?: null,
            'allocated_weight_g' => $weight,
            'touch_pct'          => $touch,
            'gatti_per_kg'       => $gattiKg ?: null,
            'tamil_name'         => trim($this->request->getPost('tamil_name') ?? '') ?: null,
        ];

        if ($allocId > 0) {
            $db->table('part_order_allocation')->where('id', $allocId)->where('part_order_id', $poId)->update($data);
            $msg = 'Allocation updated';
        } else {
            $db->table('part_order_allocation')->insert($data);
            $msg = 'Allocation added';
        }

        return redirect()->to('part-orders/view/'.$poId)->with('success', $msg);
    }

    public function deleteAllocation($poId, $allocId)
    {
        $db = \Config\Database::connect();
        $db->table('part_order_allocation')->where('id', $allocId)->where('part_order_id', $poId)->delete();
        return redirect()->to('part-orders/view/'.$poId)->with('success', 'Allocation removed');
    }

    public function updateDisplayTouch($id)
    {
        $db = \Config\Database::connect();
        $po = $db->query('SELECT id FROM part_order WHERE id = ?', [$id])->getRowArray();
        if (!$po) return redirect()->to('part-orders/view/'.$id)->with('error', 'Not found');
        $db->table('part_order')->where('id', $id)->update(['display_touch' => (float)$this->request->getPost('display_touch')]);
        return redirect()->to('part-orders/view/'.$id)->with('success', 'Display touch saved');
    }

    public function manfPlanPdf($id)
    {
        $db = \Config\Database::connect();
        $po = $db->query('
            SELECT po.*, k.name AS karigar_name
            FROM part_order po
            JOIN karigar k ON k.id = po.karigar_id
            WHERE po.id = ?
        ', [$id])->getRowArray();
        if (!$po) return redirect()->to('part-orders');

        // Linked order
        $linkedOrder = null;
        if (!empty($po['client_order_id'])) {
            $linkedOrder = $db->query('SELECT id, order_number FROM orders WHERE id = ?', [$po['client_order_id']])->getRowArray();
        }

        // Total issued weight
        $issueTot = $db->query('SELECT SUM(weight_g) AS tw FROM part_order_issue WHERE part_order_id = ?', [$id])->getRowArray();
        $totalIssuedWeight = (float)($issueTot['tw'] ?? 0);

        // Allocations
        $allocations = $db->query('
            SELECT a.*, p.name AS part_name, p.tamil_name AS part_tamil_name
            FROM part_order_allocation a
            LEFT JOIN part p ON p.id = a.part_id
            WHERE a.part_order_id = ?
            ORDER BY a.id
        ', [$id])->getResultArray();

        $totalAllocated = array_sum(array_column($allocations, 'allocated_weight_g'));

        $css = '
            body { font-family: latha; font-size: 11px; color: #222; margin: 0; padding: 0; }
            .slip { height: 99mm; overflow: hidden; box-sizing: border-box; padding: 2mm 4mm; }
            .cut  { border-top: 1px dashed #999; width: 100%; margin: 0; }
            .hdr-table { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
            .hdr-table td { padding: 0 1mm; vertical-align: top; font-size: 11px; }
            .po-no  { font-size: 13px; font-weight: bold; }
            .karigar-name { font-size: 12px; font-weight: bold; }
            .hdr-right { text-align: right; }
            .date-time  { font-size: 9px; color: #666; }
            .linked     { font-size: 10px; color: #555; }
            .touch-line { font-size: 11px; font-weight: bold; text-align: center; margin-top: 2mm; }
            .band { background: #fff3cd; text-align: center; font-size: 14px;
                    font-weight: bold; padding: 2mm 3mm; margin: 2mm 0; border: 1px solid #e0c87a; }
            table.alloc { width: 100%; border-collapse: collapse; margin-top: 2mm; font-size: 11px; }
            table.alloc th, table.alloc td { border: 1px solid #555; padding: 1mm 2mm; }
            table.alloc th { background: #eee; font-weight: bold; text-align: center; }
            table.alloc td:last-child { text-align: right; }
            table.alloc th:last-child { text-align: right; }
            .tfoot-row td { font-weight: bold; background: #f0f0f0; }
        ';

        $slipHtml = function() use ($po, $linkedOrder, $totalIssuedWeight, $allocations, $totalAllocated) {
            $dateTime   = date('d/m/Y H:i', strtotime($po['created_at']));
            $linkedText = $linkedOrder ? 'இணைப்பு: ' . htmlspecialchars($linkedOrder['order_number']) : '';
            $touchVal   = (float)($po['display_touch'] ?? 0);
            $touch      = ($touchVal == floor($touchVal))
                          ? number_format($touchVal, 0)
                          : number_format($touchVal, 2);

            $h  = '<div class="slip">';
            $h .= '<table class="hdr-table"><tr>';
            $h .= '<td><div class="po-no">' . htmlspecialchars($po['order_number']) . '</div>';
            $h .= '<div class="karigar-name">' . htmlspecialchars($po['karigar_name']) . '</div>';
            if ($linkedText) $h .= '<div class="linked">' . $linkedText . '</div>';
            $h .= '</td>';
            $h .= '<td class="hdr-right"><div class="date-time">' . $dateTime . '</div></td>';
            $h .= '</tr></table>';
            $h .= '<div class="touch-line">டச்: ' . $touch . '%</div>';

            $h .= '<div class="band">பற்று எடை: ' . number_format($totalIssuedWeight, 0) . ' g</div>';

            $h .= '<table class="alloc"><thead><tr><th>தேவையான பொருள்</th><th>கட்டி எடை</th></tr></thead><tbody>';
            foreach ($allocations as $idx => $al) {
                $label = $al['tamil_name'] ?: ($al['part_tamil_name'] ?: ($al['manual_label'] ?: ($al['part_name'] ?: '—')));
                $h .= '<tr><td>' . ($idx + 1) . '. ' . htmlspecialchars($label) . '</td>';
                $h .= '<td>' . number_format((float)$al['allocated_weight_g'], 0, '.', '') . ' g</td></tr>';
            }
            $h .= '</tbody><tfoot><tr class="tfoot-row"><td>மொத்தம்</td><td>' . number_format($totalAllocated, 0, '.', '') . ' g</td></tr></tfoot></table>';
            $h .= '</div>';
            return $h;
        };

        $slip = $slipHtml();
        $html = '<html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>'
              . $slip
              . '<div class="cut"></div>'
              . '</body></html>';

        \App\Services\PdfService::makeA5Portrait($html, 'manf-plan-' . $po['order_number'] . '.pdf');
    }

    public function addIssue($id)
    {
        $db  = \Config\Database::connect();
        $po  = $db->query('SELECT * FROM part_order WHERE id = ?', [$id])->getRowArray();
        if (!$po || $po['status'] === 'posted') return redirect()->to('part-orders/view/'.$id)->with('error', 'Cannot modify');

        $issueType = $this->request->getPost('issue_type') ?: 'gatti';
        $weight    = (float)$this->request->getPost('weight_g');

        if ($issueType === 'part') {
            $partBatchId = $this->request->getPost('part_batch_id');
            $batch       = $db->query('SELECT * FROM part_batch WHERE id = ?', [$partBatchId])->getRowArray();
            if (!$batch) return redirect()->to('part-orders/view/'.$id)->with('error', 'Part batch not found');
            if ($weight > (float)$batch['weight_in_stock_g']) {
                return redirect()->to('part-orders/view/'.$id)->with('error', 'Not enough stock. Available: '.number_format($batch['weight_in_stock_g'],4).'g');
            }
            $db->table('part_order_issue')->insert([
                'part_order_id' => $id,
                'issue_type'    => 'part',
                'part_batch_id' => $partBatchId,
                'part_id'       => $batch['part_id'],
                'gatti_stock_id'=> null,
                'weight_g'      => $weight,
                'touch_pct'     => $batch['touch_pct'],
                'stamp_id'      => null,
                'issued_at'     => date('Y-m-d H:i:s'),
            ]);
            $db->query('UPDATE part_batch SET weight_in_stock_g = GREATEST(0, weight_in_stock_g - ?) WHERE id = ?', [$weight, $partBatchId]);
        } else {
            $gattiId = $this->request->getPost('gatti_stock_id');
            $gatti   = $db->query('SELECT * FROM gatti_stock WHERE id = ?', [$gattiId])->getRowArray();
            if (!$gatti) return redirect()->to('part-orders/view/'.$id)->with('error', 'Gatti not found');
            $available = (float)$gatti['weight_g'] - (float)$gatti['qty_issued_g'];
            if ($weight > $available) return redirect()->to('part-orders/view/'.$id)->with('error', 'Not enough gatti. Available: '.number_format($available,4).'g');
            $db->table('part_order_issue')->insert([
                'part_order_id'  => $id,
                'issue_type'     => 'gatti',
                'gatti_stock_id' => $gattiId,
                'part_batch_id'  => null,
                'part_id'        => null,
                'weight_g'       => $weight,
                'touch_pct'      => $gatti['touch_pct'],
                'stamp_id'       => $this->request->getPost('stamp_id') ?: null,
                'issued_at'      => date('Y-m-d H:i:s'),
            ]);
            $db->query('UPDATE gatti_stock SET qty_issued_g = qty_issued_g + ? WHERE id = ?', [$weight, $gattiId]);
        }

        return redirect()->to('part-orders/view/'.$id)->with('success', 'Issue added');
    }

    public function deleteIssue($issueId)
    {
        $db    = \Config\Database::connect();
        $issue = $db->query('SELECT * FROM part_order_issue WHERE id = ?', [$issueId])->getRowArray();
        if ($issue) {
            $po = $db->query('SELECT * FROM part_order WHERE id = ?', [$issue['part_order_id']])->getRowArray();
            if ($po && $po['status'] !== 'posted') {
                if ($issue['issue_type'] === 'part' && $issue['part_batch_id']) {
                    $db->query('UPDATE part_batch SET weight_in_stock_g = weight_in_stock_g + ? WHERE id = ?', [$issue['weight_g'], $issue['part_batch_id']]);
                } elseif ($issue['gatti_stock_id']) {
                    $db->query('UPDATE gatti_stock SET qty_issued_g = qty_issued_g - ? WHERE id = ?', [$issue['weight_g'], $issue['gatti_stock_id']]);
                }
                $db->table('part_order_issue')->where('id', $issueId)->delete();
            }
        }
        return redirect()->to('part-orders/view/'.($issue['part_order_id'] ?? 0))->with('success', 'Issue deleted');
    }

    public function addReceive($id)
    {
        $db  = \Config\Database::connect();
        $po  = $db->query('SELECT * FROM part_order WHERE id = ?', [$id])->getRowArray();
        if (!$po || $po['status'] === 'posted') return redirect()->to('part-orders/view/'.$id)->with('error', 'Cannot modify');

        $receiveType = $this->request->getPost('receive_type');
        $weight      = (float)$this->request->getPost('weight_g');
        $pcWeight    = (float)($this->request->getPost('piece_weight_g') ?: 0);
        $touch       = (float)$this->request->getPost('touch_pct');
        $batchNo     = trim($this->request->getPost('batch_number') ?? '');

        if (!$touch) {
            $issue = $db->query('SELECT poi.touch_pct FROM part_order_issue poi WHERE poi.part_order_id = ? LIMIT 1', [$id])->getRowArray();
            $touch = $issue['touch_pct'] ?? 0;
        }

        $qty         = 0;

        if ($receiveType === 'part') {
            $partId = $this->request->getPost('part_id');
            $this->_createPartReceive($db, [
                'part_order_id'  => $id,
                'part_id'        => $partId,
                'batch_number'   => $batchNo,
                'weight_g'       => $weight,
                'piece_weight_g' => $pcWeight,
                'touch_pct'      => $touch,
                'stamp_id'       => $this->request->getPost('stamp_id') ?: null,
            ]);
        } else {
            $byprodTypeId = $this->request->getPost('byproduct_type_id');
            $db->table('part_order_receive')->insert([
                'part_order_id'     => $id,
                'receive_type'      => 'byproduct',
                'byproduct_type_id' => $byprodTypeId,
                'weight_g'          => $weight,
                'touch_pct'         => $touch,
                'stamp_id'          => $this->request->getPost('stamp_id') ?: null,
                'received_at'       => date('Y-m-d H:i:s'),
            ]);
            $db->table('byproduct_stock')->insert([
                'byproduct_type_id' => $byprodTypeId,
                'weight_g'          => $weight,
                'touch_pct'         => $touch,
                'source_job_type'   => 'partorder',
                'source_job_id'     => $id,
            ]);
        }

        return redirect()->to('part-orders/view/'.$id)->with('success', 'Receive row added');
    }

    public function importPendingReceives($id)
    {
        $db = \Config\Database::connect();
        $po = $db->query('SELECT * FROM part_order WHERE id = ?', [$id])->getRowArray();
        if (!$po || $po['status'] === 'posted') {
            return redirect()->to('part-orders/view/'.$id)->with('error', 'Cannot modify');
        }
        if (!$this->tableExists('pending_receive_entry')) {
            return redirect()->to('part-orders/view/'.$id)->with('error', 'Pending receive table is missing. Please create the new database table first.');
        }

        $pendingIds = $this->request->getPost('pending_receive_ids') ?? [];
        if (!is_array($pendingIds) || !$pendingIds) {
            return redirect()->to('part-orders/view/'.$id)->with('error', 'Select at least one pending receive row');
        }

        $imported = 0;
        foreach ($pendingIds as $pendingId) {
            $pendingId = (int)$pendingId;
            if ($pendingId <= 0) {
                continue;
            }

            $row = $db->query('SELECT * FROM pending_receive_entry WHERE id = ?', [$pendingId])->getRowArray();
            if (!$row || $row['status'] !== 'pending') {
                continue;
            }

            $receiveId = $this->_createPartReceive($db, [
                'part_order_id'  => $id,
                'part_id'        => $row['part_id'],
                'batch_number'   => $row['batch_number'],
                'weight_g'       => (float)$row['weight_g'],
                'piece_weight_g' => (float)($row['piece_weight_g'] ?: 0),
                'touch_pct'      => (float)$row['touch_pct'],
                'stamp_id'       => $row['stamp_id'] ?: null,
            ]);

            $db->table('pending_receive_entry')->where('id', $pendingId)->where('status', 'pending')->update([
                'status'               => 'used',
                'linked_part_order_id' => $id,
                'linked_receive_id'    => $receiveId,
                'used_at'              => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ]);

            if ($db->affectedRows() > 0) {
                $imported++;
            }
        }

        if ($imported === 0) {
            return redirect()->to('part-orders/view/'.$id)->with('error', 'No pending rows were imported');
        }

        return redirect()->to('part-orders/view/'.$id)->with('success', $imported.' pending receive row(s) imported');
    }

    public function deleteReceive($recvId)
    {
        $db   = \Config\Database::connect();
        $recv = $db->query('SELECT * FROM part_order_receive WHERE id = ?', [$recvId])->getRowArray();
        if ($recv) {
            $po = $db->query('SELECT * FROM part_order WHERE id = ?', [$recv['part_order_id']])->getRowArray();
            if ($po && $po['status'] !== 'posted') {
                if ($recv['part_batch_id'] && $recv['qty']) {
                    $db->query('UPDATE part_batch SET qty_in_stock = GREATEST(0, qty_in_stock - ?), weight_in_stock_g = GREATEST(0, weight_in_stock_g - ?) WHERE id = ?', [$recv['qty'], (float)$recv['weight_g'], $recv['part_batch_id']]);
                }
                $db->table('part_order_receive')->where('id', $recvId)->delete();
            }
        }
        return redirect()->to('part-orders/view/'.($recv['part_order_id'] ?? 0))->with('success', 'Row deleted');
    }

    public function post($id)
    {
        $db  = \Config\Database::connect();
        $po  = $db->query('SELECT po.*, k.name as karigar_name FROM part_order po LEFT JOIN karigar k ON k.id = po.karigar_id WHERE po.id = ?', [$id])->getRowArray();
        if (!$po || $po['status'] === 'posted') return redirect()->to('part-orders/view/'.$id)->with('error', 'Already posted');

        $issues   = $db->query('SELECT * FROM part_order_issue WHERE part_order_id = ?', [$id])->getResultArray();
        $receives = $db->query('SELECT * FROM part_order_receive WHERE part_order_id = ?', [$id])->getResultArray();

        $totalIssuedFine  = 0;
        $totalPartsWeight = 0;
        $stampNames = [];
        $partNames  = [];

        foreach ($issues as $i) {
            $totalIssuedFine += $i['weight_g'] * $i['touch_pct'] / 100;
            if ($i['stamp_id']) {
                $s = $db->query('SELECT name FROM stamp WHERE id = ?', [$i['stamp_id']])->getRowArray();
                if ($s) $stampNames[] = $s['name'];
            }
        }

        $totalRecvFine = 0;
        foreach ($receives as $r) {
            $totalRecvFine += $r['weight_g'] * $r['touch_pct'] / 100;
            if ($r['receive_type'] === 'part') {
                $totalPartsWeight += $r['weight_g'];
                if ($r['part_id']) {
                    $p = $db->query('SELECT name FROM part WHERE id = ?', [$r['part_id']])->getRowArray();
                    if ($p) $partNames[] = $p['name'].($r['batch_number'] ? ' ('.$r['batch_number'].')' : '');
                }
            }
        }

        $fineDiff = $totalIssuedFine - $totalRecvFine;

        // Use overrides if saved, else rules engine, else flat
        $overrideRows = $db->query('SELECT * FROM part_order_charge_override WHERE part_order_id = ?', [$id])->getResultArray();
        if (!empty($overrideRows)) {
            $totalMcFine = array_sum(array_map(fn($r) => $r['weight_g'] * $r['fine_pct'] / 100, $overrideRows));
            $totalMcCash = array_sum(array_map(fn($r) => $r['weight_g'] / 1000 * $r['cash_rate_per_kg'], $overrideRows));
        } else {
            $charges = $this->_computeChargeBreakdown($po['karigar_id'], $issues, $receives);
            if ($charges['hasRules']) {
                $totalMcFine = $charges['totalFine'];
                $totalMcCash = $charges['totalCash'];
            } else {
                $totalMcFine = $totalPartsWeight * $po['fine_pct'] / 100;
                $totalMcCash = $totalPartsWeight / 1000 * $po['cash_rate_per_kg'];
            }
        }

        $netFine = $fineDiff - $totalMcFine;

        $narration = $po['order_number'].' | Karigar: '.$po['karigar_name'];
        if ($stampNames) $narration .= ' | Stamps: '.implode(', ', array_unique($stampNames));
        if ($partNames)  $narration .= ' | Parts: '.implode(', ', $partNames);
        $narration .= ' | Net fine owed: '.round($netFine,4).'g';

        if ($netFine != 0) {
            $db->table('karigar_ledger')->insert([
                'karigar_id'   => $po['karigar_id'],
                'source_type'  => 'part_order',
                'source_id'    => $id,
                'account_type' => 'fine',
                'direction'    => $netFine > 0 ? 'debit' : 'credit',
                'amount'       => round(abs($netFine), 4),
                'narration'    => $narration,
            ]);
        }

        if ($totalMcCash > 0) {
            $db->table('karigar_ledger')->insert([
                'karigar_id'   => $po['karigar_id'],
                'source_type'  => 'part_order',
                'source_id'    => $id,
                'account_type' => 'cash',
                'direction'    => 'credit',
                'amount'       => round($totalMcCash, 2),
                'narration'    => $narration.' | Cash making charge',
            ]);
        }

        $db->table('part_order')->where('id', $id)->update(['status' => 'posted']);
        return redirect()->to('part-orders/view/'.$id)->with('success', 'Part order posted to ledger');
    }

    public function updateIssue($issueId)
    {
        $db    = \Config\Database::connect();
        $issue = $db->query('SELECT * FROM part_order_issue WHERE id = ?', [$issueId])->getRowArray();
        if (!$issue) return redirect()->to('part-orders')->with('error', 'Issue not found');

        $po = $db->query('SELECT * FROM part_order WHERE id = ?', [$issue['part_order_id']])->getRowArray();
        if (!$po || $po['status'] === 'posted') return redirect()->to('part-orders/view/'.$issue['part_order_id'])->with('error', 'Cannot modify');

        $newWeight = (float)$this->request->getPost('weight_g');
        $newTouch  = (float)($this->request->getPost('touch_pct') ?? 0);
        if ($newWeight <= 0) return redirect()->to('part-orders/view/'.$issue['part_order_id'])->with('error', 'Weight must be > 0');

        $oldWeight = (float)$issue['weight_g'];

        if ($issue['issue_type'] === 'part' && $issue['part_batch_id']) {
            $batch     = $db->query('SELECT * FROM part_batch WHERE id = ?', [$issue['part_batch_id']])->getRowArray();
            $available = (float)$batch['weight_in_stock_g'] + $oldWeight;
            if ($newWeight > $available) {
                return redirect()->to('part-orders/view/'.$issue['part_order_id'])->with('error', 'Not enough stock. Available: '.number_format($available,4).'g');
            }
            $db->query('UPDATE part_batch SET weight_in_stock_g = GREATEST(0, weight_in_stock_g + ? - ?) WHERE id = ?', [$oldWeight, $newWeight, $issue['part_batch_id']]);
            $db->query('UPDATE part_order_issue SET weight_g = ?, touch_pct = ? WHERE id = ?', [$newWeight, $newTouch, $issueId]);
        } else {
            $gatti     = $db->query('SELECT * FROM gatti_stock WHERE id = ?', [$issue['gatti_stock_id']])->getRowArray();
            $newStamp  = $this->request->getPost('stamp_id') ?: null;
            $available = (float)$gatti['weight_g'] - (float)$gatti['qty_issued_g'] + $oldWeight;
            if ($newWeight > $available) {
                return redirect()->to('part-orders/view/'.$issue['part_order_id'])->with('error', 'Not enough gatti. Available: '.number_format($available,4).'g');
            }
            $db->query('UPDATE gatti_stock SET qty_issued_g = qty_issued_g - ? + ? WHERE id = ?', [$oldWeight, $newWeight, $issue['gatti_stock_id']]);
            $db->query('UPDATE part_order_issue SET weight_g = ?, stamp_id = ?, touch_pct = ? WHERE id = ?', [$newWeight, $newStamp, $newTouch, $issueId]);
        }

        return redirect()->to('part-orders/view/'.$issue['part_order_id'])->with('success', 'Issue updated');
    }

    public function updateReceive($recvId)
    {
        $db   = \Config\Database::connect();
        $recv = $db->query('SELECT * FROM part_order_receive WHERE id = ?', [$recvId])->getRowArray();
        if (!$recv) return redirect()->to('part-orders')->with('error', 'Receive not found');

        $po = $db->query('SELECT * FROM part_order WHERE id = ?', [$recv['part_order_id']])->getRowArray();
        if (!$po || $po['status'] === 'posted') return redirect()->to('part-orders/view/'.$recv['part_order_id'])->with('error', 'Cannot modify');

        $newWeight = (float)$this->request->getPost('weight_g');
        $newPcWt   = (float)($this->request->getPost('piece_weight_g') ?? 0);
        $newTouch  = (float)($this->request->getPost('touch_pct') ?? 0);
        $newQty    = $newPcWt > 0 ? (int)round($newWeight / $newPcWt) : (int)$this->request->getPost('qty');

        if ($newWeight <= 0) return redirect()->to('part-orders/view/'.$recv['part_order_id'])->with('error', 'Weight must be > 0');

        if ($recv['part_batch_id']) {
            $oldQty = (int)$recv['qty'];
            $oldWt  = (float)$recv['weight_g'];
            $db->query('UPDATE part_batch SET weight_in_stock_g = GREATEST(0, weight_in_stock_g - ? + ?), qty_in_stock = GREATEST(0, qty_in_stock - ? + ?) WHERE id = ?', [$oldWt, $newWeight, $oldQty, $newQty, $recv['part_batch_id']]);
        }

        $db->query('UPDATE part_order_receive SET weight_g = ?, piece_weight_g = ?, qty = ?, touch_pct = ? WHERE id = ?', [$newWeight, $newPcWt ?: null, $newQty, $newTouch, $recvId]);

        return redirect()->to('part-orders/view/'.$recv['part_order_id'])->with('success', 'Receive updated');
    }

    private function _createPartReceive($db, array $data)
    {
        $partOrderId = (int)$data['part_order_id'];
        $partId      = (int)$data['part_id'];
        $batchNo     = trim((string)($data['batch_number'] ?? ''));
        $weight      = (float)($data['weight_g'] ?? 0);
        $pcWeight    = (float)($data['piece_weight_g'] ?? 0);
        $touch       = (float)($data['touch_pct'] ?? 0);
        $stampId     = $data['stamp_id'] ?? null;
        $qty         = $pcWeight > 0 ? (int)round($weight / $pcWeight) : 0;
        $partBatchId = null;

        if ($batchNo !== '') {
            $batch = $db->query('SELECT * FROM part_batch WHERE batch_number = ?', [$batchNo])->getRowArray();
            if ($batch) {
                $partBatchId = $batch['id'];
                $db->table('part_batch')->where('id', $batch['id'])->update([
                    'piece_weight_g'       => $pcWeight ?: $batch['piece_weight_g'],
                    'touch_pct'            => $touch,
                    'qty_in_stock'         => (int)$batch['qty_in_stock'] + $qty,
                    'weight_in_stock_g'    => (float)$batch['weight_in_stock_g'] + $weight,
                    'source_part_order_id' => $partOrderId,
                    'received_at'          => date('Y-m-d H:i:s'),
                ]);
            } else {
                $db->table('part_batch')->insert([
                    'batch_number'         => $batchNo,
                    'part_id'              => $partId,
                    'piece_weight_g'       => $pcWeight ?: null,
                    'touch_pct'            => $touch,
                    'qty_in_stock'         => $qty,
                    'weight_in_stock_g'    => $weight,
                    'source_part_order_id' => $partOrderId,
                    'received_at'          => date('Y-m-d H:i:s'),
                ]);
                $partBatchId = $db->insertID();
            }
        }

        $db->table('part_order_receive')->insert([
            'part_order_id'  => $partOrderId,
            'receive_type'   => 'part',
            'part_id'        => $partId,
            'batch_number'   => $batchNo,
            'part_batch_id'  => $partBatchId,
            'weight_g'       => $weight,
            'piece_weight_g' => $pcWeight ?: null,
            'qty'            => $qty,
            'touch_pct'      => $touch,
            'stamp_id'       => $stampId,
            'received_at'    => date('Y-m-d H:i:s'),
        ]);

        return (int)$db->insertID();
    }

    private function _nextOrderNumber()
    {
        $db  = \Config\Database::connect();
        $row = $db->query('SELECT COUNT(*) as cnt FROM part_order')->getRowArray();
        return 'PARTORD-' . str_pad(($row['cnt'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    }

    private function _computeChargeBreakdown($karigarId, $issues, $receives)
    {
        $db    = \Config\Database::connect();
        $rules = $db->query('SELECT * FROM karigar_charge_rule WHERE karigar_id = ? ORDER BY sort_order, id', [$karigarId])->getResultArray();

        $breakdown = [];
        $totalFine = 0;
        $totalCash = 0;

        foreach ($rules as $rule) {
            $filterIds = $rule['filter_ids'] ? json_decode($rule['filter_ids'], true) : [];
            $weight    = 0;

            switch ($rule['basis']) {
                case 'issued_all':
                    foreach ($issues as $i) $weight += $i['weight_g'];
                    break;
                case 'issued_gatti_only':
                    foreach ($issues as $i) {
                        if (($i['issue_type'] ?? 'gatti') === 'gatti') $weight += $i['weight_g'];
                    }
                    break;
                case 'issued_filtered':
                    foreach ($issues as $i) {
                        if ($rule['filter_type'] === 'by_part' && $i['issue_type'] === 'part' && in_array((int)$i['part_id'], $filterIds)) {
                            $weight += $i['weight_g'];
                        }
                    }
                    break;
                case 'received_all':
                    foreach ($receives as $r) $weight += $r['weight_g'];
                    break;
                case 'received_filtered':
                    foreach ($receives as $r) {
                        if ($rule['filter_type'] === 'by_part' && $r['receive_type'] === 'part' && in_array((int)$r['part_id'], $filterIds)) {
                            $weight += $r['weight_g'];
                        }
                    }
                    break;
            }

            $fine = $weight * $rule['fine_pct'] / 100;
            $cash = $weight / 1000 * $rule['cash_rate_per_kg'];
            $totalFine += $fine;
            $totalCash += $cash;

            $breakdown[] = [
                'rule'   => $rule,
                'weight' => $weight,
                'fine'   => $fine,
                'cash'   => $cash,
            ];
        }

        return [
            'breakdown' => $breakdown,
            'totalFine' => $totalFine,
            'totalCash' => $totalCash,
            'hasRules'  => !empty($rules),
        ];
    }
}
