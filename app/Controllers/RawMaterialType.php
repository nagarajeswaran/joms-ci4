<?php
namespace App\Controllers;

class RawMaterialType extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $items = $db->query('SELECT * FROM raw_material_type ORDER BY name')->getResultArray();
        return view('raw_material_type/index', ['title' => 'Raw Material Types', 'items' => $items]);
    }

    public function create()
    {
        return view('raw_material_type/form', ['title' => 'Add Raw Material Type']);
    }

    public function store()
    {
        $db = \Config\Database::connect();
        $isDefaultAlloy = $this->request->getPost('is_default_alloy') ? 1 : 0;
        if ($isDefaultAlloy) {
            $db->table('raw_material_type')->update(['is_default_alloy' => 0]);
        }
        $db->table('raw_material_type')->insert([
            'name'              => $this->request->getPost('name'),
            'default_touch_pct' => $this->request->getPost('default_touch_pct') ?: 0,
            'material_group'    => $this->request->getPost('material_group') ?: 'other',
            'is_default_alloy'  => $isDefaultAlloy,
        ]);
        return redirect()->to('raw-material-types')->with('success', 'Added');
    }

    public function edit($id)
    {
        $db = \Config\Database::connect();
        $item = $db->query('SELECT * FROM raw_material_type WHERE id = ?', [$id])->getRowArray();
        if (!$item) return redirect()->to('raw-material-types')->with('error', 'Not found');
        return view('raw_material_type/form', ['title' => 'Edit Raw Material Type', 'item' => $item]);
    }

    public function update($id)
    {
        $db = \Config\Database::connect();
        $isDefaultAlloy = $this->request->getPost('is_default_alloy') ? 1 : 0;
        if ($isDefaultAlloy) {
            $db->table('raw_material_type')->where('id !=', $id)->update(['is_default_alloy' => 0]);
        }
        $db->table('raw_material_type')->where('id', $id)->update([
            'name'              => $this->request->getPost('name'),
            'default_touch_pct' => $this->request->getPost('default_touch_pct') ?: 0,
            'material_group'    => $this->request->getPost('material_group') ?: 'other',
            'is_default_alloy'  => $isDefaultAlloy,
        ]);
        return redirect()->to('raw-material-types')->with('success', 'Updated');
    }

    public function delete($id)
    {
        $db = \Config\Database::connect();
        $db->table('raw_material_type')->where('id', $id)->delete();
        return redirect()->to('raw-material-types')->with('success', 'Deleted');
    }
}
