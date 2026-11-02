<?php

namespace App;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * A consistent copy of the site's SQLite database, for backups and for the
 * safety copy made before an update.
 *
 * SQLite 3.27 and newer copy the database themselves (VACUUM INTO). On older
 * SQLite, such as the 3.26 many cPanel hosts have, changes to the database are
 * held back for the moment it takes to copy the file, so the copy is never
 * caught half-written. Visitors can still read the site meanwhile.
 */
class DatabaseCopy
{
    /**
     * The oldest SQLite that can copy the database itself.
     */
    private const VACUUM_INTO_SINCE = '3.27.0';

    /**
     * For tests: true or false to choose the method, null to decide by version.
     */
    public static ?bool $useVacuumInto = null;

    public static function to(string $path): void
    {
        $connection = DB::connection();

        if (self::$useVacuumInto ?? version_compare(self::sqliteVersion(), self::VACUUM_INTO_SINCE, '>=')) {
            $connection->statement('VACUUM INTO ?', [$path]);

            return;
        }

        $database = (string) $connection->getDatabaseName();

        if ($database === '' || $database === ':memory:' || ! is_file($database)) {
            throw new RuntimeException('The database isn\'t a file, so it can\'t be copied.');
        }

        $pdo = $connection->getPdo();

        // In WAL mode recent changes live in a separate file; fold them in first
        if (strtolower((string) $pdo->query('pragma journal_mode')->fetchColumn()) === 'wal') {
            $pdo->exec('pragma wal_checkpoint(truncate)');
        }

        $pdo->exec('begin immediate');

        try {
            if (! copy($database, $path)) {
                throw new RuntimeException('The database couldn\'t be copied to '.$path.'.');
            }
        } finally {
            $pdo->exec('commit');
        }
    }

    /**
     * The version of the SQLite library PHP uses, such as "3.26.0".
     */
    public static function sqliteVersion(): string
    {
        return (string) DB::connection()->getPdo()->query('select sqlite_version()')->fetchColumn();
    }
}
