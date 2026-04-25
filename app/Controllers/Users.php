<?php

namespace App\Controllers;

use App\Models\UserModel;
use Config\RoleAccess;

class Users extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();
        $users = $db->table('user')
            ->select('id, username, name, email, role, modules, status, created_at')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        return view('users/index', [
            'title'   => 'User Management',
            'users'   => $users,
            'modules' => RoleAccess::MODULE_OPTIONS,
        ]);
    }

    public function create()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return view('users/create', [
                'title'   => 'Create User',
                'modules' => RoleAccess::MODULE_OPTIONS,
                'errors'  => [],
            ]);
        }

        $username       = trim((string) $this->request->getPost('username'));
        $password       = (string) $this->request->getPost('password');
        $name           = trim((string) $this->request->getPost('name'));
        $email          = trim((string) $this->request->getPost('email'));
        $role           = $this->request->getPost('role') === 'admin' ? 'admin' : 'user';
        $selectedModules = (array) ($this->request->getPost('modules') ?? []);
        $status         = $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active';

        $errors = [];

        if ($username === '') {
            $errors['username'] = 'Username is required.';
        } elseif ($this->userModel->where('username', $username)->countAllResults() > 0) {
            $errors['username'] = 'Username already exists.';
        }

        if (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }

        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email address.';
            } elseif ($this->userModel->where('email', $email)->countAllResults() > 0) {
                $errors['email'] = 'Email already exists.';
            }
        }

        if ($errors !== []) {
            return view('users/create', [
                'title'           => 'Create User',
                'modules'         => RoleAccess::MODULE_OPTIONS,
                'errors'          => $errors,
                'old_username'    => $username,
                'old_name'        => $name,
                'old_email'       => $email,
                'old_role'        => $role,
                'old_modules'     => $selectedModules,
                'old_status'      => $status,
            ]);
        }

        // Filter modules to valid keys only
        $validModules = array_intersect($selectedModules, array_keys(RoleAccess::MODULE_OPTIONS));
        $modulesStr   = $role === 'admin' ? '' : implode(',', $validModules);

        $this->userModel->skipValidation(true)->insert([
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name'     => $name !== '' ? $name : $username,
            'email'    => $email !== '' ? $email : null,
            'role'     => $role,
            'modules'  => $modulesStr,
            'status'   => $status,
        ]);

        return redirect()->to(base_url('users'))->with('success', "User '{$username}' created successfully.");
    }

    public function edit(int $id)
    {
        $user = $this->userModel->find($id);

        if (! $user) {
            return redirect()->to(base_url('users'))->with('error', 'User not found.');
        }

        // Normalise modules field to array for the form
        $user['modules_array'] = array_values(array_filter(
            array_map('trim', explode(',', (string) ($user['modules'] ?? '')))
        ));

        if (strtolower($this->request->getMethod()) !== 'post') {
            return view('users/edit', [
                'title'   => 'Edit User',
                'user'    => $user,
                'modules' => RoleAccess::MODULE_OPTIONS,
                'errors'  => [],
            ]);
        }

        $name           = trim((string) $this->request->getPost('name'));
        $email          = trim((string) $this->request->getPost('email'));
        $role           = $this->request->getPost('role') === 'admin' ? 'admin' : 'user';
        $selectedModules = (array) ($this->request->getPost('modules') ?? []);
        $status         = $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active';
        $newPassword    = (string) $this->request->getPost('password');

        $errors = [];

        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email address.';
            } else {
                $existing = $this->userModel->where('email', $email)->where('id !=', $id)->countAllResults();
                if ($existing > 0) {
                    $errors['email'] = 'Email already exists.';
                }
            }
        }

        if ($newPassword !== '' && strlen($newPassword) < 6) {
            $errors['password'] = 'New password must be at least 6 characters.';
        }

        if ($errors !== []) {
            $user['modules_array'] = $selectedModules;
            return view('users/edit', [
                'title'   => 'Edit User',
                'user'    => array_merge($user, [
                    'name'   => $name,
                    'email'  => $email,
                    'role'   => $role,
                    'status' => $status,
                ]),
                'modules' => RoleAccess::MODULE_OPTIONS,
                'errors'  => $errors,
            ]);
        }

        $validModules = array_intersect($selectedModules, array_keys(RoleAccess::MODULE_OPTIONS));
        $modulesStr   = $role === 'admin' ? '' : implode(',', $validModules);

        $update = [
            'name'    => $name !== '' ? $name : $user['username'],
            'email'   => $email !== '' ? $email : null,
            'role'    => $role,
            'modules' => $modulesStr,
            'status'  => $status,
        ];

        if ($newPassword !== '') {
            $update['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $this->userModel->skipValidation(true)->update($id, $update);

        return redirect()->to(base_url('users'))->with('success', "User '{$user['username']}' updated successfully.");
    }

    public function delete(int $id)
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to(base_url('users'));
        }

        $sessionUser = session()->get('user') ?? [];
        if ((int) ($sessionUser['id'] ?? 0) === $id) {
            return redirect()->to(base_url('users'))->with('error', 'You cannot delete your own account.');
        }

        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->to(base_url('users'))->with('error', 'User not found.');
        }

        $this->userModel->delete($id);

        return redirect()->to(base_url('users'))->with('success', "User '{$user['username']}' deleted.");
    }
}
