<?php
namespace App\Controllers;

class RawMaterialBatch extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $typeFilter = $this->request->getGet('type') ?? '';
        $showAll    = $this->request->getGet('all') ?? '';

        $builder = $db->table('raw_material_batch rb')
            ->select('rb.*, rmt.name as type_name, rmt.material_group')
            ->join('raw_material_type rmt', 'rmt.id = rb.material_type_id', 'left')
            ->orderBy('rmt.name', 'ASC')
            ->orderBy('rb.batch_number', 'ASC');

        if ($typeFilter) $builder->where('rb.material_type_id', $typeFilter);
        if (!$showAll)   $builder->where('rb.weight_in_stock_g >', 0);

        $batches = $builder->get()->getResultArray();
        $types   = $db->query('SELECT * FROM raw_material_type ORDER BY name')->getResultArray();

        $totalWeight = 0;
        $totalFine   = 0;
        foreach ($batches as $b) {
            $totalWeight += (float)$b['weight_in_stock_g'];
            $totalFine   += (float)$b['weight_in_stock_g'] * (float)$b['touch_pct'] / 100;
        }

        return view('raw_material_batch/index', [
            'title'       => 'Raw Material Batch Stock',
            'batches'     => $batches,
            'types'       => $types,
            'typeFilter'  => $typeFilter,
            'showAll'     => $showAll,
            'totalWeight' => $totalWeight,
            'totalFine'   => $totalFine,
        ]);
    }

    public function view($id)
    {
        $db    = \Config\Database::connect();
        $batch = $db->query(
            'SELECT rb.*, rmt.name as type_name, rmt.material_group
             FROM raw_material_batch rb
             JOIN raw_material_type rmt ON rmt.id = rb.material_type_id
             WHERE rb.id = ?', [$id]
        )->getRowArray();

        if (!$batch) return redirect()->to('raw-material-batches')->with('error', 'Batch not found');

        $logs = $db->query(
            'SELECT * FROM raw_material_batch_log WHERE raw_material_batch_id = ? ORDER BY created_at DESC', [$id]
        )->getResultArray();

        return view('raw_material_batch/view', [
            'title' => 'Batch: ' . $batch['batch_number'],
            'batch' => $batch,
            'logs'  => $logs,
        ]);
    }

    public function create()
    {
        $db    = \Config\Database::connect();
        $types = $db->query('SELECT * FROM raw_material_type ORDER BY name')->getResultArray();
        return view('raw_material_batch/form', [
            'title' => 'Add Raw Material Batch',
            'types' => $types,
        ]);
    }

    public function store()
    {
        $db      = \Config\Database::connect();
        $typeId  = $this->request->getPost('material_type_id');
        $touch   = $this->request->getPost('touch_pct');
        $weight  = (float)$this->request->getPost('weight_g');
        $batch   = trim($this->request->getPost('batch_number'));

        if (!$touch) {
            $row   = $db->query('SELECT default_touch_pct FROM raw_material_type WHERE id = ?', [$typeId])->getRowArray();
            $touch = $row['default_touch_pct'] ?? 0;
        }

        $db->table('raw_material_batch')->insert([
            'batch_number'       => $batch,
            'material_type_id'   => $typeId,
            'weight_in_stock_g'  => $weight,
            'touch_pct'          => $touch,
            'created_by'         => $this->currentUser(),
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
        $batchId = $db->insertID();

        $db->table('raw_material_batch_log')->insert([
            'raw_material_batch_id' => $batchId,
            'entry_type'            => 'in',
            'weight_g'              => $weight,
            'touch_pct'             => $touch,
            'reason'                => 'initial_stock',
            'notes'                 => 'Initial batch entry',
            'created_by'            => $this->currentUser(),
            'created_at'            => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('raw-material-batches')->with('success', 'Batch added');
    }

    public function addStock($id)
    {
        $db     = \Config\Database::connect();
        $batch  = $db->query('SELECT * FROM raw_material_batch WHERE id = ?', [$id])->getRowArray();
        if (!$batch) return redirect()->to('raw-material-batches')->with('error', 'Batch not found');

        $weight = (float)$this->request->getPost('weight_g');
        $touch  = (float)$this->request->getPost('touch_pct');
        $notes  = $this->request->getPost('notes') ?? '';

        $db->query('UPDATE raw_material_batch SET weight_in_stock_g = weight_in_stock_g + ? WHERE id = ?', [$weight, $id]);

        $db->table('raw_material_batch_log')->insert([
            'raw_material_batch_id' => $id,
            'entry_type'            => 'in',
            'weight_g'              => $weight,
            'touch_pct'             => $touch,
            'reason'                => 'stock_added',
            'notes'                 => $notes,
            'created_by'            => $this->currentUser(),
            'created_at'            => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('raw-material-batches/view/' . $id)->with('success', 'Stock added');
    }

    public function edit($id)
    {
        $db    = \Config\Database::connect();
        $batch = $db->query('SELECT * FROM raw_material_batch WHERE id = ?', [$id])->getRowArray();
        if (!$batch) return redirect()->to('raw-material-batches')->with('error', 'Batch not found');

        $types = $db->query('SELECT * FROM raw_material_type ORDER BY name')->getResultArray();
        return view('raw_material_batch/form', [
            'title' => 'Edit Raw Material Batch',
            'types' => $types,
            'batch' => $batch,
        ]);
    }

    public function update($id)
    {
        $db    = \Config\Database::connect();
        $batch = $db->query('SELECT * FROM raw_material_batch WHERE id = ?', [$id])->getRowArray();
        if (!$batch) return redirect()->to('raw-material-batches')->with('error', 'Batch not found');

        $batchNum = trim($this->request->getPost('batch_number'));
        $typeId   = $this->request->getPost('material_type_id');
        $touch    = $this->request->getPost('touch_pct');

        $dup = $db->query('SELECT id FROM raw_material_batch WHERE batch_number = ? AND id != ?', [$batchNum, $id])->getRowArray();
        if ($dup) return redirect()->back()->with('error', 'Batch number already exists');

        if (!$touch) {
            $row   = $db->query('SELECT default_touch_pct FROM raw_material_type WHERE id = ?', [$typeId])->getRowArray();
            $touch = $row['default_touch_pct'] ?? 0;
        }

        $db->query('UPDATE raw_material_batch SET batch_number = ?, material_type_id = ?, touch_pct = ? WHERE id = ?', [
            $batchNum, $typeId, $touch, $id
        ]);

        return redirect()->to('raw-material-batches/view/' . $id)->with('success', 'Batch updated');
    }

    public function delete($id)
    {
        $db    = \Config\Database::connect();
        $batch = $db->query('SELECT * FROM raw_material_batch WHERE id = ?', [$id])->getRowArray();
        if (!$batch) return redirect()->to('raw-material-batches')->with('error', 'Batch not found');

        $used = $db->query("SELECT COUNT(*) as cnt FROM melt_job_input WHERE input_type = 'raw_material' AND item_id = ?", [$id])->getRowArray();
        if ((int)$used['cnt'] > 0) {
            return redirect()->to('raw-material-batches/view/' . $id)->with('error', 'Cannot delete — batch used in melt jobs');
        }

        $db->query('DELETE FROM raw_material_batch_log WHERE raw_material_batch_id = ?', [$id]);
        $db->query('DELETE FROM raw_material_batch WHERE id = ?', [$id]);

        return redirect()->to('raw-material-batches')->with('success', 'Batch deleted');
    }

    public function entry()
    {
        $db         = \Config\Database::connect();
        $batchCode  = trim($this->request->getGet('batch') ?? '');
        $batch      = null;
        $logs       = [];

        if ($batchCode) {
            $batch = $db->query(
                'SELECT rb.*, rmt.name as type_name, rmt.material_group
                 FROM raw_material_batch rb
                 JOIN raw_material_type rmt ON rmt.id = rb.material_type_id
                 WHERE rb.batch_number = ?', [$batchCode]
            )->getRowArray();

            if ($batch) {
                $logs = $db->query(
                    'SELECT * FROM raw_material_batch_log WHERE raw_material_batch_id = ? ORDER BY created_at DESC', [$batch['id']]
                )->getResultArray();
            }
        }

        $types = $db->query('SELECT * FROM raw_material_type ORDER BY name')->getResultArray();

        return view('raw_material_batch/entry', [
            'title'     => 'Raw Material Entry',
            'batchCode' => $batchCode,
            'batch'     => $batch,
            'logs'      => $logs,
            'types'     => $types,
        ]);
    }
}