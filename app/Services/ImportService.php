<?php
/**
 * ImportService — Product Import from Remote Source Database
 *
 * Fetches rows from the remote `article_import` table and upserts them into
 * the local `products`, `parent_categories`, and `child_categories` tables.
 * Records every run in `import_logs`. A file-based lock prevents concurrent
 * executions. Safe to run multiple times (idempotent).
 *
 * @package App\Services
 */

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Logger;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

class ImportService
{
    private const BATCH_SIZE  = 200;
    private const LOCK_FILE   = 'storage/cache/import.lock';
    private const UNCATEGORIZED_PARENT = 'Uncategorized';
    private const UNCATEGORIZED_CHILD  = 'General';

    /** @var PDO Local (writable) database connection. */
    private PDO $local;

    /** @var PDO|null Remote source database connection (null if unavailable). */
    private ?PDO $source = null;

    /** @var string Absolute path to the project root. */
    private string $rootPath;

    /** @var string Absolute path to the lock file. */
    private string $lockFile;

    /**
     * Initialises connections.  A failure to reach the source database is
     * recorded but does not prevent the object from being constructed — the
     * error will be surfaced when {@see run()} is called.
     */
    public function __construct()
    {
        $this->rootPath = dirname(__DIR__, 2);
        $this->lockFile = $this->rootPath . DIRECTORY_SEPARATOR . self::LOCK_FILE;

        // Local DB must be available — let it throw naturally.
        $this->local = Database::local();

        // Source DB is optional at construction time; failures are deferred.
        try {
            $this->source = Database::source();
        } catch (Throwable $e) {
            Logger::warning('ImportService: source DB unavailable at init — ' .
                Database::sanitizeError($e->getMessage()));
        }
    }

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Inspects the remote source schema by running a DESCRIBE and a zero-row
     * SELECT on `article_import`.  Read-only; never mutates any data.
     *
     * @return array{columns: array<int,array<string,mixed>>, sample: array<int,array<string,mixed>>}
     *
     * @throws RuntimeException when the source database is unreachable.
     */
    public function inspectSourceSchema(): array
    {
        if ($this->source === null) {
            throw new RuntimeException('Source database connection is not available.');
        }

        $columns = $this->source
            ->query('DESCRIBE `article_import`')
            ->fetchAll(PDO::FETCH_ASSOC);

        $sample = $this->source
            ->query('SELECT * FROM `article_import` LIMIT 3')
            ->fetchAll(PDO::FETCH_ASSOC);

        return [
            'columns' => $columns,
            'sample'  => $sample,
        ];
    }

