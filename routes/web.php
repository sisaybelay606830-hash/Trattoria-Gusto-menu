<?php
/**
 * Application Routes
 */

declare(strict_types=1);

use App\Helpers\Router;
use App\Helpers\Response;
use App\Controllers\AuthController;
use App\Controllers\MenuController;
use App\Controllers\AdminController;
use App\Controllers\FeedbackController;

// ── Public ──────────────────────────────────────────────────────────────────
Router::get('/',                    fn() => Response::redirect(base_url('menu')));
Router::get('/menu',                [MenuController::class, 'index']);
Router::get('/menu/{branch}',       [MenuController::class, 'branch']);
Router::get('/feedback',            [FeedbackController::class, 'form']);
Router::post('/feedback',           [FeedbackController::class, 'submit']);
Router::get('/feedback/thank-you',  [FeedbackController::class, 'thankYou']);

// ── Authentication ──────────────────────────────────────────────────────────
Router::get('/admin/login',  [AuthController::class, 'loginForm']);
Router::post('/admin/login', [AuthController::class, 'login']);
Router::get('/admin/logout', [AuthController::class, 'logout']);

// ── Admin root redirect ─────────────────────────────────────────────────────
Router::get('/admin',        fn() => Response::redirect(base_url('admin/dashboard')));

// ── Admin — Dashboard ───────────────────────────────────────────────────────
Router::get('/admin/dashboard', [AdminController::class, 'dashboard']);

// ── Admin — Products ────────────────────────────────────────────────────────
Router::get('/admin/products',         [AdminController::class, 'products']);
Router::get('/admin/products/{id}',    [AdminController::class, 'productDetail']);
Router::post('/admin/products/{id}',   [AdminController::class, 'productUpdate']);

// ── Admin — Categories ──────────────────────────────────────────────────────
Router::get('/admin/categories',       [AdminController::class, 'categories']);
Router::post('/admin/categories/toggle',[AdminController::class, 'categoryToggle']);

// ── Admin — Branches ────────────────────────────────────────────────────────
Router::get('/admin/branches',             [AdminController::class, 'branches']);
Router::post('/admin/branches',            [AdminController::class, 'branchSave']);
Router::post('/admin/branches/{id}/delete',[AdminController::class, 'branchDelete']);

// ── Admin — QR Codes ────────────────────────────────────────────────────────
Router::get('/admin/qrcodes',                     [AdminController::class, 'qrcodes']);
Router::post('/admin/qrcodes/generate',           [AdminController::class, 'qrcodeGenerate']);
Router::post('/admin/qrcodes/{id}/edit',          [AdminController::class, 'qrcodeUpdate']);
Router::get('/admin/qrcodes/{id}/download',       [AdminController::class, 'qrcodeDownload']);
Router::get('/admin/qrcodes/{id}/print',          [AdminController::class, 'qrcodePrint']);
Router::post('/admin/qrcodes/{id}/deactivate',    [AdminController::class, 'qrcodeDeactivate']);
Router::post('/admin/qrcodes/{id}/delete',        [AdminController::class, 'qrcodeDelete']);

// ── Admin — Import ──────────────────────────────────────────────────────────
Router::get('/admin/import',       [AdminController::class, 'importPage']);
Router::post('/admin/import/run',  [AdminController::class, 'importRun']);
Router::get('/admin/import/logs',  [AdminController::class, 'importLogs']);

// ── Admin — Settings ────────────────────────────────────────────────────────
Router::get('/admin/settings',  [AdminController::class, 'settings']);
Router::post('/admin/settings', [AdminController::class, 'settingsSave']);

// ── Admin — Customer Feedbacks ───────────────────────────────────────────────
Router::get('/admin/feedbacks',                         [AdminController::class, 'feedbacks']);
Router::post('/admin/feedbacks/{id}/toggle-reviewed',   [AdminController::class, 'feedbackToggleReviewed']);

// ── Admin — Users (administrator role only) ─────────────────────────────────
Router::get('/admin/users',  [AdminController::class, 'users']);
Router::post('/admin/users', [AdminController::class, 'userSave']);

