<?php
namespace App\Controllers;

class MeltJob extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $karigarFilter = $this->request->getGet('karigar') ?? '';
        $statusFilter  = $this->request->getGet('status') ?? '';

        $builder = $db->table('melt_job mj')
            ->select('mj.*, k.name as karigar_name')
            ->join('karigar k', 'k.id = mj.karigar_id', 'left')
            ->orderBy('mj.created_at', 'DESC');
        if ($karigarFilter) $builder->where('mj.karigar_id', $karigarFilter);
        if ($statusFilter)  $builder->where('mj.status', $statusFilter);

        $items    = $builder->get()->getResultArray();
        $karigars = $db->query('SELECT id, name FROM karigar ORDER BY name')->getResultArray();

        return view('melt_jobs/index', [
            'title'         => 'Melt Jobs',
            'items'         => $items,
            'karigars'      => $karigars,
            'karigarFilter' => $karigarFilter,
            'statusFilter'  => $statusFilter,
        ]);
    }

    public function create()
    {
        $db = \Config\Database::connect();
        $karigars = $db->query('SELECT id, name, default_cash_rate, default_fine_pct FROM karigar ORDER BY name')->getResultArray();
        $rawTypes = $db->query('SELECT id, name, default_touch_pct FROM raw_material_type ORDER BY name')->getResultArray();
        $kachas   = $db->query("SELECT id, lot_number as name, touch_pct, weight, fine FROM kacha_lot WHERE status='available' ORDER BY lot_number")->getResultArray();
        $byprods  = $db->query('SELECT id, name FROM byproduct_type ORDER BY name')->getResultArray();

        $nextNum = $this->_nextJobNumber();

        return view('melt_jobs/form', [
            'title'     => 'Create Melt Job',
            'karigars'  => $karigars,
            'rawTypes'  => $rawTypes,
            'kachas'    => $kachas,
            'byprods'   => $byprods,
            'nextNum'   => $nextNum,
        ]);
    }

    public function store()
    {
        $db = \Config\Database::connect();
        $karigarId = $this->request->getPost('karigar_id');
        $karigar   = $db->query('SELECT * FROM karigar WHERE id = ?', [$karigarId])->getRowArray();

        $db->table('melt_job')->insert([
            'job_number'       => $this->_nextJobNumber(),
            'karigar_id'       => $karigarId,
            'status'           => 'draft',
            'cash_rate_per_kg' => $this->request->getPost('cash_rate_per_kg') ?? ($karigar['default_cash_rate'] ?? 0),
            'fine_pct'         => $this->request->getPost('fine_pct') ?? ($karigar['default_fine_pct'] ?? 0),
            'notes'            => $this->request->getPost('notes'),
            'required_touch_pct' => $this->request->getPost('required_touch_pct') ?: null,
            'required_weight_g'  => $this->request->getPost('required_weight_g') ?: null,
        ]);
        $jobId = $db->insertID();

        $this->_saveInputs($db, $jobId);

        return redirect()->to('melt-jobs/view/'.$jobId)->with('success', 'Melt job created');
    }


    public function searchItems()
    {
        $db = \Config\Database::connect();
        $q  = trim($this->request->getGet('q') ?? '');
        if (strlen($q) < 2) {
            return $this->response->setJSON([]);
        }
        $like = '%' . $q . '%';
        $results = [];

        $raws = $db->query('SELECT id, name, default_touch_pct FROM raw_material_type WHERE name LIKE ? ORDER BY name LIMIT 10', [$like])->getResultArray();
        foreach ($raws as $r) {
            $results[] = ['type' => 'raw_material', 'id' => $r['id'], 'name' => $r['name'], 'touch' => (float)$r['default_touch_pct'], 'weight' => null, 'fine' => null];
        }

        $kachas = $db->query("SELECT id, lot_number, touch_pct, weight, fine FROM kacha_lot WHERE status='available' AND lot_number LIKE ? ORDER BY lot_number LIMIT 10", [$like])->getResultArray();
        foreach ($kachas as $k) {
            $results[] = ['type' => 'kacha', 'id' => $k['id'], 'name' => $k['lot_number'], 'touch' => (float)$k['touch_pct'], 'weight' => (float)$k['weight'], 'fine' => (float)$k['fine']];
        }

        $byprods = $db->query('SELECT id, name FROM byproduct_type WHERE name LIKE ? ORDER BY name LIMIT 10', [$like])->getResultArray();
        foreach ($byprods as $b) {
            $results[] = ['type' => 'byproduct', 'id' => $b['id'], 'name' => $b['name'], 'touch' => 0, 'weight' => null, 'fine' => null];
        }

        return $this->response->setJSON($results);
    }
    public function view($id)
    {
        $db  = \Config\Database::connect();
        $job = $db->query('SELECT mj.*, k.name as karigar_name, k.department_id FROM melt_job mj LEFT JOIN karigar k ON k.id = mj.karigar_id WHERE mj.id = ?', [$id])->getRowArray();
        if (!$job) return redirect()->to('melt-jobs')->with('error', 'Not found');

        $inputs   = $db->query('SELECT * FROM melt_job_input WHERE melt_job_id = ?', [$id])->getResultArray();
        $receives = $db->query('SELECT mjr.*, bt.name as byprod_name, gs.weight_g as gatti_weight FROM melt_job_receive mjr LEFT JOIN byproduct_type bt ON bt.id = mjr.byproduct_type_id LEFT JOIN gatti_stock gs ON gs.id = mjr.gatti_stock_id WHERE mjr.melt_job_id = ?', [$id])->getResultArray();

        $totalIssuedWeight = array_sum(array_column($inputs, 'weight_g'));
        $totalIssuedFine   = array_sum(array_column($inputs, 'fine_g'));

        $totalRecvWeight   = 0;
        $totalRecvFine     = 0;
        $totalPartsWeight  = 0;
        foreach ($receives as $r) {
            $totalRecvWeight += $r['weight_g'];
            $totalRecvFine   += $r['weight_g'] * $r['touch_pct'] / 100;
            if ($r['receive_type'] === 'gatti') $totalPartsWeight += $r['weight_g'];
        }

        $fineDiff      = $totalIssuedFine - $totalRecvFine;
        $gattiWeightSum  = 0; $gattiFineSumCalc = 0;
        foreach ($receives as $r) { if ($r['receive_type'] === 'gatti') { $gattiWeightSum += $r['weight_g']; $gattiFineSumCalc += $r['weight_g'] * $r['touch_pct'] / 100; } }
        $avgGattiTouch   = $gattiWeightSum > 0 ? ($gattiFineSumCalc / $gattiWeightSum * 100) : 0;
        $mcFine       = $totalPartsWeight * $job['fine_pct'] / 100;
        $mcCash       = $totalPartsWeight / 1000 * $job['cash_rate_per_kg'];
        $netFine      = $fineDiff - $mcFine;

        $rawTypes = $db->query('SELECT id, name, default_touch_pct FROM raw_material_type ORDER BY name')->getResultArray();
        $kachas   = $db->query("SELECT id, lot_number as name, touch_pct, weight, fine FROM kacha_lot WHERE status='available' ORDER BY lot_number")->getResultArray();
        $byprods  = $db->query('SELECT id, name FROM byproduct_type ORDER BY name')->getResultArray();

        return view('melt_jobs/view', [
            'title'             => 'Melt Job '.$job['job_number'],
            'job'               => $job,
            'inputs'            => $inputs,
            'receives'          => $receives,
            'rawTypes'          => $rawTypes,
            'kachas'            => $kachas,
            'byprods'           => $byprods,
            'totalIssuedWeight' => $totalIssuedWeight,
            'totalIssuedFine'   => $totalIssuedFine,
            'totalRecvWeight'   => $totalRecvWeight,
            'totalRecvFine'     => $totalRecvFine,
            'fineDiff'          => $fineDiff,
            'mcFine'            => $mcFine,
            'mcCash'            => $mcCash,
            'netFine'           => $netFine,
            'gattiWeightSum'    => $gattiWeightSum,
            'avgGattiTouch'     => $avgGattiTouch,
        ]);
    }

    public function addInput($id)
    {
        $db  = \Config\Database::connect();
        $job = $db->query('SELECT * FROM melt_job WHERE id = ?', [$id])->getRowArray();
        if (!$job || $job['status'] === 'posted') return redirect()->to('melt-jobs/view/'.$id)->with('error', 'Cannot modify posted job');

        $weight   = (float)$this->request->getPost('weight_g');
        $touch    = (float)$this->request->getPost('touch_pct');
        $fineG    = $weight * $touch / 100;

        $db->table('melt_job_input')->insert([
            'melt_job_id' => $id,
            'input_type'  => $this->request->getPost('input_type'),
            'item_id'     => $this->request->getPost('item_id') ?: null,
            'item_name'   => $this->request->getPost('item_name'),
            'weight_g'    => $weight,
            'touch_pct'   => $touch,
            'fine_g'      => $fineG,
        ]);

        return redirect()->to('melt-jobs/view/'.$id)->with('success', 'Input row added');
    }

    public function deleteInput($inputId)
    {
        $db    = \Config\Database::connect();
        $input = $db->query('SELECT * FROM melt_job_input WHERE id = ?', [$inputId])->getRowArray();
        if ($input) {
            $job = $db->query('SELECT * FROM melt_job WHERE id = ?', [$input['melt_job_id']])->getRowArray();
            if ($job && $job['status'] !== 'posted') {
                $db->table('melt_job_input')->where('id', $inputId)->delete();
            }
        }
        return redirect()->to('melt-jobs/view/'.($input['melt_job_id'] ?? 0))->with('success', 'Row deleted');
    }

    public function addReceive($id)
    {
        $db  = \Config\Database::connect();
        $job = $db->query('SELECT * FROM melt_job WHERE id = ?', [$id])->getRowArray();
        if (!$job || $job['status'] === 'posted') return redirect()->to('melt-jobs/view/'.$id)->with('error', 'Cannot modify posted job');

        $db->table('melt_job_receive')->insert([
            'melt_job_id'       => $id,
            'receive_type'      => $this->request->getPost('receive_type'),
            'byproduct_type_id' => $this->request->getPost('byproduct_type_id') ?: null,
            'weight_g'          => $this->request->getPost('weight_g'),
            'touch_pct'         => $this->request->getPost('touch_pct'),
        ]);

        return redirect()->to('melt-jobs/view/'.$id)->with('success', 'Receive row added');
    }

    public function deleteReceive($recvId)
    {
        $db   = \Config\Database::connect();
        $recv = $db->query('SELECT * FROM melt_job_receive WHERE id = ?', [$recvId])->getRowArray();
        if ($recv) {
            $job = $db->query('SELECT * FROM melt_job WHERE id = ?', [$recv['melt_job_id']])->getRowArray();
            if ($job && $job['status'] !== 'posted') {
                $db->table('melt_job_receive')->where('id', $recvId)->delete();
            }
        }
        return redirect()->to('melt-jobs/view/'.($recv['melt_job_id'] ?? 0))->with('success', 'Row deleted');
    }

    public function post($id)
    {
        $db  = \Config\Database::connect();
        $job = $db->query('SELECT mj.*, k.name as karigar_name FROM melt_job mj LEFT JOIN karigar k ON k.id = mj.karigar_id WHERE mj.id = ?', [$id])->getRowArray();
        if (!$job || $job['status'] === 'posted') return redirect()->to('melt-jobs/view/'.$id)->with('error', 'Already posted');

        $inputs   = $db->query('SELECT * FROM melt_job_input WHERE melt_job_id = ?', [$id])->getResultArray();
        $receives = $db->query('SELECT * FROM melt_job_receive WHERE melt_job_id = ?', [$id])->getResultArray();

        $totalIssuedFine  = array_sum(array_column($inputs, 'fine_g'));
        $totalPartsWeight = 0;
        $totalRecvFine    = 0;

        foreach ($receives as $r) {
            $totalRecvFine += $r['weight_g'] * $r['touch_pct'] / 100;
            if ($r['receive_type'] === 'gatti') $totalPartsWeight += $r['weight_g'];
        }

        $fineDiff = $totalIssuedFine - $totalRecvFine;
        $mcFine   = $totalPartsWeight * $job['fine_pct'] / 100;
        $mcCash   = $totalPartsWeight / 1000 * $job['cash_rate_per_kg'];
        $netFine  = $fineDiff - $mcFine;

        // Create gatti_stock entries for each gatti receive
        foreach ($receives as $r) {
            if ($r['receive_type'] === 'gatti') {
                $db->table('gatti_stock')->insert([
                    'melt_job_id' => $id,
                    'weight_g'    => $r['weight_g'],
                    'touch_pct'   => $r['touch_pct'],
                    'qty_issued_g'=> 0,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
                $gsId = $db->insertID();
                $db->table('melt_job_receive')->where('id', $r['id'])->update(['gatti_stock_id' => $gsId]);
            } else {
                // byproduct stock
                $db->table('byproduct_stock')->insert([
                    'byproduct_type_id' => $r['byproduct_type_id'],
                    'weight_g'          => $r['weight_g'],
                    'touch_pct'         => $r['touch_pct'],
                    'source_job_type'   => 'melt',
                    'source_job_id'     => $id,
                ]);
            }
        }

        // Post to ledger
        $narration = $job['job_number'].' | Karigar: '.$job['karigar_name'].' | Net fine owed: '.round($netFine,4).'g';

        if ($netFine > 0) {
            $db->table('karigar_ledger')->insert([
                'karigar_id'   => $job['karigar_id'],
                'source_type'  => 'melt_job',
                'source_id'    => $id,
                'account_type' => 'fine',
                'direction'    => 'debit',
                'amount'       => round($netFine, 4),
                'narration'    => $narration,
            ]);
        }

        if ($mcCash > 0) {
            $db->table('karigar_ledger')->insert([
                'karigar_id'   => $job['karigar_id'],
                'source_type'  => 'melt_job',
                'source_id'    => $id,
                'account_type' => 'cash',
                'direction'    => 'credit',
                'amount'       => round($mcCash, 2),
                'narration'    => $narration.' | Cash making charge',
            ]);
        }

        $db->table('melt_job')->where('id', $id)->update(['status' => 'posted']);
        return redirect()->to('melt-jobs/view/'.$id)->with('success', 'Melt job posted to ledger');
    }

    private function _nextJobNumber()
    {
        $db  = \Config\Database::connect();
        $row = $db->query('SELECT COUNT(*) as cnt FROM melt_job')->getRowArray();
        return 'MELT-' . str_pad(($row['cnt'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    }

    private function _saveInputs($db, $jobId)
    {
        $types   = $this->request->getPost('input_type') ?? [];
        $names   = $this->request->getPost('item_name') ?? [];
        $weights = $this->request->getPost('weight_g') ?? [];
        $touches = $this->request->getPost('touch_pct') ?? [];
        $itemIds = $this->request->getPost('item_id') ?? [];

        foreach ($types as $i => $type) {
            if (!isset($weights[$i]) || $weights[$i] === '') continue;
            $w = (float)$weights[$i];
            $t = (float)($touches[$i] ?? 0);
            $db->table('melt_job_input')->insert([
                'melt_job_id' => $jobId,
                'input_type'  => $type,
                'item_id'     => $itemIds[$i] ?? null ?: null,
                'item_name'   => $names[$i] ?? '',
                'weight_g'    => $w,
                'touch_pct'   => $t,
                'fine_g'      => $w * $t / 100,
            ]);
        }
    }
}