    /**
     * Runs the full product import pipeline.
     *
     * Steps:
     *   1. Acquires a file-based lock.
     *   2. Writes an `import_logs` record with status = 'running'.
     *   3. Streams rows from the source DB in batches of {@see BATCH_SIZE}.
     *   4. Upserts products and resolves/creates categories for each row.
     *   5. Updates the log record with final counts and status.
     *   6. Releases the lock.
     *
     * In dry-run mode nothing is written to the local DB, but counts are
     * still calculated and logged so the caller can preview the impact.
     *
     * @param bool        $dryRun      When true, no local writes are made.
     * @param int|null    $userId      ID of the user who triggered the import.
     * @param string      $triggeredBy How the import was triggered ('manual', 'cron', etc.).
     *
     * @return array{
     *     log_id: int|null,
     *     status: string,
     *     records_inspected: int,
     *     records_inserted: int,
     *     records_updated: int,
     *     records_skipped: int,
     *     records_errored: int,
     *     error_summary: string,
     *     dry_run: bool,
     *     started_at: string,
     *     completed_at: string
     * }
     */
    public function run(
        bool   $dryRun      = false,
        ?int   $userId      = null,
        string $triggeredBy = 'manual'
    ): array {
        $startedAt = date('Y-m-d H:i:s');

        $stats = [
            'log_id'            => null,
            'status'            => 'running',
            'records_inspected' => 0,
            'records_inserted'  => 0,
            'records_updated'   => 0,
            'records_skipped'   => 0,
            'records_errored'   => 0,
            'error_summary'     => '',
            'dry_run'           => $dryRun,
            'started_at'        => $startedAt,
            'completed_at'      => '',
        ];

        // ---- Lock -------------------------------------------------------
        if (!$this->acquireLock()) {
            $stats['status']        = 'skipped';
            $stats['error_summary'] = 'Another import process is already running.';
            $stats['completed_at']  = date('Y-m-d H:i:s');
            Logger::warning('ImportService: import skipped — lock file exists.');
            return $stats;
        }

        // ---- Create log entry -------------------------------------------
        $logId = $this->createLogEntry($startedAt, $dryRun, $triggeredBy, $userId);
        $stats['log_id'] = $logId;

        try {
            // ---- Source connection check ---------------------------------
            if ($this->source === null) {
                throw new RuntimeException(
                    'Source database is unavailable. Check MYSQL_* env vars for the source connection.'
                );
            }

            // ---- Stream and process rows --------------------------------
            $offset = 0;
            do {
                $stmt = $this->source->prepare(
                    'SELECT `article_code`, `name`, `gsl_type_id`, `gsl_type`,
                            `is_active`, `child_preference_id`, `child_preference`,
                            `parent_preference_id`, `parent_preference`,
                            `default_value`, `consignee_unit`
                     FROM `article_import`
                     ORDER BY `article_code`
                     LIMIT :limit OFFSET :offset'
                );
                $stmt->bindValue(':limit',  self::BATCH_SIZE, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset,          PDO::PARAM_INT);
                $stmt->execute();

                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $count = count($rows);

                if ($count === 0) {
                    break;
                }

                $stats['records_inspected'] += $count;
                $this->importBatch($rows, $dryRun, $stats);

                $offset += $count;

            } while ($count === self::BATCH_SIZE);

            if (!$dryRun && $stats['records_inspected'] > 0) {
                // Ensure only products present in the source database are active
                $deactStmt = $this->local->prepare(
                    'UPDATE `products` SET `is_active_source` = 0 WHERE `synced_at` < :started_at OR `synced_at` IS NULL'
                );
                $deactStmt->execute([':started_at' => $startedAt]);
            }

            $stats['status'] = 'completed';
            Logger::info(sprintf(
                'ImportService: import %scompleted — inspected=%d, inserted=%d, updated=%d, skipped=%d, errored=%d',
                $dryRun ? '[DRY-RUN] ' : '',
                $stats['records_inspected'],
                $stats['records_inserted'],
                $stats['records_updated'],
                $stats['records_skipped'],
                $stats['records_errored']
            ));

        } catch (Throwable $e) {
            $stats['status']        = 'failed';
            $stats['error_summary'] = Database::sanitizeError($e->getMessage());
            Logger::error('ImportService: import failed — ' . Database::sanitizeError($e->getMessage()));
        } finally {
            $stats['completed_at'] = date('Y-m-d H:i:s');
            $this->finaliseLogEntry($logId, $stats);
            $this->releaseLock();
        }

        return $stats;
    }

    // =========================================================================
    // Private — batch processing
    // =========================================================================

    /**
     * Processes a single batch of source rows inside a local DB transaction.
     * On transaction failure the batch is rolled back and the error is
     * recorded in stats without aborting the whole import.
     *
     * @param array<int,array<string,mixed>> $rows
     * @param bool                           $dryRun
     * @param array<string,mixed>            $stats  Passed by reference.
     */
    private function importBatch(array $rows, bool $dryRun, array &$stats): void
    {
        if (!$dryRun) {
            $this->local->beginTransaction();
        }

        try {
            foreach ($rows as $row) {
                $this->upsertProduct($row, $dryRun, $stats);
            }

            if (!$dryRun) {
                $this->local->commit();
            }
        } catch (Throwable $e) {
            if (!$dryRun && $this->local->inTransaction()) {
                $this->local->rollBack();
            }
            $msg = Database::sanitizeError($e->getMessage());
            Logger::error('ImportService: batch failed — ' . $msg);
            $stats['records_errored'] += count($rows);
            if ($stats['error_summary'] === '') {
                $stats['error_summary'] = 'Batch error: ' . $msg;
            }
        }
    }

