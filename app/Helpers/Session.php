<?php
/**
 * Session helper — secure session management.
 */

declare(strict_types=1);

namespace App\Helpers;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }
        if (headers_sent()) {
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }
            self::$started = true;
            return;
        }
        $cfg = require dirname(__DIR__, 2) . '/config/app.php';
        $sessionName = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($cfg['session']['name'] ?? 'trattoria_gusto_session'));
        if (empty($sessionName) || is_numeric($sessionName)) {
            $sessionName = 'trattoria_gusto_session';
        }
        session_name($sessionName);
        session_set_cookie_params([
            'lifetime' => 0,            // Browser-session cookie
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        @session_start();
        self::$started = true;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        self::set('_flash_' . $key, $value);
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $v = self::get('_flash_' . $key, $default);
        self::remove('_flash_' . $key);
        return $v;
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
        self::$started = false;
    }

    public static function isLoggedIn(): bool
    {
        return self::has('user_id') && self::get('user_id') > 0;
    }

    public static function userId(): int
    {
        return (int) self::get('user_id', 0);
    }

    public static function userRole(): string
    {
        return (string) self::get('user_role', '');
    }
}

