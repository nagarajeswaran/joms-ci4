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
        $db  = \Config\Database::connect();
        $batch = $db->query('SELECT pb.*, p.name as part_name, p.tamil_name as part_tamil, s.name as stamp_name FROM part_batch pb LEFT JOIN part p ON p.id = pb.part_id LEFT JOIN stamp s ON s.id = pb.stamp_id WHERE pb.id = ?', [$id])->getRowArray();
        if (!$batch) return redirect()->to('part-stock')->with('error', 'Batch not found');
        return view('part_batch/view', ['title' => 'Batch: '.$batch['batch_number'], 'batch' => $batch]);
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
            if (!$partId || $qty < 1) continue;

            $part = $db->query('SELECT id, name FROM part WHERE id = ?', [$partId])->getRowArray();
            if (!$part) continue;

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
                    'part_id'      => $partId,
                    'touch_pct'    => 0,
                    'qty_in_stock' => 0,
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
                $batchId = $db->insertID();

                $batches[] = [
                    'id'           => $batchId,
                    'batch_number' => $batchNo,
                    'part_name'    => $part['name'],
                    'part_id'      => $partId,
                ];
            }
        }

        $db->query('UPDATE batch_serial_config SET prefix = ?, last_number = ?, updated_at = NOW() WHERE id = 1', [$prefix, $lastNumber]);

        return $this->response->setJSON(['success' => true, 'batches' => $batches]);
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
}