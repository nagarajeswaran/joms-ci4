<?php

namespace App\Controllers;

use App\Models\UserModel;

class Staff extends BaseController
{
    protected UserModel $userModel;
    protected $db;
    protected array $userColumns = [];
    protected array $userColumnMeta = [];

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
        $this->userColumns = $this->db->getFieldNames('user');
        foreach ($this->db->getFieldData('user') as $field) {
            $this->userColumnMeta[$field->name] = $field;
        }
    }

    public function login()
    {
        $this->ensureLoginAuditTable();
        $this->ensureStaffUserTable();

        if (session()->get('staff_logged_in')) {
            return redirect()->to(base_url('staff'));
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $username = trim((string) $this->request->getPost('username'));
            $password = (string) $this->request->getPost('password');

            if ($username === '' || $password === '') {
                return redirect()->to(base_url('staff/login'))->withInput()->with('error', 'Enter username and password');
            }

            $user = $this->userModel->login($username, $password);

            if (!$user || !$this->isStaffUser($user)) {
                return redirect()->to(base_url('staff/login'))->withInput()->with('error', 'Invalid username, name, password, or staff access');
            }

            session()->set([
                'staff_logged_in' => true,
                'staff_user'      => [
                    'id'       => $user['id'] ?? null,
                    'username' => $user['username'] ?? $username,
                    'name'     => $user['name'] ?? ($user['username'] ?? $username),
                    'role'     => $this->getStaffRole((int) ($user['id'] ?? 0)),
                ],
            ]);
            $this->recordLogin((int) ($user['id'] ?? 0), (string) ($user['username'] ?? $username));

            return redirect()->to(base_url('staff'));
        }

        return view('login/staff');
    }

    public function logout()
    {
        $this->closeLoginSession();
        session()->remove(['staff_logged_in', 'staff_user']);
        return redirect()->to(base_url('staff/login'));
    }

    public function index()
    {
        $this->ensureLoginAuditTable();
        $this->ensureStaffUserTable();

        $touchSummary = $this->db->query(
            'SELECT
                COUNT(*) AS total_entries,
                SUM(CASE WHEN received_at IS NULL THEN 1 ELSE 0 END) AS pending_entries,
                COALESCE(SUM(issue_weight_g), 0) AS total_issue_weight
             FROM touch_entry'
        )->getRowArray();

        $stockSummary = $this->db->query(
            'SELECT
                COUNT(DISTINCT product_id) AS product_count,
                COALESCE(SUM(qty), 0) AS total_qty
             FROM product_stock'
        )->getRowArray();

        $loginSummary = $this->db->query(
            'SELECT
                COUNT(*) AS total_logins,
                SUM(CASE WHEN logout_at IS NULL THEN 1 ELSE 0 END) AS active_sessions
             FROM staff_login_log'
        )->getRowArray();

        $recentLogins = $this->db->query(
            'SELECT username, login_at, logout_at, login_status
             FROM staff_login_log
             ORDER BY id DESC
             LIMIT 8'
        )->getResultArray();

        return view('staff/dashboard', [
            'title'        => 'Staff Access',
            'touchSummary' => $touchSummary,
            'stockSummary' => $stockSummary,
            'loginSummary' => $loginSummary,
            'recentLogins' => $recentLogins,
            'staffUser'    => session()->get('staff_user'),
        ]);
    }

    public function users()
    {
        $this->ensureAdminOrStaffSession();

        $staffUsers = $this->db->query(
            'SELECT su.id AS staff_access_id,
                    u.id,
                    u.username,
                    ' . $this->selectUserColumn('name') . ' AS name,
                    ' . $this->selectUserColumn('email') . ' AS email,
                    su.role,
                    su.status,
                    su.created_at,
                    ' . $this->selectUserColumn('updated_at') . ' AS updated_at
             FROM staff_user_access su
             JOIN user u ON u.id = su.user_id
             ORDER BY su.id DESC'
        )->getResultArray();

        $this->ensureLoginAuditTable();
        $loginLogs = $this->db->query(
            'SELECT username, login_at, logout_at, ip_address, login_status
             FROM staff_login_log
             ORDER BY id DESC
             LIMIT 50'
        )->getResultArray();

        return view('staff/users', [
            'title'      => 'Staff Users',
            'staffUsers' => $staffUsers,
            'loginLogs'  => $loginLogs,
        ]);
    }

    public function createUser()
    {
        $this->ensureStaffUserTable();
        $this->ensureAdminOrStaffSession();

        $hasStaffUsers = $this->db->query(
            'SELECT id FROM staff_user_access LIMIT 1'
        )->getRowArray();

        if (strtolower($this->request->getMethod()) === 'post') {
            $username = trim((string) $this->request->getPost('username'));
            $password = (string) $this->request->getPost('password');
            $name = trim((string) $this->request->getPost('name'));
            $email = trim((string) $this->request->getPost('email'));
            $role = trim((string) ($this->request->getPost('role') ?: 'staff'));
            $status = trim((string) ($this->request->getPost('status') ?: 'active'));

            if ($username === '' || $password === '') {
                return redirect()->back()->withInput()->with('error', 'Username and password are required');
            }

            if (strlen($password) < 6) {
                return redirect()->back()->withInput()->with('error', 'Password must be at least 6 characters');
            }

            $existing = $this->db->table('user')->where('username', $username)->get()->getRowArray();
            if ($existing) {
                return redirect()->back()->withInput()->with('error', 'Username already exists');
            }

            if ($email !== '' && $this->hasUserColumn('email')) {
                $existingEmail = $this->db->table('user')->where('email', $email)->get()->getRowArray();
                if ($existingEmail) {
                    return redirect()->back()->withInput()->with('error', 'Email already exists');
                }
            }

            $insert = [
                'username'   => $username,
                'password'   => $this->buildPasswordValue($password),
            ];

            if ($this->hasUserColumn('name')) {
                $insert['name'] = $name !== '' ? $name : $username;
            }
            if ($this->hasUserColumn('created_at')) {
                $insert['created_at'] = date('Y-m-d H:i:s');
            }
            if ($this->hasUserColumn('updated_at')) {
                $insert['updated_at'] = date('Y-m-d H:i:s');
            }
            if ($email !== '' && $this->hasUserColumn('email')) {
                $insert['email'] = $email;
            }

            $this->db->table('user')->insert($insert);
            $userId = (int) $this->db->insertID();

            $this->db->table('staff_user_access')->insert([
                'user_id'    => $userId,
                'role'       => $role !== '' ? $role : 'staff',
                'status'     => $status !== '' ? $status : 'active',
                'created_by' => $this->currentUser(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if (!session()->get('staff_logged_in')) {
                return redirect()->to(base_url('staff/login'))->with('success', 'First staff user created. Please log in.');
            }

            return redirect()->to(base_url('staff/users'))->with('success', 'Staff user created successfully');
        }

        return view('staff/create_user', [
            'title' => 'Create Staff User',
        ]);
    }

    public function touchBooking()
    {
        $status = (string) ($this->request->getGet('status') ?? 'pending');

        $sql = 'SELECT te.*, k.name AS karigar_name, s.name AS stamp_name
                FROM touch_entry te
                LEFT JOIN karigar k ON k.id = te.karigar_id
                LEFT JOIN stamp s ON s.id = te.stamp_id';

        if ($status === 'completed') {
            $sql .= ' WHERE te.received_at IS NOT NULL';
        } else {
            $sql .= ' WHERE te.received_at IS NULL';
        }

        $sql .= ' ORDER BY te.created_at DESC LIMIT 40';

        $entries = $this->db->query($sql)->getResultArray();

        return view('staff/touch_index', [
            'title'   => 'Touch Booking',
            'entries' => $entries,
            'status'  => $status,
        ]);
    }

    public function touchCreate()
    {
        $cfg = $this->db->query('SELECT prefix, last_number FROM touch_serial_config WHERE id = 1')->getRowArray();
        $shopNames = $this->db->query(
            "SELECT DISTINCT touch_shop_name FROM touch_entry
             WHERE touch_shop_name IS NOT NULL AND touch_shop_name <> ''
             ORDER BY touch_shop_name"
        )->getResultArray();
        $karigars = $this->db->query(
            'SELECT k.id, k.name, d.name AS dept
             FROM karigar k
             LEFT JOIN department d ON d.id = k.department_id
             ORDER BY d.name, k.name'
        )->getResultArray();
        $stamps = $this->db->query('SELECT id, name FROM stamp ORDER BY name')->getResultArray();

        return view('staff/touch_form', [
            'title'      => 'New Touch Booking',
            'nextSerial' => ($cfg['prefix'] ?? 'TS') . str_pad((string) (($cfg['last_number'] ?? 0) + 1), 4, '0', STR_PAD_LEFT),
            'shopNames'  => $shopNames,
            'karigars'   => $karigars,
            'stamps'     => $stamps,
        ]);
    }

    public function touchStore()
    {
        $issueWeight = (float) $this->request->getPost('issue_weight_g');
        if ($issueWeight <= 0) {
            return redirect()->back()->withInput()->with('error', 'Issue weight must be greater than 0');
        }

        $this->db->query('UPDATE touch_serial_config SET last_number = last_number + 1 WHERE id = 1');
        $cfg = $this->db->query('SELECT prefix, last_number FROM touch_serial_config WHERE id = 1')->getRowArray();
        $serial = ($cfg['prefix'] ?? 'TS') . str_pad((string) ($cfg['last_number'] ?? 0), 4, '0', STR_PAD_LEFT);

        $touchShopName = trim((string) $this->request->getPost('touch_shop_name'));
        if ($touchShopName === '__new__') {
            $touchShopName = trim((string) $this->request->getPost('touch_shop_name_new'));
        }

        $this->db->table('touch_entry')->insert([
            'serial_number'   => $serial,
            'karigar_id'      => (int) $this->request->getPost('karigar_id') ?: null,
            'stamp_id'        => (int) $this->request->getPost('stamp_id') ?: null,
            'touch_shop_name' => $touchShopName ?: null,
            'issue_weight_g'  => $issueWeight,
            'notes'           => trim((string) $this->request->getPost('notes')) ?: null,
            'created_by'      => $this->currentUser(),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(base_url('staff/touch-booking'))->with('success', 'Touch booking created: ' . $serial);
    }

    public function stockLookup()
    {
        $query = trim((string) ($this->request->getGet('q') ?? ''));
        $rows = [];

        if ($query !== '') {
            $rows = $this->db->query(
                'SELECT
                    p.id AS product_id,
                    p.name AS product_name,
                    p.sku,
                    p.image AS product_image,
                    pp.name AS pattern_name,
                    pp.pattern_code,
                    v.name AS variation_name,
                    v.size AS variation_size,
                    sl.name AS location_name,
                    ps.qty,
                    ps.min_qty
                 FROM product_stock ps
                 JOIN product p ON p.id = ps.product_id
                 JOIN product_pattern pp ON pp.id = ps.pattern_id
                 JOIN variation v ON v.id = ps.variation_id
                 JOIN stock_location sl ON sl.id = ps.location_id
                 WHERE sl.is_active = 1
                   AND (p.name LIKE ? OR p.sku LIKE ? OR pp.name LIKE ? OR pp.pattern_code LIKE ?)
                 ORDER BY p.name, pp.name, v.size, sl.name
                 LIMIT 80',
                ["%{$query}%", "%{$query}%", "%{$query}%", "%{$query}%"]
            )->getResultArray();
        }

        return view('staff/stock_lookup', [
            'title' => 'Stock Lookup',
            'query' => $query,
            'rows'  => $rows,
        ]);
    }

    private function isStaffUser(array $user): bool
    {
        $role = strtolower(trim($this->getStaffRole((int) ($user['id'] ?? 0))));

        if ($role === '') {
            return false;
        }

        if (str_contains($role, 'staff')) {
            return true;
        }

        return in_array($role, ['touch', 'stock', 'touch_booking', 'stock_lookup'], true);
    }

    private function getStaffRole(int $userId): string
    {
        if ($userId <= 0) {
            return '';
        }

        $staffAccess = $this->db->table('staff_user_access')
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        return (string) ($staffAccess['role'] ?? '');
    }

    private function ensureStaffUserTable(): void
    {
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS staff_user_access (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                role VARCHAR(50) NOT NULL DEFAULT "staff",
                status VARCHAR(20) NOT NULL DEFAULT "active",
                created_at DATETIME NOT NULL,
                UNIQUE KEY uq_staff_user_access_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function hasUserColumn(string $column): bool
    {
        return in_array($column, $this->userColumns, true);
    }

    private function buildPasswordValue(string $password): string
    {
        $passwordColumn = $this->userColumnMeta['password'] ?? null;
        $maxLength = isset($passwordColumn->max_length) ? (int) $passwordColumn->max_length : 0;

        if ($maxLength > 0 && $maxLength < 60) {
            return md5($password);
        }

        return password_hash($password, PASSWORD_DEFAULT);
    }

    private function selectUserColumn(string $column): string
    {
        if ($this->hasUserColumn($column)) {
            return 'u.' . $column;
        }

        return "''";
    }

    private function ensureLoginAuditTable(): void
    {
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS staff_login_log (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                username VARCHAR(100) NOT NULL,
                session_id VARCHAR(128) NULL,
                ip_address VARCHAR(64) NULL,
                user_agent TEXT NULL,
                login_status VARCHAR(20) NOT NULL DEFAULT "success",
                login_at DATETIME NOT NULL,
                logout_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function recordLogin(int $userId, string $username): void
    {
        if ($userId <= 0 || $username === '') {
            return;
        }

        $this->db->table('staff_login_log')->insert([
            'user_id'      => $userId,
            'username'     => $username,
            'session_id'   => session_id(),
            'ip_address'   => (string) $this->request->getIPAddress(),
            'user_agent'   => (string) $this->request->getUserAgent(),
            'login_status' => 'success',
            'login_at'     => date('Y-m-d H:i:s'),
        ]);

        session()->set('staff_login_log_id', $this->db->insertID());
    }

    private function closeLoginSession(): void
    {
        $this->ensureLoginAuditTable();

        $logId = (int) session()->get('staff_login_log_id');
        if ($logId > 0) {
            $this->db->table('staff_login_log')
                ->where('id', $logId)
                ->update(['logout_at' => date('Y-m-d H:i:s')]);
        }

        session()->remove('staff_login_log_id');
    }

    private function ensureAdminOrStaffSession(): void
    {
        if (session()->has('user')) {
            return;
        }

        if (session()->get('staff_logged_in')) {
            return;
        }

        redirect()->to(base_url('login'))->with('error', 'Please log in to manage users')->send();
        exit;
    }
}