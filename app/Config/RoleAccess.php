<?php

namespace Config;

class RoleAccess
{
    /**
     * Canonical module keys → display labels.
     * These are shown as checkboxes in the User create/edit form.
     */
    public const MODULE_OPTIONS = [
        'masters'            => 'Masters',
        'products'           => 'Products',
        'inventory'          => 'Product Inventory',
        'manufacturing'      => 'Manufacturing',
        'raw-material-stock' => 'Raw Material Stock',
        'mfg-masters'        => 'Mfg Masters',
    ];

    public const ROLE_ALIASES = [
        'admin'          => 'admin',
        'administrator'  => 'admin',
        'super-admin'    => 'admin',
        'super_admin'    => 'admin',
        'superadmin'     => 'admin',
        'user'           => 'user',
        'master'         => 'masters',
        'masters'        => 'masters',
        'master-data'    => 'masters',
        'master_data'    => 'masters',
        'product'        => 'products',
        'products'       => 'products',
        'inventory'      => 'inventory',
        'stock'          => 'inventory',
        'stores'         => 'inventory',
        'store'          => 'inventory',
        'manufacturing'  => 'manufacturing',
        'production'     => 'manufacturing',
        'mfg'            => 'manufacturing',
        'assembly'       => 'manufacturing',
        'raw-material'        => 'raw-material-stock',
        'raw_material'        => 'raw-material-stock',
        'rawmaterial'         => 'raw-material-stock',
        'rm'                  => 'raw-material-stock',
        'melting'             => 'raw-material-stock',
        'raw-material-stock'  => 'raw-material-stock',
        'mfg-masters'    => 'mfg-masters',
        'mfg_masters'    => 'mfg-masters',
    ];

    public const ROLE_HOME_PATHS = [
        'admin'              => '',
        'user'               => '',
        'masters'            => 'product-types',
        'products'           => 'products',
        'inventory'          => 'stock',
        'manufacturing'      => 'orders',
        'raw-material-stock' => 'kacha',
        'mfg-masters'        => 'raw-material-types',
    ];

    public const CONTROLLER_ACCESS = [
        'Staff'                => ['admin'],
        'Users'                => ['admin'],
        'ProductTypes'         => ['admin', 'masters'],
        'Bodies'               => ['admin', 'masters'],
        'Variations'           => ['admin', 'masters'],
        'Departments'          => ['admin', 'masters'],
        'Parts'                => ['admin', 'masters'],
        'Podies'               => ['admin', 'masters'],
        'Clients'              => ['admin', 'masters'],
        'Stamps'               => ['admin', 'masters'],
        'PatternNames'         => ['admin', 'masters'],
        'Templates'            => ['admin', 'masters'],
        'Products'             => ['admin', 'products'],
        'Stock'                => ['admin', 'inventory'],
        'Orders'               => ['admin', 'manufacturing'],
        'Karigar'              => ['admin', 'manufacturing'],
        'MeltJob'              => ['admin', 'manufacturing'],
        'TouchShop'            => ['admin', 'manufacturing'],
        'PartOrder'            => ['admin', 'manufacturing'],
        'PendingReceiveEntry'  => ['admin', 'manufacturing'],
        'AssemblyWork'         => ['admin', 'manufacturing'],
        'KarigarLedger'        => ['admin', 'manufacturing'],
        'Kacha'                => ['admin', 'raw-material-stock'],
        'PartBatch'            => ['admin', 'raw-material-stock'],
        'GattiStock'           => ['admin', 'raw-material-stock'],
        'RawMaterialBatch'     => ['admin', 'raw-material-stock'],
        'RawMaterialStock'     => ['admin', 'raw-material-stock'],
        'ByproductStock'       => ['admin', 'raw-material-stock'],
        'RawMaterialType'      => ['admin', 'mfg-masters'],
        'ByproductType'        => ['admin', 'mfg-masters'],
        'FinishedGoods'        => ['admin', 'mfg-masters'],
    ];

