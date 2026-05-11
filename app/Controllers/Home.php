<?php

namespace App\Controllers;

use Config\RoleAccess;

class Home extends BaseController
{
    public function index()
    {
        $user = session()->get('user');
        if (!$user) {
            return redirect()->to(base_url('login'));
        }

        $normalizedRole = RoleAccess::normalizeRole((string) ($user['role'] ?? 'user'));

        // Admin: go to hub if no zone set, otherwise zone home
        if ($normalizedRole === 'admin') {
            $zone = session()->get('admin_zone');
            if ($zone && isset(RoleAccess::ADMIN_ZONES[$zone])) {
                return redirect()->to(base_url(RoleAccess::ADMIN_ZONES[$zone]['home']));
            }
            return redirect()->to(base_url('admin/hub'));
        }

        // Non-admin: existing behavior
        return redirect()->to(base_url($this->getRoleHomePath()));
    }
}
