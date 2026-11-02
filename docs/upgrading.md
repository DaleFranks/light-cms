# Upgrading

New versions of Light CMS are published on the [Releases](https://github.com/DaleFranks/light-cms/releases) page. The version a site is running is shown at the bottom of the admin's menu.

## How to upgrade

1. On the **Backup** screen, download a backup and keep it somewhere safe.
2. Download the new **`light-cms-<version>.zip`** from Releases (not the "Source code" files), and unzip it.
3. Upload everything inside its `light-cms` folder into the site's folder (such as `public_html`), replacing the files already there.
4. Open the site. On that first visit the CMS brings its database up to date by itself (see below).

The release zip doesn't contain anything that's yours, so uploading it never overwrites your content or settings: the database (`database/database.sqlite`), your images (`public/uploads/`), the settings file (`.env`) and `storage/` are left as they are.

If you edited the `.htaccess` file in the site's folder (for example to [redirect www](redirects.md#redirecting-www-to-your-main-address-or-another-domain)), the release's `.htaccess` replaces it, so make the same edit again, or don't upload that one file.

## The database update

Some versions add something to the database, such as a new setting. Each of those changes is a file in `database/migrations/`; the site keeps a note of which ones it has applied. On the first visit after an upgrade, if there are new ones, the CMS:

1. saves a copy of the database in `storage/app/backups/` (named `before-update-` with the date and time; the newest three are kept),
2. applies the new changes,
3. notes that the database is up to date.

**Don't delete files in `database/migrations/`.** A new site, or a restored backup, needs all of them to build its database.

## If the update can't be applied

The site keeps working with its database as it was: visitors see no difference. Meanwhile:

- A warning at the top of every admin page says the database couldn't be updated, and why. Until it's done, saving pages and settings may fail, so it's best not to make changes.
- The CMS tries again every 10 minutes by itself.
- The full error is written to `storage/logs/laravel.log`.

Most often the reason is something about the server, such as an older version of a library the host provides. Send the warning's reason (or the end of `laravel.log`) to whoever looks after your site, or to the CMS's [issues page](https://github.com/DaleFranks/light-cms/issues).

To go back to how the site was before the upgrade, upload the previous release's files again, or [restore your backup](backup.md#restoring-a-backup).

## Checking the server

On a site's very first visit, before setup, the CMS checks that the server has what it needs (see the [requirements](../README.md#requirements)) and says plainly what's missing. That includes the SQLite version: the database library PHP uses. If you need to know it on a server that's already running, ask your host, or see "What SQLite version does my server have?" below.

### What SQLite version does my server have?

Create a file called `sqlite-version.php` in the CMS's own `public` folder (for example `public_html/public/`) containing:

```php
<?php echo (new PDO('sqlite::memory:'))->query('select sqlite_version()')->fetchColumn();
```

Open `https://your-site/sqlite-version.php` to see the version, then delete the file. Light CMS needs 3.26 or newer.