    public const SIDEBAR_MODULE_VISIBILITY = [
        'Dashboard'          => ['admin'],
        'Administration'     => ['admin'],
        'Masters'            => ['admin', 'masters'],
        'Products'           => ['admin', 'products'],
        'Product Inventory'  => ['admin', 'inventory'],
        'Manufacturing'      => ['admin', 'manufacturing'],
        'Raw Material Stock' => ['admin', 'raw-material-stock'],
        'Mfg Masters'        => ['admin', 'mfg-masters'],
    ];

    public const ROUTE_ROLE_PATTERNS = [
        'admin,masters' => [
            '/',
            'product-types*',
            'bodies*',
            'variations*',
            'departments*',
            'parts*',
            'podies*',
            'clients*',
            'stamps*',
            'pattern-names*',
            'templates*',
        ],
        'admin,products' => [
            'products*',
        ],
        'admin,inventory' => [
            'stock*',
        ],
        'admin,manufacturing' => [
            'orders*',
            'karigar*',
            'melt-jobs*',
            'touch-shops*',
            'part-orders*',
            'pending-receive-entry*',
            'assembly-work*',
            'karigar-ledger*',
        ],
        'admin,raw-material-stock' => [
            'kacha*',
            'raw-material-batches*',
            'raw-materials*',
            'gatti-stock*',
            'byproducts*',
            'part-stock*',
        ],
        'admin,mfg-masters' => [
            'raw-material-types*',
            'byproduct-types*',
            'finished-goods*',
        ],
        'admin' => [
            'users*',
            'staff/users*',
        ],
    ];

    public static function normalizeRole(?string $role): string
    {
        $role = strtolower(trim((string) $role));

        return self::ROLE_ALIASES[$role] ?? 'user';
    }

    public static function getRoleHomePath(?string $role): string
    {
        $normalizedRole = self::normalizeRole($role);

        return self::ROLE_HOME_PATHS[$normalizedRole] ?? '';
    }

    /**
     * Return the home path for a user based on their modules list.
     * Returns the first module's home path, or empty string.
     */
    public static function getModuleHomePath(array $modules): string
    {
        foreach ($modules as $module) {
            $module = trim((string) $module);
            if ($module !== '' && isset(self::ROLE_HOME_PATHS[$module])) {
                return self::ROLE_HOME_PATHS[$module];
            }
        }

        return '';
    }

    /**
     * Check if user can access a controller.
     * Admin role always passes.
     * User role: checks if any of their assigned modules is in the controller's allowed list.
     */
    public static function canAccessController(?string $role, string $controller, array $userModules = []): bool
    {
        if (! isset(self::CONTROLLER_ACCESS[$controller])) {
            return true;
        }

        $normalizedRole = self::normalizeRole($role);

        if ($normalizedRole === 'admin') {
            return true;
        }

        $allowed = self::CONTROLLER_ACCESS[$controller];

        // Legacy: single-role match
        if (in_array($normalizedRole, $allowed, true)) {
            return true;
        }

        // Module-based check
        foreach ($userModules as $module) {
            $module = trim((string) $module);
            if ($module !== '' && in_array($module, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a sidebar module section should be visible.
     * Admin role always sees everything.
     * User role: checks if any of their assigned modules grants visibility.
     */
    public static function canViewSidebarModule(?string $role, string $moduleTitle, array $userModules = []): bool
    {
        if (! isset(self::SIDEBAR_MODULE_VISIBILITY[$moduleTitle])) {
            return true;
        }

        $normalizedRole = self::normalizeRole($role);

        if ($normalizedRole === 'admin') {
            return true;
        }

        $allowed = self::SIDEBAR_MODULE_VISIBILITY[$moduleTitle];

        // Legacy: single-role match
        if (in_array($normalizedRole, $allowed, true)) {
            return true;
        }

        // Module-based check
        foreach ($userModules as $module) {
            $module = trim((string) $module);
            if ($module !== '' && in_array($module, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    public static function getProtectedRoutePatterns(): array
    {
        $patterns = [];

        foreach (self::ROUTE_ROLE_PATTERNS as $groupPatterns) {
            $patterns = array_merge($patterns, $groupPatterns);
        }

        return array_values(array_unique($patterns));
    }
}
