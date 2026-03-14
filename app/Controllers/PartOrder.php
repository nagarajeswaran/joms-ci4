<?php
namespace App\Controllers;

class PartOrder extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $karigarFilter = $this->request->getGet('karigar') ?? '';
        $statusFilter  = $this->request->getGet('status') ?? '';

        $builder = $db->table('part_order po')
            ->select('po.*, k.name as karigar_name')
            ->join('karigar k', 'k.id = po.karigar_id', 'left')
            ->orderBy('po.created_at', 'DESC');
        if ($karigarFilter) $builder->where('po.karigar_id', $karigarFilter);
        if ($statusFilter)  $builder->where('po.status', $statusFilter);

        $items    = $builder->get()->getResultArray();
        $karigars = $db->query('SELECT id, name FROM karigar ORDER BY name')->getResultArray();

        return view('part_orders/index', [
            'title'         => 'Part Orders (PARTORD)',
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
        $orders   = $db->query('SELECT id, order_number, title FROM orders ORDER BY id DESC LIMIT 50')->getResultArray();
        return view('part_orders/form', [
            'title'    => 'Create Part Order',
            'karigars' => $karigars,
            'orders'   => $orders,
            'nextNum'  => $this->_nextOrderNumber(),
        ]);
    }

    public function store()
    {
        $db        = \Config\Database::connect();
        $karigarId = $this->request->getPost('karigar_id');
        $karigar   = $db->query('SELECT * FROM karigar WHERE id = ?', [$karigarId])->getRowArray();

        $db->table('part_order')->insert([
            'order_number'     => $this->_nextOrderNumber(),
            'karigar_id'       => $karigarId,
            'client_order_id'  => $this->request->getPost('client_order_id') ?: null,
            'status'           => 'draft',
            'cash_rate_per_kg' => $this->request->getPost('cash_rate_per_kg') ?? ($karigar['default_cash_rate'] ?? 0),
            'fine_pct'         => $this->request->getPost('fine_pct') ?? ($karigar['default_fine_pct'] ?? 0),
            'notes'            => $this->request->getPost('notes'),
        ]);
        return redirect()->to('part-orders/view/' . $db->insertID())->with('success', 'Part order created');
    }

    public function view($id)
    {
        $db  = \Config\Database::connect();
        $po  = $db->query('SELECT po.*, k.name as karigar_name, d.name as dept_name FROM part_order po LEFT JOIN karigar k ON k.id = po.karigar_id LEFT JOIN department d ON d.id = k.department_id WHERE po.id = ?', [$id])->getRowArray();
        if (!$po) return redirect()->to('part-orders')->with('error', 'Not found');

        $issues   = $db->query('SELECT poi.*, gs.touch_pct as gatti_touch, mj.job_number, s.name as stamp_name FROM part_order_issue poi LEFT JOIN gatti_stock gs ON gs.id = poi.gatti_stock_id LEFT JOIN melt_job mj ON mj.id = gs.melt_job_id LEFT JOIN stamp s ON s.id = poi.stamp_id WHERE poi.part_order_id = ?', [$id])->getResultArray();
        $receives = $db->query('SELECT por.*, p.name as part_name, bt.name as byprod_name FROM part_order_receive por LEFT JOIN part p ON p.id = por.part_id LEFT JOIN byproduct_type bt ON bt.id = por.byproduct_type_id WHERE por.part_order_id = ?', [$id])->getResultArray();

        $totalIssuedFine   = 0;
        foreach ($issues as $i) {
            $totalIssuedFine += $i['weight_g'] * $i['touch_pct'] / 100;
        }

        $totalRecvFine    = 0;
        $totalPartsWeight = 0;
        foreach ($receives as $r) {
            $totalRecvFine += $r['weight_g'] * $r['touch_pct'] / 100;
            if ($r['receive_type'] === 'part') $totalPartsWeight += $r['weight_g'];
        }

        $fineDiff = $totalIssuedFine - $totalRecvFine;
        $mcFine   = $totalPartsWeight * $po['fine_pct'] / 100;
        $mcCash   = $totalPartsWeight / 1000 * $po['cash_rate_per_kg'];
        $netFine  = $fineDiff - $mcFine;

        $gattiStock = $db->query('SELECT gs.id, gs.weight_g, gs.touch_pct, gs.qty_issued_g, mj.job_number FROM gatti_stock gs LEFT JOIN melt_job mj ON mj.id = gs.melt_job_id WHERE (gs.weight_g - gs.qty_issued_g) > 0 ORDER BY gs.created_at DESC')->getResultArray();
        $stamps     = $db->query('SELECT id, name FROM stamp ORDER BY name')->getResultArray();
        $parts      = $db->query('SELECT id, name FROM part ORDER BY name')->getResultArray();
        $byprods    = $db->query('SELECT id, name FROM byproduct_type ORDER BY name')->getResultArray();

        return view('part_orders/view', [
            'title'             => $po['order_number'],
            'po'                => $po,
            'issues'            => $issues,
            'receives'          => $receives,
            'gattiStock'        => $gattiStock,
            'stamps'            => $stamps,
            'parts'             => $parts,
            'byprods'           => $byprods,
            'totalIssuedFine'   => $totalIssuedFine,
            'totalRecvFine'     => $totalRecvFine,
            'totalPartsWeight'  => $totalPartsWeight,
            'fineDiff'          => $fineDiff,
            'mcFine'            => $mcFine,
            'mcCash'            => $mcCash,
            'netFine'           => $netFine,
        ]);
    }

    public function addIssue($id)
    {
        $db  = \Config\Database::connect();
        $po  = $db->query('SELECT * FROM part_order WHERE id = ?', [$id])->getRowArray();
        if (!$po || $po['status'] === 'posted') return redirect()->to('part-orders/view/'.$id)->with('error', 'Cannot modify');

        $gattiId = $this->request->getPost('gatti_stock_id');
        $weight  = (float)$this->request->getPost('weight_g');
        $gatti   = $db->query('SELECT * FROM gatti_stock WHERE id = ?', [$gattiId])->getRowArray();

        if (!$gatti) return redirect()->to('part-orders/view/'.$id)->with('error', 'Gatti not found');

        $available = $gatti['weight_g'] - $gatti['qty_issued_g'];
        if ($weight > $available) return redirect()->to('part-orders/view/'.$id)->with('error', 'Not enough gatti available. Available: '.$available.'g');

        $db->table('part_order_issue')->insert([
            'part_order_id'  => $id,
            'gatti_stock_id' => $gattiId,
            'weight_g'       => $weight,
            'touch_pct'      => $gatti['touch_pct'],
            'stamp_id'       => $this->request->getPost('stamp_id') ?: null,
            'issued_at'      => date('Y-m-d H:i:s'),
        ]);

        $db->query('UPDATE gatti_stock SET qty_issued_g = qty_issued_g + ? WHERE id = ?', [$weight, $gattiId]);
        return redirect()->to('part-orders/view/'.$id)->with('success', 'Issue added');
    }

    public function deleteIssue($issueId)
    {
        $db    = \Config\Database::connect();
        $issue = $db->query('SELECT * FROM part_order_issue WHERE id = ?', [$issueId])->getRowArray();
        if ($issue) {
            $po = $db->query('SELECT * FROM part_order WHERE id = ?', [$issue['part_order_id']])->getRowArray();
            if ($po && $po['status'] !== 'posted') {
                $db->query('UPDATE gatti_stock SET qty_issued_g = qty_issued_g - ? WHERE id = ?', [$issue['weight_g'], $issue['gatti_stock_id']]);
                $db->table('part_order_issue')->where('id', $issueId)->delete();
            }
        }
        return redirect()->to('part-orders/view/'.($issue['part_order_id'] ?? 0))->with('success', 'Issue deleted');
    }

    public function addReceive($id)
    {
        $db  = \Config\Database::connect();
        $po  = $db->query('SELECT * FROM part_order WHERE id = ?', [$id])->getRowArray();
        if (!$po || $po['status'] === 'posted') return redirect()->to('part-orders/view/'.$id)->with('error', 'Cannot modify');

        $receiveType = $this->request->getPost('receive_type');
        $weight      = (float)$this->request->getPost('weight_g');
        $pcWeight    = (float)($this->request->getPost('piece_weight_g') ?: 0);
        $touch       = (float)$this->request->getPost('touch_pct');
        $batchNo     = trim($this->request->getPost('batch_number') ?? '');

        // Get touch from gatti issues if not provided
        if (!$touch) {
            $issue = $db->query('SELECT poi.touch_pct FROM part_order_issue poi WHERE poi.part_order_id = ? LIMIT 1', [$id])->getRowArray();
            $touch = $issue['touch_pct'] ?? 0;
        }

        $qty        = 0;
        $partBatchId = null;

        if ($receiveType === 'part') {
            $partId = $this->request->getPost('part_id');
            if ($pcWeight > 0) $qty = (int)round($weight / $pcWeight);

            // Find or create batch
            if ($batchNo) {
                $batch = $db->query('SELECT * FROM part_batch WHERE batch_number = ?', [$batchNo])->getRowArray();
                if ($batch) {
                    $partBatchId = $batch['id'];
                    $db->table('part_batch')->where('id', $batch['id'])->update([
                        'piece_weight_g'      => $pcWeight ?: $batch['piece_weight_g'],
                        'touch_pct'           => $touch,
                        'qty_in_stock'        => $batch['qty_in_stock'] + $qty,
                        'source_part_order_id'=> $id,
                        'received_at'         => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $db->table('part_batch')->insert([
                        'batch_number'        => $batchNo,
                        'part_id'             => $partId,
                        'piece_weight_g'      => $pcWeight,
                        'touch_pct'           => $touch,
                        'qty_in_stock'        => $qty,
                        'source_part_order_id'=> $id,
                        'received_at'         => date('Y-m-d H:i:s'),
                    ]);
                    $partBatchId = $db->insertID();
                }
            }

            $db->table('part_order_receive')->insert([
                'part_order_id'   => $id,
                'receive_type'    => 'part',
                'part_id'         => $partId,
                'batch_number'    => $batchNo,
                'part_batch_id'   => $partBatchId,
                'weight_g'        => $weight,
                'piece_weight_g'  => $pcWeight,
                'qty'             => $qty,
                'touch_pct'       => $touch,
                'received_at'     => date('Y-m-d H:i:s'),
            ]);
        } else {
            // byproduct
            $byprodTypeId = $this->request->getPost('byproduct_type_id');
            $db->table('part_order_receive')->insert([
                'part_order_id'     => $id,
                'receive_type'      => 'byproduct',
                'byproduct_type_id' => $byprodTypeId,
                'weight_g'          => $weight,
                'touch_pct'         => $touch,
                'received_at'       => date('Y-m-d H:i:s'),
            ]);
            $db->table('byproduct_stock')->insert([
                'byproduct_type_id' => $byprodTypeId,
                'weight_g'          => $weight,
                'touch_pct'         => $touch,
                'source_job_type'   => 'partorder',
                'source_job_id'     => $id,
            ]);
        }

        return redirect()->to('part-orders/view/'.$id)->with('success', 'Receive row added');
    }

    public function deleteReceive($recvId)
    {
        $db   = \Config\Database::connect();
        $recv = $db->query('SELECT * FROM part_order_receive WHERE id = ?', [$recvId])->getRowArray();
        if ($recv) {
            $po = $db->query('SELECT * FROM part_order WHERE id = ?', [$recv['part_order_id']])->getRowArray();
            if ($po && $po['status'] !== 'posted') {
                if ($recv['part_batch_id'] && $recv['qty']) {
                    $db->query('UPDATE part_batch SET qty_in_stock = GREATEST(0, qty_in_stock - ?) WHERE id = ?', [$recv['qty'], $recv['part_batch_id']]);
                }
                $db->table('part_order_receive')->where('id', $recvId)->delete();
            }
        }
        return redirect()->to('part-orders/view/'.($recv['part_order_id'] ?? 0))->with('success', 'Row deleted');
    }

    public function post($id)
    {
        $db  = \Config\Database::connect();
        $po  = $db->query('SELECT po.*, k.name as karigar_name FROM part_order po LEFT JOIN karigar k ON k.id = po.karigar_id WHERE po.id = ?', [$id])->getRowArray();
        if (!$po || $po['status'] === 'posted') return redirect()->to('part-orders/view/'.$id)->with('error', 'Already posted');

        $issues   = $db->query('SELECT * FROM part_order_issue WHERE part_order_id = ?', [$id])->getResultArray();
        $receives = $db->query('SELECT * FROM part_order_receive WHERE part_order_id = ?', [$id])->getResultArray();

        $totalIssuedFine  = 0;
        $stampNames = [];
        foreach ($issues as $i) {
            $totalIssuedFine += $i['weight_g'] * $i['touch_pct'] / 100;
            if ($i['stamp_id']) {
                $s = $db->query('SELECT name FROM stamp WHERE id = ?', [$i['stamp_id']])->getRowArray();
                if ($s) $stampNames[] = $s['name'];
            }
        }

        $totalRecvFine    = 0;
        $totalPartsWeight = 0;
        $partNames = [];
        foreach ($receives as $r) {
            $totalRecvFine += $r['weight_g'] * $r['touch_pct'] / 100;
            if ($r['receive_type'] === 'part') {
                $totalPartsWeight += $r['weight_g'];
                if ($r['part_id']) {
                    $p = $db->query('SELECT name FROM part WHERE id = ?', [$r['part_id']])->getRowArray();
                    if ($p) $partNames[] = $p['name'].($r['batch_number'] ? ' ('.$r['batch_number'].')' : '');
                }
            }
        }

        $fineDiff = $totalIssuedFine - $totalRecvFine;
        $mcFine   = $totalPartsWeight * $po['fine_pct'] / 100;
        $mcCash   = $totalPartsWeight / 1000 * $po['cash_rate_per_kg'];
        $netFine  = $fineDiff - $mcFine;

        $narration = $po['order_number'].' | Karigar: '.$po['karigar_name'];
        if ($stampNames) $narration .= ' | Stamps: '.implode(', ', array_unique($stampNames));
        if ($partNames)  $narration .= ' | Parts: '.implode(', ', $partNames);
        $narration .= ' | Net fine owed: '.round($netFine,4).'g';

        if ($netFine != 0) {
            $db->table('karigar_ledger')->insert([
                'karigar_id'   => $po['karigar_id'],
                'source_type'  => 'part_order',
                'source_id'    => $id,
                'account_type' => 'fine',
                'direction'    => $netFine > 0 ? 'debit' : 'credit',
                'amount'       => round(abs($netFine), 4),
                'narration'    => $narration,
            ]);
        }

        if ($mcCash > 0) {
            $db->table('karigar_ledger')->insert([
                'karigar_id'   => $po['karigar_id'],
                'source_type'  => 'part_order',
                'source_id'    => $id,
                'account_type' => 'cash',
                'direction'    => 'credit',
                'amount'       => round($mcCash, 2),
                'narration'    => $narration.' | Cash making charge',
            ]);
        }

        $db->table('part_order')->where('id', $id)->update(['status' => 'posted']);
        return redirect()->to('part-orders/view/'.$id)->with('success', 'Part order posted to ledger');
    }

    private function _nextOrderNumber()
    {
        $db  = \Config\Database::connect();
        $row = $db->query('SELECT COUNT(*) as cnt FROM part_order')->getRowArray();
        return 'PARTORD-' . str_pad(($row['cnt'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    }
}
