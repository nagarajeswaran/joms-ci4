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

    // ── IMPORT ──────────────────────────────────────────────────────────
    public function importForm()
    {
        return view('part_batch/import_form', ['title' => 'Import Part Stock']);
    }

    public function importSample()
    {
        $csv  = "Part Name,Batch Number,Weight (g),Weight/pc (g),Touch %,Stamp,Date\n";
        $csv .= "Gubba 150,BATCH-001,5000,2.5,0,,2024-01-01\n";
        $csv .= "Gubba 180,BATCH-002,3000,3.1,75,,2024-01-01\n";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="part_stock_sample.csv"');
        echo $csv;
        exit;
    }

    public function importPreview()
    {
        $file = $this->request->getFile('import_file');
        if (!$file || !$file->isValid()) {
            return redirect()->to('part-stock/import')->with('error', 'Please upload a valid file');
        }
        $ext  = strtolower($file->getClientExtension());
        if (!in_array($ext, ['csv', 'xlsx'])) {
            return redirect()->to('part-stock/import')->with('error', 'Only CSV and XLSX files are supported');
        }

        $tmpPath = $file->getTempName();
        $db      = \Config\Database::connect();

        // Load parts and stamps for lookup
        $partRows  = $db->query('SELECT id, LOWER(name) AS lname FROM part')->getResultArray();
        $partMap   = array_column($partRows, 'id', 'lname');
        $stampRows = $db->query('SELECT id, LOWER(name) AS lname FROM stamp')->getResultArray();
        $stampMap  = array_column($stampRows, 'id', 'lname');
        $existBatches = array_column(
            $db->query('SELECT batch_number FROM part_batch')->getResultArray(),
            'batch_number'
        );
        $existBatchSet = array_flip($existBatches);

        // Parse file
        require_once ROOTPATH . 'vendor/autoload.php';
        if ($ext === 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            // Detect encoding — Excel CSVs are UTF-8 with BOM or Windows-1252
            $sample = file_get_contents($tmpPath, false, null, 0, 4096);
            if (str_starts_with($sample, "\xEF\xBB\xBF")) {
                $reader->setInputEncoding('UTF-8');
            } elseif (mb_check_encoding($sample, 'UTF-8')) {
                $reader->setInputEncoding('UTF-8');
            } else {
                $reader->setInputEncoding('Windows-1252');
            }
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        }
        $spreadsheet = $reader->load($tmpPath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, false);

        $preview = [];
        foreach ($rows as $i => $row) {
            if ($i === 0) continue; // skip header
            $partName      = trim((string)($row[0] ?? ''));
            $batchNo       = trim((string)($row[1] ?? ''));
            $weightG       = (float)($row[2] ?? 0);
            $pieceWeightG  = (float)($row[3] ?? 0);
            $touchPct      = (float)($row[4] ?? 0);
            $stampName     = trim(strtolower((string)($row[5] ?? '')));
            $date          = trim((string)($row[6] ?? '')) ?: date('Y-m-d');

            if (!$partName || !$batchNo || $weightG <= 0) {
                $preview[] = compact('partName','batchNo','weightG','pieceWeightG','touchPct','stampName','date') + ['status'=>'error','reason'=>'Missing required field'];
                continue;
            }
            if (isset($existBatchSet[$batchNo])) {
                $preview[] = compact('partName','batchNo','weightG','pieceWeightG','touchPct','stampName','date') + ['status'=>'duplicate','reason'=>'Batch number already exists'];
                continue;
            }
            $partKey  = strtolower($partName);
            $isNew    = !isset($partMap[$partKey]);
            $partId   = $isNew ? null : $partMap[$partKey];
            $stampId  = $stampName ? ($stampMap[$stampName] ?? null) : null;
            $status   = $isNew ? 'new_part' : 'ready';
            $preview[] = compact('partName','partId','batchNo','weightG','pieceWeightG','touchPct','stampId','stampName','date','isNew') + ['status'=>$status,'reason'=>''];
        }

        $allParts = $db->query('SELECT id, name FROM part ORDER BY name')->getResultArray();
        session()->set('import_preview', $preview);
        return view('part_batch/import_preview', ['title'=>'Import Preview', 'rows'=>$preview, 'allParts'=>$allParts]);
    }

    public function importConfirm()
    {
        $rows = session()->get('import_preview');
        if (!$rows) return redirect()->to('part-stock/import')->with('error', 'Session expired. Please re-upload.');

        $db      = \Config\Database::connect();
        $mapping      = $this->request->getPost('mapping') ?? [];
        $include      = $this->request->getPost('include') ?? [];
        $createdParts = [];   // cache: lowercase name → part_id (within this import run)
        $imported = 0;
        $newParts = 0;
        $mapped   = 0;

        foreach ($rows as $i => $row) {
            if (!in_array($row['status'], ['ready', 'new_part'])) continue;
            if (!isset($include[$i])) continue;   // user unchecked this row
            $partId = $row['partId'] ?? null;
            if ($row['status'] === 'new_part') {
                $chosen = $mapping[$i] ?? '';
                if ($chosen !== '') {
                    $partId = (int)$chosen;
                    $mapped++;
                } else {
                    $key = strtolower(trim($row['partName']));
                    if (isset($createdParts[$key])) {
                        $partId = $createdParts[$key];   // reuse — already created this run
                    } else {
                        // Fetch the lowest department_id to satisfy NOT NULL FK
                        static $defaultDeptId = null;
                        if ($defaultDeptId === null) {
                            $r = $db->query('SELECT MIN(id) AS d FROM department')->getRow();
                            $defaultDeptId = (int)($r->d ?? 0);
                        }
                        $db->table('part')->insert([
                            'name'          => $row['partName'],
                            'tamil_name'    => '',
                            'weight'        => '',
                            'pcs'           => '',
                            'is_main_part'  => 0,
                            'department_id' => $defaultDeptId,
                            'gatti'         => 0,
                            'image'         => '',
                            'created_at'    => date('Y-m-d H:i:s'),
                            'updated_at'    => date('Y-m-d H:i:s'),
                        ]);
                        $partId = $db->insertID();
                        $createdParts[$key] = $partId;
                        $newParts++;
                    }
                }
            }
            $db->table('part_batch')->insert([
                'part_id'            => $partId,
                'batch_number'       => $row['batchNo'],
                'weight_in_stock_g'  => $row['weightG'],
                'piece_weight_g'     => $row['pieceWeightG'] ?: null,
                'touch_pct'          => $row['touchPct'],
                'stamp_id'           => $row['stampId'] ?? null,
                'created_at'         => $row['date'],
            ]);
            $batchId = $db->insertID();
            $db->table('part_batch_stock_log')->insert([
                'part_batch_id' => $batchId,
                'entry_type'    => 'in',
                'reason'        => 'Imported',
                'weight_g'      => $row['weightG'],
                'qty'           => 0,
                'piece_weight_g'=> $row['pieceWeightG'] ?: null,
                'touch_pct'     => $row['touchPct'] ?: null,
                'stamp_id'      => $row['stampId'] ?? null,
                'created_at'    => $row['date'],
            ]);
            $imported++;
        }

        session()->remove('import_preview');
        $msg = "{$imported} rows imported";
        if ($newParts) $msg .= " · {$newParts} new parts created";
        if ($mapped)   $msg .= " · {$mapped} mapped to existing";
        return redirect()->to('part-stock')->with('success', $msg);
    }
}