<?php
/**
 * Database Configuration
 * Local application DB + source (remote, read-only) DB
 */

declare(strict_types=1);

use App\Helpers\Env;

return [
    // ---- Local application database (read-write) ----
    'local' => [
        'driver'   => 'mysql',
        'host'     => Env::getString('APP_DB_HOST', '127.0.0.1'),
        'port'     => Env::getInt('APP_DB_PORT', 3306),
        'database' => Env::getString('APP_DB_NAME', 'trattoria gusto_menu'),
        'username' => Env::getString('APP_DB_USER', 'root'),
        'password' => Env::getString('APP_DB_PASSWORD', 'root'),
        'charset'  => 'utf8mb4',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],

    // ---- Source database (read-only, remote) ----
    'source' => [
        'driver'          => 'mysql',
        'host'            => Env::getString('MYSQL_HOST', '91.204.209.46'),
        'port'            => Env::getInt('MYSQL_PORT', 3306),
        'database'        => Env::getString('MYSQL_STAGING_DB', 'kaldisbp_trattoriabp_cnet_synced_data'),
        'username'        => Env::getString('MYSQL_USER', 'kaldisbp_sis'),
        'password'        => Env::getString('MYSQL_PASSWORD', 'gusto@2026'),
        'charset'         => 'utf8mb4',
        'connect_timeout' => Env::getInt('MYSQL_CONNECT_TIMEOUT', 20),
        'read_timeout'    => Env::getInt('MYSQL_READ_TIMEOUT', 120),
        'options'         => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => Env::getInt('MYSQL_CONNECT_TIMEOUT', 20),
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone='+00:00'",
        ],
    ],
];

