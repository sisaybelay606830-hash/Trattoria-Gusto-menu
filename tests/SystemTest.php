<?php
/**
 * Trattoria-Gusto Digital Menu System Test Suite
 *
 * Verifies all critical application components:
 * 1. Database connectivity & schema tables
 * 2. Category hierarchy grouping & relationships
 * 3. Product filtering, searching & pagination
 * 4. Administrator authentication & password hashing
 * 5. QR Code generation & file output
 * 6. Product import service & idempotency
 * 7. Settings repository persistence
 * 8. CSRF security token validation
 */

declare(strict_types=1);

namespace Tests;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
    require_once BASE_PATH . '/vendor/autoload.php';
}

use PDO;
use Throwable;

use App\Helpers\{Env, Database, Csrf, Session};
use App\Repositories\{ProductRepository, CategoryRepository, BranchRepository, SettingsRepository, ImportLogRepository};
use App\Services\{QrCodeService, ImportService};

Env::load(BASE_PATH . '/.env');

class SystemTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "======================================================\n";
        echo " Trattoria-Gusto — Digital Menu System Test Suite   \n";
        echo "======================================================\n\n";

        $this->testDatabaseConnection();
        $this->testRequiredTables();
        $this->testCategoryHierarchy();
        $this->testProductRepository();
        $this->testAdminUserAuthentication();
        $this->testQrCodeGeneration();
        $this->testFeedbackQrCodeGeneration();
        $this->testFeedbackRepository();
        $this->testImportServiceHandling();
        $this->testSettingsRepository();
        $this->testCsrfProtection();

        echo "\n------------------------------------------------------\n";
        echo "Test Summary: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "======================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
        exit(0);
    }

    private function assert(string $testName, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            $this->passed++;
            echo " [PASS] {$testName}\n";
        } else {
            $this->failed++;
            echo " [FAIL] {$testName}" . ($detail ? " — {$detail}" : "") . "\n";
        }
    }

    private function testDatabaseConnection(): void
    {
        try {
            $pdo = Database::local();
            $this->assert("Local Database Connection", $pdo instanceof PDO);
        } catch (Throwable $e) {
            $this->assert("Local Database Connection", false, $e->getMessage());
        }
    }

    private function testRequiredTables(): void
    {
        $expected = [
            'parent_categories', 'child_categories', 'products', 'branches',
            'branch_products', 'qr_codes', 'users', 'settings', 'import_logs', 'audit_logs', 'feedbacks'
        ];
        $tables = Database::local()->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $missing = array_diff($expected, $tables);
        $this->assert("Required Database Tables Present", empty($missing), "Missing: " . implode(', ', $missing));
    }

    private function testCategoryHierarchy(): void
    {
        $repo = new CategoryRepository();
        $categories = $repo->getParentCategoriesWithChildren();

        $this->assert("Parent Categories Found", !empty($categories));
        $hasChildren = false;
        foreach ($categories as $cat) {
            if (!empty($cat['children'])) {
                $hasChildren = true;
                break;
            }
        }
        $this->assert("Child Categories Nested Correctly", $hasChildren);
    }

    private function testProductRepository(): void
    {
        $repo = new ProductRepository();
        $res = $repo->findAll(['is_visible' => 1], 1, 10);

        $this->assert("Product Query Paginated", isset($res['data'], $res['total'], $res['page']));
        $this->assert("Products Found in Catalog", $res['total'] > 0);

        // Test search filter
        $searchRes = $repo->findAll(['search' => 'Macchiato'], 1, 10);
        $this->assert("Product Search Filter ('Macchiato')", $searchRes['total'] > 0);
    }

    private function testAdminUserAuthentication(): void
    {
        $stmt = Database::local()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute(['admin@kaldis.coffee']);
        $user = $stmt->fetch();

        $this->assert("Default Admin User Exists", (bool)$user);
        if ($user) {
            $validPass = password_verify('Admin@Kaldis2024', $user['password_hash']);
            $this->assert("Admin Password Verification", $validPass);
            $this->assert("Admin Has 'administrator' Role", $user['role'] === 'administrator');
        }
    }

    private function testQrCodeGeneration(): void
    {
        try {
            $qrService = new QrCodeService();
            $res = $qrService->generateGeneral("Test System Suite QR");
            $this->assert("QR Code Generation Successful", !empty($res['id']) && !empty($res['file_path']));

            $filePath = $qrService->getFilePath($res['id']);
            $this->assert("QR Code PNG File Exists on Disk", file_exists($filePath ?? ''));

            $preview = $qrService->getBase64Preview($res['id']);
            $this->assert("QR Code Base64 Preview Generated", str_starts_with($preview ?? '', 'data:image/png;base64,'));
        } catch (Throwable $e) {
            $this->assert("QR Code Generation Successful", false, $e->getMessage());
        }
    }

    private function testFeedbackQrCodeGeneration(): void
    {
        try {
            $qrService = new QrCodeService();
            $res = $qrService->generateFeedback(1, 'bole', 'T05', 'Test Bole Table 5 Feedback QR');
            $this->assert("Feedback QR Code Generated", !empty($res['id']) && $res['type'] === 'feedback');
            $this->assert("Feedback QR URL Points to /feedback", str_contains($res['url'], 'feedback') && str_contains($res['url'], 'branch=bole'));
        } catch (Throwable $e) {
            $this->assert("Feedback QR Code Generated", false, $e->getMessage());
        }
    }

    private function testFeedbackRepository(): void
    {
        $repo = new \App\Repositories\FeedbackRepository();
        $newId = $repo->create([
            'branch_id'      => 1,
            'table_number'   => 'T05',
            'rating_overall' => 5,
            'rating_coffee'  => 5,
            'rating_food'    => 4,
            'rating_service' => 5,
            'comment'        => 'Exceptional traditional coffee and friendly staff!',
            'customer_name'  => 'Test Customer',
            'customer_phone' => '0911223344',
        ]);

        $this->assert("Feedback Created in DB", $newId > 0);

        $fb = $repo->getById($newId);
        $this->assert("Feedback Retrieved by ID", $fb && $fb['rating_overall'] == 5 && $fb['table_number'] === 'T05');

        $repo->toggleReviewed($newId);
        $fbUpdated = $repo->getById($newId);
        $this->assert("Feedback Toggle Reviewed", (int)$fbUpdated['is_reviewed'] === 1);

        $stats = $repo->getStats();
        $this->assert("Feedback Stats Calculated", $stats['total_count'] > 0 && $stats['avg_overall'] > 0);
    }

    private function testImportServiceHandling(): void
    {
        $service = new ImportService();
        $result = $service->run(true, null, 'test');

        // Dry-run should handle remote source unavailability cleanly without crashing
        $this->assert("Import Service Runs and Records Result", isset($result['status'], $result['records_inspected']));

        $recentLog = (new ImportLogRepository())->getRecent(1);
        $this->assert("Import Log Persisted in DB", !empty($recentLog));
    }

    private function testSettingsRepository(): void
    {
        $repo = new SettingsRepository();
        $title = $repo->get('menu_title');
        $this->assert("Settings Read (menu_title)", !empty($title));

        $repo->set('test_key', 'test_value');
        $readBack = $repo->get('test_key');
        $this->assert("Settings Write & Read (test_key)", $readBack === 'test_value');
    }

    private function testCsrfProtection(): void
    {
        Session::start();
        $token = Csrf::token();
        $this->assert("CSRF Token Generated", !empty($token) && strlen($token) === 64);

        $_POST['_csrf_token'] = $token;
        $this->assert("CSRF Verification Passes on Valid Token", Csrf::verify());

        $_POST['_csrf_token'] = 'invalid_token_12345';
        $this->assert("CSRF Verification Blocks Invalid Token", !Csrf::verify());
        unset($_POST['_csrf_token']);
    }
}

(new SystemTest())->run();
