<?php
namespace App\Controllers;

class TouchShop extends BaseController
{
    /* ─────────────────────────────────────────────
     *  PRIVATE: get distinct touch shop names from DB
     * ───────────────────────────────────────────── */
    private function _shopNames($db): array
    {
        return $db->query(
            "SELECT DISTINCT touch_shop_name FROM touch_entry
             WHERE touch_shop_name IS NOT NULL AND touch_shop_name <> ''
             ORDER BY touch_shop_name"
        )->getResultArray();
    }

    /* ─────────────────────────────────────────────
     *  PRIVATE: resolve touch_shop_name from POST
     *  (handles "Add New" vs select from dropdown)
     * ───────────────────────────────────────────── */
    private function _resolveShopName(): ?string
    {
        $sel = trim($this->request->getPost('touch_shop_name') ?? '');
        if ($sel === '__new__') {
            $new = trim($this->request->getPost('touch_shop_name_new') ?? '');
            return $new !== '' ? $new : null;
        }
        return $sel !== '' ? $sel : null;
    }

    /* ─────────────────────────────────────────────
     *  PRIVATE: generate next serial number atomically
     * ───────────────────────────────────────────── */
    private function _nextSerialNumber($db): string
    {
        $db->query('UPDATE touch_serial_config SET last_number = last_number + 1 WHERE id = 1');
        $cfg = $db->query('SELECT prefix, last_number FROM touch_serial_config WHERE id = 1')->getRowArray();
        return $cfg['prefix'] . str_pad($cfg['last_number'], 4, '0', STR_PAD_LEFT);
    }

    /* ─────────────────────────────────────────────
     *  INDEX — unified ledger list
     * ───────────────────────────────────────────── */
    public function index()
    {
        $db = \Config\Database::connect();

        $qKarigar = (int)($this->request->getGet('karigar') ?? 0);
        $qStamp   = (int)($this->request->getGet('stamp')   ?? 0);
        $qStatus  = $this->request->getGet('status') ?? '';
        $qShop    = trim($this->request->getGet('shop') ?? '');

        $sql = "SELECT te.*,
                    k.name  AS karigar_name,
                    s.name  AS stamp_name,
                    gs.batch_number AS gatti_batch,
                    gs.touch_pct    AS gatti_touch,
                    mj.job_number
                FROM touch_entry te
                LEFT JOIN karigar      k   ON k.id  = te.karigar_id
                LEFT JOIN stamp        s   ON s.id  = te.stamp_id
                LEFT JOIN gatti_stock  gs  ON gs.id = te.gatti_stock_id
                LEFT JOIN melt_job_receive mjr ON mjr.id = te.melt_job_receive_id
                LEFT JOIN melt_job     mj  ON mj.id = mjr.melt_job_id
                WHERE 1=1";
        $binds = [];

        if ($qKarigar) { $sql .= ' AND te.karigar_id = ?'; $binds[] = $qKarigar; }
        if ($qStamp)   { $sql .= ' AND te.stamp_id   = ?'; $binds[] = $qStamp; }
        if ($qShop)    { $sql .= ' AND te.touch_shop_name = ?'; $binds[] = $qShop; }
        if ($qStatus === 'pending')   { $sql .= ' AND te.received_at IS NULL'; }
        if ($qStatus === 'completed') { $sql .= ' AND te.received_at IS NOT NULL'; }

        $sql .= ' ORDER BY te.created_at DESC';

        $entries  = $db->query($sql, $binds)->getResultArray();
        $karigars = $db->query("SELECT id, name FROM karigar ORDER BY name")->getResultArray();
        $stamps   = $db->query("SELECT id, name FROM stamp ORDER BY name")->getResultArray();
        $shopNames = $this->_shopNames($db);
        $cfg      = $db->query("SELECT prefix, last_number FROM touch_serial_config WHERE id = 1")->getRowArray();

        $totalIssued  = array_sum(array_column($entries, 'issue_weight_g'));
        $pendingCount = count(array_filter($entries, fn($e) => $e['received_at'] === null));

        return view('touch_shop/index', [
            'title'        => 'Touch Ledger',
            'entries'      => $entries,
            'karigars'     => $karigars,
            'stamps'       => $stamps,
            'shopNames'    => $shopNames,
            'totalIssued'  => $totalIssued,
            'pendingCount' => $pendingCount,
            'qKarigar'     => $qKarigar,
            'qStamp'       => $qStamp,
            'qStatus'      => $qStatus,
            'qShop'        => $qShop,
            'nextSerial'   => $cfg['prefix'] . str_pad($cfg['last_number'] + 1, 4, '0', STR_PAD_LEFT),
        ]);
    }

