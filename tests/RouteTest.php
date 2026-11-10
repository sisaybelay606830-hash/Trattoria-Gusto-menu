<?php
/**
 * Route & View Rendering Integration Test
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

use App\Helpers\{Env, Session, Logger};

Env::load(BASE_PATH . '/.env');
Logger::init(BASE_PATH . '/storage/logs/');
Session::start();

// Mock logged-in administrator session
Session::set('user_id', 1);
Session::set('user_name', 'Administrator');
Session::set('user_email', 'admin@trattoriagusto.com');
Session::set('user_role', 'administrator');

function testRoute(string $method, string $uri, array $getParams = []): void {
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_GET = $getParams;

    ob_start();
    try {
        require BASE_PATH . '/public/index.php';
        $output = ob_get_clean();
        echo " [PASS] Route {$method} {$uri} (Length: " . strlen($output) . " bytes)\n";
    } catch (\Throwable $e) {
        ob_end_clean();
        echo " [FAIL] Route {$method} {$uri}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        exit(1);
    }
}

echo "Testing Public Menu, Feedback, and Admin Routes:\n";
testRoute('GET', '/menu');
testRoute('GET', '/menu', ['q' => 'Espresso']);
testRoute('GET', '/feedback');
testRoute('GET', '/feedback', ['branch' => 'bole', 'table' => '4']);
testRoute('GET', '/feedback/thank-you');
testRoute('GET', '/admin/dashboard');
testRoute('GET', '/admin/products');
testRoute('GET', '/admin/categories');
testRoute('GET', '/admin/branches');
testRoute('GET', '/admin/qrcodes');
testRoute('GET', '/admin/feedbacks');
testRoute('GET', '/admin/import');
testRoute('GET', '/admin/settings');
testRoute('GET', '/admin/users');

echo "\nAll Route & View Render Tests Succeeded!\n";

