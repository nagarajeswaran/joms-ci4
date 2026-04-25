<?php

namespace App\Controllers;

use App\Models\UserModel;
use Config\Database;
use Throwable;

class Login extends BaseController
{
    protected UserModel $userModel;
    protected $db;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->db = Database::connect();
    }

    public function index()
    {
        if (session()->has('user')) {
            return redirect()->to(base_url($this->getRoleHomePath((string) (session()->get('user')['role'] ?? 'admin'))));
        }

        $hasUsers = $this->hasUsers();

        if (strtolower($this->request->getMethod()) === 'post') {
            if (!$hasUsers && $this->request->getPost('setup_admin')) {
                return $this->createFirstAdmin();
            }

            $username = trim((string) $this->request->getPost('username'));
            $password = (string) $this->request->getPost('password');
            $user = $this->userModel->login($username, $password);

            if ($user) {
                // Decode modules CSV into array for session
                $user['modules'] = array_values(array_filter(
                    array_map('trim', explode(',', (string) ($user['modules'] ?? '')))
                ));

                session()->set('user', $user);
                session()->set([
                    'logged_in' => true,
                    'username' => $user['username'] ?? $username,
                    'user_id' => $user['id'] ?? null,
                ]);

                return redirect()->to(base_url($this->getRoleHomePath((string) ($user['role'] ?? 'admin'))));
            }

            return redirect()->back()->withInput()->with('error', 'Invalid username or password');
        }

        return view('login/index', [
            'canSetupAdmin' => !$hasUsers,
        ]);
    }

    public function logout()
    {
        session()->remove(['user', 'logged_in', 'username', 'user_id']);
        session()->destroy();
        return redirect()->to(base_url('login'));
    }

    private function hasUsers(): bool
    {
        try {
            return $this->db->table('user')->countAllResults() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function createFirstAdmin()
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $name = trim((string) $this->request->getPost('name'));
        $email = trim((string) $this->request->getPost('email'));

        if ($username === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'Username and password are required');
        }

        if (strlen($password) < 6) {
            return redirect()->back()->withInput()->with('error', 'Password must be at least 6 characters');
        }

        if ($this->hasUsers()) {
            return redirect()->to(base_url('login'))->with('error', 'Admin user already exists. Please log in.');
        }

        $columns = $this->db->getFieldNames('user');
        $insert = [];

        if (in_array('username', $columns, true)) {
            $existingUsername = $this->db->table('user')->where('username', $username)->get()->getRowArray();
            if ($existingUsername) {
                return redirect()->back()->withInput()->with('error', 'Username already exists');
            }
            $insert['username'] = $username;
        }

        if (in_array('password', $columns, true)) {
            $passwordMeta = $this->db->getFieldData('user');
            $passwordMaxLength = 0;
            foreach ($passwordMeta as $field) {
                if (($field->name ?? '') === 'password') {
                    $passwordMaxLength = isset($field->max_length) ? (int) $field->max_length : 0;
                    break;
                }
            }

            $insert['password'] = ($passwordMaxLength > 0 && $passwordMaxLength < 60)
                ? md5($password)
                : password_hash($password, PASSWORD_DEFAULT);
        }

        if (in_array('name', $columns, true)) {
            $insert['name'] = $name !== '' ? $name : $username;
        }
        if (in_array('email', $columns, true) && $email !== '') {
            $existingEmail = $this->db->table('user')->where('email', $email)->get()->getRowArray();
            if ($existingEmail) {
                return redirect()->back()->withInput()->with('error', 'Email already exists');
            }
            $insert['email'] = $email;
        }
        if (in_array('role', $columns, true)) {
            $insert['role'] = 'admin';
        }
        if (in_array('status', $columns, true)) {
            $insert['status'] = 'active';
        }
        if (in_array('created_at', $columns, true)) {
            $insert['created_at'] = date('Y-m-d H:i:s');
        }
        if (in_array('updated_at', $columns, true)) {
            $insert['updated_at'] = date('Y-m-d H:i:s');
        }

        try {
            $this->db->table('user')->insert($insert);
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if (! $this->db->affectedRows()) {
            return redirect()->back()->withInput()->with('error', 'Could not create admin user.');
        }

        return redirect()->to(base_url('login'))->with('success', 'Admin user created. Please log in.');
    }
}
