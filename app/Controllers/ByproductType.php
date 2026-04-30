<?php
namespace App\Controllers;

class ByproductType extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $items = $db->query('SELECT * FROM byproduct_type ORDER BY name')->getResultArray();
        return view('byproduct_type/index', ['title' => 'Byproduct Types', 'items' => $items]);
    }

    public function create()
    {
        return view('byproduct_type/form', ['title' => 'Add Byproduct Type']);
    }

    public function store()
    {
        $db = \Config\Database::connect();
        $db->table('byproduct_type')->insert([
            'name' => $this->request->getPost('name'),
            'created_by' => $this->currentUser(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('byproduct-types')->with('success', 'Added');
    }

    public function edit($id)
    {
        $db = \Config\Database::connect();
        $item = $db->query('SELECT * FROM byproduct_type WHERE id = ?', [$id])->getRowArray();
        if (!$item) return redirect()->to('byproduct-types')->with('error', 'Not found');
        return view('byproduct_type/form', ['title' => 'Edit Byproduct Type', 'item' => $item]);
    }

    public function update($id)
    {
        $db = \Config\Database::connect();
        $db->table('byproduct_type')->where('id', $id)->update(['name' => $this->request->getPost('name')]);
        return redirect()->to('byproduct-types')->with('success', 'Updated');
    }

    public function delete($id)
    {
        $db = \Config\Database::connect();
        $db->table('byproduct_type')->where('id', $id)->delete();
        return redirect()->to('byproduct-types')->with('success', 'Deleted');
    }
}
