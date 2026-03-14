<?php

namespace App\Controllers;

use App\Models\ClientModel;

class Clients extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new ClientModel();
    }

    public function index()
    {
        return view('clients/index', [
            'title' => 'Clients',
            'items' => $this->model->orderBy('name', 'ASC')->findAll()
        ]);
    }

    public function create()
    {
        return view('clients/form', ['title' => 'Add Client']);
    }

    public function store()
    {
        $this->model->insert([
            'name' => $this->request->getPost('name'),
        ]);
        return redirect()->to('clients')->with('success', 'Client added successfully');
    }

    public function edit($id)
    {
        $item = $this->model->find($id);
        if (!$item) return redirect()->to('clients')->with('error', 'Not found');
        return view('clients/form', ['title' => 'Edit Client', 'item' => $item]);
    }

    public function update($id)
    {
        $this->model->update($id, [
            'name' => $this->request->getPost('name'),
        ]);
        return redirect()->to('clients')->with('success', 'Client updated successfully');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('clients')->with('success', 'Client deleted successfully');
    }
}
