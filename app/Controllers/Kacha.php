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
                'INSERT INTO kacha_lot (lot_number, weight, touch_pct, receipt_date, party, source_type, test_touch, test_number, notes, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?)',
                [
                    $lotNum, $weight, $touch,
                    $dates[$i] ?: null,
                    $parties[$i] ?: null,
                    $sources[$i] ?: 'purchase',
                    $testTouches[$i] !== '' ? (float)$testTouches[$i] : null,
                    $testNums[$i] ?: null,
                    $notesList[$i] ?: null,
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
}
