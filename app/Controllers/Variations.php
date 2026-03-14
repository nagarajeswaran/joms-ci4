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
        $this->model->insert([
            'group_name' => $this->request->getPost('group_name'),
            'name' => $this->request->getPost('name'),
            'size' => $this->request->getPost('size'),
        ]);
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
        $this->model->update($id, [
            'group_name' => $this->request->getPost('group_name'),
            'name' => $this->request->getPost('name'),
            'size' => $this->request->getPost('size'),
        ]);
        return redirect()->to('variations')->with('success', 'Variation updated successfully');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('variations')->with('success', 'Variation deleted successfully');
    }
}
