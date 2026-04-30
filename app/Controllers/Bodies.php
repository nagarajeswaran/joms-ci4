<?php

namespace App\Controllers;

use App\Models\BodyModel;

class Bodies extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new BodyModel();
    }

    public function index()
    {
        return view('bodies/index', [
            'title' => 'Bodies',
            'items' => $this->model->orderBy('name', 'ASC')->findAll()
        ]);
    }

    public function create()
    {
        return view('bodies/form', ['title' => 'Add Body']);
    }

    public function store()
    {
        $this->model->insert([
            'name' => $this->request->getPost('name'),
            'tamil_name' => $this->request->getPost('tamil_name'),
            'clasp_size' => $this->request->getPost('clasp_size'),
            'created_by' => $this->currentUser(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('bodies')->with('success', 'Body added successfully');
    }

    public function edit($id)
    {
        $item = $this->model->find($id);
        if (!$item) return redirect()->to('bodies')->with('error', 'Not found');
        return view('bodies/form', ['title' => 'Edit Body', 'item' => $item]);
    }

    public function update($id)
    {
        $this->model->update($id, [
            'name' => $this->request->getPost('name'),
            'tamil_name' => $this->request->getPost('tamil_name'),
            'clasp_size' => $this->request->getPost('clasp_size'),
        ]);
        return redirect()->to('bodies')->with('success', 'Body updated successfully');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('bodies')->with('success', 'Body deleted successfully');
    }
}
