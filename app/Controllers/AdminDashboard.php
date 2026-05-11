<?php

namespace App\Controllers;

use Config\RoleAccess;

class AdminDashboard extends BaseController
{
    /**
     * Hub page — show zone cards for admin to select.
     */
    public function hub()
    {
        $user = session()->get('user');
        if (!$user) {
            return redirect()->to(base_url('login'));
        }

        $normalizedRole = RoleAccess::normalizeRole((string) ($user['role'] ?? 'user'));
        if ($normalizedRole !== 'admin') {
            return redirect()->to(base_url($this->getRoleHomePath()));
        }

        return view('admin/hub', [
            'title' => 'Admin Dashboard',
            'zones' => RoleAccess::ADMIN_ZONES,
        ]);
    }

    /**
     * Set the active zone in session and redirect to the zone's home page.
     */
    public function setZone($zone = null)
    {
        if (!$zone || !isset(RoleAccess::ADMIN_ZONES[$zone])) {
            return redirect()->to(base_url('admin/hub'))->with('error', 'Invalid zone selected.');
        }

        session()->set('admin_zone', $zone);

        $home = RoleAccess::ADMIN_ZONES[$zone]['home'];

        return redirect()->to(base_url($home));
    }

    /**
     * Clear the active zone and return to hub.
     */
    public function clearZone()
    {
        session()->remove('admin_zone');

        return redirect()->to(base_url('admin/hub'));
    }
}
