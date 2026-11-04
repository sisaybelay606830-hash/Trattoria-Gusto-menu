<?php
/**
 * AutoSyncService — Automatic Background Synchronizer
 *
 * Ensures that changes in `kaldisbp_cnet_synced_data.article_import` automatically
 * update the local menu database (`kaldis-menu`) without requiring any manual clicks.
 *
 * Employs a lightweight throttle (e.g. checks at most once every 3-5 minutes)
 * to prevent database overhead while guaranteeing fresh data.
 *
 * @package App\Services
 */

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Logger;
use App\Repositories\SettingsRepository;
use Throwable;

class AutoSyncService
{
    /** Setting key where the last sync timestamp is stored */
    private const LAST_SYNC_KEY = 'last_auto_sync_at';

    /** Minimum interval between sync checks in seconds (default: 180s / 3 minutes) */
    private const DEFAULT_INTERVAL = 180;

    /**
     * Check if a synchronization is due, and run it if necessary.
     * Designed to be non-blocking and safe for user-facing requests.
     *
     * @param int|null $intervalSeconds Optional custom interval in seconds
     * @return bool True if sync was executed, false if skipped/throttled
     */
    public static function checkAndSync(?int $intervalSeconds = null): bool
    {
        $interval = $intervalSeconds ?? self::DEFAULT_INTERVAL;

        try {
            $settingsRepo = new SettingsRepository();
            $lastSync = (int) $settingsRepo->get(self::LAST_SYNC_KEY, 0);
            $now = time();

            // Throttle: Skip if last check was within the interval window
            if (($now - $lastSync) < $interval) {
                return false;
            }

            // Update timestamp immediately to prevent concurrent triggers
            $settingsRepo->set(self::LAST_SYNC_KEY, (string) $now);

            // Execute live import
            $importService = new ImportService();
            $result = $importService->run(false, null, 'auto_sync');

            Logger::info(sprintf(
                'AutoSyncService: Completed auto-sync (inspected=%d, inserted=%d, updated=%d)',
                $result['records_inspected'] ?? 0,
                $result['records_inserted'] ?? 0,
                $result['records_updated'] ?? 0
            ));

            return true;
        } catch (Throwable $e) {
            // Fail silently so customer browsing is never interrupted by sync errors
            Logger::error('AutoSyncService: background check failed — ' . Database::sanitizeError($e->getMessage()));
            return false;
        }
    }

    /**
     * Force an immediate sync regardless of throttle interval.
     */
    public static function forceSync(): array
    {
        $settingsRepo = new SettingsRepository();
        $settingsRepo->set(self::LAST_SYNC_KEY, (string) time());

        $importService = new ImportService();
        return $importService->run(false, null, 'force_sync');
    }
}

