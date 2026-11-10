<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\{View, Session, Csrf, Request, Response, Database, Logger, AuditLog};
use App\Middleware\AuthMiddleware;

class AuthController
{
    private const MAX_ATTEMPTS    = 5;
    private const LOCKOUT_MINUTES = 15;

    public function loginForm(): void
    {
        AuthMiddleware::requireGuest();
        View::render('auth/login', [
            'title' => 'Admin Login — Trattoria Gusto',
            'error' => Session::getFlash('error'),
        ], 'base');
    }

    public function login(): void
    {
        // CSRF protection
        if (!Csrf::verify()) {
            http_response_code(403);
            echo 'Invalid request. Please go back and try again.';
            exit;
        }

        $email    = trim(Request::post('email', ''));
        $password = Request::post('password', '');

        if ($email === '' || $password === '') {
            Session::flash('error', 'Email and password are required.');
            Response::redirect(base_url('admin/login'));
        }

        $pdo  = Database::local();
        $stmt = $pdo->prepare(
            "SELECT `id`, `name`, `email`, `password_hash`, `role`, `is_active`,
                    `failed_attempts`, `locked_until`
               FROM `users`
              WHERE `email` = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Generic failure response — do not reveal whether email exists
        if (!$user || !(bool)$user['is_active']) {
            Session::flash('error', 'Invalid email or password.');
            Response::redirect(base_url('admin/login'));
        }

        // Check lockout
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $until = date('H:i', strtotime($user['locked_until']));
            Session::flash('error', "Too many failed attempts. Account locked until {$until}.");
            Logger::warning("Login blocked — account locked: {$email}");
            Response::redirect(base_url('admin/login'));
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $attempts = (int)$user['failed_attempts'] + 1;
            $lockedUntil = null;
            if ($attempts >= self::MAX_ATTEMPTS) {
                $lockedUntil = date('Y-m-d H:i:s', time() + self::LOCKOUT_MINUTES * 60);
                Session::flash('error', "Too many failed attempts. Account locked for " . self::LOCKOUT_MINUTES . " minutes.");
            } else {
                $remaining = self::MAX_ATTEMPTS - $attempts;
                Session::flash('error', "Invalid email or password. {$remaining} attempts remaining.");
            }
            $pdo->prepare(
                "UPDATE `users` SET `failed_attempts` = ?, `locked_until` = ? WHERE `id` = ?"
            )->execute([$attempts, $lockedUntil, $user['id']]);
            AuditLog::record('login_failed', 'user', (int)$user['id'], ['email' => $email]);
            Response::redirect(base_url('admin/login'));
        }

        // ✅ Successful login
        Session::regenerate();
        Session::set('user_id',    (int)$user['id']);
        Session::set('user_name',  $user['name']);
        Session::set('user_email', $user['email']);
        Session::set('user_role',  $user['role']);

        $pdo->prepare(
            "UPDATE `users` SET `failed_attempts` = 0, `locked_until` = NULL, `last_login_at` = NOW() WHERE `id` = ?"
        )->execute([$user['id']]);

        AuditLog::record('login_success', 'user', (int)$user['id'], ['email' => $email]);
        Logger::info("Admin login: {$email}");

        Response::redirect(base_url('admin/dashboard'));
    }

    public function logout(): void
    {
        $userId = Session::userId();
        $email  = Session::get('user_email', 'unknown');
        Session::destroy();
        AuditLog::record('logout', 'user', $userId, ['email' => $email], $userId);
        Logger::info("Admin logout: {$email}");
        Response::redirect(base_url('admin/login'));
    }
}

