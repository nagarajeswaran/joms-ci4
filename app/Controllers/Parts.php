<?php

namespace App\Controllers;

use App\Models\PartModel;

class Parts extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new PartModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();
        $q          = trim($this->request->getGet('q') ?? '');
        $deptFilter = $this->request->getGet('dept') ?? '';
        $mainFilter = $this->request->getGet('main') ?? '';
        $sortCol    = $this->request->getGet('sort') ?? 'name';
        $sortDir    = strtoupper($this->request->getGet('dir') ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $allowed = ['name', 'department_name', 'weight', 'gatti', 'is_main_part'];
        if (!in_array($sortCol, $allowed)) $sortCol = 'name';

        $builder = $db->table('part p')
            ->select('p.*, d.name as department_name, po.name as podi_name')
            ->join('department d', 'd.id = p.department_id', 'left')
            ->join('podi po', 'po.id = p.podi_id', 'left');

        if ($q !== '') {
            $builder->groupStart()
                ->like('p.name', $q)
                ->orLike('p.tamil_name', $q)
                ->groupEnd();
        }
        if ($deptFilter !== '') $builder->where('p.department_id', $deptFilter);
        if ($mainFilter !== '') $builder->where('p.is_main_part', $mainFilter);

        $orderExpr = $sortCol === 'department_name' ? "d.name $sortDir" : "p.$sortCol $sortDir";
        $items = $builder->orderBy($orderExpr)->get()->getResultArray();

        $departments = $db->query('SELECT id, name FROM department ORDER BY name')->getResultArray();

        return view('parts/index', [
            'title'       => 'Parts',
            'items'       => $items,
            'departments' => $departments,
            'q'           => $q,
            'deptFilter'  => $deptFilter,
            'mainFilter'  => $mainFilter,
            'sortCol'     => $sortCol,
            'sortDir'     => $sortDir,
        ]);
    }

    public function create()
    {
        $db = \Config\Database::connect();
        return view('parts/form', [
            'title' => 'Add Part',
            'departments' => $db->query('SELECT * FROM department ORDER BY name')->getResultArray(),
            'podies' => $db->query('SELECT * FROM podi ORDER BY name')->getResultArray()
        ]);
    }

    public function store()
    {
        $this->model->insert([
            'name' => $this->request->getPost('name'),
            'tamil_name' => $this->request->getPost('tamil_name'),
            'weight' => $this->request->getPost('weight'),
            'pcs' => $this->request->getPost('pcs'),
            'is_main_part' => $this->request->getPost('is_main_part') ?? 0,
            'department_id' => $this->request->getPost('department_id'),
            'podi_id' => $this->request->getPost('podi_id'),
            'gatti' => $this->request->getPost('gatti'),
            'created_by' => $this->currentUser(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('parts')->with('success', 'Part added successfully');
    }

    public function edit($id)
    {
        $item = $this->model->find($id);
        if (!$item) return redirect()->to('parts')->with('error', 'Not found');
        $db = \Config\Database::connect();
        return view('parts/form', [
            'title' => 'Edit Part',
            'item' => $item,
            'departments' => $db->query('SELECT * FROM department ORDER BY name')->getResultArray(),
            'podies' => $db->query('SELECT * FROM podi ORDER BY name')->getResultArray()
        ]);
    }

    public function update($id)
    {
        $this->model->update($id, [
            'name' => $this->request->getPost('name'),
            'tamil_name' => $this->request->getPost('tamil_name'),
            'weight' => $this->request->getPost('weight'),
            'pcs' => $this->request->getPost('pcs'),
            'is_main_part' => $this->request->getPost('is_main_part') ?? 0,
            'department_id' => $this->request->getPost('department_id'),
            'podi_id' => $this->request->getPost('podi_id'),
            'gatti' => $this->request->getPost('gatti'),
        ]);
        return redirect()->to('parts')->with('success', 'Part updated successfully');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('parts')->with('success', 'Part deleted successfully');
    }
}
