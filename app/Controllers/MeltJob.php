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
        $karigars = $db->query("SELECT k.id, k.name, k.default_cash_rate, k.default_fine_pct FROM karigar k INNER JOIN department d ON d.id = k.department_id WHERE d.name = 'Melting' ORDER BY k.name")->getResultArray();

        $nextNum = $this->_nextJobNumber();

        return view('melt_jobs/form', [
            'title'    => 'Create Melt Job',
            'karigars' => $karigars,
            'nextNum'  => $nextNum,
        ]);
    }

    public function store()
    {
        $db = \Config\Database::connect();
        $karigarId = $this->request->getPost('karigar_id');
        $karigar   = $db->query('SELECT * FROM karigar WHERE id = ?', [$karigarId])->getRowArray();

        $db->table('melt_job')->insert([
            'job_number'         => $this->_nextJobNumber(),
            'karigar_id'         => $karigarId,
            'status'             => 'draft',
            'cash_rate_per_kg'   => $karigar['default_cash_rate'] ?? 0,
            'fine_pct'           => $karigar['default_fine_pct'] ?? 0,
            'notes'              => $this->request->getPost('notes'),
            'required_touch_pct' => $this->request->getPost('required_touch_pct') ?: null,
            'required_weight_g'  => $this->request->getPost('required_weight_g') ?: null,
            'created_by'         => $this->currentUser(),
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
        $jobId = $db->insertID();

        return redirect()->to('melt-jobs/view/'.$jobId)->with('success', 'Melt job created');
    }


    public function searchItems()
    {
        $db = \Config\Database::connect();
        $q  = trim($this->request->getGet('q') ?? '');
        if (strlen($q) < 1) {
            return $this->response->setJSON([]);
        }
        $like = '%' . $q . '%';
        $results = [];

        // Raw material batches with remaining stock
        $batches = $db->query(
            "SELECT rb.id, rb.batch_number, rb.weight_in_stock_g, rb.touch_pct, rmt.name as type_name, rmt.material_group
             FROM raw_material_batch rb
             JOIN raw_material_type rmt ON rmt.id = rb.material_type_id
             WHERE rb.weight_in_stock_g > 0
               AND (rb.batch_number LIKE ? OR rmt.name LIKE ?)
             ORDER BY rmt.name, rb.batch_number
             LIMIT 15",
            [$like, $like]
        )->getResultArray();
        foreach ($batches as $b) {
            $results[] = [
                'type'           => 'raw_material',
                'id'             => $b['id'],
                'name'           => $b['batch_number'],
                'type_name'      => $b['type_name'],
                'touch'          => (float)$b['touch_pct'],
                'remaining'      => (float)$b['weight_in_stock_g'],
                'weight'         => (float)$b['weight_in_stock_g'],
                'fine'           => null,
                'material_group' => $b['material_group'] ?? 'other',
            ];
        }

        $kachas = $db->query("SELECT id, lot_number, touch_pct, weight, fine FROM kacha_lot WHERE status='available' AND lot_number LIKE ? ORDER BY lot_number LIMIT 10", [$like])->getResultArray();
        foreach ($kachas as $k) {
            $results[] = ['type' => 'kacha', 'id' => $k['id'], 'name' => $k['lot_number'], 'touch' => (float)$k['touch_pct'], 'weight' => (float)$k['weight'], 'fine' => (float)$k['fine'], 'remaining' => (float)$k['weight']];
        }

        $byprods = $db->query('SELECT id, name FROM byproduct_type WHERE name LIKE ? ORDER BY name LIMIT 10', [$like])->getResultArray();
        foreach ($byprods as $b) {
            $results[] = ['type' => 'byproduct', 'id' => $b['id'], 'name' => $b['name'], 'touch' => 0, 'weight' => null, 'fine' => null, 'remaining' => null];
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

        // Touch suggestion data
        $silverItems = $db->query(
            "SELECT rb.id, rb.batch_number, rb.weight_in_stock_g, rb.touch_pct, rmt.name as type_name
             FROM raw_material_batch rb
             JOIN raw_material_type rmt ON rmt.id = rb.material_type_id
             WHERE rmt.material_group = 'silver' AND rb.weight_in_stock_g > 0
             ORDER BY rmt.name, rb.batch_number"
        )->getResultArray();

        $defaultAlloy = $db->query("SELECT id, name FROM raw_material_type WHERE is_default_alloy = 1 LIMIT 1")->getRowArray();

        // Touch ledger data
        $stamps = $db->query('SELECT id, name FROM stamp ORDER BY name')->getResultArray();
        $touchShopNames = $db->query(
            "SELECT DISTINCT touch_shop_name FROM touch_entry
             WHERE touch_shop_name IS NOT NULL AND touch_shop_name <> ''
             ORDER BY touch_shop_name"
        )->getResultArray();
        // Which receive rows are linked to a touch_entry
        $issuedMap = [];
        $touchEntries = $db->query(
            "SELECT te.melt_job_receive_id, te.serial_number, te.received_at, te.id AS touch_entry_id,
                    te.touch_result_pct
             FROM touch_entry te WHERE te.melt_job_receive_id IS NOT NULL"
        )->getResultArray();
        foreach ($touchEntries as $te) {
            $issuedMap[$te['melt_job_receive_id']] = $te;
        }
        // Gatti stock for linking in modal
        $gattiOptions = $db->query(
            "SELECT gs.id, gs.batch_number, gs.weight_g, gs.touch_pct, mj2.job_number
             FROM gatti_stock gs LEFT JOIN melt_job mj2 ON mj2.id = gs.melt_job_id
             ORDER BY gs.created_at DESC LIMIT 200"
        )->getResultArray();

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
            'silverItems'       => $silverItems,
            'defaultAlloy'      => $defaultAlloy,
            'stamps'            => $stamps,
            'touchShopNames'    => $touchShopNames,
            'issuedMap'         => $issuedMap,
            'gattiOptions'      => $gattiOptions,
            'touchShops'        => [],
        ]);
    }

    public function addInput($id)
    {
        $db  = \Config\Database::connect();
        $job = $db->query('SELECT * FROM melt_job WHERE id = ?', [$id])->getRowArray();
        if (!$job || $job['status'] === 'posted') return redirect()->to('melt-jobs/view/'.$id)->with('error', 'Cannot modify posted job');

        $weight    = (float)$this->request->getPost('weight_g');
        $touch     = (float)$this->request->getPost('touch_pct');
        $inputType = $this->request->getPost('input_type');
        $itemId    = $this->request->getPost('item_id') ?: null;
        $fineG     = $weight * $touch / 100;

        // For raw_material: validate and deduct from batch
        if ($inputType === 'raw_material' && $itemId) {
            $batch = $db->query('SELECT * FROM raw_material_batch WHERE id = ?', [$itemId])->getRowArray();
            if (!$batch) return redirect()->to('melt-jobs/view/'.$id)->with('error', 'Raw material batch not found.');
            if ($weight > $batch['weight_in_stock_g']) {
                return redirect()->to('melt-jobs/view/'.$id)->with('error',
                    'Insufficient stock. Available: ' . number_format($batch['weight_in_stock_g'], 4) . 'g in ' . $batch['batch_number']);
            }
        }

        $db->table('melt_job_input')->insert([
            'melt_job_id' => $id,
            'input_type'  => $inputType,
            'item_id'     => $itemId,
            'item_name'   => $this->request->getPost('item_name'),
            'weight_g'    => $weight,
            'touch_pct'   => $touch,
            'fine_g'      => $fineG,
            'created_by'  => $this->currentUser(),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $inputRowId = $db->insertID();

        // Deduct from raw material batch and log it
        if ($inputType === 'raw_material' && $itemId) {
            $db->query('UPDATE raw_material_batch SET weight_in_stock_g = weight_in_stock_g - ? WHERE id = ?', [$weight, $itemId]);
            $db->table('raw_material_batch_log')->insert([
                'raw_material_batch_id' => $itemId,
                'entry_type'            => 'out',
                'weight_g'              => $weight,
                'touch_pct'             => $touch,
                'reason'                => 'used_in_production',
                'ref_type'              => 'melt_job',
                'ref_id'                => $inputRowId,
                'notes'                 => 'Melt Job #' . $job['job_number'],
                'created_by'            => $this->currentUser(),
                'created_at'            => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->to('melt-jobs/view/'.$id)->with('success', 'Input row added');
    }
    public function deleteInput($inputId)
    {
        $db    = \Config\Database::connect();
        $input = $db->query('SELECT * FROM melt_job_input WHERE id = ?', [$inputId])->getRowArray();
        if ($input) {
            $job = $db->query('SELECT * FROM melt_job WHERE id = ?', [$input['melt_job_id']])->getRowArray();
            if ($job && $job['status'] !== 'posted') {
                // Reverse raw material batch deduction
                if ($input['input_type'] === 'raw_material' && $input['item_id']) {
                    $db->query(
                        'UPDATE raw_material_batch SET weight_in_stock_g = weight_in_stock_g + ? WHERE id = ?',
                        [$input['weight_g'], $input['item_id']]
                    );
                    $db->table('raw_material_batch_log')->insert([
                        'raw_material_batch_id' => $input['item_id'],
                        'entry_type'            => 'in',
                        'weight_g'              => $input['weight_g'],
                        'touch_pct'             => $input['touch_pct'],
                        'reason'                => 'Input deleted from melt job',
                        'ref_type'              => 'melt_job',
                        'ref_id'                => $inputId,
                        'notes'                 => 'Reversed: Melt Job #' . $job['job_number'],
                        'created_by'            => $this->currentUser(),
                        'created_at'            => date('Y-m-d H:i:s'),
                    ]);
                }
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

        $receiveType     = $this->request->getPost('receive_type');
        $batchNumber     = trim($this->request->getPost('batch_number') ?? '');
        $weight          = (float)$this->request->getPost('weight_g');
        $touchPct        = (float)$this->request->getPost('touch_pct');
        $byproductTypeId = $this->request->getPost('byproduct_type_id') ?: null;

        if ($receiveType === 'gatti' && $batchNumber === '') {
            return redirect()->to('melt-jobs/view/'.$id.'#receivedSection')->with('error', 'Batch number is required for Gatti receives');
        }

        $db->table('melt_job_receive')->insert([
            'melt_job_id'       => $id,
            'receive_type'      => $receiveType,
            'byproduct_type_id' => $byproductTypeId,
            'weight_g'          => $weight,
            'touch_pct'         => $touchPct,
            'batch_number'      => $batchNumber ?: null,
            'created_by'        => $this->currentUser(),
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
        $recvRowId = $db->insertID();

        if ($receiveType === 'gatti') {
            // Create gatti_stock immediately
            $db->table('gatti_stock')->insert([
                'melt_job_id'  => $id,
                'weight_g'     => $weight,
                'touch_pct'    => $touchPct,
                'batch_number' => $batchNumber ?: null,
                'qty_issued_g' => 0,
                'created_by'   => $this->currentUser(),
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
            $gsId = $db->insertID();
            $db->table('melt_job_receive')->where('id', $recvRowId)->update(['gatti_stock_id' => $gsId]);
            $db->table('gatti_stock_log')->insert([
                'gatti_stock_id' => $gsId,
                'entry_type'     => 'in',
                'reason'         => 'melt_job_receive',
                'weight_g'       => $weight,
                'touch_pct'      => $touchPct,
                'notes'          => 'Received from Melt Job '.$job['job_number'],
                'created_by'     => $this->currentUser(),
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        } elseif ($receiveType === 'byproduct') {
            // Create byproduct_stock immediately
            $db->table('byproduct_stock')->insert([
                'byproduct_type_id' => $byproductTypeId,
                'weight_g'          => $weight,
                'touch_pct'         => $touchPct,
                'source_job_type'   => 'melt',
                'source_job_id'     => $id,
                'created_by'        => $this->currentUser(),
                'created_at'        => date('Y-m-d H:i:s'),
            ]);
            $bsId = $db->insertID();
            $db->table('melt_job_receive')->where('id', $recvRowId)->update(['byproduct_stock_id' => $bsId]);
        }

        return redirect()->to('melt-jobs/view/'.$id.'#receivedSection')->with('success', 'Receive row added');
    }

    public function deleteReceive($recvId)
    {
        $db   = \Config\Database::connect();
        $recv = $db->query('SELECT * FROM melt_job_receive WHERE id = ?', [$recvId])->getRowArray();
        if ($recv) {
            // Guard: cannot delete if linked to a touch entry
            $linked = $db->query('SELECT serial_number FROM touch_entry WHERE melt_job_receive_id = ?', [$recvId])->getRowArray();
            if ($linked) {
                return redirect()->to('melt-jobs/view/'.($recv['melt_job_id']).'#receivedSection')
                    ->with('error', 'Cannot delete: linked to Touch Entry '.$linked['serial_number'].'. Delete the touch entry first.');
            }
            $job = $db->query('SELECT * FROM melt_job WHERE id = ?', [$recv['melt_job_id']])->getRowArray();
            if ($job && $job['status'] !== 'posted') {
                // Reverse gatti_stock
                if ($recv['receive_type'] === 'gatti' && $recv['gatti_stock_id']) {
                    $gs = $db->query('SELECT qty_issued_g FROM gatti_stock WHERE id = ?', [$recv['gatti_stock_id']])->getRowArray();
                    if ($gs && $gs['qty_issued_g'] > 0) {
                        return redirect()->to('melt-jobs/view/'.$recv['melt_job_id'].'#receivedSection')
                            ->with('error', 'Cannot delete: gatti stock has already been partially issued.');
                    }
                    $db->query('DELETE FROM gatti_stock_log WHERE gatti_stock_id = ?', [$recv['gatti_stock_id']]);
                    $db->query('DELETE FROM gatti_stock WHERE id = ?', [$recv['gatti_stock_id']]);
                }
                // Reverse byproduct_stock
                if ($recv['receive_type'] === 'byproduct' && $recv['byproduct_stock_id']) {
                    $db->query('DELETE FROM byproduct_stock WHERE id = ?', [$recv['byproduct_stock_id']]);
                }
                $db->table('melt_job_receive')->where('id', $recvId)->delete();
            }
        }
        return redirect()->to('melt-jobs/view/'.($recv['melt_job_id'] ?? 0).'#receivedSection')->with('success', 'Row deleted');
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

        // Stock (gatti_stock, byproduct_stock) already created in real-time on addReceive.
        // Only post to karigar ledger here.
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
                'created_by'   => $this->currentUser(),
                'created_at'   => date('Y-m-d H:i:s'),
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
                'created_by'   => $this->currentUser(),
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        $db->table('melt_job')->where('id', $id)->update(['status' => 'posted']);
        return redirect()->to('melt-jobs/view/'.$id)->with('success', 'Melt job posted to ledger');
    }

    public function updateReceive($recvId)
    {
        $db   = \Config\Database::connect();
        $recv = $db->query('SELECT mjr.*, mj.status FROM melt_job_receive mjr JOIN melt_job mj ON mj.id = mjr.melt_job_id WHERE mjr.id = ?', [$recvId])->getRowArray();
        if (!$recv) return $this->response->setJSON(['success' => false, 'error' => 'Row not found']);
        if ($recv['status'] === 'posted') return $this->response->setJSON(['success' => false, 'error' => 'Cannot edit posted job']);

        $field = $this->request->getPost('field');
        $value = $this->request->getPost('value');
        if (!in_array($field, ['touch_pct', 'weight_g', 'batch_number'])) return $this->response->setJSON(['success' => false, 'error' => 'Invalid field']);

        if ($field === 'batch_number') {
            $strVal = trim($value);
            $db->table('melt_job_receive')->where('id', $recvId)->update(['batch_number' => $strVal ?: null]);
            // Sync to gatti_stock
            if ($recv['gatti_stock_id']) {
                $db->table('gatti_stock')->where('id', $recv['gatti_stock_id'])->update(['batch_number' => $strVal ?: null]);
            }
            $updated = $db->query('SELECT * FROM melt_job_receive WHERE id = ?', [$recvId])->getRowArray();
            return $this->response->setJSON([
                'success'      => true,
                'batch_number' => $updated['batch_number'],
                'csrf_hash'    => csrf_hash(),
            ]);
        }

        $numVal = (float)$value;
        if ($field === 'touch_pct' && ($numVal < 0 || $numVal > 101)) return $this->response->setJSON(['success' => false, 'error' => 'Touch% must be 0–101']);
        if ($field === 'weight_g' && $numVal <= 0) return $this->response->setJSON(['success' => false, 'error' => 'Weight must be > 0']);

        $db->table('melt_job_receive')->where('id', $recvId)->update([$field => $numVal]);

        // Sync weight/touch to linked stock tables
        if ($recv['gatti_stock_id']) {
            $db->table('gatti_stock')->where('id', $recv['gatti_stock_id'])->update([$field => $numVal]);
        }
        if ($recv['byproduct_stock_id']) {
            $db->table('byproduct_stock')->where('id', $recv['byproduct_stock_id'])->update([$field => $numVal]);
        }

        // Return fresh values
        $updated = $db->query('SELECT * FROM melt_job_receive WHERE id = ?', [$recvId])->getRowArray();
        $fineG   = $updated['weight_g'] * $updated['touch_pct'] / 100;

        return $this->response->setJSON([
            'success'   => true,
            'touch_pct' => (float)$updated['touch_pct'],
            'weight_g'  => (float)$updated['weight_g'],
            'fine_g'    => $fineG,
            'csrf_hash' => csrf_hash(),
        ]);
    }

    public function updateInput($inputId)
    {
        $db    = \Config\Database::connect();
        $input = $db->query('SELECT mi.*, mj.status FROM melt_job_input mi JOIN melt_job mj ON mj.id = mi.melt_job_id WHERE mi.id = ?', [$inputId])->getRowArray();
        if (!$input)               return $this->response->setJSON(['success' => false, 'error' => 'Input row not found']);
        if ($input['status'] === 'posted') return $this->response->setJSON(['success' => false, 'error' => 'Cannot edit a posted job']);

        $touchPct = $this->request->getPost('touch_pct');
        if ($touchPct === null || $touchPct === '') return $this->response->setJSON(['success' => false, 'error' => 'Touch % required']);
        $touchPct = (float)$touchPct;
        if ($touchPct < 0 || $touchPct > 101) return $this->response->setJSON(['success' => false, 'error' => 'Touch % must be 0-101']);

        $fineG = (float)$input['weight_g'] * $touchPct / 100;
        $db->table('melt_job_input')->where('id', $inputId)->update([
            'touch_pct' => $touchPct,
            'fine_g'    => $fineG,
        ]);

        return $this->response->setJSON([
            'success'   => true,
            'touch_pct' => $touchPct,
            'fine_g'    => $fineG,
            'weight_g'  => (float)$input['weight_g'],
        ]);
    }
    public function updateField($id)
    {
        $db  = \Config\Database::connect();
        $job = $db->query('SELECT * FROM melt_job WHERE id = ?', [$id])->getRowArray();
        if (!$job) return $this->response->setJSON(['success' => false, 'error' => 'Job not found']);
        if ($job['status'] === 'posted') return $this->response->setJSON(['success' => false, 'error' => 'Cannot edit a posted job']);

        $allowed = ['cash_rate_per_kg', 'fine_pct', 'required_touch_pct', 'required_weight_g'];
        $field   = $this->request->getPost('field');
        $value   = $this->request->getPost('value');

        if (!in_array($field, $allowed)) return $this->response->setJSON(['success' => false, 'error' => 'Invalid field']);

        $numVal = ($value === '' || $value === null) ? null : (float)$value;
        $db->table('melt_job')->where('id', $id)->update([$field => $numVal]);

        return $this->response->setJSON(['success' => true, 'field' => $field, 'value' => $numVal]);
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
                'created_by'  => $this->currentUser(),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function applyTouchToAll($jobId)
    {
        $db  = \Config\Database::connect();
        $job = $db->query('SELECT * FROM melt_job WHERE id = ?', [$jobId])->getRowArray();
        if (!$job || $job['status'] === 'posted')
            return redirect()->to('melt-jobs/view/'.$jobId)->with('error', 'Cannot modify posted job');

        $touchPct = (float)$this->request->getPost('touch_pct');
        if ($touchPct < 0 || $touchPct > 101)
            return redirect()->to('melt-jobs/view/'.$jobId.'#receivedSection')->with('error', 'Invalid touch%');

        $db->query(
            'UPDATE melt_job_receive SET touch_pct = ? WHERE melt_job_id = ? AND receive_type = ?',
            [$touchPct, $jobId, 'gatti']
        );

        // Also sync the already-created gatti_stock rows
        $db->query(
            'UPDATE gatti_stock gs
             JOIN melt_job_receive mjr ON mjr.gatti_stock_id = gs.id
             SET gs.touch_pct = ?
             WHERE mjr.melt_job_id = ? AND mjr.receive_type = ?',
            [$touchPct, $jobId, 'gatti']
        );

        return redirect()->to('melt-jobs/view/'.$jobId.'#receivedSection')
            ->with('success', 'Touch '.number_format($touchPct, 2).'% applied to all gatti receives');
    }
}
