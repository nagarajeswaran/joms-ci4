<?php
namespace App\Controllers;

class PartBatch extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $partFilter  = $this->request->getGet('part') ?? '';
        $stampFilter = $this->request->getGet('stamp') ?? '';

        $builder = $db->table('part_batch pb')
            ->select('pb.*, p.name as part_name, p.tamil_name as part_tamil, s.name as stamp_name')
            ->join('part p', 'p.id = pb.part_id', 'left')
            ->join('stamp s', 's.id = pb.stamp_id', 'left')
            ->where('EXISTS (SELECT 1 FROM part_batch_stock_log pbl WHERE pbl.part_batch_id = pb.id)', null, false)
            ->orderBy('pb.created_at', 'DESC');

        if ($partFilter)  $builder->where('pb.part_id', $partFilter);
        if ($stampFilter) $builder->where('pb.stamp_id', $stampFilter);

        $items  = $builder->get()->getResultArray();
        $parts  = $db->query('SELECT id, name FROM part ORDER BY name')->getResultArray();
        $stamps = $db->query('SELECT id, name FROM stamp ORDER BY name')->getResultArray();

        return view('part_batch/index', [
            'title'       => 'Part Batch Stock',
            'items'       => $items,
            'parts'       => $parts,
            'stamps'      => $stamps,
            'partFilter'  => $partFilter,
            'stampFilter' => $stampFilter,
        ]);
    }

    public function view($id)
    {
        $db    = \Config\Database::connect();
        $batch = $db->query('SELECT pb.*, p.name as part_name, p.tamil_name as part_tamil, s.name as stamp_name FROM part_batch pb LEFT JOIN part p ON p.id = pb.part_id LEFT JOIN stamp s ON s.id = pb.stamp_id WHERE pb.id = ?', [$id])->getRowArray();
        if (!$batch) return redirect()->to('part-stock')->with('error', 'Batch not found');

        $manual = $db->query("SELECT id, 'manual' as source, entry_type, reason, weight_g, qty, piece_weight_g, touch_pct, notes, created_at, NULL as part_order_id FROM part_batch_stock_log WHERE part_batch_id = ?", [$id])->getResultArray();

        $fromOrders = $db->query("SELECT 'part_order' as source, 'in' as entry_type, CONCAT('Karigar receive — Part Order #', part_order_id) as reason, weight_g, qty, piece_weight_g, touch_pct, NULL as notes, received_at as created_at, part_order_id FROM part_order_receive WHERE part_batch_id = ? AND receive_type = 'part'", [$id])->getResultArray();

        $history = array_merge($manual, $fromOrders);
        usort($history, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return view('part_batch/view', [
            'title'   => 'Batch: ' . $batch['batch_number'],
            'batch'   => $batch,
            'history' => $history,
        ]);
    }

    public function stockEntry()
    {
        $db      = \Config\Database::connect();
        $stamps  = $db->query('SELECT id, name FROM stamp ORDER BY name')->getResultArray();
        $parts   = $db->query('SELECT id, name FROM part ORDER BY name')->getResultArray();
        $batchNo = trim($this->request->getGet('batch') ?? '');
        $batch   = null;
        $newBatch = false;
        if ($batchNo) {
            $batch = $db->query('SELECT pb.*, p.name as part_name, p.tamil_name as part_tamil, s.name as stamp_name FROM part_batch pb LEFT JOIN part p ON p.id = pb.part_id LEFT JOIN stamp s ON s.id = pb.stamp_id WHERE pb.batch_number = ?', [$batchNo])->getRowArray();
            if (!$batch) {
                $newBatch = true;
            }
        }
        return view('part_batch/stock_entry', [
            'title'    => 'Stock Entry',
            'stamps'   => $stamps,
            'parts'    => $parts,
            'batch'    => $batch,
            'batchNo'  => $batchNo,
            'newBatch' => $newBatch,
        ]);
    }

    public function saveStockEntry($id)
    {
        $db    = \Config\Database::connect();
        $batch = $db->query('SELECT * FROM part_batch WHERE id = ?', [$id])->getRowArray();
        if (!$batch) return redirect()->to('part-stock/entry')->with('error', 'Batch not found');

        $entryType = $this->request->getPost('entry_type');
        $weightG   = (float)$this->request->getPost('weight_g');
        $pcWeightG = (float)($this->request->getPost('piece_weight_g') ?: $batch['piece_weight_g']);
        $pcs       = (int)$this->request->getPost('pcs');

        // Weight is primary; if not given, back-calc from pcs
        if ($weightG <= 0 && $pcs > 0 && $pcWeightG > 0) {
            $weightG = round($pcs * $pcWeightG, 4);
        }

        if ($weightG <= 0) {
            return redirect()->to('part-stock/entry?batch=' . urlencode($batch['batch_number']))->with('error', 'Weight must be greater than 0');
        }

        if ($entryType === 'out' && $weightG > (float)$batch['weight_in_stock_g']) {
            return redirect()->to('part-stock/entry?batch=' . urlencode($batch['batch_number']))->with('error', 'Cannot remove ' . $weightG . 'g — only ' . $batch['weight_in_stock_g'] . 'g in stock');
        }

        $delta = $entryType === 'out' ? -$weightG : $weightG;
        $db->query('UPDATE part_batch SET weight_in_stock_g = GREATEST(0, weight_in_stock_g + ?), qty_in_stock = ROUND(GREATEST(0, weight_in_stock_g + ?) / NULLIF(piece_weight_g, 0)) WHERE id = ?', [$delta, $delta, $id]);

        // Fix 1: stamp locked once set — only update if currently null
        $stampId = $batch['stamp_id'] ?: ($this->request->getPost('stamp_id') ?: null);
        if (!$batch['stamp_id'] && $stampId) {
            $db->query('UPDATE part_batch SET stamp_id = ? WHERE id = ?', [$stampId, $id]);
        }

        // Fix 2: allow part_id change if different from current
        $postedPartId = (int)($this->request->getPost('part_id') ?? 0);
        if ($postedPartId > 0 && $postedPartId !== (int)$batch['part_id']) {
            $db->query('UPDATE part_batch SET part_id = ? WHERE id = ?', [$postedPartId, $id]);
        }

        $db->table('part_batch_stock_log')->insert([
            'part_batch_id'  => $id,
            'entry_type'     => $entryType,
            'reason'         => $this->request->getPost('reason') ?? 'manual',
            'weight_g'       => $weightG,
            'qty'            => $pcWeightG > 0 ? (int)round($weightG / $pcWeightG) : $pcs,
            'piece_weight_g' => $pcWeightG ?: null,
            'touch_pct'      => (float)($this->request->getPost('touch_pct') ?? 0),
            'stamp_id'       => $stampId,
            'notes'          => $this->request->getPost('notes') ?: null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $action = $entryType === 'out' ? 'Removed' : 'Added';
        return redirect()->to('part-stock/entry?batch=' . urlencode($batch['batch_number']))->with('success', $action . ' ' . number_format($weightG, 4) . 'g successfully');
    }

    public function labels()
    {
        $db    = \Config\Database::connect();
        $parts = $db->query('SELECT id, name FROM part ORDER BY name')->getResultArray();
        $config = $db->query('SELECT * FROM batch_serial_config WHERE id = 1')->getRowArray();
        $nextPreview = ($config['prefix'] ?? 'A') . str_pad(($config['last_number'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);
        return view('part_batch/labels', [
            'title'       => 'Generate Batch Labels',
            'parts'       => $parts,
            'nextPreview' => $nextPreview,
        ]);
    }

    public function generateBatchNumbers()
    {
        $db    = \Config\Database::connect();
        $items = $this->request->getPost('items') ?? [];

        $config = $db->query('SELECT * FROM batch_serial_config WHERE id = 1')->getRowArray();
        if (!$config) {
            return $this->response->setJSON(['success' => false, 'error' => 'Serial config not found. Please go to Serial Settings.']);
        }

        $prefix     = $config['prefix'];
        $lastNumber = (int)$config['last_number'];
        $maxNumber  = (int)$config['max_number'];

        $batches = [];

        foreach ($items as $item) {
            $partId = (int)($item['part_id'] ?? 0);
            $qty    = (int)($item['qty'] ?? 1);
            if ($qty < 1) continue;

            $part     = $partId ? $db->query('SELECT id, name FROM part WHERE id = ?', [$partId])->getRowArray() : null;
            $partName = $part ? $part['name'] : '(No Part)';

            for ($i = 0; $i < $qty; $i++) {
                $lastNumber++;
                if ($lastNumber > $maxNumber) {
                    $nextPrefix = chr(ord($prefix) + 1);
                    if ($nextPrefix > 'Z') {
                        return $this->response->setJSON([
                            'success' => false,
                            'error'   => "Serial exhausted at Z{$maxNumber}. Go to Serial Settings to set a new prefix.",
                        ]);
                    }
                    $prefix     = $nextPrefix;
                    $lastNumber = 1;
                }

                $batchNo = $prefix . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);

                $db->table('part_batch')->insert([
                    'batch_number' => $batchNo,
                    'part_id'      => $partId ?: null,
                    'touch_pct'    => 0,
                    'qty_in_stock' => 0,
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
                $batchId = $db->insertID();

                $batches[] = [
                    'id'           => $batchId,
                    'batch_number' => $batchNo,
                    'part_name'    => $partName,
                    'part_id'      => $partId ?: null,
                ];
            }
        }

        $db->query('UPDATE batch_serial_config SET prefix = ?, last_number = ?, updated_at = NOW() WHERE id = 1', [$prefix, $lastNumber]);

        return $this->response->setJSON(['success' => true, 'batches' => $batches]);
    }

    public function editStockEntry($logId)
    {
        $db  = \Config\Database::connect();
        $log = $db->query('SELECT * FROM part_batch_stock_log WHERE id = ?', [$logId])->getRowArray();
        if (!$log) return redirect()->to('part-stock')->with('error', 'Entry not found');

        $batch  = $db->query('SELECT pb.*, p.name as part_name, p.tamil_name as part_tamil, s.name as stamp_name FROM part_batch pb LEFT JOIN part p ON p.id = pb.part_id LEFT JOIN stamp s ON s.id = pb.stamp_id WHERE pb.id = ?', [$log['part_batch_id']])->getRowArray();
        $stamps = $db->query('SELECT id, name FROM stamp ORDER BY name')->getResultArray();
        $parts  = $db->query('SELECT id, name FROM part ORDER BY name')->getResultArray();

        return view('part_batch/stock_entry', [
            'title'    => 'Edit Stock Entry',
            'stamps'   => $stamps,
            'parts'    => $parts,
            'batch'    => $batch,
            'batchNo'  => $batch['batch_number'],
            'logEntry' => $log,
        ]);
    }

    public function updateStockEntry($logId)
    {
        $db  = \Config\Database::connect();
        $log = $db->query('SELECT * FROM part_batch_stock_log WHERE id = ?', [$logId])->getRowArray();
        if (!$log) return redirect()->to('part-stock')->with('error', 'Entry not found');

        $batch = $db->query('SELECT * FROM part_batch WHERE id = ?', [$log['part_batch_id']])->getRowArray();
        if (!$batch) return redirect()->to('part-stock')->with('error', 'Batch not found');

        $newEntryType = $this->request->getPost('entry_type');
        $newWeightG   = (float)$this->request->getPost('weight_g');
        $pcWeightG    = (float)($this->request->getPost('piece_weight_g') ?: $batch['piece_weight_g']);
        $pcs          = (int)$this->request->getPost('pcs');

        if ($newWeightG <= 0 && $pcs > 0 && $pcWeightG > 0) {
            $newWeightG = round($pcs * $pcWeightG, 4);
        }
        if ($newWeightG <= 0) {
            return redirect()->to('part-stock/stock-log/' . $logId . '/edit')->with('error', 'Weight must be greater than 0');
        }

        // Reverse old delta, then apply new delta
        $oldDelta     = $log['entry_type'] === 'in' ? -(float)$log['weight_g'] : (float)$log['weight_g'];
        $newDelta     = $newEntryType === 'in' ? $newWeightG : -$newWeightG;
        $stockAfter   = (float)$batch['weight_in_stock_g'] + $oldDelta + $newDelta;

        if ($stockAfter < 0) {
            return redirect()->to('part-stock/stock-log/' . $logId . '/edit')->with('error', 'Insufficient stock after update');
        }

        $db->query('UPDATE part_batch SET weight_in_stock_g = GREATEST(0, weight_in_stock_g + ? + ?), qty_in_stock = ROUND(GREATEST(0, weight_in_stock_g + ? + ?) / NULLIF(piece_weight_g, 0)) WHERE id = ?', [$oldDelta, $newDelta, $oldDelta, $newDelta, $batch['id']]);

        $stampId = $batch['stamp_id'] ?: ($this->request->getPost('stamp_id') ?: null);
        $postedPartId = (int)($this->request->getPost('part_id') ?? 0);
        if ($postedPartId > 0 && $postedPartId !== (int)$batch['part_id']) {
            $db->query('UPDATE part_batch SET part_id = ? WHERE id = ?', [$postedPartId, $batch['id']]);
        }

        $db->query('UPDATE part_batch_stock_log SET entry_type=?, reason=?, weight_g=?, qty=?, piece_weight_g=?, touch_pct=?, stamp_id=?, notes=? WHERE id = ?', [
            $newEntryType,
            $this->request->getPost('reason') ?? 'manual',
            $newWeightG,
            $pcWeightG > 0 ? (int)round($newWeightG / $pcWeightG) : $pcs,
            $pcWeightG ?: null,
            (float)($this->request->getPost('touch_pct') ?? 0),
            $stampId,
            $this->request->getPost('notes') ?: null,
            $logId,
        ]);

        return redirect()->to('part-stock/batch/' . $batch['id'])->with('success', 'Entry updated successfully');
    }

    public function deleteStockEntry($logId)
    {
        $db  = \Config\Database::connect();
        $log = $db->query('SELECT * FROM part_batch_stock_log WHERE id = ?', [$logId])->getRowArray();
        if (!$log) return redirect()->to('part-stock')->with('error', 'Entry not found');

        $batch = $db->query('SELECT * FROM part_batch WHERE id = ?', [$log['part_batch_id']])->getRowArray();
        if (!$batch) return redirect()->to('part-stock')->with('error', 'Batch not found');

        $delta = $log['entry_type'] === 'in' ? -(float)$log['weight_g'] : (float)$log['weight_g'];
        $db->query('UPDATE part_batch SET weight_in_stock_g = GREATEST(0, weight_in_stock_g + ?), qty_in_stock = ROUND(GREATEST(0, weight_in_stock_g + ?) / NULLIF(piece_weight_g, 0)) WHERE id = ?', [$delta, $delta, $batch['id']]);
        $db->query('DELETE FROM part_batch_stock_log WHERE id = ?', [$logId]);

        return redirect()->to('part-stock/batch/' . $batch['id'])->with('success', 'Entry deleted and stock reversed');
    }

    public function scan()
    {
        return view('part_batch/scan', ['title' => 'Scan Batch Barcode']);
    }

    public function lookupBatch()
    {
        $db = \Config\Database::connect();
        $q  = trim($this->request->getGet('q') ?? '');
        if (!$q) return $this->response->setJSON(['error' => 'No batch number provided']);

        $batch = $db->query('SELECT id FROM part_batch WHERE batch_number = ?', [$q])->getRowArray();
        if (!$batch) return $this->response->setJSON(['error' => 'Not found']);

        return $this->response->setJSON(['id' => $batch['id']]);
    }

    public function serialSettings()
    {
        $db     = \Config\Database::connect();
        $config = $db->query('SELECT * FROM batch_serial_config WHERE id = 1')->getRowArray();
        $next   = ($config['prefix'] ?? 'A') . str_pad(($config['last_number'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);

        return view('part_batch/serial_settings', [
            'title'  => 'Batch Serial Settings',
            'config' => $config,
            'next'   => $next,
        ]);
    }

    public function saveSerialSettings()
    {
        $db         = \Config\Database::connect();
        $prefix     = strtoupper(trim($this->request->getPost('prefix') ?? ''));
        $lastNumber = (int)$this->request->getPost('last_number');
        $maxNumber  = (int)$this->request->getPost('max_number');

        if (strlen($prefix) !== 1 || $prefix < 'A' || $prefix > 'Z') {
            return redirect()->to('part-stock/serial-settings')->with('error', 'Prefix must be a single letter A-Z.');
        }
        if ($maxNumber < 1 || $maxNumber > 9999) {
            return redirect()->to('part-stock/serial-settings')->with('error', 'Max number must be between 1 and 9999.');
        }
        if ($lastNumber < 0 || $lastNumber >= $maxNumber) {
            return redirect()->to('part-stock/serial-settings')->with('error', 'Last number must be between 0 and ' . ($maxNumber - 1) . '.');
        }

        $db->query('UPDATE batch_serial_config SET prefix = ?, last_number = ?, max_number = ?, updated_at = NOW() WHERE id = 1', [$prefix, $lastNumber, $maxNumber]);

        return redirect()->to('part-stock/serial-settings')->with('success', 'Serial settings saved.');
    }

    public function saveEntryByBatchNumber()
    {
        $db        = \Config\Database::connect();
        $batchNo   = trim($this->request->getPost('batch_number') ?? '');
        $entryType = $this->request->getPost('entry_type');
        $weightG   = (float)$this->request->getPost('weight_g');
        $pcWeightG = (float)($this->request->getPost('piece_weight_g') ?? 0);
        $pcs       = (int)$this->request->getPost('pcs');

        if (!$batchNo) {
            return redirect()->to('part-stock/entry')->with('error', 'Batch number is required');
        }

        // Weight is primary; back-calc from pcs if needed
        if ($weightG <= 0 && $pcs > 0 && $pcWeightG > 0) {
            $weightG = round($pcs * $pcWeightG, 4);
        }
        if ($weightG <= 0) {
            return redirect()->to('part-stock/entry?batch=' . urlencode($batchNo))->with('error', 'Weight must be greater than 0');
        }

        // Look up or auto-create the batch
        $batch = $db->query('SELECT * FROM part_batch WHERE batch_number = ?', [$batchNo])->getRowArray();

        if (!$batch) {
            $partId = (int)($this->request->getPost('part_id') ?? 0) ?: null;
            try {
                $db->table('part_batch')->insert([
                    'batch_number'     => $batchNo,
                    'part_id'          => $partId,
                    'touch_pct'        => (float)($this->request->getPost('touch_pct') ?? 0),
                    'weight_in_stock_g'=> 0,
                    'qty_in_stock'     => 0,
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
            } catch (\Exception $e) {
                return redirect()->to('part-stock/entry?batch=' . urlencode($batchNo))->with('error', 'Batch number already exists or could not be created');
            }
            $batch = $db->query('SELECT * FROM part_batch WHERE batch_number = ?', [$batchNo])->getRowArray();
        }

        $id = $batch['id'];

        if ($entryType === 'out' && $weightG > (float)$batch['weight_in_stock_g']) {
            return redirect()->to('part-stock/entry?batch=' . urlencode($batchNo))->with('error', 'Cannot remove ' . $weightG . 'g — only ' . $batch['weight_in_stock_g'] . 'g in stock');
        }

        $delta = $entryType === 'out' ? -$weightG : $weightG;
        $db->query('UPDATE part_batch SET weight_in_stock_g = GREATEST(0, weight_in_stock_g + ?), qty_in_stock = ROUND(GREATEST(0, weight_in_stock_g + ?) / NULLIF(piece_weight_g, 0)) WHERE id = ?', [$delta, $delta, $id]);

        // Stamp locked once set
        $stampId = $batch['stamp_id'] ?: ($this->request->getPost('stamp_id') ?: null);
        if (!$batch['stamp_id'] && $stampId) {
            $db->query('UPDATE part_batch SET stamp_id = ? WHERE id = ?', [$stampId, $id]);
        }

        // Allow part_id update if different
        $postedPartId = (int)($this->request->getPost('part_id') ?? 0);
        if ($postedPartId > 0 && $postedPartId !== (int)$batch['part_id']) {
            $db->query('UPDATE part_batch SET part_id = ? WHERE id = ?', [$postedPartId, $id]);
        }

        // pc_weight: prefer posted, fall back to batch
        $pcWeightG = $pcWeightG ?: (float)($batch['piece_weight_g'] ?? 0);

        $db->table('part_batch_stock_log')->insert([
            'part_batch_id'  => $id,
            'entry_type'     => $entryType,
            'reason'         => $this->request->getPost('reason') ?? 'manual',
            'weight_g'       => $weightG,
            'qty'            => $pcWeightG > 0 ? (int)round($weightG / $pcWeightG) : $pcs,
            'piece_weight_g' => $pcWeightG ?: null,
            'touch_pct'      => (float)($this->request->getPost('touch_pct') ?? 0),
            'stamp_id'       => $stampId,
            'notes'          => $this->request->getPost('notes') ?: null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $action = $entryType === 'out' ? 'Removed' : 'Added';
        return redirect()->to('part-stock/entry?batch=' . urlencode($batchNo))->with('success', $action . ' ' . number_format($weightG, 4) . 'g successfully');
    }
}