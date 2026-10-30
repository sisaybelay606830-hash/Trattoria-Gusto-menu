<?php
/**
 * CLI Product Import Script
 *
 * Runs the product synchronization from the source article_import table
 * to the local database.
 *
 * Usage:
 *   php scripts/import_products.php [--dry-run] [--force]
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

use App\Helpers\Database;
use App\Helpers\Env;
use App\Helpers\Logger;
use App\Services\ImportService;

Env::load(BASE_PATH . '/.env');
Logger::init(BASE_PATH . '/storage/logs/');

$dryRun = in_array('--dry-run', $argv ?? [], true);
$force  = in_array('--force', $argv ?? [], true);
$clean  = in_array('--clean', $argv ?? [], true) || in_array('--fresh', $argv ?? [], true);

echo "======================================================\n";
echo " Trattoria-Gusto — Product Import & Synchronization \n";
echo "======================================================\n";
echo "Mode: " . ($dryRun ? "DRY-RUN (no local writes)" : "LIVE SYNC") . "\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

if ($clean && !$dryRun) {
    echo "Clearing existing products and categories for clean sync...\n";
    $pdo = Database::local();
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE branch_products");
    $pdo->exec("TRUNCATE TABLE products");
    $pdo->exec("TRUNCATE TABLE child_categories");
    $pdo->exec("TRUNCATE TABLE parent_categories");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "[OK] Tables truncated.\n\n";
}

try {
    $service = new ImportService();
    $result = $service->run($dryRun, null, 'cli');

    echo "Status:             " . strtoupper($result['status']) . "\n";
    echo "Records Inspected:  " . $result['records_inspected'] . "\n";
    echo "Records Inserted:   " . $result['records_inserted'] . "\n";
    echo "Records Updated:    " . $result['records_updated'] . "\n";
    echo "Records Skipped:    " . $result['records_skipped'] . "\n";
    echo "Records Errored:    " . $result['records_errored'] . "\n";

    if (!empty($result['error_summary'])) {
        echo "Error Summary:      " . $result['error_summary'] . "\n";
    }

    echo "\nCompleted at: " . $result['completed_at'] . "\n";

    if ($result['status'] === 'completed') {
        exit(0);
    } else {
        exit(1);
    }
} catch (Throwable $e) {
    echo "\n[FATAL ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

