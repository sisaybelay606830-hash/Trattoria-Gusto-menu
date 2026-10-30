<?php
/**
 * AdminController — Back-office administration management
 *
 * Covers dashboard metrics, product management, category visibility,
 * branch configuration, QR code management, import trigger/logs,
 * system settings, and user administration.
 *
 * @package App\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\{View, Session, Csrf, Request, Response, Database, Logger, AuditLog, Env};
use App\Middleware\AuthMiddleware;
use App\Repositories\{ProductRepository, CategoryRepository, BranchRepository, SettingsRepository, ImportLogRepository};
use App\Services\{ImportService, QrCodeService};
use Throwable;

class AdminController
{
    private ProductRepository $productRepo;
    private CategoryRepository $categoryRepo;
    private BranchRepository $branchRepo;
    private SettingsRepository $settingsRepo;
    private ImportLogRepository $importLogRepo;

    public function __construct()
    {
        // Enforce authentication on all admin endpoints
        AuthMiddleware::requireAuth();

        $this->productRepo   = new ProductRepository();
        $this->categoryRepo  = new CategoryRepository();
        $this->branchRepo    = new BranchRepository();
        $this->settingsRepo  = new SettingsRepository();
        $this->importLogRepo = new ImportLogRepository();
    }

    // =========================================================================
    // Dashboard
    // =========================================================================

    public function dashboard(): void
    {
        $stats = $this->importLogRepo->getDashboardStats();
        $recentLogs = $this->importLogRepo->getRecent(5);
        $branches = $this->branchRepo->getActive();

        // Recent audit logs
        $stmt = Database::local()->prepare(
            "SELECT a.*, u.name as user_name
             FROM `audit_logs` a
             LEFT JOIN `users` u ON a.user_id = u.id
             ORDER BY a.created_at DESC LIMIT 6"
        );
        $stmt->execute();
        $recentAudit = $stmt->fetchAll();

        View::render('admin/dashboard', [
            'title'       => 'Dashboard — Trattoria Gusto Admin',
            'stats'       => $stats,
            'recentLogs'  => $recentLogs,
            'branches'    => $branches,
            'recentAudit' => $recentAudit,
            'user'        => [
                'name'  => Session::get('user_name'),
                'email' => Session::get('user_email'),
                'role'  => Session::userRole(),
            ],
            'success'     => Session::getFlash('success'),
            'error'       => Session::getFlash('error'),
        ], 'admin');
    }

    // =========================================================================
    // Products Management
    // =========================================================================

    public function products(): void
    {
        $page     = Request::int('page', 1);
        $perPage  = (int) $this->settingsRepo->get('items_per_page', 24);
        $search   = Request::str('q');
        $parentId = Request::int('parent');
        $childId  = Request::int('child');
        $visible  = Request::get('visible');

        $filters = [];
        if ($search !== '') {
            $filters['search'] = $search;
        }
        if ($parentId > 0) {
            $filters['parent_category_id'] = $parentId;
        }
        if ($childId > 0) {
            $filters['child_category_id'] = $childId;
        }
        if ($visible !== null && $visible !== '') {
            $filters['is_visible'] = (int) $visible;
        }

        $result     = $this->productRepo->findAll($filters, $page, $perPage);
        $categories = $this->categoryRepo->getParentCategoriesWithChildren();
        $vatRate    = (float) $this->settingsRepo->get('vat_rate', '1.20');

        View::render('admin/products/index', [
            'title'      => 'Product Management — Trattoria Gusto Admin',
            'products'   => $result['data'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'perPage'    => $result['per_page'],
            'lastPage'   => $result['last_page'],
            'categories' => $categories,
            'vatRate'    => $vatRate,
            'search'     => $search,
            'parentId'   => $parentId,
            'childId'    => $childId,
            'visible'    => $visible,
            'success'    => Session::getFlash('success'),
            'error'      => Session::getFlash('error'),
        ], 'admin');
    }

    public function productDetail(string $id): void
    {
        $product = $this->productRepo->findById((int) $id);
        if (!$product) {
            Session::flash('error', 'Product not found.');
            Response::redirect(base_url('admin/products'));
        }

        $vatRate = (float) $this->settingsRepo->get('vat_rate', '1.20');

        View::render('admin/products/edit', [
            'title'   => 'Edit Product: ' . e($product['name']),
            'product' => $product,
            'vatRate' => $vatRate,
            'success' => Session::getFlash('success'),
            'error'   => Session::getFlash('error'),
        ], 'admin');
    }

    public function productUpdate(string $id): void
    {
        if (!Csrf::verify()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::redirect(base_url("admin/products/{$id}"));
        }

        $product = $this->productRepo->findById((int) $id);
        if (!$product) {
            Session::flash('error', 'Product not found.');
            Response::redirect(base_url('admin/products'));
        }

        $localDesc  = trim(Request::post('local_description', ''));
        $isVisible  = Request::post('is_visible') !== null ? 1 : 0;
        $priceRaw   = trim((string) Request::post('price', ''));
        $price      = null;

        if ($priceRaw !== '') {
            $cleaned = preg_replace('/[^0-9.]/', '', $priceRaw);
            if (is_numeric($cleaned)) {
                $price = number_format((float) $cleaned, 2, '.', '');
            }
        }

        $fields = [
            'local_description' => $localDesc !== '' ? $localDesc : null,
            'is_visible'        => $isVisible,
            'price'             => $price,
        ];

        // Handle image upload securely
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $maxBytes = (int) Env::getInt('UPLOAD_MAX_SIZE_MB', 5) * 1024 * 1024;

            if ($file['size'] > $maxBytes) {
                Session::flash('error', 'Uploaded image exceeds maximum size limit of ' . Env::getInt('UPLOAD_MAX_SIZE_MB', 5) . 'MB.');
                Response::redirect(base_url("admin/products/{$id}"));
            }

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedMimes, true)) {
                Session::flash('error', 'Invalid file type. Only JPG, PNG, and WebP images are allowed.');
                Response::redirect(base_url("admin/products/{$id}"));
            }

            $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $ext = $extMap[$mime] ?? 'jpg';
            $filename = 'prod_' . $product['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $uploadDir = BASE_PATH . '/public/uploads/products/';

            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // If previous image exists and is local, remove it
                if (!empty($product['image_path'])) {
                    $oldPath = BASE_PATH . '/public/' . ltrim($product['image_path'], '/');
                    if (file_exists($oldPath) && is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $fields['image_path'] = 'uploads/products/' . $filename;
            } else {
                Session::flash('error', 'Failed to save uploaded image. Check directory permissions.');
                Response::redirect(base_url("admin/products/{$id}"));
            }
        }

        $this->productRepo->updateAdminFields((int) $id, $fields);
        AuditLog::record('product_updated', 'product', (int) $id, [
            'name'       => $product['name'],
            'is_visible' => $isVisible,
            'price'      => $price,
        ]);

        Session::flash('success', "Product '{$product['name']}' updated successfully.");
        Response::redirect(base_url("admin/products/{$id}"));
    }

    // =========================================================================
    // Category Hierarchy Management
    // =========================================================================

    public function categories(): void
    {
        $categories = $this->categoryRepo->getParentCategoriesWithChildren();

        View::render('admin/categories/index', [
            'title'      => 'Category Management — Trattoria Gusto Admin',
            'categories' => $categories,
            'success'    => Session::getFlash('success'),
            'error'      => Session::getFlash('error'),
        ], 'admin');
    }

    public function categoryToggle(): void
    {
        if (!Csrf::verify()) {
            Session::flash('error', 'Invalid security token.');
            Response::redirect(base_url('admin/categories'));
        }

        $type    = Request::post('type');
        $id      = Request::int('id');
        $visible = (bool) Request::int('is_visible');

        if ($type === 'parent') {
            $this->categoryRepo->updateParentVisibility($id, $visible);
            AuditLog::record('category_parent_visibility', 'parent_category', $id, ['visible' => $visible]);
        } elseif ($type === 'child') {
            $this->categoryRepo->updateChildVisibility($id, $visible);
            AuditLog::record('category_child_visibility', 'child_category', $id, ['visible' => $visible]);
        }

        Session::flash('success', 'Category visibility updated.');
        Response::redirect(base_url('admin/categories'));
    }

    // =========================================================================
    // Branch Management
    // =========================================================================

    public function branches(): void
    {
        $branches = $this->branchRepo->getAll();

        View::render('admin/branches/index', [
            'title'    => 'Branch Management — Trattoria Gusto Admin',
            'branches' => $branches,
            'success'  => Session::getFlash('success'),
            'error'    => Session::getFlash('error'),
        ], 'admin');
    }

    public function branchSave(): void
    {
        if (!Csrf::verify()) {
            Session::flash('error', 'Invalid security token.');
            Response::redirect(base_url('admin/branches'));
        }

        $id       = Request::int('id');
        $code     = strtoupper(trim(preg_replace('/[^a-zA-Z0-9_\-]/', '_', Request::post('code', ''))));
        $name     = trim(Request::post('name', ''));
        $address  = trim(Request::post('address', ''));
        $phone    = trim(Request::post('phone', ''));
        $isActive = Request::post('is_active') !== null ? 1 : 0;

        if ($code === '' || $name === '') {
            Session::flash('error', 'Branch Code and Branch Name are required.');
            Response::redirect(base_url('admin/branches'));
        }

        $data = [
            'id'        => $id > 0 ? $id : null,
            'code'      => $code,
            'name'      => $name,
            'address'   => $address,
            'phone'     => $phone,
            'is_active' => $isActive,
        ];

        $savedId = $this->branchRepo->save($data);
        AuditLog::record($id > 0 ? 'branch_updated' : 'branch_created', 'branch', $savedId, [
            'code' => $code,
            'name' => $name,
        ]);

        Session::flash('success', "Branch '{$name}' saved successfully.");
        Response::redirect(base_url('admin/branches'));
    }

    // =========================================================================
    // QR Code Management
    // =========================================================================

    public function qrcodes(): void
    {
        $qrService = new QrCodeService();
        $qrcodes   = $qrService->getAll();
        $branches  = $this->branchRepo->getActive();

        View::render('admin/qrcodes/index', [
            'title'    => 'QR Code Management — Trattoria Gusto Admin',
            'qrcodes'  => $qrcodes,
            'branches' => $branches,
            'service'  => $qrService,
            'success'  => Session::getFlash('success'),
            'error'    => Session::getFlash('error'),
        ], 'admin');
    }

    public function qrcodeGenerate(): void
    {
        if (!Csrf::verify()) {
            Session::flash('error', 'Invalid security token.');
            Response::redirect(base_url('admin/qrcodes'));
        }

        $type     = Request::post('type', 'general');
        $label    = trim(Request::post('label', ''));
        $branchId = Request::int('branch_id');
        $tableRef = trim((string)Request::post('table_ref', ''));

        $qrService = new QrCodeService();

        try {
            if ($type === 'feedback') {
                $branch = $branchId > 0 ? $this->branchRepo->findById($branchId) : null;
                $branchCode = $branch['code'] ?? null;
                $defaultLabel = 'KaTrattoria Gusto Feedback QR';
                if ($branch) {
                    $defaultLabel .= ' - ' . $branch['name'];
                }
                if ($tableRef !== '') {
                    $defaultLabel .= ' (Table ' . $tableRef . ')';
                }
                $finalLabel = $label !== '' ? $label : $defaultLabel;
                $res = $qrService->generateFeedback($branch ? (int)$branch['id'] : null, $branchCode, $tableRef ?: null, $finalLabel);
            } elseif ($type === 'table' && $branchId > 0 && $tableRef !== '') {
                $branch = $this->branchRepo->findById($branchId);
                if (!$branch) {
                    Session::flash('error', 'Selected branch was not found.');
                    Response::redirect(base_url('admin/qrcodes'));
                }
                $finalLabel = $label !== '' ? $label : "Trattoria Gusto - {$branch['name']} (Table {$tableRef})";
                $res = $qrService->generateTable($branchId, $branch['code'], $tableRef, $finalLabel);
            } elseif ($type === 'branch' && $branchId > 0) {
                $branch = $this->branchRepo->findById($branchId);
                if (!$branch) {
                    Session::flash('error', 'Selected branch was not found.');
                    Response::redirect(base_url('admin/qrcodes'));
                }
                $finalLabel = $label !== '' ? $label : "Trattoria Gusto - {$branch['name']}";
                $res = $qrService->generateBranch($branchId, $branch['code'], $finalLabel);
            } else {
                $finalLabel = $label !== '' ? $label : 'Trattoria Gusto - General Digital Menu';
                $res = $qrService->generateGeneral($finalLabel);
            }

            AuditLog::record('qr_generated', 'qr_code', $res['id'], [
                'type'  => $res['type'],
                'label' => $res['label'],
                'url'   => $res['url'],
            ]);

            Session::flash('success', "QR Code '{$finalLabel}' generated successfully!");
        } catch (Throwable $e) {
            Logger::error('QR generation failed: ' . $e->getMessage());
            Session::flash('error', 'QR Code generation failed: ' . $e->getMessage());
        }

        Response::redirect(base_url('admin/qrcodes'));
    }

    public function qrcodeDownload(string $id): void
    {
        $qrService = new QrCodeService();
        $row       = $qrService->getById((int) $id);

        if (!$row) {
            Response::abort(404, 'QR code not found.');
        }

        $filePath = $qrService->getFilePath((int) $id);
        if (!$filePath || !file_exists($filePath)) {
            Response::abort(404, 'QR code image file not found on disk.');
        }
        $downloadName = 'Trattoria Gusto_qr_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $row['label']) . '.png';

        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($filePath);
        exit;
    }

    public function qrcodePrint(string $id): void
    {
        $qrService = new QrCodeService();
        $qr = $qrService->getById((int) $id);

        if (!$qr) {
            Response::abort(404, 'QR code not found.');
        }

        $previewBase64 = $qrService->getBase64Preview((int) $id);

        View::render('admin/qrcodes/print', [
            'title'         => 'Print QR Label — ' . e($qr['label']),
            'qr'            => $qr,
            'previewBase64' => $previewBase64,
        ], '');
    }

    public function qrcodeDeactivate(string $id): void
    {
        if (!Csrf::verify()) {
            Session::flash('error', 'Invalid security token.');
            Response::redirect(base_url('admin/qrcodes'));
        }

        $qrService = new QrCodeService();
        $qrService->deactivate((int) $id);
        AuditLog::record('qr_deactivated', 'qr_code', (int) $id);

        Session::flash('success', 'QR code deactivated.');
        Response::redirect(base_url('admin/qrcodes'));
    }

    // =========================================================================
    // Product Import & Synchronization
    // =========================================================================

    public function importPage(): void
    {
        $stats      = $this->importLogRepo->getDashboardStats();
        $recentLogs = $this->importLogRepo->getRecent(15);

        // Check source connectivity safely without breaking page
        $sourceStatus = ['connected' => false, 'message' => ''];
        try {
            $sourcePdo = Database::source();
            $sourceStatus['connected'] = true;
            $sourceStatus['message']   = 'Connected to source MySQL database at ' . Env::getString('MYSQL_HOST', '91.204.209.46');
        } catch (Throwable $e) {
            $sourceStatus['connected'] = false;
            $sourceStatus['message']   = Database::sanitizeError($e->getMessage());
        }

        View::render('admin/import/index', [
            'title'        => 'Product Import & Synchronization — Trattoria Gusto Admin',
            'stats'        => $stats,
            'recentLogs'   => $recentLogs,
            'sourceStatus' => $sourceStatus,
            'success'      => Session::getFlash('success'),
            'error'        => Session::getFlash('error'),
        ], 'admin');
    }

    public function importRun(): void
    {
        if (!Csrf::verify()) {
            Session::flash('error', 'Invalid security token.');
            Response::redirect(base_url('admin/import'));
        }

        $dryRun = (bool) Request::post('dry_run', 0);

        try {
            $importService = new ImportService();
            $result = $importService->run($dryRun, Session::userId(), 'admin');

            AuditLog::record('import_executed', 'import_log', $result['log_id'], [
                'dry_run'   => $dryRun,
                'status'    => $result['status'],
                'inserted'  => $result['records_inserted'],
                'updated'   => $result['records_updated'],
            ]);

            if ($result['status'] === 'completed') {
                $msg = sprintf(
                    "Import completed successfully%s! Inspected: %d, Inserted: %d, Updated: %d, Skipped: %d.",
                    $dryRun ? ' (DRY RUN)' : '',
                    $result['records_inspected'],
                    $result['records_inserted'],
                    $result['records_updated'],
                    $result['records_skipped']
                );
                Session::flash('success', $msg);
            } elseif ($result['status'] === 'skipped') {
                Session::flash('error', 'Import skipped: ' . ($result['error_summary'] ?? 'Another import is in progress.'));
            } else {
                Session::flash('error', 'Import failed: ' . ($result['error_summary'] ?? 'Unknown database error.'));
            }
        } catch (Throwable $e) {
            Logger::error('Import error: ' . $e->getMessage());
            Session::flash('error', 'Import error: ' . Database::sanitizeError($e->getMessage()));
        }

        Response::redirect(base_url('admin/import'));
    }

    public function importLogs(): void
    {
        $logs = $this->importLogRepo->getRecent(50);

        View::render('admin/import/logs', [
            'title' => 'Import Histo Trattoria Gustory & Logs — Trattoria Gusto Admin',
            'logs'  => $logs,
        ], 'admin');
    }

    // =========================================================================
    // Settings Management
    // =========================================================================

    public function settings(): void
    {
        $settings = $this->settingsRepo->getAll();
        $branches = $this->branchRepo->getActive();

        View::render('admin/settings/index', [
            'title'    => 'Menu Settings — Trattoria Gusto Admin',
            'settings' => $settings,
            'branches' => $branches,
            'success'  => Session::getFlash('success'),
            'error'    => Session::getFlash('error'),
        ], 'admin');
    }

    public function settingsSave(): void
    {
        if (!Csrf::verify()) {
            Session::flash('error', 'Invalid security token.');
            Response::redirect(base_url('admin/settings'));
        }

        $rawVat = trim((string) Request::post('vat_rate', '1.20'));
        $vatRate = is_numeric($rawVat) && (float)$rawVat > 0 ? number_format((float)$rawVat, 2, '.', '') : '1.20';

        $kvp = [
            'menu_title'          => trim(Request::post('menu_title', 'Trattoria Gusto')),
            'menu_subtitle'       => trim(Request::post('menu_subtitle', 'Digital Menu')),
            'default_branch_code' => trim(Request::post('default_branch_code', '')),
            'fallback_category'   => trim(Request::post('fallback_category', 'Uncategorized')),
            'show_prices'         => Request::post('show_prices') !== null ? '1' : '0',
            'vat_rate'            => $vatRate,
            'items_per_page'      => (string) max(10, min(100, Request::int('items_per_page', 24))),
            'maintenance_mode'    => Request::post('maintenance_mode') !== null ? '1' : '0',
        ];

        $this->settingsRepo->setMany($kvp);
        AuditLog::record('settings_updated', 'settings', null, $kvp);

        Session::flash('success', 'Menu settings saved successfully.');
        Response::redirect(base_url('admin/settings'));
    }

    // =========================================================================
    // User Administration (Administrator role only)
    // =========================================================================

    public function users(): void
    {
        AuthMiddleware::requireAdmin();

        $users = Database::local()->query(
            "SELECT id, name, email, role, is_active, last_login_at, created_at
             FROM users ORDER BY name ASC"
        )->fetchAll();

        View::render('admin/users/index', [
            'title'   => 'User Management — Trattoria Gusto Admin',
            'users'   => $users,
            'success' => Session::getFlash('success'),
            'error'   => Session::getFlash('error'),
        ], 'admin');
    }

    public function userSave(): void
    {
        AuthMiddleware::requireAdmin();

        if (!Csrf::verify()) {
            Session::flash('error', 'Invalid security token.');
            Response::redirect(base_url('admin/users'));
        }

        $name     = trim(Request::post('name', ''));
        $email    = strtolower(trim(Request::post('email', '')));
        $password = Request::post('password', '');
        $role     = Request::post('role', 'menu_manager');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Valid name and email address are required.');
            Response::redirect(base_url('admin/users'));
        }

        if (strlen($password) < 8) {
            Session::flash('error', 'Password must be at least 8 characters long.');
            Response::redirect(base_url('admin/users'));
        }

        if (!in_array($role, ['administrator', 'menu_manager'], true)) {
            $role = 'menu_manager';
        }

        $pdo = Database::local();
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch()) {
            Session::flash('error', "User with email '{$email}' already exists.");
            Response::redirect(base_url('admin/users'));
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role, is_active)
             VALUES (?, ?, ?, ?, 1)"
        );
        $stmt->execute([$name, $email, $hash, $role]);
        $newId = (int) $pdo->lastInsertId();

        AuditLog::record('user_created', 'user', $newId, ['name' => $name, 'email' => $email, 'role' => $role]);

        Session::flash('success', "User '{$name}' created successfully.");
        Response::redirect(base_url('admin/users'));
    }

    // ──────────────────────────────────────────────────────── Customer Feedbacks
    public function feedbacks(): void
    {
        AuthMiddleware::requireAuth();
        $feedbackRepo = new \App\Repositories\FeedbackRepository();

        $page     = max(1, Request::int('page', 1));
        $branchId = Request::int('branch_id', 0);
        $rating   = Request::int('rating', 0);
        $reviewed = Request::get('reviewed', '');
        $search   = trim((string)Request::get('search', ''));

        $filters = [];
        if ($branchId > 0) $filters['branch_id'] = $branchId;
        if ($rating > 0)   $filters['rating'] = $rating;
        if ($reviewed !== '') $filters['reviewed'] = (int)$reviewed;
        if ($search !== '')   $filters['search'] = $search;

        $feedbackData = $feedbackRepo->getAll($filters, $page, 20);
        $stats        = $feedbackRepo->getStats();
        $branches     = $this->branchRepo->getAll();

        View::render('admin/feedbacks/index', [
            'title'      => 'Customer Feedback — Admin',
            'feedbacks'  => $feedbackData['data'],
            'total'      => $feedbackData['total'],
            'page'       => $feedbackData['page'],
            'totalPages' => $feedbackData['total_pages'],
            'stats'      => $stats,
            'branches'   => $branches,
            'filters'    => [
                'branch_id' => $branchId,
                'rating'    => $rating,
                'reviewed'  => $reviewed,
                'search'    => $search,
            ],
            'success'    => Session::getFlash('success'),
            'error'      => Session::getFlash('error'),
        ], 'admin');
    }

    public function feedbackToggleReviewed(string $id): void
    {
        AuthMiddleware::requireAuth();
        if (!Csrf::verify()) {
            Session::flash('error', 'Invalid security token.');
            Response::redirect(base_url('admin/feedbacks'));
        }

        $feedbackRepo = new \App\Repositories\FeedbackRepository();
        $feedbackRepo->toggleReviewed((int)$id);
        Session::flash('success', 'Feedback review status updated.');
        Response::redirect(base_url('admin/feedbacks'));
    }
}

