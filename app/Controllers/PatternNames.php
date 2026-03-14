<?php

namespace App\Controllers;

class PatternNames extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $items = $this->db->query('SELECT * FROM pattern_name ORDER BY name')->getResultArray();
        return view('pattern_names/index', ['title' => 'Pattern Names', 'items' => $items]);
    }

    public function create()
    {
        return view('pattern_names/form', ['title' => 'Add Pattern Name']);
    }

    public function store()
    {
        $this->db->table('pattern_name')->insert([
            'name' => $this->request->getPost('name'),
            'tamil_name' => $this->request->getPost('tamil_name') ?? '',
        ]);
        return redirect()->to('pattern-names')->with('success', 'Pattern name added');
    }

    public function edit($id)
    {
        $item = $this->db->query('SELECT * FROM pattern_name WHERE id = ?', [$id])->getRowArray();
        if (!$item) return redirect()->to('pattern-names')->with('error', 'Not found');
        return view('pattern_names/form', ['title' => 'Edit Pattern Name', 'item' => $item]);
    }

    public function update($id)
    {
        $this->db->table('pattern_name')->where('id', $id)->update([
            'name' => $this->request->getPost('name'),
            'tamil_name' => $this->request->getPost('tamil_name') ?? '',
        ]);
        return redirect()->to('pattern-names')->with('success', 'Pattern name updated');
    }

    public function delete($id)
    {
        $used = $this->db->query('SELECT COUNT(*) as cnt FROM product_pattern WHERE pattern_name_id = ?', [$id])->getRowArray()['cnt'];
        if ($used > 0) return redirect()->to('pattern-names')->with('error', 'Cannot delete: used by ' . $used . ' product pattern(s)');
        $this->db->query('DELETE FROM pattern_name WHERE id = ?', [$id]);
        return redirect()->to('pattern-names')->with('success', 'Pattern name deleted');
    }
}
