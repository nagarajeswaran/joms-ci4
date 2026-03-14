<?php
namespace App\Controllers;

class Karigar extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $items = $db->query('SELECT k.*, d.name as dept_name FROM karigar k LEFT JOIN department d ON d.id = k.department_id ORDER BY k.name')->getResultArray();
        return view('karigar/index', ['title' => 'Karigar', 'items' => $items]);
    }

    public function create()
    {
        $db = \Config\Database::connect();
        $departments = $db->query('SELECT id, name FROM department ORDER BY name')->getResultArray();
        return view('karigar/form', ['title' => 'Add Karigar', 'departments' => $departments]);
    }

    public function store()
    {
        $db = \Config\Database::connect();
        $db->table('karigar')->insert([
            'name'              => $this->request->getPost('name'),
            'tamil_name'        => $this->request->getPost('tamil_name'),
            'department_id'     => $this->request->getPost('department_id') ?: null,
            'default_cash_rate' => $this->request->getPost('default_cash_rate') ?: 0,
            'default_fine_pct'  => $this->request->getPost('default_fine_pct') ?: 0,
            'notes'             => $this->request->getPost('notes'),
        ]);
        return redirect()->to('karigar')->with('success', 'Karigar added');
    }

    public function edit($id)
    {
        $db = \Config\Database::connect();
        $item = $db->query('SELECT * FROM karigar WHERE id = ?', [$id])->getRowArray();
        if (!$item) return redirect()->to('karigar')->with('error', 'Not found');
        $departments = $db->query('SELECT id, name FROM department ORDER BY name')->getResultArray();
        return view('karigar/form', ['title' => 'Edit Karigar', 'item' => $item, 'departments' => $departments]);
    }

    public function update($id)
    {
        $db = \Config\Database::connect();
        $db->table('karigar')->where('id', $id)->update([
            'name'              => $this->request->getPost('name'),
            'tamil_name'        => $this->request->getPost('tamil_name'),
            'department_id'     => $this->request->getPost('department_id') ?: null,
            'default_cash_rate' => $this->request->getPost('default_cash_rate') ?: 0,
            'default_fine_pct'  => $this->request->getPost('default_fine_pct') ?: 0,
            'notes'             => $this->request->getPost('notes'),
        ]);
        return redirect()->to('karigar')->with('success', 'Karigar updated');
    }

    public function delete($id)
    {
        $db = \Config\Database::connect();
        $db->table('karigar')->where('id', $id)->delete();
        return redirect()->to('karigar')->with('success', 'Karigar deleted');
    }

    public function getInfo()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('id');
        $row = $db->query('SELECT default_cash_rate, default_fine_pct FROM karigar WHERE id = ?', [$id])->getRowArray();
        return $this->response->setJSON($row ?: []);
    }
}
