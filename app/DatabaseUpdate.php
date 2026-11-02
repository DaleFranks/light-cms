<?php

namespace App;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Brings the database up to date after a newer copy of the CMS is uploaded
 * over an older one, so a site owner never has to run anything.
 *
 * A marker file records the newest database change the site has applied.
 * Checking it costs one file read per visit; only when the CMS has newer
 * changes is the database copied (so it can be put back if something goes
 * wrong) and then updated.
 */
class DatabaseUpdate
{
    /**
     * How many copies made before updates are kept.
     */
    public const KEPT_COPIES = 3;

    /**
     * How long to wait after a failed update before trying again, in minutes.
     */
    public const RETRY_AFTER_MINUTES = 10;

    /**
     * Run the update if it's due, without ever stopping the site: if it fails,
     * the reason is logged and noted for the admin, the site carries on with
     * the database as it was, and it's tried again a little later.
     */
    public static function runSafely(): void
    {
        $failure = self::failure();

        if ($failure !== null && now()->subMinutes(self::RETRY_AFTER_MINUTES)->lt($failure['at'])) {
            return;
        }

        try {
            self::run();
            File::delete(self::failurePath());
        } catch (Throwable $exception) {
            report($exception);

            File::put(self::failurePath(), json_encode([
                'at' => now()->toIso8601String(),
                'message' => $exception->getMessage(),
            ]));
        }
    }

    /**
     * The last failed update, if the database is still waiting for one.
     *
     * @return array{at: Carbon, message: string}|null
     */
    public static function failure(): ?array
    {
        if (! File::exists(self::failurePath())) {
            return null;
        }

        $failure = json_decode((string) File::get(self::failurePath()), true);

        return [
            'at' => Carbon::parse($failure['at'] ?? '2000-01-01'),
            'message' => (string) ($failure['message'] ?? ''),
        ];
    }

    public static function isNeeded(): bool
    {
        return self::appliedVersion() !== self::latestVersion();
    }

    /**
     * Copy the database, apply the changes and record them, unless another
     * visit is already doing it (which this one then waits for).
     */
    public static function run(): void
    {
        Cache::lock('database-update', 120)->block(60, function (): void {
            if (! self::isNeeded()) {
                return;
            }

            self::copyDatabase();
            Artisan::call('migrate', ['--force' => true]);
            self::markApplied();
        });
    }

    /**
     * Record that the database has every change this copy of the CMS has.
     */
    public static function markApplied(): void
    {
        File::put(self::markerPath(), self::latestVersion()."\n");
    }

    /**
     * The newest database change this copy of the CMS has, such as
     * "2026_11_01_224850_add_two_step_login_to_users_table".
     */
    public static function latestVersion(): string
    {
        $migrations = glob(database_path('migrations/*.php')) ?: [];

        return $migrations === [] ? '' : basename(max($migrations), '.php');
    }

    public static function appliedVersion(): ?string
    {
        return File::exists(self::markerPath()) ? trim(File::get(self::markerPath())) : null;
    }

    /**
     * A copy of the database as it was before the update, in
     * storage/app/backups, keeping the newest few.
     */
    private static function copyDatabase(): void
    {
        $folder = storage_path('app/backups');
        File::ensureDirectoryExists($folder);

        DatabaseCopy::to($folder.'/before-update-'.now()->format('Y-m-d-His').'.sqlite');

        $copies = glob($folder.'/before-update-*.sqlite') ?: [];
        rsort($copies);
        File::delete(array_slice($copies, self::KEPT_COPIES));
    }

    private static function failurePath(): string
    {
        return storage_path('app/database-update-failed');
    }

    private static function markerPath(): string
    {
        return storage_path('app/database-version');
    }
}
