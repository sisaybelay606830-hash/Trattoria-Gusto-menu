<?php
/**
 * Application Configuration
 */

declare(strict_types=1);

use App\Helpers\Env;

return [
    'name'    => Env::getString('APP_NAME', 'Trattoria-Gusto-menu Digital Menu'),
    'env'     => Env::getString('APP_ENV', 'production'),
    'debug'   => Env::getBool('APP_DEBUG', false),
    'url'     => Env::getString('APP_URL', 'http://localhost/Trattoria-Gusto-menu/public'),
    'secret'  => Env::getString('APP_SECRET', ''),

    'session' => [
        'lifetime' => Env::getInt('SESSION_LIFETIME', 7200),
        'name'     => Env::getString('SESSION_NAME', 'Trattoria-Gusto-menu_session'),
    ],

    'security' => [
        'bcrypt_cost'   => Env::getInt('BCRYPT_COST', 12),
        'login_max_attempts' => 5,
        'login_lockout_minutes' => 15,
    ],

    'upload' => [
        'max_size_mb'     => Env::getInt('UPLOAD_MAX_SIZE_MB', 5),
        'allowed_types'   => ['image/jpeg', 'image/png', 'image/webp'],
        'allowed_exts'    => ['jpg', 'jpeg', 'png', 'webp'],
        'products_path'   => dirname(__DIR__) . '/public/uploads/products/',
    ],

    'qr' => [
        'base_url'   => Env::getString('QR_PUBLIC_BASE_URL', 'http://localhost/Trattoria-Gusto-menu/public/menu'),
        'output_dir' => dirname(__DIR__) . '/storage/qrcodes/',
    ],

    'log_path' => dirname(__DIR__) . '/storage/logs/',
];

