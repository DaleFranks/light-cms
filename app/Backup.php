<?php

namespace App;

use App\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * A copy of everything that makes a site its own, as one zip: the database
 * (all pages, settings, accounts and form submissions), the uploaded images,
 * logo and tab icon, and the .env settings file, whose key unlocks the saved
 * SMTP password and two-step login.
 *
 * Inside the zip the files sit where they belong in the site's folder, so
 * restoring is uploading them over a copy of the CMS.
 */
class Backup
{
    /**
     * Whether this server can make zip files.
     */
    public static function isAvailable(): bool
    {
        return class_exists(ZipArchive::class);
    }

    /**
     * Make the zip and return where it is. The caller removes it once sent.
     */
    public static function create(): string
    {
        if (! self::isAvailable()) {
            throw new RuntimeException('This server can\'t make zip files (PHP\'s zip extension is missing).');
        }

        $folder = storage_path('app/backups');
        File::ensureDirectoryExists($folder);

        $zipPath = $folder.'/'.self::filename();
        $snapshotPath = $folder.'/'.Str::random(16).'.sqlite';

        try {
            // A snapshot rather than the live file, so a change made while the backup
            // is being made can't leave a half-written copy.
            DB::statement('VACUUM INTO ?', [$snapshotPath]);

            $zip = new ZipArchive;

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('The backup file couldn\'t be created.');
            }

            $zip->addFile($snapshotPath, 'database/database.sqlite');

            if (File::exists(base_path('.env'))) {
                $zip->addFile(base_path('.env'), '.env');
            }

            // Which database changes the copy has, so restoring it onto a newer CMS updates it
            $zip->addFromString('storage/app/database-version', (string) DatabaseUpdate::appliedVersion()."\n");

            $uploads = Storage::disk(Image::DISK);

            foreach ($uploads->allFiles() as $file) {
                $zip->addFile($uploads->path($file), 'public/uploads/'.$file);
            }

            $zip->addFromString('RESTORING.txt', self::instructions());
            $zip->close();
        } finally {
            File::delete($snapshotPath);
        }

        return $zipPath;
    }

    /**
     * Such as "backup-example.com-2026-11-01-1530.zip".
     */
    public static function filename(): string
    {
        $site = Str::slug(parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'site', '.');

        return 'backup-'.$site.'-'.now()->format('Y-m-d-Hi').'.zip';
    }

    private static function instructions(): string
    {
        return <<<'TEXT'
            Light CMS backup
            ================

            This zip holds everything that makes the site its own:

              database/database.sqlite   pages, posts, cards, modules, sliders, forms,
                                         form submissions, redirects, the Theme and
                                         Email settings, and the admin accounts
              public/uploads/            the image library, logo and tab icon
              .env                       the site's settings file; its key unlocks the
                                         saved SMTP password and two-step login
              storage/app/database-version   which database changes the copy has

            To restore the site:

              1. Put a copy of Light CMS on the server: the version the site was
                 running when this backup was made, or a newer one. A newer one
                 updates the database itself on the first visit.
              2. Upload the files in this zip into the site's folder, replacing the
                 ones already there.
              3. If the site's address has changed, open .env and change APP_URL to
                 the new address.

            Keep this zip private: it contains the admin accounts and the key to the
            saved passwords.

            TEXT;
    }
}
