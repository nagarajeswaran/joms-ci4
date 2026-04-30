<?php

namespace App\Controllers;

use App\Models\UserModel;
use Config\RoleAccess;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $request;
    protected $helpers = ['url', 'form'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->refreshUserSession();
        $this->enforceRoleAccess();
    }

    /**
     * Re-fetch the logged-in user from DB on every request so that role/module
     * changes take effect immediately without requiring logout.
     */
    protected function refreshUserSession(): void
    {
        if (! session()->has('user')) {
            return;
        }

        $userId = session()->get('user')['id'] ?? null;
        if (! $userId) {
            return;
        }

        $dbUser = (new UserModel())->find((int) $userId);

        if (! $dbUser) {
            // User was deleted — force logout
            session()->destroy();
            return;
        }

        unset($dbUser['password']);
        $dbUser['modules'] = array_values(array_filter(
            array_map('trim', explode(',', (string) ($dbUser['modules'] ?? '')))
        ));

        session()->set('user', $dbUser);
    }

    protected function tableExists(string $tableName): bool
    {
        try {
            return \Config\Database::connect()->tableExists($tableName);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function normalizeRole(string $role): string
    {
        return RoleAccess::normalizeRole($role);
    }

    protected function getRoleHomePath(?string $role = null): string
    {
        $sessionUser = session()->get('user') ?? [];
        $roleStr = $role ?? (string) ($sessionUser['role'] ?? 'user');
        $normalizedRole = RoleAccess::normalizeRole($roleStr);

        // user role: redirect to first granted module's home
        if ($normalizedRole === 'user') {
            $modules = (array) ($sessionUser['modules'] ?? []);
            return RoleAccess::getModuleHomePath($modules);
        }

        return RoleAccess::getRoleHomePath($roleStr);
    }

    protected function enforceRoleAccess(): void
    {
        if (! session()->has('user')) {
            return;
        }

        $controller = class_basename(static::class);
        if (! array_key_exists($controller, RoleAccess::CONTROLLER_ACCESS)) {
            return;
        }

        $currentRole = (string) (session()->get('user')['role'] ?? 'user');
        $userModules = (array) (session()->get('user')['modules'] ?? []);

        if (RoleAccess::canAccessController($currentRole, $controller, $userModules)) {
            return;
        }

        throw new RedirectException(
            redirect()->to(base_url($this->getRoleHomePath($currentRole)))->with('error', 'You do not have permission to access that page.')
        );
    }

    /**
     * Get the current logged-in username for audit tracking.
     */
    protected function currentUser(): string
    {
        $user = session()->get('user') ?? [];
        return $user['username'] ?? $user['name'] ?? 'system';
    }
}
