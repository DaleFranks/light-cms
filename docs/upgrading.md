# Upgrading

New versions of Light CMS are published on the [Releases](https://github.com/DaleFranks/light-cms/releases) page. The version a site is running is shown at the bottom of the admin's menu.

## How to upgrade

1. On the **Backup** screen, download a backup and keep it somewhere safe.
2. Download the new **`light-cms-<version>.zip`** from Releases (not the "Source code" files), and unzip it.
3. Upload everything inside its `light-cms` folder into the site's folder (such as `public_html`), replacing the files already there.
4. Open the site. On that first visit the CMS brings its database up to date by itself (see below).

The release zip doesn't contain anything that's yours, so uploading it never overwrites your content or settings: the database (`database/database.sqlite`), your images (`public/uploads/`), the settings file (`.env`) and `storage/` are left as they are.

If you edited the `.htaccess` file in the site's folder (for example to [redirect www](redirects.md#redirecting-www-to-your-main-address-or-another-domain)), the release's `.htaccess` replaces it, so make the same edit again, or don't upload that one file.

## What looks different after upgrading to 0.6.0

Most new settings leave a site as it was until they're used, but a few things change by themselves:

- **Italics** are now the font's real italics wherever it has them, rather than slanted upright letters.
- **Bold** is no longer faked for a font with no bold of its own; it shows at its natural weight.
- **Page text line height** is now 1.6, down from 1.625, and is set on the Theme screen.
- **Left and right images and videos** go on their own line, centered, at 768 pixels wide and below.
- **Videos with no width set**: centered ones now fill the text column on large screens rather than 75% of it, and left and right ones take 40% of it.
- **Bullets** sit beside their words in centered and right-aligned cards and modules, and match the text on Primary color panels.

If your **Custom CSS** sets these, check them after upgrading. In particular, `body { line-height: ... }` no longer reaches page text; use the Theme screen's **Line height** instead.

## What looks different after upgrading to 0.7.0

- **Adding images in the editor**: the image button in the editor's toolbar is gone, and images can no longer be dragged or pasted in. Use **Insert image**, just above the editor, to choose an image from the library, upload one, or give the address of one kept elsewhere. See [Writing content](writing-content.md#images).
- **Alt text**: what the Images screen called an image's description is now called its alt text. Nothing about it has changed.

Images already in your pages keep working, however they were added.

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
