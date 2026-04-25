<?php

namespace App\Filters;

use Config\RoleAccess;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Role implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->has('user')) {
            return redirect()->to(base_url('login'));
        }

        $allowedRoles = array_values(array_filter(array_map('trim', $arguments ?? [])));
        if ($allowedRoles === []) {
            return;
        }

        $currentRole  = RoleAccess::normalizeRole((string) (session()->get('user')['role'] ?? 'admin'));
        $userModules  = (array) (session()->get('user')['modules'] ?? []);
        $normalizedAllowed = array_map(
            static fn(string $r): string => RoleAccess::normalizeRole($r),
            $allowedRoles
        );

        // Admin always passes
        if ($currentRole === 'admin') {
            return;
        }

        // Legacy single-role match
        if (in_array($currentRole, $normalizedAllowed, true)) {
            return;
        }

        // Module-based match: any of the user's modules in the allowed list
        foreach ($userModules as $module) {
            $module = trim((string) $module);
            if ($module !== '' && in_array($module, $normalizedAllowed, true)) {
                return;
            }
        }

        $homePath = $currentRole === 'user'
            ? RoleAccess::getModuleHomePath($userModules)
            : RoleAccess::getRoleHomePath($currentRole);

        return redirect()
            ->to(base_url($homePath))
            ->with('error', 'You do not have permission to access that page.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
