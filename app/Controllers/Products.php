<?php

namespace App\Controllers;

class Products extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    private function getVariationGroups()
    {
        $rows = $this->db->query('SELECT DISTINCT group_name FROM variation ORDER BY group_name')->getResultArray();
        return array_column($rows, 'group_name');
    }

    private function getVariationGroupsForType($productTypeId)
    {
        if (!$productTypeId) return $this->getVariationGroups();
        $pt = $this->db->query('SELECT variations FROM product_type WHERE id = ?', [$productTypeId])->getRowArray();
        if (!$pt || empty($pt['variations'])) return [];
        $vids = array_filter(array_map('trim', explode(',', $pt['variations'])));
        if (!$vids) return [];
        $ph = implode(',', array_fill(0, count($vids), '?'));
        $rows = $this->db->query("SELECT DISTINCT group_name FROM variation WHERE id IN ($ph) ORDER BY group_name", $vids)->getResultArray();
        return array_column($rows, 'group_name');
    }

    private function getFormData($productTypeId = null)
    {
        $templates = $this->db->query(
            'SELECT bt.*, pt.name as type_name FROM bom_template bt LEFT JOIN product_type pt ON bt.product_type_id = pt.id ORDER BY bt.name'
        )->getResultArray();

        return [
            'productTypes' => $this->db->query('SELECT * FROM product_type ORDER BY name')->getResultArray(),
            'bodies' => $this->db->query('SELECT * FROM body ORDER BY name')->getResultArray(),
            'parts' => $this->db->query('SELECT * FROM part ORDER BY name')->getResultArray(),
            'mainParts' => $this->db->query('SELECT * FROM part WHERE is_main_part = 1 ORDER BY name')->getResultArray(),
            'podies' => $this->db->query('SELECT * FROM podi ORDER BY name')->getResultArray(),
            'variationGroups' => $this->getVariationGroupsForType($productTypeId),
            'templates' => $templates,
        ];
    }

    public function index()
    {
        $search = $this->request->getGet('q') ?? '';
        $filterType = $this->request->getGet('type') ?? '';
        $sortBy = $this->request->getGet('sort') ?? 'name';
        $sortDir = $this->request->getGet('dir') ?? 'asc';

        $allowed = ['name', 'sku', 'product_type_name', 'pattern_count'];
        if (!in_array($sortBy, $allowed)) $sortBy = 'name';
        $sortDir = $sortDir === 'desc' ? 'DESC' : 'ASC';

        $sql = 'SELECT p.*, pt.name as product_type_name, b.name as body_name, mp.name as main_part_name,
                   (SELECT COUNT(*) FROM product_pattern pp WHERE pp.product_id = p.id) as pattern_count
            FROM product p
            LEFT JOIN product_type pt ON p.product_type_id = pt.id
            LEFT JOIN body b ON p.body_id = b.id
            LEFT JOIN part mp ON p.main_part_id = mp.id
            WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (p.name LIKE ? OR p.sku LIKE ? OR p.tamil_name LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($filterType !== '') {
            $sql .= ' AND p.product_type_id = ?';
            $params[] = $filterType;
        }

        if ($sortBy === 'sku') {
            $sql .= ' ORDER BY ISNULL(p.sku) ASC, (p.sku = \'\') ASC, LENGTH(p.sku) ' . $sortDir . ', p.sku ' . $sortDir;
        } elseif ($sortBy === 'pattern_count') {
            $sql .= ' ORDER BY pattern_count ' . $sortDir;
        } elseif ($sortBy === 'product_type_name') {
            $sql .= ' ORDER BY pt.name ' . $sortDir;
        } else {
            $sql .= ' ORDER BY p.' . $sortBy . ' ' . $sortDir;
        }

        $items = $this->db->query($sql, $params)->getResultArray();
        $productTypes = $this->db->query('SELECT * FROM product_type ORDER BY name')->getResultArray();

        return view('products/index', [
            'title' => 'Products',
            'items' => $items,
            'productTypes' => $productTypes,
            'search' => $search,
            'filterType' => $filterType,
            'sortBy' => $sortBy,
            'sortDir' => strtolower($sortDir),
        ]);
    }

    public function create()
    {
        return view('products/form', array_merge(['title' => 'Add Product'], $this->getFormData()));
    }

    public function store()
    {
        $data = $this->request->getPost();

        $sku = trim($data['sku'] ?? '');
        if ($sku !== '') {
            $dupe = $this->db->query('SELECT id FROM product WHERE sku = ?', [$sku])->getRowArray();
            if ($dupe) return redirect()->back()->withInput()->with('error', 'SKU "' . $sku . '" already exists. Please use a different SKU.');
        }

        $productData = [
            'name' => $data['name'] ?? '',
            'short_name' => trim($data['short_name'] ?? '') ?: null,
            'sku' => $data['sku'] ?? '',
            'tamil_name' => $data['tamil_name'] ?? '',
            'product_type_id' => $data['product_type_id'] ?: null,
            'body_id' => $data['body_id'] ?: null,
            'main_part_id' => $data['main_part_id'] ?: null,
            'pidi' => $data['pidi'] ?? '',
        ];

        $image = $this->request->getFile('product_image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $newName = $image->getRandomName();
            $uploadPath = FCPATH . 'uploads/products';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
            $image->move($uploadPath, $newName);
            $productData['image'] = $newName;
        }

        $this->db->table('product')->insert($productData);
        $productId = $this->db->insertID();

        $this->saveBom($productId, $data);

        // Create default Standard pattern
        $this->db->table('product_pattern')->insert([
            'product_id' => $productId,
            'name' => 'Standard',
            'tamil_name' => '',
            'is_default' => 1,
        ]);

        return redirect()->to('products/view/' . $productId)->with('success', 'Product added successfully');
    }

    public function view($id)
    {
        $product = $this->db->query('
            SELECT p.*, pt.name as product_type_name, pt.multiplication_factor,
                   pt.variations as pt_variations,
                   b.name as body_name, b.clasp_size, mp.name as main_part_name
            FROM product p
            LEFT JOIN product_type pt ON p.product_type_id = pt.id
            LEFT JOIN body b ON p.body_id = b.id
            LEFT JOIN part mp ON p.main_part_id = mp.id
            WHERE p.id = ?
        ', [$id])->getRowArray();

        if (!$product) return redirect()->to('products')->with('error', 'Not found');

        $bom = $this->db->query('
            SELECT bom.*, pa.name as part_name, po.name as podi_name
            FROM product_bill_of_material bom
            LEFT JOIN part pa ON bom.part_id = pa.id
            LEFT JOIN podi po ON bom.podi_id = po.id
            WHERE bom.product_id = ?
        ', [$id])->getResultArray();

        $cbomCount = $this->db->query('SELECT COUNT(*) as cnt FROM product_customize_bill_of_material WHERE product_id = ?', [$id])->getRowArray()['cnt'];

        // --- Variation coverage warnings ---
        $ptVariationIds = [];
        if (!empty($product['pt_variations'])) {
            $ptVariationIds = array_filter(array_map('trim', explode(',', $product['pt_variations'])));
        }
        $totalVarCount = count($ptVariationIds);

        // Count CBOM rows missing qty entries for some variations
        $missingCbomCount = 0;
        if ($totalVarCount > 0 && $cbomCount > 0) {
            $cbomIds = $this->db->query('SELECT id FROM product_customize_bill_of_material WHERE product_id = ?', [$id])->getResultArray();
            foreach ($cbomIds as $c) {
                $covered = (int)$this->db->query(
                    'SELECT COUNT(DISTINCT variation_id) as cnt FROM product_customize_bill_of_material_quantity WHERE product_customize_bill_of_material_id = ?',
                    [$c['id']]
                )->getRowArray()['cnt'];
                if ($covered < $totalVarCount) $missingCbomCount++;
            }
        }

        // Find Logic BOM variation groups not covered by any BOM row
        $uncoveredBomGroups = [];
        if (!empty($ptVariationIds) && !empty($bom)) {
            $ph = implode(',', array_fill(0, count($ptVariationIds), '?'));
            $ptGroups = array_column(
                $this->db->query("SELECT DISTINCT group_name FROM variation WHERE id IN ($ph) ORDER BY group_name", $ptVariationIds)->getResultArray(),
                'group_name'
            );
            foreach ($ptGroups as $group) {
                $covered = false;
                foreach ($bom as $b) {
                    $vg = trim($b['variation_group'] ?? '');
                    if ($vg === '') { $covered = true; break; }
                    if (in_array($group, array_map('trim', explode(',', $vg)))) { $covered = true; break; }
                }
                if (!$covered) $uncoveredBomGroups[] = $group;
            }
        }
        // --- end warnings ---

        $patterns = $this->db->query('
            SELECT pp.*, pn.name as global_pattern_name
            FROM product_pattern pp
            LEFT JOIN pattern_name pn ON pp.pattern_name_id = pn.id
            WHERE pp.product_id = ?
            ORDER BY pp.is_default DESC, pp.name
        ', [$id])->getResultArray();

        foreach ($patterns as &$pat) {
            $pat['changes'] = $this->db->query('
                SELECT pc.*, pa.name as part_name, rp.name as replace_part_name
                FROM product_pattern_bom_change pc
                LEFT JOIN part pa ON pc.part_id = pa.id
                LEFT JOIN part rp ON pc.replace_part_id = rp.id
                WHERE pc.product_pattern_id = ?
            ', [$pat['id']])->getResultArray();
        }

        $templates = $this->db->query(
            'SELECT bt.*, pt.name as type_name FROM bom_template bt LEFT JOIN product_type pt ON bt.product_type_id = pt.id ORDER BY bt.name'
        )->getResultArray();

        return view('products/view', [
            'title' => $product['name'],
            'product' => $product,
            'bom' => $bom,
            'cbomCount' => $cbomCount,
            'missingCbomCount' => $missingCbomCount,
            'uncoveredBomGroups' => $uncoveredBomGroups,
            'patterns' => $patterns,
            'patternNames' => $this->db->query('SELECT * FROM pattern_name ORDER BY name')->getResultArray(),
            'parts' => $this->db->query('SELECT * FROM part ORDER BY name')->getResultArray(),
            'podies' => $this->db->query('SELECT * FROM podi ORDER BY name')->getResultArray(),
            'variationGroups' => $this->getVariationGroupsForType($product['product_type_id']),
            'templates' => $templates,
        ]);
    }

    public function edit($id)
    {
        $product = $this->db->query('SELECT * FROM product WHERE id = ?', [$id])->getRowArray();
        if (!$product) return redirect()->to('products')->with('error', 'Not found');

        $bom = $this->db->query('SELECT * FROM product_bill_of_material WHERE product_id = ?', [$id])->getResultArray();

        return view('products/form', array_merge(
            ['title' => 'Edit: ' . $product['name'], 'item' => $product, 'bom' => $bom],
            $this->getFormData($product['product_type_id'])
        ));
    }

    public function update($id)
    {
        $data = $this->request->getPost();

        $sku = trim($data['sku'] ?? '');
        if ($sku !== '') {
            $dupe = $this->db->query('SELECT id FROM product WHERE sku = ? AND id != ?', [$sku, $id])->getRowArray();
            if ($dupe) return redirect()->back()->withInput()->with('error', 'SKU "' . $sku . '" already used by another product.');
        }

        $productData = [
            'name' => $data['name'] ?? '',
            'short_name' => trim($data['short_name'] ?? '') ?: null,
            'sku' => $data['sku'] ?? '',
            'tamil_name' => $data['tamil_name'] ?? '',
            'product_type_id' => $data['product_type_id'] ?: null,
            'body_id' => $data['body_id'] ?: null,
            'main_part_id' => $data['main_part_id'] ?: null,
            'pidi' => $data['pidi'] ?? '',
        ];

        $image = $this->request->getFile('product_image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $newName = $image->getRandomName();
            $uploadPath = FCPATH . 'uploads/products';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
            $image->move($uploadPath, $newName);
            $productData['image'] = $newName;
        }

        $this->db->table('product')->where('id', $id)->update($productData);
        $this->db->query('DELETE FROM product_bill_of_material WHERE product_id = ?', [$id]);
        $this->saveBom($id, $data);

        return redirect()->to('products/view/' . $id)->with('success', 'Product updated');
    }

    private function saveBom($productId, $data)
    {
        if (empty($data['bom_part_id'])) return;
        foreach ($data['bom_part_id'] as $i => $partId) {
            if (empty($partId)) continue;
            $vg = '';
            if (isset($data['bom_variation_group'][$i]) && is_array($data['bom_variation_group'][$i])) {
                $vg = implode(',', $data['bom_variation_group'][$i]);
            }
            $this->db->table('product_bill_of_material')->insert([
                'product_id' => $productId,
                'part_id' => $partId,
                'part_pcs' => $data['bom_part_pcs'][$i] ?? null,
                'scale' => $data['bom_scale'][$i] ?? null,
                'variation_group' => $vg,
                'podi_id' => $data['bom_podi_id'][$i] ?: null,
                'podi_pcs' => $data['bom_podi_pcs'][$i] ?? null,
            ]);
        }
    }

    public function delete($id)
    {
        $this->db->transStart();
        $cbomIds = $this->db->query('SELECT id FROM product_customize_bill_of_material WHERE product_id = ?', [$id])->getResultArray();
        foreach ($cbomIds as $c) {
            $this->db->query('DELETE FROM product_customize_bill_of_material_quantity WHERE product_customize_bill_of_material_id = ?', [$c['id']]);
        }
        $this->db->query('DELETE FROM product_customize_bill_of_material WHERE product_id = ?', [$id]);
        $this->db->query('DELETE FROM product_bill_of_material WHERE product_id = ?', [$id]);
        $patIds = $this->db->query('SELECT id FROM product_pattern WHERE product_id = ?', [$id])->getResultArray();
        foreach ($patIds as $p) {
            $this->db->query('DELETE FROM product_pattern_bom_change WHERE product_pattern_id = ?', [$p['id']]);
        }
        $this->db->query('DELETE FROM product_pattern WHERE product_id = ?', [$id]);
        $this->db->query('DELETE FROM product WHERE id = ?', [$id]);
        $this->db->transComplete();
        return redirect()->to('products')->with('success', 'Product deleted');
    }

    // ========== DUPLICATE PRODUCT (Fix: copies CBOM quantities too) ==========

    public function duplicate($id)
    {
        $this->db->transStart();

        $src = $this->db->query('SELECT * FROM product WHERE id = ?', [$id])->getRowArray();
        if (!$src) return redirect()->to('products')->with('error', 'Not found');

        // Insert new product
        $this->db->table('product')->insert([
            'name' => 'Copy of ' . $src['name'],
            'sku' => '',
            'tamil_name' => $src['tamil_name'],
            'product_type_id' => $src['product_type_id'],
            'body_id' => $src['body_id'],
            'main_part_id' => $src['main_part_id'],
            'pidi' => $src['pidi'],
        ]);
        $newId = $this->db->insertID();

        // Copy BOM
        $bomRows = $this->db->query('SELECT * FROM product_bill_of_material WHERE product_id = ?', [$id])->getResultArray();
        foreach ($bomRows as $row) {
            unset($row['id']);
            $row['product_id'] = $newId;
            $this->db->table('product_bill_of_material')->insert($row);
        }

        // Copy CBOM + quantities (the fix)
        $cbomRows = $this->db->query('SELECT * FROM product_customize_bill_of_material WHERE product_id = ?', [$id])->getResultArray();
        foreach ($cbomRows as $cbomRow) {
            $oldCbomId = $cbomRow['id'];
            unset($cbomRow['id']);
            $cbomRow['product_id'] = $newId;
            $this->db->table('product_customize_bill_of_material')->insert($cbomRow);
            $newCbomId = $this->db->insertID();

            // Copy all quantity rows for this CBOM item
            $qtyRows = $this->db->query('SELECT * FROM product_customize_bill_of_material_quantity WHERE product_customize_bill_of_material_id = ?', [$oldCbomId])->getResultArray();
            foreach ($qtyRows as $qty) {
                unset($qty['id']);
                $qty['product_customize_bill_of_material_id'] = $newCbomId;
                $this->db->table('product_customize_bill_of_material_quantity')->insert($qty);
            }
        }

        // Copy patterns + changes
        $patterns = $this->db->query('SELECT * FROM product_pattern WHERE product_id = ?', [$id])->getResultArray();
        foreach ($patterns as $pat) {
            $oldPatId = $pat['id'];
            unset($pat['id']);
            $pat['product_id'] = $newId;
            $this->db->table('product_pattern')->insert($pat);
            $newPatId = $this->db->insertID();

            $changes = $this->db->query('SELECT * FROM product_pattern_bom_change WHERE product_pattern_id = ?', [$oldPatId])->getResultArray();
            foreach ($changes as $ch) {
                unset($ch['id']);
                $ch['product_pattern_id'] = $newPatId;
                $this->db->table('product_pattern_bom_change')->insert($ch);
            }
        }

        $this->db->transComplete();
        return redirect()->to('products/edit/' . $newId)->with('success', 'Product duplicated. Update name and SKU.');
    }

    // ========== CUSTOMIZE BOM (Separate Screen) ==========

    public function cbom($id)
    {
        $product = $this->db->query('
            SELECT p.*, pt.name as product_type_name, pt.variations as pt_variations
            FROM product p LEFT JOIN product_type pt ON p.product_type_id = pt.id
            WHERE p.id = ?
        ', [$id])->getRowArray();
        if (!$product) return redirect()->to('products')->with('error', 'Not found');

        $variations = [];
        if (!empty($product['pt_variations'])) {
            $vids = array_map('trim', explode(',', $product['pt_variations']));
            $ph = implode(',', array_fill(0, count($vids), '?'));
            $variations = $this->db->query("SELECT * FROM variation WHERE id IN ($ph) ORDER BY group_name, size", $vids)->getResultArray();
        }

        $cbom = $this->db->query('
            SELECT cbom.*, pa.name as part_name, po.name as podi_name
            FROM product_customize_bill_of_material cbom
            LEFT JOIN part pa ON cbom.part_id = pa.id
            LEFT JOIN podi po ON cbom.podi_id = po.id
            WHERE cbom.product_id = ?
        ', [$id])->getResultArray();

        foreach ($cbom as &$c) {
            $c['quantities'] = $this->db->query('
                SELECT q.*, v.name as variation_name, v.group_name, v.size
                FROM product_customize_bill_of_material_quantity q
                LEFT JOIN variation v ON q.variation_id = v.id
                WHERE q.product_customize_bill_of_material_id = ?
                ORDER BY v.group_name, v.size
            ', [$c['id']])->getResultArray();
        }

        return view('products/cbom', [
            'title' => 'Customize BOM: ' . $product['name'],
            'product' => $product,
            'cbom' => $cbom,
            'variations' => $variations,
            'parts' => $this->db->query('SELECT * FROM part ORDER BY name')->getResultArray(),
            'podies' => $this->db->query('SELECT * FROM podi ORDER BY name')->getResultArray(),
            'templates' => $this->db->query('SELECT * FROM bom_template WHERE product_type_id = ? OR product_type_id IS NULL ORDER BY name', [$product['product_type_id']])->getResultArray(),
        ]);
    }

    public function saveCbom($id)
    {
        $data = $this->request->getPost();
        $product = $this->db->query('SELECT p.*, pt.variations as pt_variations FROM product p LEFT JOIN product_type pt ON p.product_type_id = pt.id WHERE p.id = ?', [$id])->getRowArray();
        if (!$product) return redirect()->to('products')->with('error', 'Not found');

        $variationIds = [];
        if (!empty($product['pt_variations'])) {
            $variationIds = array_map('trim', explode(',', $product['pt_variations']));
        }

        $oldCbom = $this->db->query('SELECT id FROM product_customize_bill_of_material WHERE product_id = ?', [$id])->getResultArray();
        foreach ($oldCbom as $oc) {
            $this->db->query('DELETE FROM product_customize_bill_of_material_quantity WHERE product_customize_bill_of_material_id = ?', [$oc['id']]);
        }
        $this->db->query('DELETE FROM product_customize_bill_of_material WHERE product_id = ?', [$id]);

        if (!empty($data['cbom_part_id'])) {
            foreach ($data['cbom_part_id'] as $i => $partId) {
                if (empty($partId)) continue;
                $this->db->table('product_customize_bill_of_material')->insert([
                    'product_id' => $id,
                    'part_id' => $partId,
                    'podi_id' => $data['cbom_podi_id'][$i] ?: null,
                ]);
                $cbomId = $this->db->insertID();
                foreach ($variationIds as $vid) {
                    $pq = $data['cbom_qty_' . $vid . '_part'][$i] ?? null;
                    $dq = $data['cbom_qty_' . $vid . '_podi'][$i] ?? null;
                    if ($pq || $dq) {
                        $this->db->table('product_customize_bill_of_material_quantity')->insert([
                            'product_customize_bill_of_material_id' => $cbomId,
                            'variation_id' => $vid,
                            'part_quantity' => $pq ?: 0,
                            'podi_quantity' => $dq ?: 0,
                        ]);
                    }
                }
            }
        }

        return redirect()->to('products/view/' . $id)->with('success', 'Customize BOM saved');
    }

    // ========== IMPORT TEMPLATE ==========

    public function importBomTemplate($id)
    {
        $templateId = $this->request->getPost('template_id');
        if (!$templateId) return redirect()->to('products/edit/' . $id)->with('error', 'No template selected');

        $tmpl = $this->db->query('SELECT * FROM bom_template WHERE id = ?', [$templateId])->getRowArray();
        if (!$tmpl) return redirect()->to('products/edit/' . $id)->with('error', 'Template not found');

        $product = $this->db->query('SELECT product_type_id FROM product WHERE id = ?', [$id])->getRowArray();
        if (!empty($tmpl['product_type_id']) && $tmpl['product_type_id'] != $product['product_type_id']) {
            return redirect()->to('products/edit/' . $id)->with('error', 'Template is for a different product type');
        }

        $items = $this->db->query('SELECT * FROM bom_template_item WHERE template_id = ? AND type = "bom"', [$templateId])->getResultArray();
        foreach ($items as $item) {
            $this->db->table('product_bill_of_material')->insert([
                'product_id' => $id,
                'part_id' => $item['part_id'],
                'part_pcs' => $item['part_pcs'],
                'scale' => $item['scale'],
                'variation_group' => $item['variation_group'],
                'podi_id' => $item['podi_id'],
                'podi_pcs' => $item['podi_pcs'],
            ]);
        }

        $tName = $this->db->query('SELECT name FROM bom_template WHERE id = ?', [$templateId])->getRowArray()['name'] ?? '';
        return redirect()->to('products/edit/' . $id)->with('success', count($items) . ' BOM items imported from "' . $tName . '"');
    }

    public function importCbomTemplate($id)
    {
        $templateId = $this->request->getPost('template_id');
        if (!$templateId) return redirect()->to('products/cbom/' . $id)->with('error', 'No template selected');

        $tmpl = $this->db->query('SELECT * FROM bom_template WHERE id = ?', [$templateId])->getRowArray();
        if (!$tmpl) return redirect()->to('products/cbom/' . $id)->with('error', 'Template not found');

        $product = $this->db->query('SELECT p.product_type_id, pt.variations as pt_variations FROM product p LEFT JOIN product_type pt ON p.product_type_id = pt.id WHERE p.id = ?', [$id])->getRowArray();
        if (!empty($tmpl['product_type_id']) && $tmpl['product_type_id'] != $product['product_type_id']) {
            return redirect()->to('products/cbom/' . $id)->with('error', 'Template is for a different product type');
        }
        $variationIds = [];
        if (!empty($product['pt_variations'])) {
            $variationIds = array_map('trim', explode(',', $product['pt_variations']));
        }

        $items = $this->db->query('SELECT * FROM bom_template_item WHERE template_id = ? AND type = "cbom"', [$templateId])->getResultArray();
        $count = 0;
        foreach ($items as $item) {
            $this->db->table('product_customize_bill_of_material')->insert([
                'product_id' => $id,
                'part_id' => $item['part_id'],
                'podi_id' => $item['podi_id'],
            ]);
            $cbomId = $this->db->insertID();
            $count++;

            foreach ($variationIds as $vid) {
                $qty = $this->db->query('SELECT * FROM bom_template_cbom_qty WHERE template_item_id = ? AND variation_id = ?', [$item['id'], $vid])->getRowArray();
                if ($qty) {
                    $this->db->table('product_customize_bill_of_material_quantity')->insert([
                        'product_customize_bill_of_material_id' => $cbomId,
                        'variation_id' => $vid,
                        'part_quantity' => $qty['part_quantity'] ?? 0,
                        'podi_quantity' => $qty['podi_quantity'] ?? 0,
                    ]);
                }
            }
        }

        $tName = $this->db->query('SELECT name FROM bom_template WHERE id = ?', [$templateId])->getRowArray()['name'] ?? '';
        return redirect()->to('products/cbom/' . $id)->with('success', $count . ' CBOM items imported from "' . $tName . '"');
    }

    // ========== AJAX ==========

    public function getVariations()
    {
        $ptId = $this->request->getPost('product_type_id');
        if (!$ptId) return $this->response->setJSON(['variations' => [], 'variation_groups' => []]);

        $pt = $this->db->query('SELECT variations FROM product_type WHERE id = ?', [$ptId])->getRowArray();
        if (!$pt || empty($pt['variations'])) return $this->response->setJSON(['variations' => [], 'variation_groups' => []]);

        $vids = array_map('trim', explode(',', $pt['variations']));
        $ph = implode(',', array_fill(0, count($vids), '?'));
        $variations = $this->db->query("SELECT * FROM variation WHERE id IN ($ph) ORDER BY group_name, size", $vids)->getResultArray();
        $vgRows = $this->db->query("SELECT DISTINCT group_name FROM variation WHERE id IN ($ph) ORDER BY group_name", $vids)->getResultArray();

        return $this->response->setJSON(['variations' => $variations, 'variation_groups' => array_column($vgRows, 'group_name')]);
    }

    // ========== PATTERNS ==========

    public function addPattern($productId)
    {
        $data = $this->request->getPost();

        // Resolve or create pattern_name entry
        $patternNameId = null;
        if (!empty($data['pattern_name_id'])) {
            $patternNameId = $data['pattern_name_id'];
        } elseif (!empty($data['new_pattern_name'])) {
            $existing = $this->db->query('SELECT id FROM pattern_name WHERE name = ?', [trim($data['new_pattern_name'])])->getRowArray();
            if ($existing) {
                $patternNameId = $existing['id'];
            } else {
                $this->db->table('pattern_name')->insert(['name' => trim($data['new_pattern_name']), 'tamil_name' => $data['new_pattern_tamil'] ?? '']);
                $patternNameId = $this->db->insertID();
            }
        }

        $displayName = '';
        if ($patternNameId) {
            $pn = $this->db->query('SELECT name FROM pattern_name WHERE id = ?', [$patternNameId])->getRowArray();
            $displayName = $pn['name'] ?? '';
        }

        $patShortName = trim($data['short_name'] ?? '');
        $patternData = [
            'product_id' => $productId,
            'pattern_name_id' => $patternNameId,
            'name' => $displayName,
            'short_name' => $patShortName !== '' ? $patShortName : null,
            'tamil_name' => $data['new_pattern_tamil'] ?? '',
            'is_default' => 0,
        ];
        $patImage = $this->request->getFile('pattern_image');
        if ($patImage && $patImage->isValid() && !$patImage->hasMoved()) {
            $newName = $patImage->getRandomName();
            $uploadPath = FCPATH . 'uploads/patterns';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
            $patImage->move($uploadPath, $newName);
            $patternData['image'] = $newName;
        }
        $this->db->table('product_pattern')->insert($patternData);
        $patternId = $this->db->insertID();

        $importTemplateIds = array_filter(array_map('intval', (array)($this->request->getPost('import_template_id') ?? [])));
        foreach ($importTemplateIds as $importTemplateId) {
            $items = $this->db->query(
                'SELECT * FROM bom_template_item WHERE template_id = ? AND type = "bom"',
                [$importTemplateId]
            )->getResultArray();
            foreach ($items as $item) {
                $this->db->table('product_pattern_bom_change')->insert([
                    'product_pattern_id' => $patternId,
                    'action' => 'add',
                    'part_id' => $item['part_id'],
                    'part_pcs' => $item['part_pcs'],
                    'scale' => $item['scale'],
                    'variation_group' => $item['variation_group'] ?? '',
                    'podi_id' => $item['podi_id'] ?? null,
                    'podi_pcs' => $item['podi_pcs'] ?? 0,
                    'replace_part_id' => null,
                ]);
            }
        }

        return redirect()->to('products/view/' . $productId)->with('success', 'Pattern "' . $displayName . '" added' . (!empty($importTemplateIds) ? ' with ' . count($importTemplateIds) . ' template(s)' : ''));
    }

    public function deletePattern($patternId)
    {
        $pat = $this->db->query('SELECT * FROM product_pattern WHERE id = ?', [$patternId])->getRowArray();
        if (!$pat || $pat['is_default']) return redirect()->back()->with('error', 'Cannot delete default pattern');
        $this->db->query('DELETE FROM product_pattern_bom_change WHERE product_pattern_id = ?', [$patternId]);
        $this->db->query('DELETE FROM product_pattern WHERE id = ?', [$patternId]);
        return redirect()->to('products/view/' . $pat['product_id'])->with('success', 'Pattern deleted');
    }

    public function updatePattern($patternId)
    {
        $pat = $this->db->query('SELECT * FROM product_pattern WHERE id = ?', [$patternId])->getRowArray();
        if (!$pat) return redirect()->back()->with('error', 'Pattern not found');

        $shortName = trim($this->request->getPost('short_name') ?? '');
        $update = [
            'name'       => trim($this->request->getPost('name') ?? $pat['name']),
            'short_name' => $shortName !== '' ? $shortName : null,
            'tamil_name' => trim($this->request->getPost('tamil_name') ?? ''),
        ];

        if ($this->request->getPost('remove_image')) {
            if (!empty($pat['image'])) {
                $old = FCPATH . 'uploads/patterns/' . $pat['image'];
                if (file_exists($old)) @unlink($old);
            }
            $update['image'] = null;
        } else {
            $file = $this->request->getFile('pattern_image');
            if ($file && $file->isValid() && !$file->hasMoved()) {
                if (!empty($pat['image'])) {
                    $old = FCPATH . 'uploads/patterns/' . $pat['image'];
                    if (file_exists($old)) @unlink($old);
                }
                $newName = $file->getRandomName();
                $uploadPath = FCPATH . 'uploads/patterns';
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
                $file->move($uploadPath, $newName);
                $update['image'] = $newName;
            }
        }

        $this->db->table('product_pattern')->where('id', $patternId)->update($update);
        return redirect()->to('products/view/' . $pat['product_id'])->with('success', 'Pattern updated');
    }

    public function importTemplateToPattern($patternId)
    {
        $pat = $this->db->query('SELECT * FROM product_pattern WHERE id = ?', [$patternId])->getRowArray();
        if (!$pat) return redirect()->back()->with('error', 'Pattern not found');

        $templateIds = $this->request->getPost('template_id');
        if (empty($templateIds)) return redirect()->to('products/view/' . $pat['product_id'])->with('error', 'No template selected');
        if (!is_array($templateIds)) $templateIds = [$templateIds];

        $product = $this->db->query('SELECT product_type_id FROM product WHERE id = ?', [$pat['product_id']])->getRowArray();
        $totalCount = 0;
        $importedNames = [];

        foreach ($templateIds as $templateId) {
            $tmpl = $this->db->query('SELECT * FROM bom_template WHERE id = ?', [$templateId])->getRowArray();
            if (!$tmpl) continue;
            if (!empty($tmpl['product_type_id']) && $tmpl['product_type_id'] != $product['product_type_id']) continue;

            $items = $this->db->query('SELECT * FROM bom_template_item WHERE template_id = ? AND type = "bom"', [$templateId])->getResultArray();
            foreach ($items as $item) {
                $this->db->table('product_pattern_bom_change')->insert([
                    'product_pattern_id' => $patternId,
                    'action' => 'add',
                    'part_id' => $item['part_id'],
                    'part_pcs' => $item['part_pcs'],
                    'scale' => $item['scale'],
                    'variation_group' => $item['variation_group'],
                    'podi_id' => $item['podi_id'],
                    'podi_pcs' => $item['podi_pcs'],
                    'replace_part_id' => null,
                ]);
                $totalCount++;
            }
            $importedNames[] = $tmpl['name'];
        }

        return redirect()->to('products/view/' . $pat['product_id'])->with('success', $totalCount . ' items imported from template(s): ' . implode(', ', $importedNames));
    }

    public function savePatternChanges($patternId)
    {
        $data = $this->request->getPost();
        $pat = $this->db->query('SELECT * FROM product_pattern WHERE id = ?', [$patternId])->getRowArray();
        if (!$pat) return redirect()->back()->with('error', 'Pattern not found');

        $this->db->query('DELETE FROM product_pattern_bom_change WHERE product_pattern_id = ?', [$patternId]);

        if (!empty($data['change_action'])) {
            foreach ($data['change_action'] as $i => $action) {
                if (empty($data['change_part_id'][$i])) continue;
                $vg = '';
                if (isset($data['change_variation_group'][$i]) && is_array($data['change_variation_group'][$i])) {
                    $vg = implode(',', $data['change_variation_group'][$i]);
                }
                $this->db->table('product_pattern_bom_change')->insert([
                    'product_pattern_id' => $patternId,
                    'action' => $action,
                    'part_id' => $data['change_part_id'][$i],
                    'part_pcs' => $data['change_part_pcs'][$i] ?? null,
                    'scale' => $data['change_scale'][$i] ?? null,
                    'variation_group' => $vg,
                    'podi_id' => $data['change_podi_id'][$i] ?: null,
                    'podi_pcs' => $data['change_podi_pcs'][$i] ?? null,
                    'replace_part_id' => $data['change_replace_part_id'][$i] ?: null,
                ]);
            }
        }

        return redirect()->to('products/view/' . $pat['product_id'])->with('success', 'Pattern changes saved');
    }
}
