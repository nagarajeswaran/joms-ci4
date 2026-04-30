<?php
namespace App\Controllers;

class KarigarLedger extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $karigars = $db->query('SELECT k.*, d.name as dept_name FROM karigar k LEFT JOIN department d ON d.id = k.department_id ORDER BY k.name')->getResultArray();

        foreach ($karigars as &$k) {
            $fine = $db->query('SELECT SUM(CASE WHEN direction="debit" THEN amount ELSE -amount END) as bal FROM karigar_ledger WHERE karigar_id = ? AND account_type = "fine"', [$k['id']])->getRowArray();
            $cash = $db->query('SELECT SUM(CASE WHEN direction="credit" THEN amount ELSE -amount END) as bal FROM karigar_ledger WHERE karigar_id = ? AND account_type = "cash"', [$k['id']])->getRowArray();
            $k['fine_balance'] = $fine['bal'] ?? 0;
            $k['cash_balance'] = $cash['bal'] ?? 0;
        }

        return view('karigar_ledger/index', ['title' => 'Karigar Ledger Summary', 'karigars' => $karigars]);
    }

    public function detail($karigarId)
    {
        $db      = \Config\Database::connect();
        $karigar = $db->query('SELECT k.*, d.name as dept_name FROM karigar k LEFT JOIN department d ON d.id = k.department_id WHERE k.id = ?', [$karigarId])->getRowArray();
        if (!$karigar) return redirect()->to('karigar-ledger')->with('error', 'Not found');

        $ledger = $db->query('SELECT * FROM karigar_ledger WHERE karigar_id = ? ORDER BY posted_at ASC', [$karigarId])->getResultArray();
        $conversions = $db->query('SELECT * FROM karigar_ledger_conversion WHERE karigar_id = ? ORDER BY converted_at DESC', [$karigarId])->getResultArray();

        $fineBalance = 0;
        $cashBalance = 0;
        foreach ($ledger as &$row) {
            if ($row['account_type'] === 'fine') {
                $fineBalance += $row['direction'] === 'debit' ? $row['amount'] : -$row['amount'];
            } else {
                $cashBalance += $row['direction'] === 'credit' ? $row['amount'] : -$row['amount'];
            }
            $row['fine_running'] = $fineBalance;
            $row['cash_running'] = $cashBalance;
        }
        unset($row);

        return view('karigar_ledger/detail', [
            'title'       => 'Ledger: '.esc($karigar['name']),
            'karigar'     => $karigar,
            'ledger'      => $ledger,
            'conversions' => $conversions,
            'fineBalance' => $fineBalance,
            'cashBalance' => $cashBalance,
        ]);
    }

    public function convert($karigarId)
    {
        $db      = \Config\Database::connect();
        $karigar = $db->query('SELECT * FROM karigar WHERE id = ?', [$karigarId])->getRowArray();
        if (!$karigar) return redirect()->to('karigar-ledger')->with('error', 'Not found');

        $fine = $db->query('SELECT SUM(CASE WHEN direction="debit" THEN amount ELSE -amount END) as bal FROM karigar_ledger WHERE karigar_id = ? AND account_type = "fine"', [$karigarId])->getRowArray();
        $cash = $db->query('SELECT SUM(CASE WHEN direction="credit" THEN amount ELSE -amount END) as bal FROM karigar_ledger WHERE karigar_id = ? AND account_type = "cash"', [$karigarId])->getRowArray();

        return view('karigar_ledger/convert', [
            'title'       => 'Convert: '.esc($karigar['name']),
            'karigar'     => $karigar,
            'fineBalance' => $fine['bal'] ?? 0,
            'cashBalance' => $cash['bal'] ?? 0,
        ]);
    }

    public function storeConvert($karigarId)
    {
        $db          = \Config\Database::connect();
        $fromAccount = $this->request->getPost('from_account');
        $fromAmount  = (float)$this->request->getPost('from_amount');
        $rate        = (float)$this->request->getPost('rate_per_kg');
        $notes       = $this->request->getPost('notes');
        $toAccount   = $fromAccount === 'fine' ? 'cash' : 'fine';

        if ($fromAccount === 'fine') {
            $toAmount = $fromAmount / 1000 * $rate;
        } else {
            $toAmount = $fromAmount / $rate * 1000;
        }

        $db->table('karigar_ledger_conversion')->insert([
            'karigar_id'   => $karigarId,
            'from_account' => $fromAccount,
            'to_account'   => $toAccount,
            'from_amount'  => $fromAmount,
            'to_amount'    => round($toAmount, 4),
            'rate_per_kg'  => $rate,
            'notes'        => $notes,
            'created_by'   => $this->currentUser(),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        // Debit from source
        $db->table('karigar_ledger')->insert([
            'karigar_id'   => $karigarId,
            'source_type'  => 'part_order',
            'source_id'    => 0,
            'account_type' => $fromAccount,
            'direction'    => $fromAccount === 'fine' ? 'credit' : 'debit',
            'amount'       => $fromAmount,
            'narration'    => 'Conversion: '.ucfirst($fromAccount).' to '.ucfirst($toAccount).' @ Rs'.$rate.'/kg'.($notes ? ' | '.$notes : ''),
            'created_by'   => $this->currentUser(),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        // Credit to destination
        $db->table('karigar_ledger')->insert([
            'karigar_id'   => $karigarId,
            'source_type'  => 'part_order',
            'source_id'    => 0,
            'account_type' => $toAccount,
            'direction'    => $toAccount === 'cash' ? 'credit' : 'debit',
            'amount'       => round($toAmount, 4),
            'narration'    => 'Conversion: '.ucfirst($fromAccount).' to '.ucfirst($toAccount).' @ Rs'.$rate.'/kg'.($notes ? ' | '.$notes : ''),
            'created_by'   => $this->currentUser(),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('karigar-ledger/'.$karigarId)->with('success', 'Conversion posted');
    }
}
