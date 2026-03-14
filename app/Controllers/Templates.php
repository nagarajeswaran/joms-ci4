<?php

namespace App\Controllers;

class Templates extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    private function getFormData(int $productTypeId = null): array
    {
        $variations = [];
        $variationGroups = [];

        if ($productTypeId) {
            $pt = $this->db->query('SELECT variations FROM product_type WHERE id = ?', [$productTypeId])->getRowArray();
            if ($pt && !empty($pt['variations'])) {
                $vids = array_filter(array_map('trim', explode(',', $pt['variations'])));
                $ph = implode(',', array_fill(0, count($vids), '?'));
                $variations = $this->db->query("SELECT * FROM variation WHERE id IN ($ph) ORDER BY group_name, size", $vids)->getResultArray();
                $variationGroups = array_column(
                    $this->db->query("SELECT DISTINCT group_name FROM variation WHERE id IN ($ph) ORDER BY group_name", $vids)->getResultArray(),
                    'group_name'
                );
            }
        } else {
            $variations = $this->db->query('SELECT * FROM variation ORDER BY group_name, size')->getResultArray();
            $variationGroups = array_column(
                $this->db->query('SELECT DISTINCT group_name FROM variation ORDER BY group_name')->getResultArray(),
                'group_name'
            );
        }

        return [
            'parts' => $this->db->query('SELECT * FROM part ORDER BY name')->getResultArray(),
            'podies' => $this->db->query('SELECT * FROM podi ORDER BY name')->getResultArray(),
            'productTypes' => $this->db->query('SELECT * FROM product_type ORDER BY name')->getResultArray(),
            'variations' => $variations,
            'variationGroups' => $variationGroups,
        ];
    }

    public function index()
    {
        $items = $this->db->query('
            SELECT t.*, pt.name as product_type_name,
                   SUM(CASE WHEN ti.type="bom"  THEN 1 ELSE 0 END) as bom_count,
                   SUM(CASE WHEN ti.type="cbom" THEN 1 ELSE 0 END) as cbom_count
            FROM bom_template t
            LEFT JOIN product_type pt ON t.product_type_id = pt.id
            LEFT JOIN bom_template_item ti ON ti.template_id = t.id
            GROUP BY t.id
            ORDER BY (t.product_type_id IS NULL), pt.name, t.name
        ')->getResultArray();

        return view('templates/index', ['title' => 'BOM Templates', 'items' => $items]);
    }

    public function create()
    {
        return view('templates/form', array_merge(['title' => 'Create BOM Template'], $this->getFormData()));
    }

    public function store()
    {
        $data = $this->request->getPost();

        $this->db->table('bom_template')->insert([
            'name' => $data['name'],
            'tamil_name' => $data['tamil_name'] ?? '',
            'description' => $data['description'] ?? '',
            'product_type_id' => !empty($data['product_type_id']) ? (int)$data['product_type_id'] : null,
        ]);
        $templateId = $this->db->insertID();

        $this->saveItems($templateId, $data);

        return redirect()->to('templates/view/' . $templateId)->with('success', 'Template created');
    }

    public function view($id)
    {
        $template = $this->db->query('
            SELECT t.*, pt.name as product_type_name
            FROM bom_template t
            LEFT JOIN product_type pt ON t.product_type_id = pt.id
            WHERE t.id = ?
        ', [$id])->getRowArray();
        if (!$template) return redirect()->to('templates')->with('error', 'Not found');

        $items = $this->db->query('
            SELECT ti.*, pa.name as part_name, po.name as podi_name
            FROM bom_template_item ti
            LEFT JOIN part pa ON ti.part_id = pa.id
            LEFT JOIN podi po ON ti.podi_id = po.id
            WHERE ti.template_id = ?
            ORDER BY ti.type, ti.id
        ', [$id])->getResultArray();

        foreach ($items as &$item) {
            if ($item['type'] === 'cbom') {
                $item['quantities'] = $this->db->query('
                    SELECT q.*, v.name as variation_name, v.group_name, v.size
                    FROM bom_template_cbom_qty q
                    LEFT JOIN variation v ON q.variation_id = v.id
                    WHERE q.template_item_id = ?
                    ORDER BY v.group_name, v.size
                ', [$item['id']])->getResultArray();
            }
        }

        return view('templates/view', ['title' => $template['name'], 'template' => $template, 'items' => $items]);
    }

    public function edit($id)
    {
        $template = $this->db->query('SELECT * FROM bom_template WHERE id = ?', [$id])->getRowArray();
        if (!$template) return redirect()->to('templates')->with('error', 'Not found');

        $bomItems = $this->db->query('SELECT * FROM bom_template_item WHERE template_id = ? AND type = "bom"', [$id])->getResultArray();
        $cbomItems = $this->db->query('SELECT * FROM bom_template_item WHERE template_id = ? AND type = "cbom"', [$id])->getResultArray();
        foreach ($cbomItems as &$c) {
            $c['quantities'] = $this->db->query('SELECT * FROM bom_template_cbom_qty WHERE template_item_id = ?', [$c['id']])->getResultArray();
        }

        $ptId = !empty($template['product_type_id']) ? (int)$template['product_type_id'] : null;

        return view('templates/form', array_merge(
            ['title' => 'Edit Template: ' . $template['name'], 'template' => $template, 'bomItems' => $bomItems, 'cbomItems' => $cbomItems],
            $this->getFormData($ptId)
        ));
    }

    public function update($id)
    {
        $data = $this->request->getPost();
        $this->db->table('bom_template')->where('id', $id)->update([
            'name' => $data['name'],
            'tamil_name' => $data['tamil_name'] ?? '',
            'description' => $data['description'] ?? '',
            'product_type_id' => !empty($data['product_type_id']) ? (int)$data['product_type_id'] : null,
        ]);

        $oldItems = $this->db->query('SELECT id FROM bom_template_item WHERE template_id = ?', [$id])->getResultArray();
        foreach ($oldItems as $oi) {
            $this->db->query('DELETE FROM bom_template_cbom_qty WHERE template_item_id = ?', [$oi['id']]);
        }
        $this->db->query('DELETE FROM bom_template_item WHERE template_id = ?', [$id]);

        $this->saveItems($id, $data);

        return redirect()->to('templates/view/' . $id)->with('success', 'Template updated');
    }

    public function delete($id)
    {
        $items = $this->db->query('SELECT id FROM bom_template_item WHERE template_id = ?', [$id])->getResultArray();
        foreach ($items as $item) {
            $this->db->query('DELETE FROM bom_template_cbom_qty WHERE template_item_id = ?', [$item['id']]);
        }
        $this->db->query('DELETE FROM bom_template_item WHERE template_id = ?', [$id]);
        $this->db->query('DELETE FROM bom_template WHERE id = ?', [$id]);
        return redirect()->to('templates')->with('success', 'Template deleted');
    }

    private function saveItems($templateId, $data)
    {
        // Determine which variation IDs to use for CBOM quantities
        $tmpl = $this->db->query('SELECT product_type_id FROM bom_template WHERE id = ?', [$templateId])->getRowArray();
        $allVids = [];
        if (!empty($tmpl['product_type_id'])) {
            $pt = $this->db->query('SELECT variations FROM product_type WHERE id = ?', [$tmpl['product_type_id']])->getRowArray();
            if ($pt && !empty($pt['variations'])) {
                $allVids = array_filter(array_map('trim', explode(',', $pt['variations'])));
            }
        }
        if (empty($allVids)) {
            $allVids = array_column($this->db->query('SELECT id FROM variation')->getResultArray(), 'id');
        }

        // Save BOM items
        if (!empty($data['bom_part_id'])) {
            foreach ($data['bom_part_id'] as $i => $partId) {
                if (empty($partId)) continue;
                $vg = '';
                if (isset($data['bom_variation_group'][$i]) && is_array($data['bom_variation_group'][$i])) {
                    $vg = implode(',', $data['bom_variation_group'][$i]);
                }
                $this->db->table('bom_template_item')->insert([
                    'template_id' => $templateId,
                    'type' => 'bom',
                    'part_id' => $partId,
                    'part_pcs' => $data['bom_part_pcs'][$i] ?? null,
                    'scale' => $data['bom_scale'][$i] ?? null,
                    'variation_group' => $vg,
                    'podi_id' => $data['bom_podi_id'][$i] ?: null,
                    'podi_pcs' => $data['bom_podi_pcs'][$i] ?? null,
                ]);
            }
        }

        // Save CBOM items
        if (!empty($data['cbom_part_id'])) {
            foreach ($data['cbom_part_id'] as $i => $partId) {
                if (empty($partId)) continue;
                $this->db->table('bom_template_item')->insert([
                    'template_id' => $templateId,
                    'type' => 'cbom',
                    'part_id' => $partId,
                    'podi_id' => $data['cbom_podi_id'][$i] ?: null,
                ]);
                $itemId = $this->db->insertID();
                foreach ($allVids as $vid) {
                    $pqKey = 'cbom_qty_' . $vid . '_part';
                    $dqKey = 'cbom_qty_' . $vid . '_podi';
                    $pq = isset($data[$pqKey][$i]) ? $data[$pqKey][$i] : null;
                    $dq = isset($data[$dqKey][$i]) ? $data[$dqKey][$i] : null;
                    if ($pq !== null || $dq !== null) {
                        $this->db->table('bom_template_cbom_qty')->insert([
                            'template_item_id' => $itemId,
                            'variation_id' => $vid,
                            'part_quantity' => $pq ?: 0,
                            'podi_quantity' => $dq ?: 0,
                        ]);
                    }
                }
            }
        }
    }

    // AJAX: return variations scoped to a product type
    public function getVariationsByType()
    {
        $ptId = $this->request->getPost('product_type_id');
        if (!$ptId) {
            $variations = $this->db->query('SELECT * FROM variation ORDER BY group_name, size')->getResultArray();
            $groups = array_column($this->db->query('SELECT DISTINCT group_name FROM variation ORDER BY group_name')->getResultArray(), 'group_name');
            return $this->response->setJSON(['variations' => $variations, 'variation_groups' => $groups]);
        }

        $pt = $this->db->query('SELECT variations FROM product_type WHERE id = ?', [$ptId])->getRowArray();
        if (!$pt || empty($pt['variations'])) {
            return $this->response->setJSON(['variations' => [], 'variation_groups' => []]);
        }

        $vids = array_filter(array_map('trim', explode(',', $pt['variations'])));
        $ph = implode(',', array_fill(0, count($vids), '?'));
        return $this->response->setJSON([
            'variations' => $this->db->query("SELECT * FROM variation WHERE id IN ($ph) ORDER BY group_name, size", $vids)->getResultArray(),
            'variation_groups' => array_column(
                $this->db->query("SELECT DISTINCT group_name FROM variation WHERE id IN ($ph) ORDER BY group_name", $vids)->getResultArray(),
                'group_name'
            ),
        ]);
    }

    // AJAX: return template items as JSON for product import
    public function getItems($id)
    {
        $items = $this->db->query('
            SELECT ti.*, pa.name as part_name, po.name as podi_name
            FROM bom_template_item ti
            LEFT JOIN part pa ON ti.part_id = pa.id
            LEFT JOIN podi po ON ti.podi_id = po.id
            WHERE ti.template_id = ?
        ', [$id])->getResultArray();

        foreach ($items as &$item) {
            if ($item['type'] === 'cbom') {
                $item['quantities'] = $this->db->query('
                    SELECT q.*, v.name as variation_name, v.group_name, v.size
                    FROM bom_template_cbom_qty q
                    LEFT JOIN variation v ON q.variation_id = v.id
                    WHERE q.template_item_id = ?
                    ORDER BY v.group_name, v.size
                ', [$item['id']])->getResultArray();
            }
        }

        return $this->response->setJSON(['items' => $items]);
    }
}