    /**
     * Inserts or updates a single product row in the local database.
     *
     * The following local-only fields are never overwritten on UPDATE:
     * `local_description`, `image_path`, `is_visible`, `price`, `price_currency`.
     *
     * @param array<string,mixed> $row
     * @param bool                $dryRun
     * @param array<string,mixed> $stats  Passed by reference.
     */
    private function upsertProduct(array $row, bool $dryRun, array &$stats): void
    {
        // Skip rows without a valid article_code.
        $articleCode = trim((string) ($row['article_code'] ?? ''));
        if ($articleCode === '') {
            $stats['records_skipped']++;
            Logger::warning('ImportService: skipped row with empty article_code.');
            return;
        }

        // Resolve categories.
        $catIds = $this->resolveCategory(
            isset($row['parent_preference_id']) && $row['parent_preference_id'] !== ''
                ? (int) $row['parent_preference_id'] : null,
            isset($row['parent_preference']) && trim((string) $row['parent_preference']) !== ''
                ? trim((string) $row['parent_preference']) : null,
            isset($row['child_preference_id']) && $row['child_preference_id'] !== ''
                ? (int) $row['child_preference_id'] : null,
            isset($row['child_preference']) && trim((string) $row['child_preference']) !== ''
                ? trim((string) $row['child_preference']) : null
        );

        $isActiveSource = isset($row['is_active']) ? (int)(bool)$row['is_active'] : 0;
        $name           = trim((string) ($row['name']          ?? ''));
        $gslTypeId      = $row['gsl_type_id']  !== null && $row['gsl_type_id'] !== ''
                            ? (int) $row['gsl_type_id'] : null;
        $gslType        = isset($row['gsl_type'])       ? trim((string) $row['gsl_type'])       : null;
        $consigneeUnit  = isset($row['consignee_unit'])  ? trim((string) $row['consignee_unit']) : null;
        $defaultValue   = isset($row['default_value'])   ? (string) $row['default_value']        : null;

        // Check for existing product.
        $existing = $this->local
            ->prepare('SELECT `id` FROM `products` WHERE `article_code` = :code LIMIT 1');
        $existing->execute([':code' => $articleCode]);
        $existingRow = $existing->fetch(PDO::FETCH_ASSOC);

        if ($dryRun) {
            if ($existingRow) {
                $stats['records_updated']++;
            } else {
                $stats['records_inserted']++;
            }
            return;
        }

        if ($existingRow) {
            // UPDATE — do not touch local-only fields.
            $stmt = $this->local->prepare(
                'UPDATE `products` SET
                    `name`                = :name,
                    `gsl_type_id`         = :gsl_type_id,
                    `gsl_type`            = :gsl_type,
                    `consignee_unit`      = :consignee_unit,
                    `default_value`       = :default_value,
                    `is_active_source`    = :is_active_source,
                    `src_parent_pref_id`  = :src_parent_pref_id,
                    `src_parent_pref`     = :src_parent_pref,
                    `src_child_pref_id`   = :src_child_pref_id,
                    `src_child_pref`      = :src_child_pref,
                    `parent_category_id`  = :parent_category_id,
                    `child_category_id`   = :child_category_id,
                    `synced_at`           = :synced_at,
                    `updated_at`          = :updated_at
                 WHERE `article_code` = :article_code'
            );
            $stmt->execute([
                ':name'                => $name,
                ':gsl_type_id'         => $gslTypeId,
                ':gsl_type'            => $gslType,
                ':consignee_unit'      => $consigneeUnit,
                ':default_value'       => $defaultValue,
                ':is_active_source'    => $isActiveSource,
                ':src_parent_pref_id'  => isset($row['parent_preference_id']) && $row['parent_preference_id'] !== ''
                                            ? (int) $row['parent_preference_id'] : null,
                ':src_parent_pref'     => isset($row['parent_preference']) ? trim((string) $row['parent_preference']) : null,
                ':src_child_pref_id'   => isset($row['child_preference_id']) && $row['child_preference_id'] !== ''
                                            ? (int) $row['child_preference_id'] : null,
                ':src_child_pref'      => isset($row['child_preference']) ? trim((string) $row['child_preference']) : null,
                ':parent_category_id'  => $catIds['parent_category_id'],
                ':child_category_id'   => $catIds['child_category_id'],
                ':synced_at'           => date('Y-m-d H:i:s'),
                ':updated_at'          => date('Y-m-d H:i:s'),
                ':article_code'        => $articleCode,
            ]);
            $stats['records_updated']++;
        } else {
            // INSERT.
            $now  = date('Y-m-d H:i:s');
            $stmt = $this->local->prepare(
                'INSERT INTO `products`
                    (`article_code`, `name`, `gsl_type_id`, `gsl_type`, `consignee_unit`,
                     `default_value`, `is_active_source`,
                     `src_parent_pref_id`, `src_parent_pref`,
                     `src_child_pref_id`,  `src_child_pref`,
                     `parent_category_id`, `child_category_id`,
                     `local_description`, `image_path`, `is_visible`,
                     `price`, `price_currency`,
                     `synced_at`, `created_at`, `updated_at`)
                 VALUES
                    (:article_code, :name, :gsl_type_id, :gsl_type, :consignee_unit,
                     :default_value, :is_active_source,
                     :src_parent_pref_id, :src_parent_pref,
                     :src_child_pref_id,  :src_child_pref,
                     :parent_category_id, :child_category_id,
                     NULL, NULL, 1,
                     NULL, NULL,
                     :synced_at, :created_at, :updated_at)'
            );
            $stmt->execute([
                ':article_code'       => $articleCode,
                ':name'               => $name,
                ':gsl_type_id'        => $gslTypeId,
                ':gsl_type'           => $gslType,
                ':consignee_unit'     => $consigneeUnit,
                ':default_value'      => $defaultValue,
                ':is_active_source'   => $isActiveSource,
                ':src_parent_pref_id' => isset($row['parent_preference_id']) && $row['parent_preference_id'] !== ''
                                           ? (int) $row['parent_preference_id'] : null,
                ':src_parent_pref'    => isset($row['parent_preference']) ? trim((string) $row['parent_preference']) : null,
                ':src_child_pref_id'  => isset($row['child_preference_id']) && $row['child_preference_id'] !== ''
                                           ? (int) $row['child_preference_id'] : null,
                ':src_child_pref'     => isset($row['child_preference']) ? trim((string) $row['child_preference']) : null,
                ':parent_category_id' => $catIds['parent_category_id'],
                ':child_category_id'  => $catIds['child_category_id'],
                ':synced_at'          => $now,
                ':created_at'         => $now,
                ':updated_at'         => $now,
            ]);
            $stats['records_inserted']++;
        }
    }

