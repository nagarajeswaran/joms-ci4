<?php

namespace App\Controllers;

use App\Models\VariationModel;

class Variations extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new VariationModel();
    }

    public function index()
    {
        return view('variations/index', [
            'title' => 'Variations',
            'items' => $this->model->orderBy('group_name', 'ASC')->orderBy('name', 'ASC')->findAll()
        ]);
    }

    public function create()
    {
        return view('variations/form', ['title' => 'Add Variation']);
    }

    public function store()
    {
        $groupName  = $this->request->getPost('group_name');
        $groupTamil = $this->request->getPost('group_tamil_name');
        $this->model->insert([
            'group_name'       => $groupName,
            'group_tamil_name' => $groupTamil,
            'name'             => $this->request->getPost('name'),
            'size'             => $this->request->getPost('size'),
            'created_by'       => $this->currentUser(),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
        // Sync Tamil name across all variations in the same group
        $db = \Config\Database::connect();
        $db->query('UPDATE variation SET group_tamil_name = ? WHERE group_name = ?', [$groupTamil, $groupName]);
        return redirect()->to('variations')->with('success', 'Variation added successfully');
    }

    public function edit($id)
    {
        $item = $this->model->find($id);
        if (!$item) return redirect()->to('variations')->with('error', 'Not found');
        return view('variations/form', ['title' => 'Edit Variation', 'item' => $item]);
    }

    public function update($id)
    {
        $groupName  = $this->request->getPost('group_name');
        $groupTamil = $this->request->getPost('group_tamil_name');
        $this->model->update($id, [
            'group_name'       => $groupName,
            'group_tamil_name' => $groupTamil,
            'name'             => $this->request->getPost('name'),
            'size'             => $this->request->getPost('size'),
        ]);
        // Sync Tamil name across all variations in the same group
        $db = \Config\Database::connect();
        $db->query('UPDATE variation SET group_tamil_name = ? WHERE group_name = ?', [$groupTamil, $groupName]);
        return redirect()->to('variations')->with('success', 'Variation updated successfully');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('variations')->with('success', 'Variation deleted successfully');
    }
}
