<?php
namespace App\Controllers;

class GattiStock extends BaseController
{
    private function _gsSelect()
    {
        return 'SELECT gs.*, mj.job_number, s.name as stamp_name FROM gatti_stock gs LEFT JOIN melt_job mj ON mj.id = gs.melt_job_id LEFT JOIN stamp s ON s.id = gs.stamp_id';
    }

    public function index()
    {
        $db     = \Config\Database::connect();
        $items  = $db->query($this->_gsSelect() . ' ORDER BY gs.created_at DESC')->getResultArray();
        $stamps = $db->query('SELECT id, name FROM stamp ORDER BY name')->getResultArray();
        return view('gatti_stock/index', ['title' => 'Gatti Stock', 'items' => $items, 'stamps' => $stamps]);
    }

    public function view($id)
    {
        $db  = \Config\Database::connect();
        $row = $db->query($this->_gsSelect() . ' WHERE gs.id = ?', [$id])->getRowArray();
        if (!$row) return redirect()->to('gatti-stock')->with('error', 'Gatti stock row not found');

        $logs = $db->query('SELECT * FROM gatti_stock_log WHERE gatti_stock_id = ? ORDER BY created_at DESC', [$id])->getResultArray();

        $label = $row['batch_number'] ?? ($row['job_number'] ? 'Job ' . $row['job_number'] : 'ID #' . $id);
        return view('gatti_stock/view', [
            'title' => 'Gatti Stock — ' . $label,
            'row'   => $row,
            'logs'  => $logs,
        ]);
    }

    public function stockEntry()
    {
        $db       = \Config\Database::connect();
        $batchNo  = trim($this->request->getGet('batch') ?? '');
        $row      = null;
        $newBatch = false;

        if ($batchNo) {
            $row = $db->query($this->_gsSelect() . ' WHERE gs.batch_number = ?', [$batchNo])->getRowArray();
            if (!$row) $newBatch = true;
        }

        $stamps = $db->query('SELECT id, name FROM stamp ORDER BY name')->getResultArray();

        return view('gatti_stock/entry', [
            'title'    => 'Gatti Stock Entry',
            'row'      => $row,
            'batchNo'  => $batchNo,
            'newBatch' => $newBatch,
            'stamps'   => $stamps,
        ]);
    }

