<?php

namespace App\Controllers;

use App\Models\DepartmentModel;
use App\Models\DepartmentGroupModel;

class Departments extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new DepartmentModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();
        $items = $db->query('SELECT d.*, dg.name as group_name FROM department d LEFT JOIN department_group dg ON d.department_group_id = dg.id ORDER BY d.name')->getResultArray();
        $groups = $db->query('SELECT * FROM department_group ORDER BY name')->getResultArray();
        return view('departments/index', [
            'title' => 'Departments',
            'items' => $items,
            'groups' => $groups
        ]);
    }

    public function create()
    {
        $db = \Config\Database::connect();
        $groups = $db->query('SELECT * FROM department_group ORDER BY name')->getResultArray();
        return view('departments/form', ['title' => 'Add Department', 'groups' => $groups]);
    }

    public function store()
    {
        $this->model->insert([
            'name' => $this->request->getPost('name'),
            'tamil_name' => $this->request->getPost('tamil_name'),
            'wastage' => $this->request->getPost('wastage'),
            'department_group_id' => $this->request->getPost('department_group_id'),
        ]);
        return redirect()->to('departments')->with('success', 'Department added successfully');
    }

    public function edit($id)
    {
        $item = $this->model->find($id);
        if (!$item) return redirect()->to('departments')->with('error', 'Not found');
        $db = \Config\Database::connect();
        $groups = $db->query('SELECT * FROM department_group ORDER BY name')->getResultArray();
        return view('departments/form', ['title' => 'Edit Department', 'item' => $item, 'groups' => $groups]);
    }

    public function update($id)
    {
        $this->model->update($id, [
            'name' => $this->request->getPost('name'),
            'tamil_name' => $this->request->getPost('tamil_name'),
            'wastage' => $this->request->getPost('wastage'),
            'department_group_id' => $this->request->getPost('department_group_id'),
        ]);
        return redirect()->to('departments')->with('success', 'Department updated successfully');
    }


    public function groupStore()
    {
        $db = \Config\Database::connect();
        $name = $this->request->getPost('name');
        if ($name) {
            $db->query("INSERT INTO department_group (name, created_at, updated_at) VALUES (?, NOW(), NOW())", [$name]);
        }
        return redirect()->to('departments')->with('success', 'Group added successfully');
    }

    public function groupUpdate($id)
    {
        $db = \Config\Database::connect();
        $name = $this->request->getPost('name');
        if ($name) {
            $db->query("UPDATE department_group SET name=?, updated_at=NOW() WHERE id=?", [$name, $id]);
        }
        return redirect()->to('departments')->with('success', 'Group updated successfully');
    }

    public function groupDelete($id)
    {
        $db = \Config\Database::connect();
        $db->query("DELETE FROM department_group WHERE id=?", [$id]);
        return redirect()->to('departments')->with('success', 'Group deleted successfully');
    }    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('departments')->with('success', 'Department deleted successfully');
    }
}
