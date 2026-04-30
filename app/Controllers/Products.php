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

        $bodies       = $this->db->query('SELECT id, name FROM body ORDER BY name')->getResultArray();
        $pidiRaw      = $this->db->query("SELECT DISTINCT pidi FROM product WHERE pidi IS NOT NULL AND pidi != '' ORDER BY pidi+0, pidi")->getResultArray();
        $mainParts    = $this->db->query('SELECT DISTINCT mp.id, mp.name FROM part mp JOIN product p ON p.main_part_id = mp.id ORDER BY mp.name')->getResultArray();

        return view('products/index', [
            'title'        => 'Products',
            'items'        => $items,
            'productTypes' => $productTypes,
            'bodies'       => $bodies,
            'pidiValues'   => array_column($pidiRaw, 'pidi'),
            'mainParts'    => $mainParts,
            'search'       => $search,
            'filterType'   => $filterType,
            'sortBy'       => $sortBy,
            'sortDir'      => strtolower($sortDir),
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
            'product_type_id' => !empty($data['product_type_id']) ? $data['product_type_id'] : null,
            'body_id' => !empty($data['body_id']) ? $data['body_id'] : null,
            'main_part_id' => !empty($data['main_part_id']) ? $data['main_part_id'] : null,
            'pidi' => $data['pidi'] ?? '',
            'created_by' => $this->currentUser(),
            'created_at' => date('Y-m-d H:i:s'),
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
            'created_by' => $this->currentUser(),
            'created_at' => date('Y-m-d H:i:s'),
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

        try {
            $sku = trim($data['sku'] ?? '');
            if ($sku !== '') {
                $dupe = $this->db->query('SELECT id FROM product WHERE sku = ? AND id != ?', [$sku, $id])->getRowArray();
                if ($dupe) return redirect()->back()->withInput()->with('error', 'SKU "' . $sku . '" already used by another product.');
            }

            $mainPartId = !empty($data['main_part_id']) ? $data['main_part_id'] : null;
            if ($mainPartId) {
                $partExists = $this->db->query('SELECT id FROM part WHERE id = ?', [$mainPartId])->getRowArray();
                if (!$partExists) $mainPartId = null;
            }

            $productData = [
                'name' => $data['name'] ?? '',
                'short_name' => trim($data['short_name'] ?? '') ?: null,
                'sku' => $data['sku'] ?? '',
                'tamil_name' => $data['tamil_name'] ?? '',
                'product_type_id' => !empty($data['product_type_id']) ? $data['product_type_id'] : null,
                'body_id' => !empty($data['body_id']) ? $data['body_id'] : null,
                'main_part_id' => $mainPartId,
                'pidi' => $data['pidi'] ?? '',
            ];

            // Handle image upload or removal
            $image = $this->request->getFile('product_image');
            if ($image && $image->isValid() && !$image->hasMoved()) {
                $newName = $image->getRandomName();
                $uploadPath = FCPATH . 'uploads/products';
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
                $image->move($uploadPath, $newName);
                $productData['image'] = $newName;
            } elseif (!empty($data['remove_image'])) {
                $productData['image'] = null;
            }

            // Fetch old SKU before updating
            $oldRow = $this->db->query('SELECT sku FROM product WHERE id = ?', [$id])->getRowArray();
            $oldSku = $oldRow ? ($oldRow['sku'] ?? '') : '';

            $this->db->table('product')->where('id', $id)->update($productData);
            $this->db->query('DELETE FROM product_bill_of_material WHERE product_id = ?', [$id]);
            $this->saveBom($id, $data);

            // Regenerate pattern codes if SKU changed to a non-empty value
            $newSku = trim($data['sku'] ?? '');
            if ($newSku !== '' && $newSku !== trim((string)$oldSku)) {
                $patterns = $this->db->query(
                    'SELECT id, is_default FROM product_pattern WHERE product_id = ? ORDER BY is_default DESC, id ASC',
                    [$id]
                )->getResultArray();
                $nonDefaultCount = 0;
                foreach ($patterns as $pat) {
                    if (!empty($pat['is_default'])) {
                        $newCode = $newSku . '-P00';
                    } else {
                        $nonDefaultCount++;
                        $newCode = $newSku . '-P' . str_pad($nonDefaultCount, 2, '0', STR_PAD_LEFT);
                    }
                    $this->db->table('product_pattern')->where('id', $pat['id'])->update(['pattern_code' => $newCode]);
                }
            }

            return redirect()->to('products/view/' . $id)->with('success', 'Product updated');
        } catch (\Exception $e) {
            log_message('error', 'Product update failed for ID ' . $id . ': ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Update failed: ' . $e->getMessage());
        }
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
                'podi_id' => !empty($data['bom_podi_id'][$i]) ? $data['bom_podi_id'][$i] : null,
                'podi_pcs' => $data['bom_podi_pcs'][$i] ?? null,
                'created_by' => $this->currentUser(),
                'created_at' => date('Y-m-d H:i:s'),
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
        $src = $this->db->query('SELECT * FROM product WHERE id = ?', [$id])->getRowArray();
        if (!$src) return redirect()->to('products')->with('error', 'Not found');

        // Validate FK fields exist on this server's DB
        $productTypeId = $src['product_type_id'];
        if ($productTypeId && !$this->db->query('SELECT id FROM product_type WHERE id = ?', [$productTypeId])->getRowArray()) $productTypeId = null;

        $bodyId = $src['body_id'];
        if ($bodyId && !$this->db->query('SELECT id FROM part WHERE id = ?', [$bodyId])->getRowArray()) $bodyId = null;

        $mainPartId = $src['main_part_id'];
        if ($mainPartId && !$this->db->query('SELECT id FROM part WHERE id = ?', [$mainPartId])->getRowArray()) $mainPartId = null;

        $this->db->transStart();

        // Insert new product
        $this->db->table('product')->insert([
            'name' => 'Copy of ' . $src['name'],
            'sku' => null,
            'tamil_name' => $src['tamil_name'],
            'product_type_id' => $productTypeId,
            'body_id' => $bodyId,
            'main_part_id' => $mainPartId,
            'pidi' => $src['pidi'],
            'created_by' => $this->currentUser(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $newId = $this->db->insertID();

        if (!$newId) {
            $this->db->transRollback();
            return redirect()->to('products')->with('error', 'Failed to duplicate product. Please try again.');
        }

        // Copy BOM
        $bomRows = $this->db->query('SELECT * FROM product_bill_of_material WHERE product_id = ?', [$id])->getResultArray();
        foreach ($bomRows as $row) {
            unset($row['id']);
            $row['product_id'] = $newId;
            $row['created_by'] = $this->currentUser();
            $row['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('product_bill_of_material')->insert($row);
        }

        // Copy CBOM + quantities (the fix)
        $cbomRows = $this->db->query('SELECT * FROM product_customize_bill_of_material WHERE product_id = ?', [$id])->getResultArray();
        foreach ($cbomRows as $cbomRow) {
            $oldCbomId = $cbomRow['id'];
            unset($cbomRow['id']);
            $cbomRow['product_id'] = $newId;
            $cbomRow['created_by'] = $this->currentUser();
            $cbomRow['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('product_customize_bill_of_material')->insert($cbomRow);
            $newCbomId = $this->db->insertID();

            // Copy all quantity rows for this CBOM item
            $qtyRows = $this->db->query('SELECT * FROM product_customize_bill_of_material_quantity WHERE product_customize_bill_of_material_id = ?', [$oldCbomId])->getResultArray();
            foreach ($qtyRows as $qty) {
                unset($qty['id']);
                $qty['product_customize_bill_of_material_id'] = $newCbomId;
                $qty['created_by'] = $this->currentUser();
                $qty['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('product_customize_bill_of_material_quantity')->insert($qty);
            }
        }

        // Copy patterns + changes
        $patterns = $this->db->query('SELECT * FROM product_pattern WHERE product_id = ?', [$id])->getResultArray();
        $nonDefaultCount = 0;
        foreach ($patterns as $pat) {
            $oldPatId = $pat['id'];
            unset($pat['id']);
            $pat['product_id'] = $newId;
            // Regenerate pattern_code to avoid unique constraint violation
            $srcSku = trim((string)($src['sku'] ?? ''));
            $codePrefix = $srcSku !== '' ? $srcSku . '-COPY' : 'COPY-' . $newId;
            if (!empty($pat['is_default'])) {
                $pat['pattern_code'] = $codePrefix . '-P00';
            } else {
                $nonDefaultCount++;
                $pat['pattern_code'] = $codePrefix . '-P' . str_pad($nonDefaultCount, 2, '0', STR_PAD_LEFT);
            }
            $pat['created_by'] = $this->currentUser();
            $pat['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('product_pattern')->insert($pat);
            $newPatId = $this->db->insertID();

            $changes = $this->db->query('SELECT * FROM product_pattern_bom_change WHERE product_pattern_id = ?', [$oldPatId])->getResultArray();
            foreach ($changes as $ch) {
                unset($ch['id']);
                $ch['product_pattern_id'] = $newPatId;
                $ch['created_by'] = $this->currentUser();
                $ch['created_at'] = date('Y-m-d H:i:s');
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
                    'created_by' => $this->currentUser(),
                    'created_at' => date('Y-m-d H:i:s'),
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
                            'created_by' => $this->currentUser(),
                            'created_at' => date('Y-m-d H:i:s'),
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
                'created_by' => $this->currentUser(),
                'created_at' => date('Y-m-d H:i:s'),
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
                'created_by' => $this->currentUser(),
                'created_at' => date('Y-m-d H:i:s'),
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
                        'created_by' => $this->currentUser(),
                        'created_at' => date('Y-m-d H:i:s'),
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
                $this->db->table('pattern_name')->insert(['name' => trim($data['new_pattern_name']), 'tamil_name' => $data['new_pattern_tamil'] ?? '', 'created_by' => $this->currentUser(), 'created_at' => date('Y-m-d H:i:s')]);
                $patternNameId = $this->db->insertID();
            }
        }

        $displayName = '';
        if ($patternNameId) {
            $pn = $this->db->query('SELECT name FROM pattern_name WHERE id = ?', [$patternNameId])->getRowArray();
            $displayName = $pn['name'] ?? '';
        }

        $patShortName = trim($data['short_name'] ?? '');

        // Auto-generate pattern_code
        $productSku = $this->db->query('SELECT sku FROM product WHERE id = ?', [$productId])->getRowArray()['sku'] ?? '';
        $isDefault  = !empty($data['is_default']);
        if ($isDefault) {
            $patternCode = $productSku . '-P00';
        } else {
            $existingCount = $this->db->query(
                'SELECT COUNT(*) as cnt FROM product_pattern WHERE product_id = ? AND is_default = 0',
                [$productId]
            )->getRowArray()['cnt'];
            $patternCode = $productSku . '-P' . str_pad($existingCount + 1, 2, '0', STR_PAD_LEFT);
        }

        $patternData = [
            'product_id' => $productId,
            'pattern_name_id' => $patternNameId,
            'pattern_code' => $patternCode,
            'name' => $displayName,
            'short_name' => $patShortName !== '' ? $patShortName : null,
            'tamil_name' => $data['new_pattern_tamil'] ?? '',
            'is_default' => 0,
            'created_by' => $this->currentUser(),
            'created_at' => date('Y-m-d H:i:s'),
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
                    'created_by' => $this->currentUser(),
                    'created_at' => date('Y-m-d H:i:s'),
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
                    'created_by' => $this->currentUser(),
                    'created_at' => date('Y-m-d H:i:s'),
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
                    'created_by' => $this->currentUser(),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return redirect()->to('products/view/' . $pat['product_id'])->with('success', 'Pattern changes saved');
    }
    // =========================================================
    // BULK TEXT UPDATE
    // =========================================================

    public function bulkEdit()
    {
        $productTypes = $this->db->query('SELECT * FROM product_type ORDER BY name')->getResultArray();
        $bodies       = $this->db->query('SELECT id, name FROM body ORDER BY name')->getResultArray();
        $pidiRaw      = $this->db->query("SELECT DISTINCT pidi FROM product WHERE pidi IS NOT NULL AND pidi != '' ORDER BY pidi+0, pidi")->getResultArray();
        $mainParts    = $this->db->query('SELECT DISTINCT mp.id, mp.name FROM part mp JOIN product p ON p.main_part_id = mp.id ORDER BY mp.name')->getResultArray();

        return view('products/bulk_edit', [
            'title'        => 'Bulk Update Products',
            'productTypes' => $productTypes,
            'bodies'       => $bodies,
            'pidiValues'   => array_column($pidiRaw, 'pidi'),
            'mainParts'    => $mainParts,
        ]);
    }

    public function bulkExportCsv()
    {
        $filterType = $this->request->getGet('type') ?? '';
        $filterBody = $this->request->getGet('body') ?? '';
        $filterPidi = $this->request->getGet('pidi') ?? '';
        $filterMain = $this->request->getGet('main') ?? '';

        $where  = [];
        $params = [];
        if ($filterType !== '') { $where[] = 'p.product_type_id = ?'; $params[] = $filterType; }
        if ($filterBody !== '') { $where[] = 'p.body_id = ?';          $params[] = $filterBody; }
        if ($filterPidi !== '') { $where[] = 'p.pidi = ?';             $params[] = $filterPidi; }
        if ($filterMain !== '') { $where[] = 'p.main_part_id = ?';     $params[] = $filterMain; }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $rows = $this->db->query("
            SELECT p.id as product_id, p.sku as product_sku, p.name as product_name,
                   p.tamil_name as product_tamil_name, p.short_name as product_short_name,
                   pp.id as pattern_id, COALESCE(pn.name,'Default') as pattern_name,
                   pp.tamil_name as pattern_tamil_name, pp.short_name as pattern_short_name
            FROM product p
            JOIN product_pattern pp ON pp.product_id = p.id
            LEFT JOIN pattern_name pn ON pn.id = pp.pattern_name_id
            $whereClause
            ORDER BY p.name, pp.is_default DESC, pp.id
        ", $params)->getResultArray();

        $parts = array_filter([$filterType ? 'type'.$filterType : '', $filterBody ? 'body'.$filterBody : '',
                               $filterPidi ? 'pidi'.str_replace('.','_',$filterPidi) : '', $filterMain ? 'part'.$filterMain : '']);
        $filename = 'products_bulk' . ($parts ? '_' . implode('_', $parts) : '') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['product_id','product_sku','product_name','product_tamil_name','product_short_name',
                       'pattern_id','pattern_name','pattern_tamil_name','pattern_short_name']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['product_id'], $r['product_sku'], $r['product_name'],
                $r['product_tamil_name'], $r['product_short_name'],
                $r['pattern_id'], $r['pattern_name'],
                $r['pattern_tamil_name'], $r['pattern_short_name'],
            ]);
        }
        fclose($out);
        exit;
    }

    public function bulkPreview()
    {
      try {
        $file = $this->request->getFile('csv_file');
        if (!$file || !$file->isValid()) {
            return redirect()->to('products/bulkEdit')->with('error', 'Please upload a valid file.');
        }
        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['csv', 'xlsx'])) {
            return redirect()->to('products/bulkEdit')->with('error', 'Only CSV and XLSX files are supported.');
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return redirect()->to('products/bulkEdit')->with('error', 'File too large (max 5 MB).');
        }

        $tmpPath = $file->getTempName();
        $allRows = [];

        if ($ext === 'xlsx') {
            // Use PhpSpreadsheet for XLSX
            if (file_exists(ROOTPATH . 'vendor/autoload.php')) {
                require_once ROOTPATH . 'vendor/autoload.php';
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                $spreadsheet = $reader->load($tmpPath);
                $sheet = $spreadsheet->getActiveSheet();
                $allRows = $sheet->toArray(null, true, true, false);
            } else {
                return redirect()->to('products/bulkEdit')->with('error', 'XLSX support not available. Please upload a CSV file.');
            }
        } else {
            // Native CSV parsing
            $handle = fopen($tmpPath, 'r');
            if (!$handle) {
                return redirect()->to('products/bulkEdit')->with('error', 'Failed to read uploaded file.');
            }
            // Skip BOM if present
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);

            while (($row = fgetcsv($handle)) !== false) {
                $allRows[] = $row;
            }
            fclose($handle);
        }

        if (empty($allRows)) {
            return redirect()->to('products/bulkEdit')->with('error', 'The uploaded file is empty.');
        }

        // Normalize header: trim whitespace and lowercase for flexible matching
        $header = array_map(function($v) { return strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string)$v))); }, $allRows[0]);
        $expected = ['product_id','product_sku','product_name','product_tamil_name','product_short_name',
                     'pattern_id','pattern_name','pattern_tamil_name','pattern_short_name'];
        if ($header !== $expected) {
            return redirect()->to('products/bulkEdit')->with('error', 'Columns do not match the template. Expected: ' . implode(', ', $expected) . '. Got: ' . implode(', ', array_slice($header, 0, 9)));
        }

        $products   = array_column($this->db->query('SELECT id, sku, name, tamil_name, short_name FROM product')->getResultArray(), null, 'id');
        $patternRows = $this->db->query("
            SELECT pp.id, pp.product_id, COALESCE(pn.name,'Default') as pattern_name,
                   pp.tamil_name, pp.short_name
            FROM product_pattern pp
            LEFT JOIN pattern_name pn ON pn.id = pp.pattern_name_id
        ")->getResultArray();
        $patternMap = array_column($patternRows, null, 'id');

        $changes = [];
        $errors  = [];

        $rowCount = count($allRows);
        for ($rowNum = 1; $rowNum < $rowCount; $rowNum++) {
            $row = $allRows[$rowNum];
            $displayRow = $rowNum + 1;
            if (count($row) < 9) { $errors[] = "Row $displayRow: too few columns."; continue; }
            $pid  = (int)$row[0];
            $ptid = (int)$row[5];
            if (!isset($products[$pid]))    { $errors[] = "Row $displayRow: product_id $pid not found.";  continue; }
            if (!isset($patternMap[$ptid])) { $errors[] = "Row $displayRow: pattern_id $ptid not found."; continue; }
            if ($patternMap[$ptid]['product_id'] != $pid) { $errors[] = "Row $displayRow: pattern $ptid does not belong to product $pid."; continue; }

            $cur    = $products[$pid];
            $curPat = $patternMap[$ptid];

            $newSku      = trim((string)($row[1] ?? ''));
            $newName     = trim((string)($row[2] ?? ''));
            $newTamil    = trim((string)($row[3] ?? ''));
            $newShort    = trim((string)($row[4] ?? ''));
            $newPatName  = trim((string)($row[6] ?? ''));
            $newPatTamil = trim((string)($row[7] ?? ''));
            $newPatShort = trim((string)($row[8] ?? ''));

            $dbSku      = trim((string)($cur['sku']               ?? ''));
            $dbName     = trim((string)($cur['name']              ?? ''));
            $dbTamil    = trim((string)($cur['tamil_name']        ?? ''));
            $dbShort    = trim((string)($cur['short_name']        ?? ''));
            $dbPatName  = trim((string)($curPat['pattern_name']   ?? ''));
            $dbPatTamil = trim((string)($curPat['tamil_name']     ?? ''));
            $dbPatShort = trim((string)($curPat['short_name']     ?? ''));

            $prodChanged = ($newSku   !== $dbSku)   ||
                           ($newName  !== $dbName)  ||
                           ($newTamil !== $dbTamil) ||
                           ($newShort !== $dbShort);
            $patChanged  = ($newPatName  !== $dbPatName)  ||
                           ($newPatTamil !== $dbPatTamil) ||
                           ($newPatShort !== $dbPatShort);

            if (!$prodChanged && !$patChanged) continue;

            $changes[] = [
                'product_id'    => $pid,
                'old_sku'       => $cur['sku']           ?? '',
                'old_name'      => $cur['name']          ?? '',
                'old_tamil'     => $cur['tamil_name']    ?? '',
                'old_short'     => $cur['short_name']    ?? '',
                'new_sku'       => $newSku,
                'new_name'      => $newName,
                'new_tamil'     => $newTamil,
                'new_short'     => $newShort,
                'prod_changed'  => $prodChanged,
                'pattern_id'    => $ptid,
                'old_pat_name'  => $curPat['pattern_name'] ?? '',
                'old_pat_tamil' => $curPat['tamil_name']   ?? '',
                'old_pat_short' => $curPat['short_name']   ?? '',
                'new_pat_name'  => $newPatName,
                'new_pat_tamil' => $newPatTamil,
                'new_pat_short' => $newPatShort,
                'pat_changed'   => $patChanged,
            ];
        }

        if (!empty($errors)) {
            return redirect()->to('products/bulkEdit')->with('error', implode('<br>', array_slice($errors, 0, 10)));
        }
        if (empty($changes)) {
            return redirect()->to('products/bulkEdit')->with('info', 'No changes detected in the uploaded CSV.');
        }

        session()->set('bulk_changes', $changes);
        return view('products/bulk_preview', ['title' => 'Preview Changes', 'changes' => $changes]);
      } catch (\Throwable $e) {
        return redirect()->to('products/bulkEdit')->with('error', 'Error processing file: ' . $e->getMessage());
      }
    }

    public function bulkConfirm()
    {
        $changes = session()->get('bulk_changes');
        if (!$changes) {
            return redirect()->to('products/bulkEdit')->with('error', 'Session expired. Please re-upload the CSV.');
        }
        session()->remove('bulk_changes');

        $updatedProducts = [];
        $updatedPatterns = 0;

        foreach ($changes as $ch) {
            if ($ch['prod_changed'] && !in_array($ch['product_id'], $updatedProducts)) {
                $oldSku = trim((string)($this->db->query('SELECT sku FROM product WHERE id = ?', [$ch['product_id']])->getRowArray()['sku'] ?? ''));
                $this->db->table('product')->where('id', $ch['product_id'])->update([
                    'sku'        => $ch['new_sku']   ?: null,
                    'name'       => $ch['new_name'],
                    'tamil_name' => $ch['new_tamil'],
                    'short_name' => $ch['new_short'] ?: null,
                ]);
                $updatedProducts[] = $ch['product_id'];

                // Regenerate pattern codes if SKU changed to a non-empty value
                $newSku = trim((string)$ch['new_sku']);
                if ($newSku !== '' && $newSku !== $oldSku) {
                    $patterns = $this->db->query(
                        'SELECT id, is_default FROM product_pattern WHERE product_id = ? ORDER BY is_default DESC, id ASC',
                        [$ch['product_id']]
                    )->getResultArray();
                    $nonDefaultCount = 0;
                    foreach ($patterns as $pat) {
                        if (!empty($pat['is_default'])) {
                            $newCode = $newSku . '-P00';
                        } else {
                            $nonDefaultCount++;
                            $newCode = $newSku . '-P' . str_pad($nonDefaultCount, 2, '0', STR_PAD_LEFT);
                        }
                        $this->db->table('product_pattern')->where('id', $pat['id'])->update(['pattern_code' => $newCode]);
                    }
                }
            }
            if ($ch['pat_changed']) {
                $patternNameId = null;
                if ($ch['new_pat_name'] !== '' && $ch['new_pat_name'] !== 'Default') {
                    $existing = $this->db->query("SELECT id FROM pattern_name WHERE name = ?", [$ch['new_pat_name']])->getRowArray();
                    if ($existing) {
                        $patternNameId = $existing['id'];
                    } else {
                        $this->db->table('pattern_name')->insert(['name' => $ch['new_pat_name'], 'tamil_name' => $ch['new_pat_tamil'], 'created_by' => $this->currentUser(), 'created_at' => date('Y-m-d H:i:s')]);
                        $patternNameId = $this->db->insertID();
                    }
                }
                $this->db->table('product_pattern')->where('id', $ch['pattern_id'])->update([
                    'pattern_name_id' => $patternNameId,
                    'tamil_name'      => $ch['new_pat_tamil'],
                    'short_name'      => $ch['new_pat_short'] ?: null,
                ]);
                $updatedPatterns++;
            }
        }

        $msg = count($updatedProducts) . ' products and ' . $updatedPatterns . ' patterns updated successfully.';
        return redirect()->to('products')->with('success', $msg);
    }

    // =========================================================
    // IMAGE GALLERY
    // =========================================================

    public function imageGallery()
    {
        $products = $this->db->query("
            SELECT p.id, p.sku, p.name, p.image, p.product_type_id,
                   pt.name as product_type_name
            FROM product p
            LEFT JOIN product_type pt ON pt.id = p.product_type_id
            ORDER BY p.name
        ")->getResultArray();

        $patterns = $this->db->query("
            SELECT pp.id, pp.product_id, pp.image, pp.is_default,
                   COALESCE(pn.name,'Default') as pattern_name
            FROM product_pattern pp
            LEFT JOIN pattern_name pn ON pn.id = pp.pattern_name_id
            ORDER BY pp.product_id, pp.is_default DESC, pp.id
        ")->getResultArray();

        $patternsByProduct = [];
        foreach ($patterns as $p) {
            $patternsByProduct[$p['product_id']][] = $p;
        }

        $productTypes = $this->db->query('SELECT id, name FROM product_type ORDER BY name')->getResultArray();

        return view('products/image_gallery', [
            'title'             => 'Image Gallery',
            'products'          => $products,
            'patternsByProduct' => $patternsByProduct,
            'productTypes'      => $productTypes,
        ]);
    }

    public function ajaxUploadProductImage($productId)
    {
        $productId = (int)$productId;
        $file = $this->request->getFile('product_image');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'error' => 'No valid file received.']);
        }
        if (!in_array($file->getMimeType(), ['image/jpeg','image/png','image/gif','image/webp'])) {
            return $this->response->setJSON(['success' => false, 'error' => 'Only image files allowed.']);
        }
        if ($file->getSize() > 2 * 1024 * 1024) {
            return $this->response->setJSON(['success' => false, 'error' => 'File too large (max 2 MB).']);
        }
        $uploadPath = FCPATH . 'uploads/products';
        $row = $this->db->query('SELECT image FROM product WHERE id = ?', [$productId])->getRowArray();
        if (!$row) return $this->response->setJSON(['success' => false, 'error' => 'Product not found.']);
        if (!empty($row['image']) && file_exists($uploadPath . '/' . $row['image'])) @unlink($uploadPath . '/' . $row['image']);
        $newName = $file->getRandomName();
        $file->move($uploadPath, $newName);
        $this->db->table('product')->where('id', $productId)->update(['image' => $newName]);
        return $this->response->setJSON(['success' => true, 'url' => upload_url('products/' . $newName)]);
    }

    public function ajaxUploadPatternImage($patternId)
    {
        $patternId = (int)$patternId;
        $file = $this->request->getFile('pattern_image');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'error' => 'No valid file received.']);
        }
        if (!in_array($file->getMimeType(), ['image/jpeg','image/png','image/gif','image/webp'])) {
            return $this->response->setJSON(['success' => false, 'error' => 'Only image files allowed.']);
        }
        if ($file->getSize() > 2 * 1024 * 1024) {
            return $this->response->setJSON(['success' => false, 'error' => 'File too large (max 2 MB).']);
        }
        $uploadPath = FCPATH . 'uploads/patterns';
        $row = $this->db->query('SELECT image FROM product_pattern WHERE id = ?', [$patternId])->getRowArray();
        if (!$row) return $this->response->setJSON(['success' => false, 'error' => 'Pattern not found.']);
        if (!empty($row['image']) && file_exists($uploadPath . '/' . $row['image'])) @unlink($uploadPath . '/' . $row['image']);
        $newName = $file->getRandomName();
        $file->move($uploadPath, $newName);
        $this->db->table('product_pattern')->where('id', $patternId)->update(['image' => $newName]);
        return $this->response->setJSON(['success' => true, 'url' => upload_url('patterns/' . $newName)]);
    }

    /**
     * Clean up broken image references in the database.
     * Checks if image files actually exist on disk; sets to NULL if missing.
     */
    public function cleanBrokenImages()
    {
        $cleaned = [];

        // Check product images
        $products = $this->db->query('SELECT id, name, image FROM product WHERE image IS NOT NULL AND image != ""')->getResultArray();
        foreach ($products as $p) {
            $path = FCPATH . 'uploads/products/' . $p['image'];
            if (!file_exists($path)) {
                $this->db->table('product')->where('id', $p['id'])->update(['image' => null]);
                $cleaned[] = 'Product #' . $p['id'] . ' (' . $p['name'] . ') — ' . $p['image'];
            }
        }

        // Check pattern images
        $patterns = $this->db->query('SELECT pp.id, pp.name, pp.image, p.name as product_name FROM product_pattern pp LEFT JOIN product p ON pp.product_id = p.id WHERE pp.image IS NOT NULL AND pp.image != ""')->getResultArray();
        foreach ($patterns as $pat) {
            $path = FCPATH . 'uploads/patterns/' . $pat['image'];
            if (!file_exists($path)) {
                $this->db->table('product_pattern')->where('id', $pat['id'])->update(['image' => null]);
                $cleaned[] = 'Pattern #' . $pat['id'] . ' (' . ($pat['product_name'] ?? '') . ' / ' . $pat['name'] . ') — ' . $pat['image'];
            }
        }

        if (empty($cleaned)) {
            return $this->response->setJSON(['message' => 'No broken image references found.', 'cleaned' => 0]);
        }

        return $this->response->setJSON([
            'message' => count($cleaned) . ' broken image reference(s) cleaned.',
            'cleaned' => count($cleaned),
            'details' => $cleaned,
        ]);
    }

}