    public function saveEntry()
    {
        $db      = \Config\Database::connect();
        $batchNo = trim($this->request->getPost('batch_number') ?? '');

        if (!$batchNo) {
            return redirect()->to('gatti-stock/entry')->with('error', 'Batch number is required');
        }

        $entryType = $this->request->getPost('entry_type');
        $weightG   = (float)$this->request->getPost('weight_g');
        $touchPct  = (float)($this->request->getPost('touch_pct') ?? 0);
        $stampId   = $this->request->getPost('stamp_id') ?: null;

        if ($weightG <= 0) {
            return redirect()->to('gatti-stock/entry?batch=' . urlencode($batchNo))->with('error', 'Weight must be greater than 0');
        }

        // Look up or auto-create by batch_number
        $row = $db->query('SELECT * FROM gatti_stock WHERE batch_number = ?', [$batchNo])->getRowArray();

        if (!$row) {
            try {
                $db->table('gatti_stock')->insert([
                    'batch_number' => $batchNo,
                    'weight_g'     => 0,
                    'touch_pct'    => $touchPct,
                    'stamp_id'     => $stampId,
                    'qty_issued_g' => 0,
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            } catch (\Exception $e) {
                return redirect()->to('gatti-stock/entry?batch=' . urlencode($batchNo))->with('error', 'Batch number already exists or could not be created');
            }
            $row = $db->query('SELECT * FROM gatti_stock WHERE batch_number = ?', [$batchNo])->getRowArray();
        }

        $id = $row['id'];

        if ($entryType === 'out' && $weightG > (float)$row['weight_g']) {
            return redirect()->to('gatti-stock/entry?batch=' . urlencode($batchNo))->with('error', 'Cannot remove ' . $weightG . 'g — only ' . $row['weight_g'] . 'g in stock');
        }

        $delta = $entryType === 'out' ? -$weightG : $weightG;

        // Update weight, touch, and stamp (stamp_id: set if not already set, or always update)
        $updateData = ['weight_g = GREATEST(0, weight_g + ' . $delta . ')', 'touch_pct = ' . $touchPct];
        if ($stampId) $updateData[] = 'stamp_id = ' . (int)$stampId;
        $db->query('UPDATE gatti_stock SET weight_g = GREATEST(0, weight_g + ?), touch_pct = ?, stamp_id = COALESCE(stamp_id, ?) WHERE id = ?', [$delta, $touchPct, $stampId, $id]);

        // If stamp is explicitly being changed, allow override
        if ($stampId && $stampId != $row['stamp_id']) {
            $db->query('UPDATE gatti_stock SET stamp_id = ? WHERE id = ?', [$stampId, $id]);
        }

        $db->table('gatti_stock_log')->insert([
            'gatti_stock_id' => $id,
            'entry_type'     => $entryType,
            'reason'         => $this->request->getPost('reason') ?? 'manual',
            'weight_g'       => $weightG,
            'touch_pct'      => $touchPct,
            'notes'          => $this->request->getPost('notes') ?: null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $action = $entryType === 'out' ? 'Removed' : 'Added';
        return redirect()->to('gatti-stock/entry?batch=' . urlencode($batchNo))->with('success', $action . ' ' . number_format($weightG, 4) . 'g successfully');
    }

    public function update($id)
    {
        $db  = \Config\Database::connect();
        $row = $db->query('SELECT * FROM gatti_stock WHERE id = ?', [$id])->getRowArray();
        if (!$row) return redirect()->to('gatti-stock')->with('error', 'Batch not found');

        $newBatchNo = trim($this->request->getPost('batch_number') ?? $row['batch_number']);
        $touchPct   = (float)$this->request->getPost('touch_pct');
        $stampId    = $this->request->getPost('stamp_id') ?: null;
        $notes      = $this->request->getPost('notes') ?: null;

        // If batch_number is being renamed, check guards
        if ($newBatchNo !== (string)$row['batch_number']) {
            if ((float)$row['qty_issued_g'] > 0) {
                return redirect()->to('gatti-stock')->with('error', 'Cannot rename batch — it has been issued to part orders');
            }
            $logCount = $db->query('SELECT COUNT(*) as cnt FROM gatti_stock_log WHERE gatti_stock_id = ?', [$id])->getRowArray()['cnt'];
            if ($logCount > 0) {
                return redirect()->to('gatti-stock')->with('error', 'Cannot rename batch — stock log entries exist');
            }
            $taken = $db->query('SELECT id FROM gatti_stock WHERE batch_number = ? AND id != ?', [$newBatchNo, $id])->getRowArray();
            if ($taken) {
                return redirect()->to('gatti-stock')->with('error', 'Batch number "' . $newBatchNo . '" is already in use');
            }
        }

        $db->query('UPDATE gatti_stock SET batch_number = ?, touch_pct = ?, stamp_id = ?, notes = ? WHERE id = ?', [
            $newBatchNo ?: null, $touchPct, $stampId, $notes, $id
        ]);

        return redirect()->to('gatti-stock')->with('success', 'Batch updated successfully');
    }

    public function updateLogEntry($logId)
    {
        $db  = \Config\Database::connect();
        $log = $db->query('SELECT * FROM gatti_stock_log WHERE id = ?', [$logId])->getRowArray();
        if (!$log) return redirect()->to('gatti-stock')->with('error', 'Log entry not found');

        $row = $db->query('SELECT * FROM gatti_stock WHERE id = ?', [$log['gatti_stock_id']])->getRowArray();
        if (!$row) return redirect()->to('gatti-stock')->with('error', 'Gatti stock row not found');

        $newType    = $this->request->getPost('entry_type');
        $newWeight  = (float)$this->request->getPost('weight_g');
        $newTouch   = (float)$this->request->getPost('touch_pct');
        $newReason  = $this->request->getPost('reason') ?? 'manual';
        $newNotes   = $this->request->getPost('notes') ?: null;

        if ($newWeight <= 0) {
            return redirect()->to('gatti-stock/view/' . $row['id'])->with('error', 'Weight must be greater than 0');
        }

        // Reverse old delta to find available stock
        $oldDelta  = $log['entry_type'] === 'in' ? -(float)$log['weight_g'] : (float)$log['weight_g'];
        $available = (float)$row['weight_g'] + $oldDelta;

        if ($newType === 'out' && $newWeight > $available) {
            return redirect()->to('gatti-stock/view/' . $row['id'])->with('error',
                'Cannot remove ' . $newWeight . 'g — only ' . number_format($available, 4) . 'g available after reversal');
        }

        $newDelta = $newType === 'in' ? $newWeight : -$newWeight;
        $netDelta = $oldDelta + $newDelta;

        $db->query('UPDATE gatti_stock SET weight_g = GREATEST(0, weight_g + ?) WHERE id = ?', [$netDelta, $row['id']]);
        $db->query('UPDATE gatti_stock_log SET entry_type = ?, weight_g = ?, touch_pct = ?, reason = ?, notes = ? WHERE id = ?', [
            $newType, $newWeight, $newTouch, $newReason, $newNotes, $logId
        ]);

        return redirect()->to('gatti-stock/view/' . $row['id'])->with('success', 'Log entry updated successfully');
    }

    public function deleteLogEntry($logId)
    {
        $db  = \Config\Database::connect();
        $log = $db->query('SELECT * FROM gatti_stock_log WHERE id = ?', [$logId])->getRowArray();
        if (!$log) return redirect()->to('gatti-stock')->with('error', 'Log entry not found');

        $row = $db->query('SELECT * FROM gatti_stock WHERE id = ?', [$log['gatti_stock_id']])->getRowArray();
        if (!$row) return redirect()->to('gatti-stock')->with('error', 'Gatti stock row not found');

        $reverseDelta = $log['entry_type'] === 'in' ? -(float)$log['weight_g'] : (float)$log['weight_g'];
        $db->query('UPDATE gatti_stock SET weight_g = GREATEST(0, weight_g + ?) WHERE id = ?', [$reverseDelta, $row['id']]);
        $db->query('DELETE FROM gatti_stock_log WHERE id = ?', [$logId]);

        return redirect()->to('gatti-stock/view/' . $row['id'])->with('success', 'Entry deleted and stock reversed');
    }
}