    // =========================================================================
    // Private — category resolution
    // =========================================================================

    /**
     * Resolves (and creates if necessary) local parent and child category rows.
     *
     * Falls back to an "Uncategorized / General" category when the source row
     * carries no category information.
     *
     * @param int|null    $parentId   Source parent_preference_id
     * @param string|null $parentName Source parent_preference
     * @param int|null    $childId    Source child_preference_id
     * @param string|null $childName  Source child_preference
     *
     * @return array{parent_category_id: int, child_category_id: int|null}
     */
    private function resolveCategory(
        ?int    $parentId,
        ?string $parentName,
        ?int    $childId,
        ?string $childName
    ): array {
        $effectiveParentName = ($parentName !== null && $parentName !== '')
            ? $parentName
            : self::UNCATEGORIZED_PARENT;

        $localParentId = $this->upsertParentCategory($parentId, $effectiveParentName);

        $localChildId = null;
        if ($childName !== null && $childName !== '') {
            $localChildId = $this->upsertChildCategory($childId, $childName, $localParentId);
        } elseif ($parentName === null || $parentName === '') {
            // Both parent and child are empty — put under the general child too.
            $localChildId = $this->upsertChildCategory(null, self::UNCATEGORIZED_CHILD, $localParentId);
        }

        return [
            'parent_category_id' => $localParentId,
            'child_category_id'  => $localChildId,
        ];
    }

