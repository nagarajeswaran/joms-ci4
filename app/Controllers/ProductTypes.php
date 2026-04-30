<?php

namespace App\Controllers;

class ProductTypes extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $items = $this->db->query('SELECT * FROM product_type ORDER BY name')->getResultArray();

        // Resolve variation names for each product type
        $allVariations = $this->db->query('SELECT id, name, group_name, size FROM variation')->getResultArray();
        $varMap = array_column($allVariations, null, 'id');

        foreach ($items as &$item) {
            $names = [];
            if (!empty($item['variations'])) {
                foreach (array_filter(array_map('trim', explode(',', $item['variations']))) as $vid) {
                    if (isset($varMap[$vid])) $names[] = $varMap[$vid]['name'];
                }
            }
            $item['variation_names'] = $names;
        }

        return view('product_types/index', ['title' => 'Product Types', 'items' => $items]);
    }

    public function create()
    {
        return view('product_types/form', [
            'title' => 'Add Product Type',
            'variationGroups' => $this->getGroupedVariations(),
        ]);
    }

    public function store()
    {
        $variations = $this->request->getPost('variations');
        $this->db->table('product_type')->insert([
            'name' => $this->request->getPost('name'),
            'tamil_name' => $this->request->getPost('tamil_name'),
            'variations' => is_array($variations) ? implode(',', $variations) : '',
            'multiplication_factor' => $this->request->getPost('multiplication_factor'),
            'created_by' => $this->currentUser(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('product-types')->with('success', 'Product Type added successfully');
    }

    public function edit($id)
    {
        $item = $this->db->query('SELECT * FROM product_type WHERE id = ?', [$id])->getRowArray();
        if (!$item) return redirect()->to('product-types')->with('error', 'Not found');

        return view('product_types/form', [
            'title' => 'Edit Product Type',
            'item' => $item,
            'variationGroups' => $this->getGroupedVariations(),
            'selectedVariations' => array_filter(array_map('trim', explode(',', $item['variations'] ?? ''))),
        ]);
    }

    public function update($id)
    {
        $variations = $this->request->getPost('variations');
        $this->db->table('product_type')->where('id', $id)->update([
            'name' => $this->request->getPost('name'),
            'tamil_name' => $this->request->getPost('tamil_name'),
            'variations' => is_array($variations) ? implode(',', $variations) : '',
            'multiplication_factor' => $this->request->getPost('multiplication_factor'),
        ]);
        return redirect()->to('product-types')->with('success', 'Product Type updated successfully');
    }

    public function delete($id)
    {
        $this->db->query('DELETE FROM product_type WHERE id = ?', [$id]);
        return redirect()->to('product-types')->with('success', 'Product Type deleted');
    }

    private function getGroupedVariations(): array
    {
        $rows = $this->db->query('SELECT * FROM variation ORDER BY group_name, size+0, size')->getResultArray();
        $groups = [];
        foreach ($rows as $v) {
            $groups[$v['group_name']][] = $v;
        }
        return $groups;
    }
}
