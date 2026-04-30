<?php
namespace App\Controllers;

class FinishedGoods extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $items = $this->db->query('SELECT * FROM finished_goods_master ORDER BY name, tamil_name')->getResultArray();
        return view('finished_goods/index', [
            'title' => 'Finished Goods',
            'items' => $items,
        ]);
    }

    public function create()
    {
        return view('finished_goods/form', [
            'title' => 'Add Finished Good',
            'item'  => null,
        ]);
    }

    public function store()
    {
        $name = trim((string)$this->request->getPost('name'));
        $tamilName = trim((string)$this->request->getPost('tamil_name'));

        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Name is required');
        }

        $this->db->table('finished_goods_master')->insert([
            'name'       => $name,
            'tamil_name' => $tamilName ?: null,
            'created_by' => $this->currentUser(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('finished-goods')->with('success', 'Finished good added');
    }

    public function edit($id)
    {
        $item = $this->db->query('SELECT * FROM finished_goods_master WHERE id = ?', [$id])->getRowArray();
        if (!$item) {
            return redirect()->to('finished-goods')->with('error', 'Finished good not found');
        }

        return view('finished_goods/form', [
            'title' => 'Edit Finished Good',
            'item'  => $item,
        ]);
    }

    public function update($id)
    {
        $item = $this->db->query('SELECT * FROM finished_goods_master WHERE id = ?', [$id])->getRowArray();
        if (!$item) {
            return redirect()->to('finished-goods')->with('error', 'Finished good not found');
        }

        $name = trim((string)$this->request->getPost('name'));
        $tamilName = trim((string)$this->request->getPost('tamil_name'));

        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Name is required');
        }

        $this->db->table('finished_goods_master')->where('id', $id)->update([
            'name'       => $name,
            'tamil_name' => $tamilName ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('finished-goods')->with('success', 'Finished good updated');
    }

    public function delete($id)
    {
        $this->db->table('finished_goods_master')->where('id', $id)->delete();
        return redirect()->to('finished-goods')->with('success', 'Finished good deleted');
    }
}