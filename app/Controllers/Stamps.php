<?php

namespace App\Controllers;

use App\Models\StampModel;

class Stamps extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new StampModel();
    }

    public function index()
    {
        return view('stamps/index', [
            'title' => 'Stamps',
            'items' => $this->model->orderBy('name', 'ASC')->findAll()
        ]);
    }

    public function create()
    {
        return view('stamps/form', ['title' => 'Add Stamp']);
    }

    public function store()
    {
        $this->model->insert([
            'name' => $this->request->getPost('name'),
        ]);
        return redirect()->to('stamps')->with('success', 'Stamp added successfully');
    }

    public function edit($id)
    {
        $item = $this->model->find($id);
        if (!$item) return redirect()->to('stamps')->with('error', 'Not found');
        return view('stamps/form', ['title' => 'Edit Stamp', 'item' => $item]);
    }

    public function update($id)
    {
        $this->model->update($id, [
            'name' => $this->request->getPost('name'),
        ]);
        return redirect()->to('stamps')->with('success', 'Stamp updated successfully');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('stamps')->with('success', 'Stamp deleted successfully');
    }
}
