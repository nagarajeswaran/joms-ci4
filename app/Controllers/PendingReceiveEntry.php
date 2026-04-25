<?php
namespace App\Controllers;

class PendingReceiveEntry extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        if (!$this->tableExists('pending_receive_entry')) {
            return view('pending_receive_entry/index', [
                'title'        => 'Pending Receive Entry',
                'items'        => [],
                'parts'        => $db->query('SELECT id, name FROM part ORDER BY name')->getResultArray(),
                'stamps'       => $db->query('SELECT id, name FROM stamp ORDER BY name')->getResultArray(),
                'statusFilter' => 'pending',
                'partFilter'   => 0,
                'batchFilter'  => '',
                'tableMissing' => true,
            ]);
        }

        $status = trim((string)($this->request->getGet('status') ?? 'pending'));
        $partId = (int)($this->request->getGet('part_id') ?? 0);
        $batch  = trim((string)($this->request->getGet('batch') ?? ''));

        $builder = $db->table('pending_receive_entry pre')
            ->select('pre.*, p.name AS part_name, s.name AS stamp_name, po.order_number AS linked_order_number')
            ->join('part p', 'p.id = pre.part_id', 'left')
            ->join('stamp s', 's.id = pre.stamp_id', 'left')
            ->join('part_order po', 'po.id = pre.linked_part_order_id', 'left')
            ->orderBy('CASE WHEN pre.status = "pending" THEN 0 WHEN pre.status = "used" THEN 1 ELSE 2 END', '', false)
            ->orderBy('pre.created_at', 'DESC');

        if ($status !== '') {
            $builder->where('pre.status', $status);
        }
        if ($partId > 0) {
            $builder->where('pre.part_id', $partId);
        }
        if ($batch !== '') {
            $builder->like('pre.batch_number', $batch);
        }

        $items  = $builder->get()->getResultArray();
        $parts  = $db->query('SELECT id, name FROM part ORDER BY name')->getResultArray();
        $stamps = $db->query('SELECT id, name FROM stamp ORDER BY name')->getResultArray();

        return view('pending_receive_entry/index', [
            'title'        => 'Pending Receive Entry',
            'items'        => $items,
            'parts'        => $parts,
            'stamps'       => $stamps,
            'statusFilter' => $status,
            'partFilter'   => $partId,
            'batchFilter'  => $batch,
            'tableMissing' => false,
        ]);
    }

    public function store()
    {
        $db = \Config\Database::connect();

        if (!$this->tableExists('pending_receive_entry')) {
            return redirect()->to('pending-receive-entry')->with('error', 'Pending receive table is missing. Please create the new database table first.');
        }

        $partId    = (int)$this->request->getPost('part_id');
        $batchNo   = trim((string)$this->request->getPost('batch_number'));
        $weight    = (float)$this->request->getPost('weight_g');
        $pcWeight  = (float)($this->request->getPost('piece_weight_g') ?: 0);
        $touch     = (float)($this->request->getPost('touch_pct') ?: 0);
        $note      = trim((string)$this->request->getPost('note'));
        $stampId   = $this->request->getPost('stamp_id') ?: null;
        $qty       = $pcWeight > 0 ? (int)round($weight / $pcWeight) : 0;
        $createdBy = $this->_currentUsername();

        if ($partId <= 0) {
            return redirect()->to('pending-receive-entry')->with('error', 'Part is required');
        }
        if ($batchNo === '') {
            return redirect()->to('pending-receive-entry')->with('error', 'Batch barcode is required');
        }
        if ($weight <= 0) {
            return redirect()->to('pending-receive-entry')->with('error', 'Weight must be greater than 0');
        }
        if ($pcWeight < 0) {
            return redirect()->to('pending-receive-entry')->with('error', 'One pc weight cannot be negative');
        }

        $db->table('pending_receive_entry')->insert([
            'part_id'         => $partId,
            'batch_number'    => $batchNo,
            'weight_g'        => $weight,
            'piece_weight_g'  => $pcWeight ?: null,
            'qty'             => $qty,
            'touch_pct'       => $touch,
            'note'            => $note ?: null,
            'stamp_id'        => $stampId,
            'created_by'      => $createdBy,
            'status'          => 'pending',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('pending-receive-entry')->with('success', 'Pending receive entry added');
    }

    public function update($id)
    {
        $db   = \Config\Database::connect();
        if (!$this->tableExists('pending_receive_entry')) {
            return redirect()->to('pending-receive-entry')->with('error', 'Pending receive table is missing. Please create the new database table first.');
        }
        $item = $db->query('SELECT * FROM pending_receive_entry WHERE id = ?', [$id])->getRowArray();
        if (!$item) {
            return redirect()->to('pending-receive-entry')->with('error', 'Entry not found');
        }
        if ($item['status'] !== 'pending') {
            return redirect()->to('pending-receive-entry')->with('error', 'Only pending rows can be edited');
        }

        $partId   = (int)$this->request->getPost('part_id');
        $batchNo  = trim((string)$this->request->getPost('batch_number'));
        $weight   = (float)$this->request->getPost('weight_g');
        $pcWeight = (float)($this->request->getPost('piece_weight_g') ?: 0);
        $touch    = (float)($this->request->getPost('touch_pct') ?: 0);
        $note     = trim((string)$this->request->getPost('note'));
        $stampId  = $this->request->getPost('stamp_id') ?: null;
        $qty      = $pcWeight > 0 ? (int)round($weight / $pcWeight) : 0;

        if ($partId <= 0 || $batchNo === '' || $weight <= 0 || $pcWeight < 0) {
            return redirect()->to('pending-receive-entry')->with('error', 'Please enter valid part, batch barcode, weight, and pc weight');
        }

        $db->table('pending_receive_entry')->where('id', $id)->update([
            'part_id'        => $partId,
            'batch_number'   => $batchNo,
            'weight_g'       => $weight,
            'piece_weight_g' => $pcWeight ?: null,
            'qty'            => $qty,
            'touch_pct'      => $touch,
            'note'           => $note ?: null,
            'stamp_id'       => $stampId,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('pending-receive-entry')->with('success', 'Pending receive entry updated');
    }

    public function cancel($id)
    {
        $db   = \Config\Database::connect();
        if (!$this->tableExists('pending_receive_entry')) {
            return redirect()->to('pending-receive-entry')->with('error', 'Pending receive table is missing. Please create the new database table first.');
        }
        $item = $db->query('SELECT * FROM pending_receive_entry WHERE id = ?', [$id])->getRowArray();
        if (!$item) {
            return redirect()->to('pending-receive-entry')->with('error', 'Entry not found');
        }
        if ($item['status'] !== 'pending') {
            return redirect()->to('pending-receive-entry')->with('error', 'Only pending rows can be cancelled');
        }

        $db->table('pending_receive_entry')->where('id', $id)->update([
            'status'     => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('pending-receive-entry')->with('success', 'Pending receive entry cancelled');
    }

    private function _currentUsername()
    {
        return session()->get('username')
            ?: session()->get('user')
            ?: session()->get('staff_username')
            ?: 'system';
    }
}