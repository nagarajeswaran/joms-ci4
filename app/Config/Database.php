<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 * 
 * Migrated from CI3 database.php
 * Date: 2026-02-10
 * 
 * INSTRUCTIONS:
 * Copy this file to: C:\programing\htdocs\joms-ci4\app\Config\Database.php
 * (Replace the existing Database.php file)
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     * 
     * ✅ Same database as your CI3 application
     */
    public array $default = [
        'DSN'          => '',
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'psboffic1_psboffi1_joms',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true, // Set to false in production
        'charset'      => 'utf8',
        'DBCollat'     => 'utf8_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
    ];

    /**
     * This database connection is used when running PHPUnit database tests.
     */
    public array $tests = [
        'DSN'         => '',
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'psboffic1_psboffi1_joms',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8',
        'DBCollat'    => '',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
    ];

    public function __construct()
    {
        parent::__construct();

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }
}
