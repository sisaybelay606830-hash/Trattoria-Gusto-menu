<?php
/**
 * Database installer script
 * Creates the local trattoria-gusto database and runs all migrations.
 *
 * Usage: php scripts/install_db.php [--force]
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Helpers\Env;

// ── Bootstrap ─────────────────────────────────────────────────────────────────
$envFile = dirname(__DIR__) . '/.env';
Env::load($envFile);

$force = in_array('--force', $argv ?? [], true);

$host   = Env::getString('APP_DB_HOST', '127.0.0.1');
$port   = Env::getInt('APP_DB_PORT', 3306);
$dbName = Env::getString('APP_DB_NAME', 'trattoria-gusto');
$user   = Env::getString('APP_DB_USER', 'root');
$pass   = Env::getString('APP_DB_PASSWORD', '');

echo "=== Trattoria-Gusto — Database Installer ===\n";
echo "Target: {$user}@{$host}:{$port} / {$dbName}\n\n";

// ── Connect without database selected first ────────────────────────────────
$dsn  = "mysql:host={$host};port={$port};charset=utf8mb4";
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $user, $pass, $opts);
    echo "[OK] Connected to MySQL server.\n";
} catch (PDOException $e) {
    echo "[ERROR] Cannot connect: " . $e->getMessage() . "\n";
    exit(1);
}

// ── Create database if not exists ─────────────────────────────────────────
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$dbName}`");
echo "[OK] Database `{$dbName}` ready.\n";

// ── Run migrations in order ───────────────────────────────────────────────
$migDir = dirname(__DIR__) . '/database/migrations/';
$files  = glob($migDir . '*.sql');
sort($files);

/**
 * Split SQL file into individual statements, respecting quoted strings
 * and line comments so semicolons inside strings/comments are ignored.
 */
function splitSql(string $sql): array
{
    $statements = [];
    $current    = '';
    $inSingle   = false;
    $inDouble   = false;
    $len        = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $ch   = $sql[$i];
        $next = $sql[$i + 1] ?? '';

        // Skip line comments
        if (!$inSingle && !$inDouble && $ch === '-' && $next === '-') {
            while ($i < $len && $sql[$i] !== "\n") { $i++; }
            $current .= "\n";
            continue;
        }

        // Toggle single-quote string
        if ($ch === "'" && !$inDouble) {
            $inSingle = !$inSingle;
        }
        // Toggle double-quote string
        if ($ch === '"' && !$inSingle) {
            $inDouble = !$inDouble;
        }
        // Statement delimiter
        if ($ch === ';' && !$inSingle && !$inDouble) {
            $stmt = trim($current);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $current = '';
            continue;
        }
        $current .= $ch;
    }
    $last = trim($current);
    if ($last !== '') {
        $statements[] = $last;
    }
    return $statements;
}

foreach ($files as $file) {
    $name = basename($file);
    echo "  → Running migration: {$name} ... ";
    $sql = file_get_contents($file);
    try {
        foreach (splitSql($sql) as $stmt) {
            $pdo->exec($stmt);
        }
        echo "OK\n";
    } catch (PDOException $e) {
        echo "FAILED\n";
        echo "  [ERROR] " . $e->getMessage() . "\n";
        if (!$force) {
            echo "  Use --force to continue past errors.\n";
            exit(1);
        }
    }
}

// ── Create default admin user if not exists ───────────────────────────────
$checkUser = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ((int)$checkUser === 0) {
    echo "\n[INFO] No users found. Creating default admin account...\n";
    $defaultPass = 'Admin@Kaldis2024';
    $hash = password_hash($defaultPass, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'administrator')"
    );
    $stmt->execute(['Administrator', 'admin@kaldis.coffee', $hash]);
    echo "[OK] Default admin created:\n";
    echo "     Email   : admin@kaldis.coffee\n";
    echo "     Password: {$defaultPass}\n";
    echo "     ** CHANGE THIS PASSWORD IMMEDIATELY AFTER FIRST LOGIN **\n";
}

// ── Verify tables ─────────────────────────────────────────────────────────
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "\n[OK] Tables created: " . implode(', ', $tables) . "\n";
echo "\n=== Installation complete ===\n";
