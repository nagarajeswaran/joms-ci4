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
        $newId = $db->insertID();
        return redirect()->to('karigar/edit/' . $newId)->with('success', 'Karigar added. You can now add making charge rules below.');
    }

    public function edit($id)
    {
        $db          = \Config\Database::connect();
        $item        = $db->query('SELECT * FROM karigar WHERE id = ?', [$id])->getRowArray();
        if (!$item) return redirect()->to('karigar')->with('error', 'Not found');
        $departments = $db->query('SELECT id, name FROM department ORDER BY name')->getResultArray();
        $chargeRules = $db->query('SELECT * FROM karigar_charge_rule WHERE karigar_id = ? ORDER BY sort_order, id', [$id])->getResultArray();
        $parts       = $db->query('SELECT id, name FROM part ORDER BY name')->getResultArray();

        // Gatti list for issued_filtered grid
        $rawGatti = $db->query('SELECT gs.id, gs.batch_number, gs.weight_g, gs.touch_pct, gs.qty_issued_g, mj.job_number FROM gatti_stock gs LEFT JOIN melt_job mj ON mj.id = gs.melt_job_id ORDER BY gs.created_at DESC')->getResultArray();
        $gattiList = [];
        foreach ($rawGatti as $g) {
            $gattiList[] = [
                'id'        => $g['id'],
                'label'     => $g['batch_number'] ?? ($g['job_number'] ? 'Job '.$g['job_number'] : '#'.$g['id']),
                'touch_pct' => $g['touch_pct'],
                'avail'     => (float)$g['weight_g'] - (float)$g['qty_issued_g'],
            ];
        }

        return view('karigar/form', [
            'title'       => 'Edit Karigar',
            'item'        => $item,
            'departments' => $departments,
            'chargeRules' => $chargeRules,
            'parts'       => $parts,
            'gattiList'   => $gattiList,
        ]);
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
        return redirect()->to('karigar/edit/'.$id)->with('success', 'Karigar updated');
    }

    public function delete($id)
    {
        $db = \Config\Database::connect();
        $db->table('karigar')->where('id', $id)->delete();
        return redirect()->to('karigar')->with('success', 'Karigar deleted');
    }

    public function getInfo()
    {
        $db  = \Config\Database::connect();
        $id  = $this->request->getPost('id');
        $row = $db->query('SELECT default_cash_rate, default_fine_pct FROM karigar WHERE id = ?', [$id])->getRowArray();
        return $this->response->setJSON($row ?: []);
    }

    public function storeChargeRule($karigarId)
    {
        $db         = \Config\Database::connect();
        $basis      = $this->request->getPost('basis');
        $filterType = ($basis === 'issued_all' || $basis === 'received_all') ? 'none' : ($this->request->getPost('filter_type') ?? 'none');
        $rawIds     = ($filterType !== 'none') ? ($this->request->getPost('filter_ids') ?? []) : [];
        $filterIds  = !empty($rawIds) ? json_encode(array_values(array_map('intval', (array)$rawIds))) : null;

        $db->table('karigar_charge_rule')->insert([
            'karigar_id'       => $karigarId,
            'basis'            => $basis,
            'filter_type'      => $filterType,
            'filter_ids'       => $filterIds,
            'fine_pct'         => $this->request->getPost('fine_pct') ?: 0,
            'cash_rate_per_kg' => $this->request->getPost('cash_rate_per_kg') ?: 0,
            'notes'            => $this->request->getPost('notes'),
        ]);
        return redirect()->to('karigar/edit/'.$karigarId)->with('success', 'Charge rule added');
    }

    public function deleteChargeRule($ruleId)
    {
        $db   = \Config\Database::connect();
        $rule = $db->query('SELECT karigar_id FROM karigar_charge_rule WHERE id = ?', [$ruleId])->getRowArray();
        $db->table('karigar_charge_rule')->where('id', $ruleId)->delete();
        return redirect()->to('karigar/edit/'.($rule['karigar_id'] ?? 0))->with('success', 'Rule deleted');
    }
}
