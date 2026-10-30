<?php
/**
 * Application Entry Point
 * All HTTP requests are routed through this file via .htaccess.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/Helpers/functions.php';

use App\Helpers\Env;
use App\Helpers\Logger;
use App\Helpers\Session;
use App\Helpers\Router;

// ── Load environment ────────────────────────────────────────────────────────
Env::load(BASE_PATH . '/.env');
Logger::init(BASE_PATH . '/storage/logs/');
Session::start();

// ── Global helper functions ─────────────────────────────────────────────────

if (!function_exists('base_url')) {
    /**
     * Build a full URL from a relative path, using APP_URL from .env.
     */
    function base_url(string $path = ''): string
    {
        $base = rtrim(Env::getString('APP_URL', 'http://localhost/Trattoria-Gusto-menu/public'), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('e')) {
    /**
     * Safely escape a value for HTML output.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

// ── Load routes & dispatch ──────────────────────────────────────────────────
try {
    require BASE_PATH . '/routes/web.php';
    Router::dispatch();
} catch (\Throwable $ex) {
    Logger::error('Unhandled exception: ' . $ex->getMessage(), [
        'file' => $ex->getFile(),
        'line' => $ex->getLine(),
    ]);

    http_response_code(500);
    $debug   = Env::getBool('APP_DEBUG', false);
    $message = $debug ? $ex->getMessage() : 'An unexpected error occurred. Please try again.';

    if (file_exists(BASE_PATH . '/resources/views/errors/500.php')) {
        require BASE_PATH . '/resources/views/errors/500.php';
    } else {
        echo '<h1>500 — Server Error</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    }
}

