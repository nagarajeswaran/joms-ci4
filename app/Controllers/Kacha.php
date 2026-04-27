<?php
namespace App\Controllers;

class Kacha extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $status = $this->request->getGet('status') ?? '';
        $q      = $this->request->getGet('q') ?? '';

        $sql = 'SELECT * FROM kacha_lot WHERE 1=1';
        $params = [];
        if ($status) { $sql .= ' AND status = ?'; $params[] = $status; }
        if ($q)      { $sql .= ' AND (lot_number LIKE ? OR party LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
        $sql .= ' ORDER BY created_at DESC';

        $lots = $this->db->query($sql, $params)->getResultArray();

        $totalWeight = array_sum(array_column($lots, 'weight'));
        $totalFine   = array_sum(array_column($lots, 'fine'));
        $avgTouch    = $totalWeight > 0 ? round($totalFine / $totalWeight * 100, 4) : 0;

        return view('kacha/index', [
            'title'       => 'Kacha — Bullion List',
            'lots'        => $lots,
            'status'      => $status,
            'q'           => $q,
            'totalWeight' => $totalWeight,
            'totalFine'   => $totalFine,
            'avgTouch'    => $avgTouch,
        ]);
    }

    public function create()
    {
        return view('kacha/create', ['title' => 'Add Kacha Lots']);
    }

    public function store()
    {
        $lotNumbers = $this->request->getPost('lot_number') ?? [];
        $weights    = $this->request->getPost('weight') ?? [];
        $touches    = $this->request->getPost('touch_pct') ?? [];
        $dates      = $this->request->getPost('receipt_date') ?? [];
        $parties    = $this->request->getPost('party') ?? [];
        $sources    = $this->request->getPost('source_type') ?? [];
        $testTouches= $this->request->getPost('test_touch') ?? [];
        $testNums   = $this->request->getPost('test_number') ?? [];
        $notesList  = $this->request->getPost('notes') ?? [];

        $errors = [];
        $saved  = 0;
        $now    = date('Y-m-d H:i:s');

        foreach ($lotNumbers as $i => $lotNum) {
            $lotNum = trim($lotNum);
            $weight = (float)($weights[$i] ?? 0);
            $touch  = (float)($touches[$i] ?? 0);
            if (!$lotNum || $weight <= 0 || $touch <= 0) continue;

            $exists = $this->db->query('SELECT id FROM kacha_lot WHERE lot_number = ?', [$lotNum])->getRowArray();
            if ($exists) { $errors[] = "Lot number '$lotNum' already exists."; continue; }

            $this->db->query(
                'INSERT INTO kacha_lot (lot_number, weight, touch_pct, receipt_date, party, source_type, test_touch, test_number, notes, created_by, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $lotNum, $weight, $touch,
                    $dates[$i] ?: null,
                    $parties[$i] ?: null,
                    $sources[$i] ?: 'purchase',
                    $testTouches[$i] !== '' ? (float)$testTouches[$i] : null,
                    $testNums[$i] ?: null,
                    $notesList[$i] ?: null,
                    $this->currentUser(),
                    $now,
                ]
            );
            $saved++;
        }

        if ($errors) {
            return redirect()->back()->withInput()->with('error', implode(' | ', $errors));
        }
        return redirect()->to('kacha')->with('success', "$saved lot(s) added successfully.");
    }

    public function view($id)
    {
        $lot = $this->db->query('SELECT * FROM kacha_lot WHERE id = ?', [$id])->getRowArray();
        if (!$lot) return redirect()->to('kacha')->with('error', 'Lot not found.');
        $meltJob = null;
        if ($lot['used_in_melt_job_id']) {
            $meltJob = $this->db->query('SELECT id FROM melt_job WHERE id = ?', [$lot['used_in_melt_job_id']])->getRowArray();
        }
        return view('kacha/view', ['title' => 'Kacha Lot — ' . $lot['lot_number'], 'lot' => $lot, 'meltJob' => $meltJob]);
    }

    public function edit($id)
    {
        $lot = $this->db->query('SELECT * FROM kacha_lot WHERE id = ?', [$id])->getRowArray();
        if (!$lot) return redirect()->to('kacha')->with('error', 'Lot not found.');
        return view('kacha/form', ['title' => 'Edit Kacha Lot', 'lot' => $lot]);
    }

    public function update($id)
    {
        $lot = $this->db->query('SELECT * FROM kacha_lot WHERE id = ?', [$id])->getRowArray();
        if (!$lot) return redirect()->to('kacha')->with('error', 'Lot not found.');
        if ($lot['status'] === 'used') return redirect()->to('kacha')->with('error', 'Cannot edit a used lot.');

        $lotNum = trim($this->request->getPost('lot_number'));
        $dup = $this->db->query('SELECT id FROM kacha_lot WHERE lot_number = ? AND id != ?', [$lotNum, $id])->getRowArray();
        if ($dup) return redirect()->back()->withInput()->with('error', "Lot number '$lotNum' already exists.");

        $this->db->query(
            'UPDATE kacha_lot SET lot_number=?, weight=?, touch_pct=?, receipt_date=?, party=?, source_type=?, test_touch=?, test_number=?, notes=? WHERE id=?',
            [
                $lotNum,
                (float)$this->request->getPost('weight'),
                (float)$this->request->getPost('touch_pct'),
                $this->request->getPost('receipt_date') ?: null,
                $this->request->getPost('party') ?: null,
                $this->request->getPost('source_type') ?: 'purchase',
                $this->request->getPost('test_touch') !== '' ? (float)$this->request->getPost('test_touch') : null,
                $this->request->getPost('test_number') ?: null,
                $this->request->getPost('notes') ?: null,
                $id,
            ]
        );
        return redirect()->to('kacha')->with('success', "Lot '$lotNum' updated.");
    }

    public function delete($id)
    {
        $lot = $this->db->query('SELECT * FROM kacha_lot WHERE id = ?', [$id])->getRowArray();
        if (!$lot) return redirect()->to('kacha')->with('error', 'Lot not found.');
        if ($lot['status'] === 'used') return redirect()->to('kacha')->with('error', 'Cannot delete a used lot.');
        $this->db->query('DELETE FROM kacha_lot WHERE id = ?', [$id]);
        return redirect()->to('kacha')->with('success', 'Lot deleted.');
    }

    public function listAvailable()
    {
        $q = $this->request->getPost('q') ?? '';
        $sql = 'SELECT id, lot_number, receipt_date, weight, touch_pct, fine, party FROM kacha_lot WHERE status = "available"';
        $params = [];
        if ($q) { $sql .= ' AND (lot_number LIKE ? OR party LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
        $sql .= ' ORDER BY receipt_date DESC, lot_number';
        $lots = $this->db->query($sql, $params)->getResultArray();
        return $this->response->setJSON($lots);
    }

    // ── IMPORT ──────────────────────────────────────────────────────────
    public function importForm()
    {
        return view('kacha/import_form', ['title' => 'Import Kacha Lots']);
    }

    public function importSample()
    {
        $csv  = "Lot Number,Weight (g),Touch %,Receipt Date,Party,Source Type,Test Touch,Test Number,Notes\n";
        $csv .= "LOT-001,500.000,92.50,2024-01-15,ABC Traders,purchase,92.30,T-001,First lot\n";
        $csv .= "LOT-002,750.000,91.80,2024-01-16,XYZ Gold,internal,,,\n";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="kacha_lot_sample.csv"');
        echo $csv;
        exit;
    }

    public function importPreview()
    {
        $file = $this->request->getFile('import_file');
        if (!$file || !$file->isValid()) {
            return redirect()->to('kacha/import')->with('error', 'Please upload a valid file');
        }
        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['csv', 'xlsx'])) {
            return redirect()->to('kacha/import')->with('error', 'Only CSV and XLSX files are supported');
        }

        try {
            $tmpPath = $file->getTempName();

            $existLots = array_column(
                $this->db->query('SELECT lot_number FROM kacha_lot')->getResultArray(),
                'lot_number'
            );
            $existLotSet = array_flip($existLots);

            $allowedSources = ['purchase', 'internal', 'part_order', 'melt_job'];

            require_once ROOTPATH . 'vendor/autoload.php';
            if ($ext === 'csv') {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
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

            $preview    = [];
            $seenInFile = [];

            foreach ($rows as $i => $row) {
                if ($i === 0) continue; // skip header

                $lotNumber   = trim((string)($row[0] ?? ''));
                $weight      = (float)($row[1] ?? 0);
                $touchPct    = (float)($row[2] ?? 0);
                $receiptDate = trim((string)($row[3] ?? ''));
                $party       = trim((string)($row[4] ?? ''));
                $sourceType  = strtolower(trim((string)($row[5] ?? '')));
                $testTouch   = trim((string)($row[6] ?? ''));
                $testNumber  = trim((string)($row[7] ?? ''));
                $notes       = trim((string)($row[8] ?? ''));

                // Handle XLSX numeric dates
                if ($ext === 'xlsx' && is_numeric($receiptDate) && (float)$receiptDate > 0) {
                    try {
                        $receiptDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$receiptDate)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $receiptDate = '';
                    }
                }
                if (!$receiptDate) $receiptDate = date('Y-m-d');

                if (!in_array($sourceType, $allowedSources)) $sourceType = 'purchase';

                $fine = $weight > 0 && $touchPct > 0 ? round($weight * $touchPct / 100, 4) : 0;

                $base = compact('lotNumber', 'weight', 'touchPct', 'receiptDate', 'party', 'sourceType', 'testTouch', 'testNumber', 'notes', 'fine');

                if (!$lotNumber || $weight <= 0 || $touchPct <= 0) {
                    $preview[] = $base + ['status' => 'error', 'reason' => 'Missing required field (lot number, weight > 0, touch > 0)'];
                    continue;
                }
                if (isset($existLotSet[$lotNumber])) {
                    $preview[] = $base + ['status' => 'duplicate', 'reason' => 'Lot number already exists in database'];
                    continue;
                }
                if (isset($seenInFile[$lotNumber])) {
                    $preview[] = $base + ['status' => 'duplicate', 'reason' => 'Duplicate within file'];
                    continue;
                }

                $seenInFile[$lotNumber] = true;
                $preview[] = $base + ['status' => 'ready', 'reason' => ''];
            }

            session()->set('kacha_import_preview', $preview);
            return view('kacha/import_preview', ['title' => 'Import Preview', 'rows' => $preview]);

        } catch (\Throwable $e) {
            return redirect()->to('kacha/import')->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function importConfirm()
    {
        $rows = session()->get('kacha_import_preview');
        if (!$rows) return redirect()->to('kacha/import')->with('error', 'Session expired. Please re-upload.');

        $include  = $this->request->getPost('include') ?? [];
        $imported = 0;
        $now      = date('Y-m-d H:i:s');

        foreach ($rows as $i => $row) {
            if ($row['status'] !== 'ready') continue;
            if (!isset($include[$i])) continue;

            $this->db->query(
                'INSERT INTO kacha_lot (lot_number, weight, touch_pct, receipt_date, party, source_type, test_touch, test_number, notes, created_by, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $row['lotNumber'],
                    $row['weight'],
                    $row['touchPct'],
                    $row['receiptDate'] ?: null,
                    $row['party'] ?: null,
                    $row['sourceType'] ?: 'purchase',
                    $row['testTouch'] !== '' ? (float)$row['testTouch'] : null,
                    $row['testNumber'] ?: null,
                    $row['notes'] ?: null,
                    $this->currentUser(),
                    $now,
                ]
            );
            $imported++;
        }

        session()->remove('kacha_import_preview');
        return redirect()->to('kacha')->with('success', "{$imported} lot(s) imported successfully.");
    }
}
