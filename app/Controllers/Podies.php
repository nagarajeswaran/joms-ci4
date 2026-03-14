<?php

namespace App\Controllers;

use App\Models\PodiModel;

class Podies extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new PodiModel();
    }

    public function index()
    {
        return view('podies/index', [
            'title' => 'Podi Management',
            'items' => $this->model->orderBy('name', 'ASC')->findAll()
        ]);
    }

    public function create()
    {
        return view('podies/form', ['title' => 'Add Podi']);
    }

    public function store()
    {
        $this->model->insert([
            'name' => $this->request->getPost('name'),
            'number' => $this->request->getPost('number'),
            'weight' => $this->request->getPost('weight'),
        ]);
        return redirect()->to('podies')->with('success', 'Podi added successfully');
    }

    public function edit($id)
    {
        $item = $this->model->find($id);
        if (!$item) return redirect()->to('podies')->with('error', 'Not found');
        return view('podies/form', ['title' => 'Edit Podi', 'item' => $item]);
    }

    public function update($id)
    {
        $this->model->update($id, [
            'name' => $this->request->getPost('name'),
            'number' => $this->request->getPost('number'),
            'weight' => $this->request->getPost('weight'),
        ]);
        return redirect()->to('podies')->with('success', 'Podi updated successfully');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('podies')->with('success', 'Podi deleted successfully');
    }
}
