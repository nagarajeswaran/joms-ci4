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
        return view('part_batch/labels', ['title' => 'Generate Batch Labels', 'parts' => $parts]);
    }

    public function generateBatchNumbers()
    {
        $db      = \Config\Database::connect();
        $items   = $this->request->getPost('items') ?? [];
        $batches = [];

        foreach ($items as $item) {
            $partId = (int)($item['part_id'] ?? 0);
            $qty    = (int)($item['qty'] ?? 1);
            if (!$partId || $qty < 1) continue;

            $part = $db->query('SELECT id, name FROM part WHERE id = ?', [$partId])->getRowArray();
            if (!$part) continue;

            // Get or create sequence
            $seq = $db->query('SELECT last_sequence FROM part_batch_sequence WHERE part_id = ?', [$partId])->getRowArray();
            $next = $seq ? (int)$seq['last_sequence'] : 0;

            $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $part['name']));
            $prefix = substr($prefix, 0, 8);

            for ($i = 0; $i < $qty; $i++) {
                $next++;
                $batchNo = $prefix . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);

                // Insert pre-created batch
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

            // Update sequence
            if ($seq) {
                $db->query('UPDATE part_batch_sequence SET last_sequence = ? WHERE part_id = ?', [$next, $partId]);
            } else {
                $db->query('INSERT INTO part_batch_sequence (part_id, last_sequence) VALUES (?, ?)', [$partId, $next]);
            }
        }

        return $this->response->setJSON(['success' => true, 'batches' => $batches]);
    }

    public function printLabels()
    {
        $db       = \Config\Database::connect();
        $ids      = $this->request->getPost('batch_ids') ?? [];
        $paper    = $this->request->getPost('paper') ?? 'A4';
        $rows     = (int)($this->request->getPost('rows') ?? 4);
        $cols     = (int)($this->request->getPost('cols') ?? 3);

        if (!$ids) return redirect()->to('part-stock/labels')->with('error', 'No batches selected');

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $batches = $db->query("SELECT pb.*, p.name as part_name FROM part_batch pb LEFT JOIN part p ON p.id = pb.part_id WHERE pb.id IN ($placeholders)", $ids)->getResultArray();

        return view('part_batch/print_labels', [
            'title'   => 'Print Batch Labels',
            'batches' => $batches,
            'paper'   => $paper,
            'rows'    => $rows,
            'cols'    => $cols,
        ]);
    }

    public function qrImage($batchId)
    {
        $db    = \Config\Database::connect();
        $batch = $db->query('SELECT * FROM part_batch WHERE id = ?', [$batchId])->getRowArray();
        if (!$batch) return $this->response->setStatusCode(404);

        $url = base_url('part-stock/batch/' . $batchId);
        require_once ROOTPATH . 'vendor/autoload.php';

        $qr = \Endroid\QrCode\Builder\Builder::create()
            ->writer(new \Endroid\QrCode\Writer\PngWriter())
            ->data($url)
            ->size(200)
            ->margin(6)
            ->build();

        return $this->response->setContentType('image/png')->setBody($qr->getString());
    }

    public function scan()
    {
        return view('part_batch/scan', ['title' => 'Scan Batch QR']);
    }
}
