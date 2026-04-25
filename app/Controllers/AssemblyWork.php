<?php
namespace App\Controllers;

class AssemblyWork extends BaseController
{
    protected $db;

    private const REQUIRED_TABLES = [
        'finished_goods_master',
        'assembly_work',
        'assembly_work_order',
        'assembly_work_issue',
        'assembly_work_receive',
        'assembly_work_summary',
    ];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $karigarFilter = $this->request->getGet('karigar') ?? '';
        $statusFilter  = $this->request->getGet('status') ?? '';

        $builder = $this->db->table('assembly_work aw')
            ->select('aw.*, k.name as karigar_name')
            ->join('karigar k', 'k.id = aw.karigar_id', 'left')
            ->orderBy('aw.created_at', 'DESC');

        if ($karigarFilter) {
            $builder->where('aw.karigar_id', $karigarFilter);
        }
        if ($statusFilter) {
            $builder->where('aw.status', $statusFilter);
        }

        $items    = $builder->get()->getResultArray();
        $karigars = $this->db->query('SELECT id, name FROM karigar ORDER BY name')->getResultArray();

        return view('assembly_work/index', [
            'title'         => 'Assembly Work',
            'items'         => $items,
            'karigars'      => $karigars,
            'karigarFilter' => $karigarFilter,
            'statusFilter'  => $statusFilter,
        ]);
    }

    public function create()
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $karigars = $this->db->query('
            SELECT k.id, k.name, k.department_id, d.name as dept_name
            FROM karigar k
            LEFT JOIN department d ON d.id = k.department_id
            ORDER BY d.name, k.name
        ')->getResultArray();
        $departments = $this->db->query('SELECT id, name FROM department ORDER BY name')->getResultArray();
        $orders = $this->db->query('
            SELECT o.id, o.order_number, o.title, c.name as client_name
            FROM orders o
            LEFT JOIN client c ON c.id = o.client_id
            ORDER BY o.id DESC
            LIMIT 100
        ')->getResultArray();

        return view('assembly_work/form', [
            'title'       => 'Create Assembly Work',
            'karigars'    => $karigars,
            'departments' => $departments,
            'orders'      => $orders,
            'nextNum'     => $this->_nextWorkNumber(),
        ]);
    }

    public function store()
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $karigarId = (int)$this->request->getPost('karigar_id');
        if ($karigarId <= 0) {
            return redirect()->to('assembly-work/create')->with('error', 'Karigar is required');
        }

        $this->db->table('assembly_work')->insert([
            'work_number'       => $this->_nextWorkNumber(),
            'karigar_id'        => $karigarId,
            'status'            => 'draft',
            'notes'             => $this->request->getPost('notes') ?: null,
            'making_charge_cash'=> 0,
            'making_charge_fine'=> 0,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
        $workId = $this->db->insertID();

        $orderIds = $this->request->getPost('order_ids') ?? [];
        foreach ($orderIds as $orderId) {
            $orderId = (int)$orderId;
            if ($orderId > 0) {
                $this->db->table('assembly_work_order')->insert([
                    'assembly_work_id' => $workId,
                    'order_id'         => $orderId,
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
            }
        }

        if (!empty($orderIds)) {
            $this->db->table('assembly_work')->where('id', $workId)->update([
                'status'     => 'in_progress',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->to('assembly-work/view/'.$workId)->with('success', 'Assembly work created');
    }

    public function view($id)
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $work = $this->db->query('
            SELECT aw.*, k.name as karigar_name, d.name as dept_name
            FROM assembly_work aw
            LEFT JOIN karigar k ON k.id = aw.karigar_id
            LEFT JOIN department d ON d.id = k.department_id
            WHERE aw.id = ?
        ', [$id])->getRowArray();
        if (!$work) {
            return redirect()->to('assembly-work')->with('error', 'Assembly work not found');
        }

        $linkedOrders = $this->db->query('
            SELECT awo.*, o.order_number, o.title, c.name as client_name
            FROM assembly_work_order awo
            LEFT JOIN orders o ON o.id = awo.order_id
            LEFT JOIN client c ON c.id = o.client_id
            WHERE awo.assembly_work_id = ?
            ORDER BY awo.id
        ', [$id])->getResultArray();

        $issues = $this->db->query('
            SELECT awi.*, p.name as part_name, p.tamil_name as part_tamil, s.name as stamp_name, pb.batch_number, pb.weight_in_stock_g as current_stock_g
            FROM assembly_work_issue awi
            LEFT JOIN part p ON p.id = awi.part_id
            LEFT JOIN stamp s ON s.id = awi.stamp_id
            LEFT JOIN part_batch pb ON pb.id = awi.part_batch_id
            WHERE awi.assembly_work_id = ?
            ORDER BY awi.issued_at DESC, awi.id DESC
        ', [$id])->getResultArray();

        $receives = $this->db->query('
            SELECT awr.*, p.name as part_name, p.tamil_name as part_tamil, fg.name as finished_goods_name, fg.tamil_name as finished_goods_tamil,
                   s.name as stamp_name, bt.name as byproduct_name, kl.lot_number as kacha_name
            FROM assembly_work_receive awr
            LEFT JOIN part p ON p.id = awr.part_id
            LEFT JOIN finished_goods_master fg ON fg.id = awr.finished_goods_id
            LEFT JOIN stamp s ON s.id = awr.stamp_id
            LEFT JOIN byproduct_type bt ON bt.id = awr.byproduct_type_id
            LEFT JOIN kacha_lot kl ON kl.id = awr.kacha_lot_id
            WHERE awr.assembly_work_id = ?
            ORDER BY awr.received_at DESC, awr.id DESC
        ', [$id])->getResultArray();

        $summaryRows = $this->db->query('
            SELECT aws.*, dg.name as group_name
            FROM assembly_work_summary aws
            LEFT JOIN department_group dg ON dg.id = aws.department_group_id
            WHERE aws.assembly_work_id = ?
            ORDER BY dg.name
        ', [$id])->getResultArray();

        $orders = $this->db->query('
            SELECT o.id, o.order_number, o.title, c.name as client_name
            FROM orders o
            LEFT JOIN client c ON c.id = o.client_id
            WHERE o.id NOT IN (
                SELECT order_id FROM assembly_work_order WHERE assembly_work_id = ?
            )
            ORDER BY o.id DESC
            LIMIT 100
        ', [$id])->getResultArray();

        $partBatches = $this->db->query('
            SELECT pb.id, pb.batch_number, pb.part_id, pb.stamp_id, pb.piece_weight_g, pb.touch_pct, pb.qty_in_stock, pb.weight_in_stock_g,
                   p.name as part_name, p.tamil_name as part_tamil, s.name as stamp_name
            FROM part_batch pb
            LEFT JOIN part p ON p.id = pb.part_id
            LEFT JOIN stamp s ON s.id = pb.stamp_id
            WHERE pb.weight_in_stock_g > 0
            ORDER BY p.name, pb.batch_number
        ')->getResultArray();

        $parts = $this->db->query('SELECT id, name, tamil_name, weight FROM part ORDER BY name')->getResultArray();
        $finishedGoods = $this->db->query('SELECT id, name, tamil_name FROM finished_goods_master ORDER BY name')->getResultArray();
        $stamps = $this->db->query('SELECT id, name FROM stamp ORDER BY name')->getResultArray();
        $byproducts = $this->db->query('SELECT id, name FROM byproduct_type ORDER BY name')->getResultArray();

        $requirementRows = $this->_getRequirementSummary($id);
        $deptSummary = $this->_getDepartmentSummary($id, $summaryRows);

        $totalIssuedWeight = array_sum(array_map(fn($row) => (float)$row['weight_g'], $issues));
        $totalIssuedPcs    = array_sum(array_map(fn($row) => (float)$row['pcs'], $issues));
        $totalRecvWeight   = array_sum(array_map(fn($row) => (float)$row['weight_g'], $receives));
        $finishedRows      = array_filter($receives, fn($row) => $row['receive_type'] === 'finished_good');
        $totalFinishedWeight = array_sum(array_map(fn($row) => (float)$row['weight_g'], $finishedRows));
        $totalFinishedPcs    = array_sum(array_map(fn($row) => (float)$row['pcs'], $finishedRows));

        return view('assembly_work/view', [
            'title'               => $work['work_number'],
            'work'                => $work,
            'linkedOrders'        => $linkedOrders,
            'issues'              => $issues,
            'receives'            => $receives,
            'summaryRows'         => $summaryRows,
            'orders'              => $orders,
            'partBatches'         => $partBatches,
            'parts'               => $parts,
            'finishedGoods'       => $finishedGoods,
            'stamps'              => $stamps,
            'byproducts'          => $byproducts,
            'requirements'        => $requirementRows,
            'departmentSummary'   => $deptSummary,
            'totalIssuedWeight'   => $totalIssuedWeight,
            'totalIssuedPcs'      => $totalIssuedPcs,
            'totalRecvWeight'     => $totalRecvWeight,
            'totalFinishedWeight' => $totalFinishedWeight,
            'totalFinishedPcs'    => $totalFinishedPcs,
        ]);
    }

    public function addOrder($id)
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $work = $this->_getWork($id);
        if (!$work || in_array($work['status'], ['finished', 'completed'], true)) {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Cannot modify linked orders');
        }

        $orderId = (int)$this->request->getPost('order_id');
        if ($orderId <= 0) {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Order is required');
        }

        $exists = $this->db->query('SELECT id FROM assembly_work_order WHERE assembly_work_id = ? AND order_id = ?', [$id, $orderId])->getRowArray();
        if (!$exists) {
            $this->db->table('assembly_work_order')->insert([
                'assembly_work_id' => $id,
                'order_id'         => $orderId,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        $this->_touchInProgress($id);

        return redirect()->to('assembly-work/view/'.$id)->with('success', 'Order linked');
    }

    public function removeOrder($id, $linkId)
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $work = $this->_getWork($id);
        if (!$work || in_array($work['status'], ['finished', 'completed'], true)) {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Cannot modify linked orders');
        }

        $this->db->table('assembly_work_order')->where('id', $linkId)->where('assembly_work_id', $id)->delete();
        $this->_touchInProgress($id);

        return redirect()->to('assembly-work/view/'.$id)->with('success', 'Order removed');
    }

    public function addIssue($id)
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $work = $this->_getWork($id);
        if (!$work || in_array($work['status'], ['finished', 'completed'], true)) {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Cannot add issue');
        }

        $partBatchId = (int)$this->request->getPost('part_batch_id');
        $weightG     = (float)$this->request->getPost('weight_g');

        if ($partBatchId <= 0 || $weightG <= 0) {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Batch and weight are required');
        }

        $batch = $this->db->query('SELECT * FROM part_batch WHERE id = ?', [$partBatchId])->getRowArray();
        if (!$batch) {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Part batch not found');
        }
        if ($weightG > (float)$batch['weight_in_stock_g']) {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Not enough stock. Available: '.number_format($batch['weight_in_stock_g'], 4).'g');
        }

        $pieceWeight = (float)($batch['piece_weight_g'] ?? 0);
        $pcs = $pieceWeight > 0 ? round($weightG / $pieceWeight, 4) : 0;
        $enteredBy = $this->_currentUser();

        $this->db->table('assembly_work_issue')->insert([
            'assembly_work_id' => $id,
            'part_batch_id'    => $partBatchId,
            'part_id'          => $batch['part_id'],
            'stamp_id'         => $batch['stamp_id'],
            'weight_g'         => $weightG,
            'piece_weight_g'   => $pieceWeight ?: null,
            'pcs'              => $pcs,
            'touch_pct'        => (float)($batch['touch_pct'] ?? 0),
            'created_by_user_id' => $enteredBy['id'],
            'created_by_username' => $enteredBy['username'],
            'notes'            => $this->request->getPost('notes') ?: null,
            'issued_at'        => date('Y-m-d H:i:s'),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->db->query('UPDATE part_batch SET weight_in_stock_g = GREATEST(0, weight_in_stock_g - ?) WHERE id = ?', [$weightG, $partBatchId]);
        $this->_touchInProgress($id);

        return redirect()->to('assembly-work/view/'.$id)->with('success', 'Issue added');
    }

    public function deleteIssue($issueId)
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $issue = $this->db->query('SELECT * FROM assembly_work_issue WHERE id = ?', [$issueId])->getRowArray();
        if (!$issue) {
            return redirect()->to('assembly-work')->with('error', 'Issue not found');
        }

        $work = $this->_getWork($issue['assembly_work_id']);
        if (!$work || in_array($work['status'], ['finished', 'completed'], true)) {
            return redirect()->to('assembly-work/view/'.$issue['assembly_work_id'])->with('error', 'Cannot delete issue');
        }

        $this->db->query('UPDATE part_batch SET weight_in_stock_g = weight_in_stock_g + ? WHERE id = ?', [(float)$issue['weight_g'], $issue['part_batch_id']]);
        $this->db->table('assembly_work_issue')->where('id', $issueId)->delete();
        $this->_touchInProgress($issue['assembly_work_id']);

        return redirect()->to('assembly-work/view/'.$issue['assembly_work_id'])->with('success', 'Issue deleted');
    }

    public function updateIssue($issueId)
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $issue = $this->db->query('SELECT * FROM assembly_work_issue WHERE id = ?', [$issueId])->getRowArray();
        if (!$issue) {
            return redirect()->to('assembly-work')->with('error', 'Issue not found');
        }

        $work = $this->_getWork($issue['assembly_work_id']);
        if (!$work || in_array($work['status'], ['finished', 'completed'], true)) {
            return redirect()->to('assembly-work/view/'.$issue['assembly_work_id'])->with('error', 'Cannot update issue');
        }

        $newWeight = (float)$this->request->getPost('weight_g');
        if ($newWeight <= 0) {
            return redirect()->to('assembly-work/view/'.$issue['assembly_work_id'])->with('error', 'Weight must be greater than zero');
        }

        $batch = $this->db->query('SELECT * FROM part_batch WHERE id = ?', [$issue['part_batch_id']])->getRowArray();
        if (!$batch) {
            return redirect()->to('assembly-work/view/'.$issue['assembly_work_id'])->with('error', 'Part batch not found');
        }

        $oldWeight = (float)$issue['weight_g'];
        $available = (float)$batch['weight_in_stock_g'] + $oldWeight;
        if ($newWeight > $available) {
            return redirect()->to('assembly-work/view/'.$issue['assembly_work_id'])->with('error', 'Not enough stock. Available: '.number_format($available, 4).'g');
        }

        $pieceWeight = (float)($issue['piece_weight_g'] ?? $batch['piece_weight_g'] ?? 0);
        $pcs = $pieceWeight > 0 ? round($newWeight / $pieceWeight, 4) : 0;

        $this->db->query(
            'UPDATE part_batch SET weight_in_stock_g = GREATEST(0, weight_in_stock_g + ? - ?) WHERE id = ?',
            [$oldWeight, $newWeight, $issue['part_batch_id']]
        );
        $this->db->table('assembly_work_issue')->where('id', $issueId)->update([
            'weight_g'   => $newWeight,
            'pcs'        => $pcs,
            'notes'      => $this->request->getPost('notes') ?: null,
            'issued_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->_touchInProgress($issue['assembly_work_id']);

        return redirect()->to('assembly-work/view/'.$issue['assembly_work_id'])->with('success', 'Issue updated');
    }

    public function addReceive($id)
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $work = $this->_getWork($id);
        if (!$work || $work['status'] === 'completed') {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Cannot add receive');
        }

        $receiveType = $this->request->getPost('receive_type');
        $weightG     = (float)$this->request->getPost('weight_g');
        $pieceWeight = (float)($this->request->getPost('piece_weight_g') ?: 0);
        $batchNumber = trim($this->request->getPost('batch_number') ?? '');
        $touchPct    = (float)($this->request->getPost('touch_pct') ?? 0);
        $pcs         = $pieceWeight > 0 ? round($weightG / $pieceWeight, 4) : (float)($this->request->getPost('pcs') ?: 0);
        $enteredBy   = $this->_currentUser();

        if ($weightG <= 0 || $batchNumber === '') {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Batch number and weight are required');
        }

        $partId = null;
        $finishedGoodsId = null;
        $byproductTypeId = null;
        $kachaLotId = null;
        $partBatchId = null;

        if ($receiveType === 'finished_good') {
            $finishedGoodsId = (int)$this->request->getPost('finished_goods_id');
            if ($finishedGoodsId <= 0) {
                return redirect()->to('assembly-work/view/'.$id)->with('error', 'Finished good is required');
            }
            $pcs = null;
            $pieceWeight = 0;
        } elseif ($receiveType === 'returned_part') {
            $partId = (int)$this->request->getPost('part_id');
            if ($partId <= 0) {
                return redirect()->to('assembly-work/view/'.$id)->with('error', 'Part is required');
            }

            $exists = $this->db->query('SELECT id FROM part_batch WHERE batch_number = ?', [$batchNumber])->getRowArray();
            if ($exists) {
                return redirect()->to('assembly-work/view/'.$id)->with('error', 'Batch number already exists. Receive must use a new batch');
            }

            $this->db->table('part_batch')->insert([
                'batch_number'         => $batchNumber,
                'part_id'              => $partId,
                'stamp_id'             => $this->request->getPost('stamp_id') ?: null,
                'piece_weight_g'       => $pieceWeight ?: null,
                'touch_pct'            => $touchPct,
                'qty_in_stock'         => (int)round($pcs),
                'weight_in_stock_g'    => $weightG,
                'source_part_order_id' => null,
                'received_at'          => date('Y-m-d H:i:s'),
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
            $partBatchId = $this->db->insertID();
        } elseif ($receiveType === 'by_product') {
            $byproductTypeId = (int)$this->request->getPost('byproduct_type_id');
            if ($byproductTypeId <= 0) {
                return redirect()->to('assembly-work/view/'.$id)->with('error', 'By-product type is required');
            }

            $this->db->table('byproduct_stock')->insert([
                'byproduct_type_id' => $byproductTypeId,
                'weight_g'          => $weightG,
                'touch_pct'         => $touchPct,
                'source_job_type'   => null,
                'source_job_id'     => $id,
                'added_at'          => date('Y-m-d H:i:s'),
            ]);
        } elseif ($receiveType === 'kacha') {
            $exists = $this->db->query('SELECT id FROM kacha_lot WHERE lot_number = ?', [$batchNumber])->getRowArray();
            if ($exists) {
                return redirect()->to('assembly-work/view/'.$id)->with('error', 'Kacha lot number already exists');
            }
            $this->db->query(
                'INSERT INTO kacha_lot (lot_number, weight, touch_pct, receipt_date, party, source_type, test_touch, test_number, notes, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?)',
                [
                    $batchNumber,
                    $weightG,
                    $touchPct,
                    date('Y-m-d'),
                    null,
                    'internal',
                    null,
                    null,
                    $this->request->getPost('notes') ?: null,
                    date('Y-m-d H:i:s'),
                ]
            );
            $kachaLotId = $this->db->insertID();
            $pcs = null;
            $pieceWeight = 0;
        } else {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Invalid receive type');
        }

        $this->db->table('assembly_work_receive')->insert([
            'assembly_work_id'  => $id,
            'receive_type'      => $receiveType,
            'part_id'           => $partId ?: null,
            'finished_goods_id' => $finishedGoodsId ?: null,
            'part_batch_id'     => $partBatchId,
            'byproduct_type_id' => $byproductTypeId ?: null,
            'kacha_lot_id'      => $kachaLotId ?: null,
            'stamp_id'          => $this->request->getPost('stamp_id') ?: null,
            'batch_number'      => $batchNumber,
            'weight_g'          => $weightG,
            'piece_weight_g'    => $pieceWeight ?: null,
            'pcs'               => $pcs ?: null,
            'touch_pct'         => $touchPct,
            'created_by_user_id' => $enteredBy['id'],
            'created_by_username' => $enteredBy['username'],
            'notes'             => $this->request->getPost('notes') ?: null,
            'received_at'       => date('Y-m-d H:i:s'),
            'created_at'        => date('Y-m-d H:i:s'),
        ]);

        $this->_touchInProgress($id);

        return redirect()->to('assembly-work/view/'.$id)->with('success', 'Receive added');
    }

    public function deleteReceive($recvId)
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $recv = $this->db->query('SELECT * FROM assembly_work_receive WHERE id = ?', [$recvId])->getRowArray();
        if (!$recv) {
            return redirect()->to('assembly-work')->with('error', 'Receive not found');
        }

        $work = $this->_getWork($recv['assembly_work_id']);
        if (!$work || in_array($work['status'], ['finished', 'completed'], true)) {
            return redirect()->to('assembly-work/view/'.$recv['assembly_work_id'])->with('error', 'Cannot delete receive');
        }

        if ($recv['part_batch_id']) {
            $this->db->table('part_batch')->where('id', $recv['part_batch_id'])->delete();
        }
        $this->db->table('assembly_work_receive')->where('id', $recvId)->delete();
        $this->_touchInProgress($recv['assembly_work_id']);

        return redirect()->to('assembly-work/view/'.$recv['assembly_work_id'])->with('success', 'Receive deleted');
    }

    public function updateReceive($recvId)
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $recv = $this->db->query('SELECT * FROM assembly_work_receive WHERE id = ?', [$recvId])->getRowArray();
        if (!$recv) {
            return redirect()->to('assembly-work')->with('error', 'Receive not found');
        }

        $work = $this->_getWork($recv['assembly_work_id']);
        if (!$work || in_array($work['status'], ['finished', 'completed'], true)) {
            return redirect()->to('assembly-work/view/'.$recv['assembly_work_id'])->with('error', 'Cannot update receive');
        }

        $newWeight = (float)$this->request->getPost('weight_g');
        if ($newWeight <= 0) {
            return redirect()->to('assembly-work/view/'.$recv['assembly_work_id'])->with('error', 'Weight must be greater than zero');
        }

        $update = [
            'weight_g'    => $newWeight,
            'notes'       => $this->request->getPost('notes') ?: null,
            'received_at' => date('Y-m-d H:i:s'),
        ];

        if ($recv['receive_type'] === 'finished_good') {
            $finishedGoodsId = (int)$this->request->getPost('finished_goods_id');
            if ($finishedGoodsId <= 0) {
                return redirect()->to('assembly-work/view/'.$recv['assembly_work_id'])->with('error', 'Finished good is required');
            }
            $update['finished_goods_id'] = $finishedGoodsId;
            $update['stamp_id'] = $this->request->getPost('stamp_id') ?: null;
        } elseif ($recv['receive_type'] === 'returned_part') {
            $pieceWeight = (float)($this->request->getPost('piece_weight_g') ?: 0);
            $pcs = $pieceWeight > 0 ? round($newWeight / $pieceWeight, 4) : 0;
            $touchPct = (float)($this->request->getPost('touch_pct') ?? 0);
            $stampId = $this->request->getPost('stamp_id') ?: null;

            if ($recv['part_batch_id']) {
                $oldBatch = $this->db->query('SELECT * FROM part_batch WHERE id = ?', [$recv['part_batch_id']])->getRowArray();
                if ($oldBatch) {
                    $oldQty = (int)round((float)($recv['pcs'] ?? 0));
                    $this->db->query(
                        'UPDATE part_batch SET weight_in_stock_g = GREATEST(0, weight_in_stock_g - ? + ?), qty_in_stock = GREATEST(0, qty_in_stock - ? + ?), piece_weight_g = ?, touch_pct = ?, stamp_id = ? WHERE id = ?',
                        [
                            (float)$recv['weight_g'],
                            $newWeight,
                            $oldQty,
                            (int)round($pcs),
                            $pieceWeight ?: null,
                            $touchPct,
                            $stampId,
                            $recv['part_batch_id']
                        ]
                    );
                }
            }

            $update['piece_weight_g'] = $pieceWeight ?: null;
            $update['pcs'] = $pcs ?: null;
            $update['touch_pct'] = $touchPct;
            $update['stamp_id'] = $stampId;
        } elseif ($recv['receive_type'] === 'by_product') {
            $existingByproduct = $this->db->query(
                'SELECT id FROM byproduct_stock WHERE source_job_id = ? AND source_job_type IS NULL AND byproduct_type_id = ? AND weight_g = ? ORDER BY id DESC LIMIT 1',
                [$recv['assembly_work_id'], $recv['byproduct_type_id'], (float)$recv['weight_g']]
            )->getRowArray();
            if ($existingByproduct) {
                $this->db->table('byproduct_stock')->where('id', $existingByproduct['id'])->update([
                    'byproduct_type_id' => (int)$this->request->getPost('byproduct_type_id'),
                    'weight_g'          => $newWeight,
                    'touch_pct'         => 0,
                ]);
            }
            $update['byproduct_type_id'] = (int)$this->request->getPost('byproduct_type_id');
            $update['touch_pct'] = 0;
            $update['pcs'] = null;
            $update['piece_weight_g'] = null;
            $update['stamp_id'] = null;
        } elseif ($recv['receive_type'] === 'kacha') {
            if ($recv['kacha_lot_id']) {
                $this->db->table('kacha_lot')->where('id', $recv['kacha_lot_id'])->update([
                    'lot_number' => trim((string)$this->request->getPost('batch_number')),
                    'weight'     => $newWeight,
                    'touch_pct'  => (float)($this->request->getPost('touch_pct') ?? 0),
                    'notes'      => $this->request->getPost('notes') ?: null,
                ]);
            }
            $update['touch_pct'] = (float)($this->request->getPost('touch_pct') ?? 0);
            $update['pcs'] = null;
            $update['piece_weight_g'] = null;
            $update['stamp_id'] = null;
        }

        $update['batch_number'] = trim((string)$this->request->getPost('batch_number'));
        $this->db->table('assembly_work_receive')->where('id', $recvId)->update($update);

        $this->_touchInProgress($recv['assembly_work_id']);

        return redirect()->to('assembly-work/view/'.$recv['assembly_work_id'])->with('success', 'Receive updated');
    }

    public function saveSummary($id)
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $work = $this->_getWork($id);
        if (!$work || $work['status'] === 'completed') {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Cannot save summary');
        }

        $groupIds     = $this->request->getPost('group_id') ?? [];
        $issueTouches = $this->request->getPost('issue_touch_pct') ?? [];
        $recvTouches  = $this->request->getPost('receive_touch_pct') ?? [];

        $base = $this->_getDepartmentSummary($id);

        foreach ($groupIds as $index => $groupId) {
            $groupId = (int)$groupId;
            if ($groupId <= 0) {
                continue;
            }

            $row = null;
            foreach ($base as $item) {
                if ((int)$item['department_group_id'] === $groupId) {
                    $row = $item;
                    break;
                }
            }
            if (!$row) {
                continue;
            }

            $issueTouch = (float)($issueTouches[$index] ?? 0);
            $recvTouch  = (float)($recvTouches[$index] ?? 0);
            $issueFine  = round((float)$row['issue_weight_g'] * $issueTouch / 100, 4);
            $recvFine   = round((float)$row['receive_weight_g'] * $recvTouch / 100, 4);

            $exists = $this->db->query('SELECT id FROM assembly_work_summary WHERE assembly_work_id = ? AND department_group_id = ?', [$id, $groupId])->getRowArray();
            $data = [
                'assembly_work_id'    => $id,
                'department_group_id' => $groupId,
                'issue_weight_g'      => $row['issue_weight_g'],
                'receive_weight_g'    => $row['receive_weight_g'],
                'issue_touch_pct'     => $issueTouch,
                'receive_touch_pct'   => $recvTouch,
                'issue_fine_g'        => $issueFine,
                'receive_fine_g'      => $recvFine,
                'difference_fine_g'   => round($issueFine - $recvFine, 4),
                'updated_at'          => date('Y-m-d H:i:s'),
            ];

            if ($exists) {
                $this->db->table('assembly_work_summary')->where('id', $exists['id'])->update($data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('assembly_work_summary')->insert($data);
            }
        }

        return redirect()->to('assembly-work/view/'.$id.'#summary')->with('success', 'Summary saved');
    }

    public function markFinished($id)
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $work = $this->_getWork($id);
        if (!$work || $work['status'] === 'completed') {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Cannot finish this work');
        }

        $this->db->table('assembly_work')->where('id', $id)->update([
            'status'      => 'finished',
            'finished_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('assembly-work/view/'.$id)->with('success', 'Assembly work marked as finished');
    }

    public function saveMakingCharge($id)
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $work = $this->_getWork($id);
        if (!$work || $work['status'] !== 'finished') {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Making charge can be saved only after finishing');
        }

        $cash = (float)$this->request->getPost('making_charge_cash');
        $fine = (float)$this->request->getPost('making_charge_fine');

        $this->db->table('assembly_work')->where('id', $id)->update([
            'making_charge_cash' => $cash,
            'making_charge_fine' => $fine,
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('assembly-work/view/'.$id)->with('success', 'Making charge saved');
    }

    public function complete($id)
    {
        if ($redirect = $this->ensureModuleTables()) {
            return $redirect;
        }

        $work = $this->_getWork($id);
        if (!$work || $work['status'] !== 'finished') {
            return redirect()->to('assembly-work/view/'.$id)->with('error', 'Work must be finished before completion');
        }

        $this->db->table('assembly_work')->where('id', $id)->update([
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('assembly-work/view/'.$id)->with('success', 'Assembly work completed');
    }

    private function _getWork($id)
    {
        return $this->db->query('SELECT * FROM assembly_work WHERE id = ?', [$id])->getRowArray();
    }

    private function ensureModuleTables()
    {
        foreach (self::REQUIRED_TABLES as $table) {
            if (!$this->db->tableExists($table)) {
                return redirect()->to(base_url())
                    ->with('error', 'Assembly Work tables are not created yet. Run run_assembly_work_migration.php once on the server.');
            }
        }

        return null;
    }

    private function _touchInProgress($id): void
    {
        $this->db->table('assembly_work')->where('id', $id)->update([
            'status'     => 'in_progress',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function _nextWorkNumber(): string
    {
        $row = $this->db->query('SELECT COUNT(*) as cnt FROM assembly_work')->getRowArray();
        return 'ASMWRK-' . str_pad((int)($row['cnt'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    }

    private function _getRequirementSummary(int $assemblyWorkId): array
    {
        $orderLinks = $this->db->query('SELECT order_id FROM assembly_work_order WHERE assembly_work_id = ?', [$assemblyWorkId])->getResultArray();
        $orderIds = array_map(fn($row) => (int)$row['order_id'], $orderLinks);
        if (empty($orderIds)) {
            return [];
        }

        $requirementsMap = [];
        foreach ($orderIds as $orderId) {
            $orderRequirements = $this->_calculateOrderPartRequirements($orderId);
            foreach ($orderRequirements as $partId => $data) {
                $partKey = $this->_buildRequirementKey((int)$partId, $data);
                if (!isset($requirementsMap[$partKey])) {
                    $requirementsMap[$partKey] = [
                        'part_id'               => (int)$partId,
                        'required_pcs'          => 0,
                        'part_name'             => '',
                        'tamil_name'            => '',
                        'master_piece_weight_g' => 0,
                        'department_name'       => '',
                        'department_group_id'   => null,
                        'department_group_name' => '',
                    ];
                }

                $requirementsMap[$partKey]['required_pcs'] += (float)($data['part_pcs'] ?? 0);
            }
        }

        if (empty($requirementsMap)) {
            return [];
        }

        $partIds = array_values(array_unique(array_map(static fn($row) => (int)$row['part_id'], $requirementsMap)));
        $partPlaceholders = implode(',', array_fill(0, count($partIds), '?'));
        $partRows = $this->db->query("
            SELECT p.id, p.name, p.tamil_name, p.weight, d.name as department_name, dg.id as department_group_id, dg.name as department_group_name
            FROM part p
            LEFT JOIN department d ON d.id = p.department_id
            LEFT JOIN department_group dg ON dg.id = d.department_group_id
            WHERE p.id IN ($partPlaceholders)
            ORDER BY p.name
        ", $partIds)->getResultArray();

        foreach ($partRows as $partRow) {
            $partId = (int)$partRow['id'];
            foreach ($requirementsMap as &$requirementRow) {
                if ((int)$requirementRow['part_id'] !== $partId) {
                    continue;
                }

                $requirementRow['part_name'] = $partRow['name'] ?? '';
                $requirementRow['tamil_name'] = $partRow['tamil_name'] ?? '';
                $requirementRow['master_piece_weight_g'] = (float)($partRow['weight'] ?? 0);
                $requirementRow['department_name'] = $partRow['department_name'] ?? '';
                $requirementRow['department_group_id'] = $partRow['department_group_id'] ?? null;
                $requirementRow['department_group_name'] = $partRow['department_group_name'] ?? '';
            }
            unset($requirementRow);
        }

        $requirements = array_values($requirementsMap);

        $issued = $this->db->query('
            SELECT part_id, SUM(weight_g) as issued_weight_g, SUM(pcs) as issued_pcs
            FROM assembly_work_issue
            WHERE assembly_work_id = ?
            GROUP BY part_id
        ', [$assemblyWorkId])->getResultArray();
        $issuedMap = [];
        foreach ($issued as $row) {
            $issuedMap[(int)$row['part_id']] = $row;
        }

        $partIds = array_values(array_unique(array_map(fn($row) => (int)$row['part_id'], $requirements)));
        $stockRows = [];
        if (!empty($partIds)) {
            $partPlaceholders = implode(',', array_fill(0, count($partIds), '?'));
            $stockRows = $this->db->query("
                SELECT part_id, SUM(weight_in_stock_g) as stock_weight_g
                FROM part_batch
                WHERE part_id IN ($partPlaceholders)
                GROUP BY part_id
            ", $partIds)->getResultArray();
        }
        $stockMap = [];
        foreach ($stockRows as $row) {
            $stockMap[(int)$row['part_id']] = (float)$row['stock_weight_g'];
        }

        foreach ($requirements as &$row) {
            $pieceWeight = (float)($row['master_piece_weight_g'] ?? 0);
            $requiredPcs = (float)($row['required_pcs'] ?? 0);
            $requiredWeight = $pieceWeight > 0 ? round($requiredPcs * $pieceWeight, 4) : 0;
            $issuedPcs = (float)($issuedMap[(int)$row['part_id']]['issued_pcs'] ?? 0);
            $issuedWeight = (float)($issuedMap[(int)$row['part_id']]['issued_weight_g'] ?? 0);
            $pendingPcs = round($requiredPcs - $issuedPcs, 4);
            $pendingWeight = round($requiredWeight - $issuedWeight, 4);
            $stockWeight = (float)($stockMap[(int)$row['part_id']] ?? 0);

            $row['required_weight_g'] = $requiredWeight;
            $row['issued_pcs'] = $issuedPcs;
            $row['issued_weight_g'] = $issuedWeight;
            $row['pending_pcs'] = $pendingPcs;
            $row['pending_weight_g'] = $pendingWeight;
            $row['stock_weight_g'] = $stockWeight;
            $row['shortage_weight_g'] = round(max(0, $pendingWeight - $stockWeight), 4);
            $row['suggested_issue_weight_g'] = round(max(0, min($pendingWeight, $stockWeight)), 4);
            $row['required_weight_g_approx'] = $requiredWeight;
            $row['pending_weight_g_approx'] = $pendingWeight;
        }
        unset($row);

        usort($requirements, static function ($left, $right) {
            return strcmp((string)($left['part_name'] ?? ''), (string)($right['part_name'] ?? ''));
        });

        return $requirements;
    }

    private function _buildRequirementKey(int $partId, array $data): string
    {
        $partName = strtolower(trim((string)($data['part_name'] ?? '')));
        if ($partName !== '') {
            return 'name:' . preg_replace('/\s+/', ' ', $partName);
        }

        return 'id:' . $partId;
    }

    private function _currentUser(): array
    {
        $staffUser = session()->get('staff_user');
        if (is_array($staffUser) && !empty($staffUser['username'])) {
            return [
                'id' => (int)($staffUser['id'] ?? 0) ?: null,
                'username' => (string)($staffUser['name'] ?? $staffUser['username']),
            ];
        }

        $username = session()->get('username');
        return [
            'id' => session()->get('user_id') ? (int)session()->get('user_id') : null,
            'username' => $username ?: 'Unknown',
        ];
    }

    private function _getDepartmentSummary(int $assemblyWorkId, ?array $savedRows = null): array
    {
        $issueRows = $this->db->query('
            SELECT dg.id as department_group_id, dg.name as department_group_name, SUM(awi.weight_g) as issue_weight_g
            FROM assembly_work_issue awi
            LEFT JOIN part p ON p.id = awi.part_id
            LEFT JOIN department d ON d.id = p.department_id
            LEFT JOIN department_group dg ON dg.id = d.department_group_id
            WHERE awi.assembly_work_id = ?
            GROUP BY dg.id, dg.name
        ', [$assemblyWorkId])->getResultArray();

        $receiveRows = $this->db->query('
            SELECT dg.id as department_group_id, dg.name as department_group_name, SUM(awr.weight_g) as receive_weight_g
            FROM assembly_work_receive awr
            LEFT JOIN part p ON p.id = awr.part_id
            LEFT JOIN department d ON d.id = p.department_id
            LEFT JOIN department_group dg ON dg.id = d.department_group_id
            WHERE awr.assembly_work_id = ? AND awr.part_id IS NOT NULL
            GROUP BY dg.id, dg.name
        ', [$assemblyWorkId])->getResultArray();

        $map = [];
        foreach ($issueRows as $row) {
            $key = (int)($row['department_group_id'] ?? 0);
            if ($key <= 0) {
                continue;
            }
            $map[$key] = [
                'department_group_id'   => $key,
                'department_group_name' => $row['department_group_name'] ?: 'Ungrouped',
                'issue_weight_g'        => (float)$row['issue_weight_g'],
                'receive_weight_g'      => 0,
                'issue_touch_pct'       => 0,
                'receive_touch_pct'     => 0,
                'issue_fine_g'          => 0,
                'receive_fine_g'        => 0,
                'difference_fine_g'     => 0,
            ];
        }

        foreach ($receiveRows as $row) {
            $key = (int)($row['department_group_id'] ?? 0);
            if ($key <= 0) {
                continue;
            }
            if (!isset($map[$key])) {
                $map[$key] = [
                    'department_group_id'   => $key,
                    'department_group_name' => $row['department_group_name'] ?: 'Ungrouped',
                    'issue_weight_g'        => 0,
                    'receive_weight_g'      => 0,
                    'issue_touch_pct'       => 0,
                    'receive_touch_pct'     => 0,
                    'issue_fine_g'          => 0,
                    'receive_fine_g'        => 0,
                    'difference_fine_g'     => 0,
                ];
            }
            $map[$key]['receive_weight_g'] = (float)$row['receive_weight_g'];
        }

        if ($savedRows) {
            foreach ($savedRows as $row) {
                $key = (int)$row['department_group_id'];
                if (!isset($map[$key])) {
                    continue;
                }
                $map[$key]['issue_touch_pct']   = (float)$row['issue_touch_pct'];
                $map[$key]['receive_touch_pct'] = (float)$row['receive_touch_pct'];
                $map[$key]['issue_fine_g']      = (float)$row['issue_fine_g'];
                $map[$key]['receive_fine_g']    = (float)$row['receive_fine_g'];
                $map[$key]['difference_fine_g'] = (float)$row['difference_fine_g'];
            }
        }

        ksort($map);
        return array_values($map);
    }

    private function _calculateOrderPartRequirements(int $orderId): array
    {
        $mainSetup = [];
        $setupRows = $this->db->query('
            SELECT omps.part_id, omps.kanni_per_inch, omps.weight_per_kanni
            FROM order_main_part_setup omps
            WHERE omps.order_id = ?
              AND EXISTS (
                    SELECT 1
                    FROM order_items oi
                    JOIN product p ON p.id = oi.product_id
                    WHERE oi.order_id = omps.order_id
                      AND p.main_part_id = omps.part_id
              )
        ', [$orderId])->getResultArray();
        foreach ($setupRows as $row) {
            $mainSetup[$row['part_id']] = $row;
        }

        $aggregated = $this->_calculatePartRequirementsBase($orderId);

        foreach ($mainSetup as $mainPartId => $setup) {
            if (!isset($aggregated[$mainPartId])) {
                $aggregated[$mainPartId] = ['part_pcs' => 0, 'podi_id' => null, 'podi_pcs' => 0, 'sum_length' => 0];
            }
        }

        foreach ($mainSetup as $mainPartId => $setup) {
            $kanniPerInch = (float)$setup['kanni_per_inch'];
            if ($kanniPerInch <= 0) {
                continue;
            }

            $orderItems = $this->db->query('
                SELECT oi.id, pt.multiplication_factor, b.clasp_size
                FROM order_items oi
                JOIN product p ON p.id = oi.product_id
                LEFT JOIN product_type pt ON pt.id = p.product_type_id
                LEFT JOIN body b ON b.id = p.body_id
                WHERE oi.order_id = ? AND p.main_part_id = ?
            ', [$orderId, $mainPartId])->getResultArray();

            $totalLength = 0;
            foreach ($orderItems as $item) {
                $factor = (float)($item['multiplication_factor'] ?? 1);
                $claspSize = (float)($item['clasp_size'] ?? 0);
                $qtyRows = $this->db->query('
                    SELECT oiq.quantity, v.size
                    FROM order_item_qty oiq
                    JOIN variation v ON v.id = oiq.variation_id
                    WHERE oiq.order_item_id = ? AND oiq.quantity > 0
                ', [$item['id']])->getResultArray();

                foreach ($qtyRows as $qtyRow) {
                    $totalLength += max(0, (float)$qtyRow['size'] - $claspSize) * (float)$qtyRow['quantity'] * $factor;
                }
            }

            $aggregated[$mainPartId]['sum_length'] = $totalLength;
            $aggregated[$mainPartId]['part_pcs'] = $totalLength * $kanniPerInch;
        }

        return $aggregated;
    }

    private function _calculatePartRequirementsBase(int $orderId): array
    {
        $kanniMap = [];
        $setupRows = $this->db->query('SELECT * FROM order_main_part_setup WHERE order_id = ?', [$orderId])->getResultArray();
        foreach ($setupRows as $setup) {
            $kanniMap[$setup['part_id']] = [
                'kanni_per_inch'   => (float)$setup['kanni_per_inch'],
                'weight_per_kanni' => (float)$setup['weight_per_kanni'],
            ];
        }

        $items = $this->db->query('
            SELECT oi.*, p.product_type_id, p.main_part_id,
                   pt.multiplication_factor, pt.variations as pt_variations,
                   b.clasp_size
            FROM order_items oi
            JOIN product p ON p.id = oi.product_id
            LEFT JOIN product_type pt ON pt.id = p.product_type_id
            LEFT JOIN body b ON b.id = p.body_id
            WHERE oi.order_id = ?
        ', [$orderId])->getResultArray();

        $aggregated = [];

        foreach ($items as $item) {
            $factor = (float)($item['multiplication_factor'] ?? 1);
            $claspSize = (float)($item['clasp_size'] ?? 0);

            $variationIds = [];
            if (!empty($item['pt_variations'])) {
                $variationIds = array_values(array_filter(array_map('trim', explode(',', $item['pt_variations']))));
            }
            if (empty($variationIds)) {
                continue;
            }

            $variationPlaceholders = implode(',', array_fill(0, count($variationIds), '?'));
            $variations = $this->db->query(
                "SELECT id, group_name, name, size FROM variation WHERE id IN ($variationPlaceholders) ORDER BY group_name, size+0",
                $variationIds
            )->getResultArray();

            $qtyRows = $this->db->query('SELECT variation_id, quantity FROM order_item_qty WHERE order_item_id = ?', [$item['id']])->getResultArray();
            $qtyMap = [];
            foreach ($qtyRows as $qtyRow) {
                $qtyMap[$qtyRow['variation_id']] = (float)$qtyRow['quantity'];
            }

            $variationStats = [];
            foreach ($variations as $variation) {
                $qty = $qtyMap[$variation['id']] ?? 0;
                if ($qty <= 0) {
                    continue;
                }

                $actualLength = (float)$variation['size'] - $claspSize;
                $variationStats[$variation['id']] = [
                    'group_name'   => $variation['group_name'],
                    'total_length' => $actualLength * $qty * $factor,
                    'raw_pcs'      => $qty,
                ];
            }

            $mainPartId = $item['main_part_id'];
            $mainKanniPerInch = isset($kanniMap[$mainPartId]) ? $kanniMap[$mainPartId]['kanni_per_inch'] : null;
            $kanniPerInch = $mainKanniPerInch ?? 12.0;

            if ($mainPartId && $mainKanniPerInch !== null) {
                $sumLength = 0;
                foreach ($variationStats as $stat) {
                    $sumLength += $stat['total_length'];
                }

                if ($sumLength > 0) {
                    $required = $sumLength * $mainKanniPerInch;
                    if (!isset($aggregated[$mainPartId])) {
                        $aggregated[$mainPartId] = ['part_pcs' => 0, 'podi_id' => null, 'podi_pcs' => 0, 'sum_length' => 0];
                    }
                    $aggregated[$mainPartId]['part_pcs'] += $required;
                    $aggregated[$mainPartId]['sum_length'] = ($aggregated[$mainPartId]['sum_length'] ?? 0) + $sumLength;
                }
            }

            $effectiveBom = $this->_getEffectiveBom($item['product_id'], $item['pattern_id']);

            foreach ($effectiveBom as $bomRow) {
                $partId = $bomRow['part_id'];
                if ((int)$partId === (int)$mainPartId && $mainKanniPerInch !== null) {
                    continue;
                }

                $partPcs = (float)($bomRow['part_pcs'] ?? 0);
                $scale = $bomRow['scale'] ?? '';
                $variationGroupRaw = trim($bomRow['variation_group'] ?? '');
                $variationGroups = $variationGroupRaw !== '' ? array_map('trim', explode(',', $variationGroupRaw)) : [];
                $podiId = $bomRow['podi_id'] ?? null;
                $podiPcs = (float)($bomRow['podi_pcs'] ?? 0);

                $sumLength = 0;
                $sumRaw = 0;
                foreach ($variationStats as $stat) {
                    $applies = empty($variationGroups) || in_array($stat['group_name'], $variationGroups, true);
                    if ($applies) {
                        $sumLength += $stat['total_length'];
                        $sumRaw += $stat['raw_pcs'];
                    }
                }

                $required = 0;
                if ($scale === 'Per Inch') {
                    $required = $sumLength * $partPcs;
                }
                if ($scale === 'Per Pair') {
                    $required = $sumRaw * $partPcs;
                }
                if ($scale === 'Per Kanni') {
                    $required = $sumLength * $kanniPerInch * $partPcs;
                    if (!isset($aggregated[$partId])) {
                        $aggregated[$partId] = ['part_pcs' => 0, 'podi_id' => $podiId, 'podi_pcs' => 0, 'sum_length' => 0];
                    }
                    $aggregated[$partId]['part_pcs'] += $required;
                    $aggregated[$partId]['part_pcs'] = max(0, $aggregated[$partId]['part_pcs']);
                    $aggregated[$partId]['sum_length'] = ($aggregated[$partId]['sum_length'] ?? 0) + $sumLength;
                    if ($podiId) {
                        $aggregated[$partId]['podi_id'] = $podiId;
                        $aggregated[$partId]['podi_pcs'] += $sumLength > 0 ? $podiPcs * $sumLength : 0;
                    }
                    continue;
                }

                if ($required == 0) {
                    continue;
                }

                if (!isset($aggregated[$partId])) {
                    $aggregated[$partId] = ['part_pcs' => 0, 'podi_id' => $podiId, 'podi_pcs' => 0, 'sum_length' => 0];
                }
                $aggregated[$partId]['part_pcs'] += $required;
                $aggregated[$partId]['part_pcs'] = max(0, $aggregated[$partId]['part_pcs']);
                if ($podiId) {
                    $aggregated[$partId]['podi_id'] = $podiId;
                    $aggregated[$partId]['podi_pcs'] += $sumLength > 0 ? $podiPcs * ($scale === 'Per Pair' ? $sumRaw : $sumLength) : 0;
                }
            }

            $cbomRows = $this->db->query('SELECT * FROM product_customize_bill_of_material WHERE product_id = ?', [$item['product_id']])->getResultArray();
            $cbomOverride = [];
            if (!empty($item['pattern_id'])) {
                $patternChanges = $this->db->query(
                    'SELECT * FROM product_pattern_cbom_change WHERE product_pattern_id = ?',
                    [$item['pattern_id']]
                )->getResultArray();
                foreach ($patternChanges as $change) {
                    $cbomOverride[$change['part_id']][$change['variation_id']] = $change;
                }
            }

            foreach ($cbomRows as $cbom) {
                $partId = $cbom['part_id'];
                if ((int)$partId === (int)$mainPartId && $mainKanniPerInch !== null) {
                    continue;
                }

                $podiId = $cbom['podi_id'] ?? null;
                $cbomQtys = $this->db->query(
                    'SELECT * FROM product_customize_bill_of_material_quantity WHERE product_customize_bill_of_material_id = ?',
                    [$cbom['id']]
                )->getResultArray();

                $partRequired = 0;
                $podiRequired = 0;
                foreach ($cbomQtys as $cbomQty) {
                    $variationId = (int)$cbomQty['variation_id'];
                    $orderQty = $qtyMap[$variationId] ?? 0;

                    if (isset($cbomOverride[$partId][$variationId])) {
                        $override = $cbomOverride[$partId][$variationId];
                        if ($override['action'] === 'remove') {
                            continue;
                        }
                        if ($override['action'] === 'replace') {
                            continue;
                        }
                        $cbomQty['part_quantity'] = (float)$override['quantity'];
                        $cbomQty['podi_id'] = $override['podi_id'] ?? $cbomQty['podi_id'];
                        $cbomQty['podi_quantity'] = isset($override['podi_qty']) ? (float)$override['podi_qty'] : ($cbomQty['podi_quantity'] ?? 0);
                    }

                    $partRequired += $orderQty * (float)($cbomQty['part_quantity'] ?? 0);
                    $podiRequired += $orderQty * (float)($cbomQty['podi_quantity'] ?? 0);
                }

                if ($partRequired <= 0) {
                    continue;
                }

                if (!isset($aggregated[$partId])) {
                    $aggregated[$partId] = ['part_pcs' => 0, 'podi_id' => $podiId, 'podi_pcs' => 0];
                }
                $aggregated[$partId]['part_pcs'] += $partRequired;
                if ($podiId) {
                    $aggregated[$partId]['podi_id'] = $podiId;
                    $aggregated[$partId]['podi_pcs'] += $podiRequired;
                }
            }

            foreach ($cbomOverride as $overridePartId => $variationOverrides) {
                if ((int)$overridePartId === (int)$mainPartId && $mainKanniPerInch !== null) {
                    continue;
                }

                foreach ($variationOverrides as $variationId => $override) {
                    if (!isset($qtyMap[$variationId])) {
                        continue;
                    }

                    $targetPartId = $override['action'] === 'replace' ? (int)$override['replace_part_id'] : $overridePartId;
                    if (!$targetPartId || $override['action'] === 'remove') {
                        continue;
                    }

                    $overrideQty = (float)$override['quantity'];
                    if ($overrideQty <= 0) {
                        continue;
                    }

                    $orderQty = $qtyMap[$variationId] ?? 0;
                    if (!isset($aggregated[$targetPartId])) {
                        $aggregated[$targetPartId] = ['part_pcs' => 0, 'podi_id' => null, 'podi_pcs' => 0];
                    }
                    $aggregated[$targetPartId]['part_pcs'] += $orderQty * $overrideQty;
                    if (!empty($override['podi_id'])) {
                        $aggregated[$targetPartId]['podi_id'] = $override['podi_id'];
                        $aggregated[$targetPartId]['podi_pcs'] += $orderQty * (float)($override['podi_qty'] ?? 0);
                    }
                }
            }
        }

        return $aggregated;
    }

    private function _getEffectiveBom($productId, $patternId): array
    {
        $bom = $this->db->query('
            SELECT bom.*, pa.name as part_name
            FROM product_bill_of_material bom
            LEFT JOIN part pa ON pa.id = bom.part_id
            WHERE bom.product_id = ?
        ', [$productId])->getResultArray();

        if (!$patternId) {
            return $bom;
        }

        $pattern = $this->db->query('SELECT is_default FROM product_pattern WHERE id = ?', [$patternId])->getRowArray();
        if (!$pattern || $pattern['is_default']) {
            return $bom;
        }

        $changes = $this->db->query('
            SELECT pc.*, pa.name as part_name
            FROM product_pattern_bom_change pc
            LEFT JOIN part pa ON pa.id = pc.part_id
            WHERE pc.product_pattern_id = ?
        ', [$patternId])->getResultArray();

        foreach ($changes as $change) {
            if ($change['action'] === 'add') {
                $bom[] = [
                    'part_id'         => $change['part_id'],
                    'part_name'       => $change['part_name'],
                    'part_pcs'        => $change['part_pcs'],
                    'scale'           => $change['scale'],
                    'variation_group' => $change['variation_group'],
                    'podi_id'         => $change['podi_id'],
                    'podi_pcs'        => $change['podi_pcs'],
                ];
            } elseif ($change['action'] === 'remove') {
                foreach ($bom as $key => $row) {
                    if ($row['part_id'] == $change['part_id']) {
                        unset($bom[$key]);
                        break;
                    }
                }
            } elseif ($change['action'] === 'replace' && $change['replace_part_id']) {
                $newPart = $this->db->query('SELECT id, name FROM part WHERE id = ?', [$change['replace_part_id']])->getRowArray();
                $bom[] = [
                    'part_id'         => $change['part_id'],
                    'part_name'       => '',
                    'part_pcs'        => -1 * abs((float)$change['part_pcs']),
                    'scale'           => $change['scale'],
                    'variation_group' => $change['variation_group'],
                    'podi_id'         => null,
                    'podi_pcs'        => 0,
                ];
                $bom[] = [
                    'part_id'         => $change['replace_part_id'],
                    'part_name'       => $newPart['name'] ?? '',
                    'part_pcs'        => $change['part_pcs'],
                    'scale'           => $change['scale'],
                    'variation_group' => $change['variation_group'],
                    'podi_id'         => $change['podi_id'],
                    'podi_pcs'        => $change['podi_pcs'],
                ];
            }
        }

        return array_values($bom);
    }
}