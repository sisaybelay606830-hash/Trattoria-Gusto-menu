<?php
/**
 * QR Code Service
 * Generates QR codes for public menu URLs using chillerlan/php-qrcode.
 */

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Logger;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use RuntimeException;

class QrCodeService
{
    private string $outputDir;
    private string $baseUrl;

    public function __construct()
    {
        $cfg = require dirname(__DIR__, 2) . '/config/app.php';
        $this->outputDir = rtrim($cfg['qr']['output_dir'], '/\\') . DIRECTORY_SEPARATOR;
        $this->baseUrl   = rtrim($cfg['qr']['base_url'], '/');

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0750, true);
        }
    }

    private function getMenuBaseUrl(): string
    {
        $base = $this->baseUrl;
        if (!str_ends_with($base, '/menu') && !str_ends_with($base, '/menu/')) {
            $base = rtrim($base, '/') . '/menu';
        }
        return $base;
    }

    // ---------------------------------------------------------------- Generate

    /**
     * Generate a general menu QR code.
     */
    public function generateGeneral(string $label = 'General Menu'): array
    {
        $url = $this->getMenuBaseUrl();
        return $this->generate($label, 'general', $url, null);
    }

    /**
     * Generate a branch-specific QR code.
     *
     * @param int    $branchId   Local branch DB id
     * @param string $branchCode URL-safe branch code
     * @param string $label      Human-readable label for this QR
     */
    public function generateBranch(int $branchId, string $branchCode, string $label): array
    {
        // Use branch code (not internal ID) in the URL
        $url = $this->getMenuBaseUrl() . '?branch=' . urlencode($branchCode);
        return $this->generate($label, 'branch', $url, $branchId);
    }

    /**
     * Generate a table-specific menu QR code.
     */
    public function generateTable(int $branchId, string $branchCode, string $tableRef, string $label): array
    {
        $url = $this->getMenuBaseUrl() . '?branch=' . urlencode($branchCode) . '&table=' . urlencode($tableRef);
        return $this->generate($label, 'table', $url, $branchId, $tableRef);
    }

    /**
     * Generate a customer feedback QR code (optional branch and table).
     */
    public function generateFeedback(?int $branchId, ?string $branchCode, ?string $tableRef, string $label): array
    {
        // Derive feedback URL from base QR URL by replacing /menu or appending /feedback
        $base = $this->baseUrl;
        if (str_ends_with($base, '/menu')) {
            $url = substr($base, 0, -5) . '/feedback';
        } else {
            $url = rtrim($base, '/') . '/feedback';
        }

        $params = [];
        if (!empty($branchCode)) {
            $params['branch'] = $branchCode;
        }
        if (!empty($tableRef)) {
            $params['table'] = $tableRef;
        }
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $this->generate($label, 'feedback', $url, $branchId, $tableRef);
    }

    // ---------------------------------------------------------------- Internal

    private function generate(
        string  $label,
        string  $type,
        string  $url,
        ?int    $branchId,
        ?string $tableRef = null
    ): array {
        // Validate URL — must be http(s) and must not redirect to external domains
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException("Invalid QR target URL: {$url}");
        }
        $parsed = parse_url($url);
        $allowedHosts = array_filter([
            parse_url($this->baseUrl, PHP_URL_HOST),
            parse_url(base_url(), PHP_URL_HOST),
        ]);
        if (!in_array($parsed['host'] ?? '', $allowedHosts, true)) {
            throw new RuntimeException("QR URL host does not match configured base URL.");
        }

        // Build output file name
        $filename   = 'qr_' . $type . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.png';
        $outputPath = $this->outputDir . $filename;

        // Generate QR code with automatic version sizing to fit arbitrary URL lengths
        $options = new QROptions([
            'version'       => QRCode::VERSION_AUTO,
            'outputType'    => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'      => QRCode::ECC_M,
            'scale'         => 8,
            'imageBase64'   => false,
            'addQuietzone'  => true,
            'quietzoneSize' => 4,
        ]);

        $qr = new QRCode($options);
        $qr->render($url, $outputPath);

        if (!file_exists($outputPath)) {
            throw new RuntimeException("QR code file was not created at: {$outputPath}");
        }

        Logger::info("QR code generated: {$filename} → {$url}");

        // Persist to DB
        $pdo  = Database::local();
        $stmt = $pdo->prepare(
            "INSERT INTO `qr_codes` (`label`, `type`, `branch_id`, `table_ref`, `public_url`, `file_path`, `is_active`)
             VALUES (:label, :type, :branch_id, :table_ref, :public_url, :file_path, 1)"
        );
        $stmt->execute([
            ':label'      => $label,
            ':type'       => $type,
            ':branch_id'  => $branchId,
            ':table_ref'  => $tableRef,
            ':public_url' => $url,
            ':file_path'  => $filename,
        ]);
        $id = (int) $pdo->lastInsertId();

        return [
            'id'        => $id,
            'label'     => $label,
            'type'      => $type,
            'url'       => $url,
            'file_path' => $filename,
            'full_path' => $outputPath,
        ];
    }

    // ---------------------------------------------------------------- Retrieve

    public function getById(int $id): ?array
    {
        $stmt = Database::local()->prepare(
            "SELECT q.*, b.name AS branch_name, b.code AS branch_code
               FROM `qr_codes` q
          LEFT JOIN `branches` b ON q.branch_id = b.id
              WHERE q.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAll(): array
    {
        return Database::local()->query(
            "SELECT q.*, b.name AS branch_name
               FROM `qr_codes` q
          LEFT JOIN `branches` b ON q.branch_id = b.id
           ORDER BY q.created_at DESC"
        )->fetchAll();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        if (array_key_exists('label', $data)) {
            $fields[] = "`label` = :label";
            $params[':label'] = $data['label'];
        }
        if (array_key_exists('branch_id', $data)) {
            $fields[] = "`branch_id` = :branch_id";
            $params[':branch_id'] = $data['branch_id'] ? (int)$data['branch_id'] : null;
        }
        if (array_key_exists('table_ref', $data)) {
            $fields[] = "`table_ref` = :table_ref";
            $params[':table_ref'] = $data['table_ref'];
        }
        if (array_key_exists('is_active', $data)) {
            $fields[] = "`is_active` = :is_active";
            $params[':is_active'] = (int)$data['is_active'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `qr_codes` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        return Database::local()->prepare($sql)->execute($params);
    }

    public function delete(int $id): bool
    {
        $filePath = $this->getFilePath($id);
        if ($filePath && file_exists($filePath)) {
            @unlink($filePath);
        }
        $stmt = Database::local()->prepare("DELETE FROM `qr_codes` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }

    public function deactivate(int $id): void
    {
        Database::local()
            ->prepare("UPDATE `qr_codes` SET `is_active` = 0 WHERE `id` = ?")
            ->execute([$id]);
    }

    public function activate(int $id): void
    {
        Database::local()
            ->prepare("UPDATE `qr_codes` SET `is_active` = 1 WHERE `id` = ?")
            ->execute([$id]);
    }

    public function getFilePath(int $id): ?string
    {
        $row = $this->getById($id);
        if (!$row || !$row['file_path']) {
            return null;
        }
        $full = $this->outputDir . $row['file_path'];
        return file_exists($full) ? $full : null;
    }

    /** Return base64-encoded PNG data URI for inline preview. */
    public function getBase64Preview(int $id): ?string
    {
        $path = $this->getFilePath($id);
        if (!$path) {
            return null;
        }
        $data = file_get_contents($path);
        return $data ? 'data:image/png;base64,' . base64_encode($data) : null;
    }
}