    /**
     * Finds or creates a parent category by its normalised name or source ID.
     *
     * The upsert uses `name_normalized` as the de-duplication key so that
     * minor spelling differences in the source are merged.
     *
     * @param int|null $sourceId  Source system parent_preference_id (may be NULL).
     * @param string   $name      Human-readable category name.
     *
     * @return int Local `parent_categories.id`.
     */
    private function upsertParentCategory(?int $sourceId, string $name): int
    {
        $normalized = mb_strtolower(trim($name));

        // Try to find by normalised name first (primary de-dup key).
        $stmt = $this->local->prepare(
            'SELECT `id` FROM `parent_categories` WHERE `name_normalized` = :nn LIMIT 1'
        );
        $stmt->execute([':nn' => $normalized]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Optionally update source_id if we now know it.
            if ($sourceId !== null) {
                $upd = $this->local->prepare(
                    'UPDATE `parent_categories`
                     SET `source_id` = :sid
                     WHERE `id` = :id AND (`source_id` IS NULL OR `source_id` = :sid2)'
                );
                $upd->execute([':sid' => $sourceId, ':id' => (int)$row['id'], ':sid2' => $sourceId]);
            }
            return (int) $row['id'];
        }

        // Insert new parent category.
        $ins = $this->local->prepare(
            'INSERT INTO `parent_categories`
                (`source_id`, `name`, `name_normalized`, `is_visible`, `sort_order`)
             VALUES (:sid, :name, :nn, 1, 0)'
        );
        $ins->execute([
            ':sid'  => $sourceId,
            ':name' => $name,
            ':nn'   => $normalized,
        ]);
        return (int) $this->local->lastInsertId();
    }

