<?php
namespace App\Controllers;

class RawMaterialStock extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $typeFilter = $this->request->getGet('type') ?? '';
        $builder = $db->table('raw_material_stock rs')
            ->select('rs.*, rmt.name as type_name')
            ->join('raw_material_type rmt', 'rmt.id = rs.material_type_id', 'left')
            ->orderBy('rs.added_at', 'DESC');
        if ($typeFilter) $builder->where('rs.material_type_id', $typeFilter);
        $items = $builder->get()->getResultArray();
        $types = $db->query('SELECT * FROM raw_material_type ORDER BY name')->getResultArray();
        return view('raw_material_stock/index', [
            'title'      => 'Raw Material Stock',
            'items'      => $items,
            'types'      => $types,
            'typeFilter' => $typeFilter,
        ]);
    }

    public function create()
    {
        $db = \Config\Database::connect();
        $types = $db->query('SELECT * FROM raw_material_type ORDER BY name')->getResultArray();
        return view('raw_material_stock/form', ['title' => 'Add Raw Material Stock', 'types' => $types]);
    }

    public function store()
    {
        $db = \Config\Database::connect();
        $typeId = $this->request->getPost('material_type_id');
        $touch = $this->request->getPost('touch_pct');
        if (!$touch) {
            $row = $db->query('SELECT default_touch_pct FROM raw_material_type WHERE id = ?', [$typeId])->getRowArray();
            $touch = $row['default_touch_pct'] ?? 0;
        }
        $db->table('raw_material_stock')->insert([
            'material_type_id' => $typeId,
            'weight_g'         => $this->request->getPost('weight_g'),
            'touch_pct'        => $touch,
            'notes'            => $this->request->getPost('notes'),
        ]);
        return redirect()->to('raw-materials')->with('success', 'Stock entry added');
    }

    public function delete($id)
    {
        $db = \Config\Database::connect();
        $db->table('raw_material_stock')->where('id', $id)->delete();
        return redirect()->to('raw-materials')->with('success', 'Deleted');
    }
}