    /* ─────────────────────────────────────────────
     *  CREATE — new entry form
     * ───────────────────────────────────────────── */
    public function create()
    {
        $db = \Config\Database::connect();

        $cfg      = $db->query("SELECT prefix, last_number FROM touch_serial_config WHERE id = 1")->getRowArray();
        $karigars = $db->query("SELECT k.id, k.name, d.name AS dept FROM karigar k LEFT JOIN department d ON d.id = k.department_id ORDER BY d.name, k.name")->getResultArray();
        $stamps   = $db->query("SELECT id, name FROM stamp ORDER BY name")->getResultArray();
        $shopNames = $this->_shopNames($db);
        $gattis   = $db->query("SELECT gs.id, gs.batch_number, gs.weight_g, gs.touch_pct, mj.job_number
                                 FROM gatti_stock gs LEFT JOIN melt_job mj ON mj.id = gs.melt_job_id
                                 ORDER BY gs.created_at DESC LIMIT 200")->getResultArray();

        return view('touch_shop/form', [
            'title'       => 'New Touch Entry',
            'nextSerial'  => $cfg['prefix'] . str_pad($cfg['last_number'] + 1, 4, '0', STR_PAD_LEFT),
            'karigars'    => $karigars,
            'stamps'      => $stamps,
            'shopNames'   => $shopNames,
            'gattis'      => $gattis,
            'prefill'     => [],
        ]);
    }

    /* ─────────────────────────────────────────────
     *  PRIVATE: compress & save image, return path
     * ───────────────────────────────────────────── */
    private function _saveCompressed(string $srcPath, string $destPath, int $maxPx = 1200, int $quality = 80): bool
    {
        $info = @getimagesize($srcPath);
        if (!$info) return false;

        [$w, $h, $type] = [$info[0], $info[1], $info[2]];

        $src = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($srcPath),
            IMAGETYPE_PNG  => imagecreatefrompng($srcPath),
            IMAGETYPE_GIF  => imagecreatefromgif($srcPath),
            IMAGETYPE_WEBP => imagecreatefromwebp($srcPath),
            default        => false,
        };
        if (!$src) return false;

        // Resize if larger than maxPx
        if ($w > $maxPx || $h > $maxPx) {
            $ratio  = min($maxPx / $w, $maxPx / $h);
            $nw     = (int)round($w * $ratio);
            $nh     = (int)round($h * $ratio);
            $resized = imagecreatetruecolor($nw, $nh);
            // Preserve transparency for PNG
            if ($type === IMAGETYPE_PNG) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            imagecopyresampled($resized, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $resized;
        }

        $ok = imagejpeg($src, $destPath, $quality);
        imagedestroy($src);
        return $ok;
    }

    /* ─────────────────────────────────────────────
     *  PRIVATE: handle sample_image upload
     *  Returns path string or null
     * ───────────────────────────────────────────── */
    private function _handleSampleImage(?string $existingPath = null): ?string
    {
        $file = $this->request->getFile('sample_image');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array(strtolower($file->getClientExtension()), $allowed)) {
                return $existingPath;
            }
            $dest     = 'uploads/touch/si_' . time() . '.jpg';
            $destFull = FCPATH . $dest;
            if (!$this->_saveCompressed($file->getTempName(), $destFull)) {
                // fallback: move original without compression
                $file->move(FCPATH . 'uploads/touch/', 'si_' . time() . '.' . $file->getClientExtension());
                $dest = 'uploads/touch/si_' . time() . '.' . $file->getClientExtension();
            }
            if ($existingPath && file_exists(FCPATH . $existingPath)) {
                unlink(FCPATH . $existingPath);
            }
            return $dest;
        }
        return $existingPath;
    }

    /* ─────────────────────────────────────────────
     *  STORE — save new pending entry
     * ───────────────────────────────────────────── */
    public function store()
    {
        $db = \Config\Database::connect();

        $issueWt = (float)$this->request->getPost('issue_weight_g');
        if ($issueWt <= 0) return redirect()->back()->withInput()->with('error', 'Issue weight must be > 0');

        $serial = $this->_nextSerialNumber($db);
        $shopName = $this->_resolveShopName();
        $sampleImg = $this->_handleSampleImage();

        $db->table('touch_entry')->insert([
            'serial_number'      => $serial,
            'karigar_id'         => (int)$this->request->getPost('karigar_id') ?: null,
            'stamp_id'           => (int)$this->request->getPost('stamp_id') ?: null,
            'touch_shop_name'    => $shopName,
            'sample_image'       => $sampleImg,
            'gatti_stock_id'     => (int)$this->request->getPost('gatti_stock_id') ?: null,
            'melt_job_receive_id'=> null,
            'issue_weight_g'     => $issueWt,
            'notes'              => $this->request->getPost('notes') ?: null,
            'created_by'         => $this->currentUser(),
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('touch-shops')->with('success', 'Entry '.$serial.' created');
    }

    /* ─────────────────────────────────────────────
     *  EDIT — show edit form for pending entry
     * ───────────────────────────────────────────── */
    public function edit($id)
    {
        $db    = \Config\Database::connect();
        $entry = $db->query('SELECT * FROM touch_entry WHERE id = ?', [$id])->getRowArray();
        if (!$entry) return redirect()->to('touch-shops')->with('error', 'Entry not found');
        if ($entry['received_at']) return redirect()->to('touch-shops')->with('error', 'Cannot edit completed entry');

        $karigars  = $db->query("SELECT k.id, k.name, d.name AS dept FROM karigar k LEFT JOIN department d ON d.id = k.department_id ORDER BY d.name, k.name")->getResultArray();
        $stamps    = $db->query("SELECT id, name FROM stamp ORDER BY name")->getResultArray();
        $shopNames = $this->_shopNames($db);
        $gattis    = $db->query("SELECT gs.id, gs.batch_number, gs.weight_g, gs.touch_pct, mj.job_number
                                  FROM gatti_stock gs LEFT JOIN melt_job mj ON mj.id = gs.melt_job_id
                                  ORDER BY gs.created_at DESC LIMIT 200")->getResultArray();

        return view('touch_shop/form', [
            'title'      => 'Edit Touch Entry — ' . $entry['serial_number'],
            'nextSerial' => $entry['serial_number'],
            'entry'      => $entry,
            'karigars'   => $karigars,
            'stamps'     => $stamps,
            'shopNames'  => $shopNames,
            'gattis'     => $gattis,
            'prefill'    => $entry,
            'isEdit'     => true,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  UPDATE — save edits to pending entry
     * ───────────────────────────────────────────── */
    public function update($id)
    {
        $db    = \Config\Database::connect();
        $entry = $db->query('SELECT * FROM touch_entry WHERE id = ?', [$id])->getRowArray();
        if (!$entry) return redirect()->to('touch-shops')->with('error', 'Entry not found');
        if ($entry['received_at']) return redirect()->to('touch-shops')->with('error', 'Cannot edit completed entry');

        $issueWt = (float)$this->request->getPost('issue_weight_g');
        if ($issueWt <= 0) return redirect()->back()->withInput()->with('error', 'Issue weight must be > 0');

        $shopName  = $this->_resolveShopName();
        $sampleImg = $this->_handleSampleImage($entry['sample_image']);

        $db->table('touch_entry')->where('id', $id)->update([
            'karigar_id'      => (int)$this->request->getPost('karigar_id') ?: null,
            'stamp_id'        => (int)$this->request->getPost('stamp_id') ?: null,
            'touch_shop_name' => $shopName,
            'sample_image'    => $sampleImg,
            'gatti_stock_id'  => (int)$this->request->getPost('gatti_stock_id') ?: null,
            'issue_weight_g'  => $issueWt,
            'notes'           => $this->request->getPost('notes') ?: null,
        ]);

        return redirect()->to('touch-shops')->with('success', 'Entry '.$entry['serial_number'].' updated');
    }

    /* ─────────────────────────────────────────────
     *  RECEIVE BACK — complete entry + optional photo
     * ───────────────────────────────────────────── */
    public function receiveBack($id)
    {
        $db    = \Config\Database::connect();
        $entry = $db->query('SELECT * FROM touch_entry WHERE id = ?', [$id])->getRowArray();
        if (!$entry) return redirect()->to('touch-shops')->with('error', 'Entry not found');
        if ($entry['received_at']) return redirect()->to('touch-shops')->with('error', 'Entry already completed');

        $recvWt    = (float)$this->request->getPost('receive_weight_g');
        $touchPct  = (float)$this->request->getPost('touch_result_pct');
        $notes     = $this->request->getPost('notes');

        if ($recvWt <= 0)           return redirect()->back()->with('error', 'Receive weight must be > 0');
        if ($recvWt > $entry['issue_weight_g']) return redirect()->back()->with('error', 'Receive weight cannot exceed issued weight ('.$entry['issue_weight_g'].'g)');
        if ($touchPct < 0 || $touchPct > 100) return redirect()->back()->with('error', 'Touch% must be 0–100');

        // Handle photo upload with compression
        $photoPath = $entry['touch_photo'];
        $file = $this->request->getFile('touch_photo');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array(strtolower($file->getClientExtension()), $allowed)) {
                return redirect()->back()->with('error', 'Invalid photo type. Allowed: jpg, png, gif, webp');
            }
            $dest     = 'uploads/touch/' . $entry['serial_number'] . '_tf_' . time() . '.jpg';
            $destFull = FCPATH . $dest;
            if (!$this->_saveCompressed($file->getTempName(), $destFull)) {
                // fallback: move original
                $fname = $entry['serial_number'] . '_tf_' . time() . '.' . $file->getClientExtension();
                $file->move(FCPATH . 'uploads/touch/', $fname);
                $dest = 'uploads/touch/' . $fname;
            }
            $photoPath = $dest;
        }

        $db->table('touch_entry')->where('id', $id)->update([
            'receive_weight_g' => $recvWt,
            'touch_result_pct' => $touchPct,
            'touch_photo'      => $photoPath,
            'notes'            => $notes ?: $entry['notes'],
            'received_at'      => date('Y-m-d H:i:s'),
        ]);

        // Update gatti_stock touch_pct if linked
        if ($entry['gatti_stock_id'] && $touchPct > 0) {
            $db->query('UPDATE gatti_stock SET touch_pct = ? WHERE id = ?', [$touchPct, $entry['gatti_stock_id']]);
            $db->table('gatti_stock_log')->insert([
                'gatti_stock_id' => $entry['gatti_stock_id'],
                'entry_type'     => 'in',
                'reason'         => 'touch_test',
                'weight_g'       => 0,
                'touch_pct'      => $touchPct,
                'notes'          => 'Touch result from entry '.$entry['serial_number'],
                'created_by'     => $this->currentUser(),
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->to('touch-shops')->with('success', 'Entry '.$entry['serial_number'].' completed — '.$recvWt.'g received, touch '.$touchPct.'%');
    }

    /* ─────────────────────────────────────────────
     *  DELETE — only pending entries
     * ───────────────────────────────────────────── */
    public function delete($id)
    {
        $db    = \Config\Database::connect();
        $entry = $db->query('SELECT * FROM touch_entry WHERE id = ?', [$id])->getRowArray();
        if (!$entry) return redirect()->to('touch-shops')->with('error', 'Entry not found');
        if ($entry['received_at']) return redirect()->to('touch-shops')->with('error', 'Cannot delete completed entry');

        // Remove photo file if exists
        if ($entry['touch_photo'] && file_exists(FCPATH . $entry['touch_photo'])) {
            unlink(FCPATH . $entry['touch_photo']);
        }

        $db->table('touch_entry')->where('id', $id)->delete();
        return redirect()->to('touch-shops')->with('success', 'Entry '.$entry['serial_number'].' deleted');
    }

    /* ─────────────────────────────────────────────
     *  ISSUE FROM MELT JOB — auto-create from Touch Gatti row
     * ───────────────────────────────────────────── */
    public function issueFromMeltJob($recvId)
    {
        $db   = \Config\Database::connect();
        $recv = $db->query(
            'SELECT mjr.*, mj.karigar_id, mj.id AS melt_job_id, mj.job_number, mjr.gatti_stock_id AS recv_gatti_stock_id
             FROM melt_job_receive mjr JOIN melt_job mj ON mj.id = mjr.melt_job_id
             WHERE mjr.id = ?', [$recvId]
        )->getRowArray();
        if (!$recv) return redirect()->back()->with('error', 'Receive row not found');

        // Check not already issued
        $existing = $db->query('SELECT id, serial_number FROM touch_entry WHERE melt_job_receive_id = ?', [$recvId])->getRowArray();
        if ($existing) return redirect()->to('melt-jobs/view/'.$recv['melt_job_id'].'#receivedSection')->with('error', 'Already issued as '.$existing['serial_number']);

        $issueWt    = (float)$this->request->getPost('issue_weight_g');
        $stampId    = (int)$this->request->getPost('stamp_id') ?: null;
        $notes      = $this->request->getPost('notes');
        $gattiStockId = (int)$this->request->getPost('gatti_stock_id') ?: $recv['recv_gatti_stock_id'] ?: null;

        if ($issueWt <= 0) return redirect()->to('melt-jobs/view/'.$recv['melt_job_id'].'#receivedSection')->with('error', 'Issue weight must be > 0');

        $serial    = $this->_nextSerialNumber($db);
        $shopName  = $this->_resolveShopName();
        $sampleImg = $this->_handleSampleImage();

        $db->table('touch_entry')->insert([
            'serial_number'       => $serial,
            'karigar_id'          => $recv['karigar_id'] ?: null,
            'stamp_id'            => $stampId,
            'touch_shop_name'     => $shopName,
            'sample_image'        => $sampleImg,
            'gatti_stock_id'      => $gattiStockId,
            'melt_job_receive_id' => $recvId,
            'issue_weight_g'      => $issueWt,
            'notes'               => $notes ?: null,
            'created_by'          => $this->currentUser(),
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('melt-jobs/view/'.$recv['melt_job_id'].'#receivedSection')
            ->with('success', 'Touch entry '.$serial.' created for Job '.$recv['job_number']);
    }
}