    /**
     * Finds or creates a child category scoped under a given parent.
     *
     * De-duplication is by `name_normalized` + `parent_category_id`.
     *
     * @param int|null $sourceId      Source system child_preference_id (may be NULL).
     * @param string   $name          Human-readable child category name.
     * @param int      $parentLocalId Local `parent_categories.id`.
     *
     * @return int Local `child_categories.id`.
     */
    private function upsertChildCategory(?int $sourceId, string $name, int $parentLocalId): int
    {
        $normalized = mb_strtolower(trim($name));

        $stmt = $this->local->prepare(
            'SELECT `id` FROM `child_categories`
             WHERE `name_normalized` = :nn AND `parent_category_id` = :pid
             LIMIT 1'
        );
        $stmt->execute([':nn' => $normalized, ':pid' => $parentLocalId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            if ($sourceId !== null) {
                $upd = $this->local->prepare(
                    'UPDATE `child_categories`
                     SET `source_id` = :sid
                     WHERE `id` = :id AND (`source_id` IS NULL OR `source_id` = :sid2)'
                );
                $upd->execute([':sid' => $sourceId, ':id' => (int)$row['id'], ':sid2' => $sourceId]);
            }
            return (int) $row['id'];
        }

        $ins = $this->local->prepare(
            'INSERT INTO `child_categories`
                (`parent_category_id`, `source_id`, `name`, `name_normalized`, `is_visible`, `sort_order`)
             VALUES (:pid, :sid, :name, :nn, 1, 0)'
        );
        $ins->execute([
            ':pid'  => $parentLocalId,
            ':sid'  => $sourceId,
            ':name' => $name,
            ':nn'   => $normalized,
        ]);
        return (int) $this->local->lastInsertId();
    }

    // =========================================================================
    // Private — import_logs helpers
    // =========================================================================

    /**
     * Writes the initial import log record and returns its ID.
     *
     * @return int|null The new log entry ID, or null on failure.
     */
    private function createLogEntry(
        string  $startedAt,
        bool    $dryRun,
        string  $triggeredBy,
        ?int    $userId
    ): ?int {
        try {
            $stmt = $this->local->prepare(
                'INSERT INTO `import_logs`
                    (`started_at`, `status`, `is_dry_run`, `triggered_by`, `user_id`,
                     `records_inspected`, `records_inserted`, `records_updated`,
                     `records_skipped`, `records_errored`, `error_summary`, `notes`)
                 VALUES
                    (:started_at, :status, :is_dry_run, :triggered_by, :user_id,
                     0, 0, 0, 0, 0, NULL, NULL)'
            );
            $stmt->execute([
                ':started_at'   => $startedAt,
                ':status'       => 'running',
                ':is_dry_run'   => (int) $dryRun,
                ':triggered_by' => $triggeredBy,
                ':user_id'      => $userId,
            ]);
            return (int) $this->local->lastInsertId();
        } catch (Throwable $e) {
            Logger::error('ImportService: could not create log entry — ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Updates the import log record with final counts and status.
     *
     * @param int|null            $logId
     * @param array<string,mixed> $stats
     */
    private function finaliseLogEntry(?int $logId, array $stats): void
    {
        if ($logId === null) {
            return;
        }
        try {
            $stmt = $this->local->prepare(
                'UPDATE `import_logs` SET
                    `completed_at`        = :completed_at,
                    `status`              = :status,
                    `records_inspected`   = :records_inspected,
                    `records_inserted`    = :records_inserted,
                    `records_updated`     = :records_updated,
                    `records_skipped`     = :records_skipped,
                    `records_errored`     = :records_errored,
                    `error_summary`       = :error_summary
                 WHERE `id` = :id'
            );
            $stmt->execute([
                ':completed_at'      => $stats['completed_at'],
                ':status'            => $stats['status'],
                ':records_inspected' => $stats['records_inspected'],
                ':records_inserted'  => $stats['records_inserted'],
                ':records_updated'   => $stats['records_updated'],
                ':records_skipped'   => $stats['records_skipped'],
                ':records_errored'   => $stats['records_errored'],
                ':error_summary'     => $stats['error_summary'] !== '' ? $stats['error_summary'] : null,
                ':id'                => $logId,
            ]);
        } catch (Throwable $e) {
            Logger::error('ImportService: could not finalise log entry — ' . $e->getMessage());
        }
    }

    // =========================================================================
    // Private — file-based lock
    // =========================================================================

    /**
     * Acquires an exclusive file-based lock to prevent concurrent imports.
     *
     * The lock file contains the current PID so stale locks from crashed
     * processes can be detected and removed.
     *
     * @return bool True if the lock was acquired, false if already locked.
     */
    private function acquireLock(): bool
    {
        $lockDir = dirname($this->lockFile);
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0750, true);
        }

        // Check for a stale lock (process no longer running).
        if (file_exists($this->lockFile)) {
            $pid = (int) trim((string) @file_get_contents($this->lockFile));
            if ($pid > 0 && function_exists('posix_kill')) {
                // On Unix-like: check if process is alive.
                if (!posix_kill($pid, 0)) {
                    Logger::warning("ImportService: removing stale lock (PID $pid no longer running).");
                    @unlink($this->lockFile);
                } else {
                    return false;
                }
            } else {
                // Windows or no posix: trust the lock file.
                return false;
            }
        }

        $fp = @fopen($this->lockFile, 'x'); // exclusive create
        if ($fp === false) {
            return false; // Another process created it between the check and now.
        }
        fwrite($fp, (string) getmypid());
        fclose($fp);
        return true;
    }

    /**
     * Releases the file-based import lock.
     */
    private function releaseLock(): void
    {
        if (file_exists($this->lockFile)) {
            @unlink($this->lockFile);
        }
    }
}
