# Light CMS

A lightweight, self-contained content management system for anyone who wants a website that's quick to set up and easy to look after.

Everything lives in the site's own folder: the content in a single SQLite database file, and images in an `uploads` folder. There's no database server or other outside service to connect to. It runs on ordinary shared hosting (Apache and cPanel), with no command line and no settings files to edit.

- **Pages, blogs and posts** written in Markdown, with a menu built from the pages
- **Cards, modules and sliders** to build up pages without writing HTML
- **An image library**, embedded YouTube videos, and **forms** with stored submissions and email notifications
- **A Theme screen** for the logo, colors, fonts, header, footer and an optional top bar, with no template code to edit
- **Redirects** (added automatically when a page moves), an automatic **sitemap** and `robots.txt`
- **Several admins**, optional **two-step login**, password reset by email, and HTTPS enforced on HTTPS sites
- **One-click backups**, and the database updates itself after an upgrade

## Requirements

- PHP **8.4.1 or newer**, with these extensions (most hosts have them all): `pdo_sqlite`, `mbstring`, `intl`, `openssl`, `fileinfo`, `tokenizer`, `ctype`, `dom`, `xmlreader`, `filter` and `session`. The `zip` extension is also needed for the Backup screen.
- **SQLite 3.26 or newer**, the database library PHP uses (3.26 is what Red Hat, AlmaLinux and CloudLinux 8 servers have)
- Apache with `mod_rewrite` (standard on cPanel hosting)

If something is missing, the site says what on its first visit, in words you can pass on to your host.

## Installing

1. Download **`light-cms-<version>.zip`** from the [Releases](https://github.com/DaleFranks/light-cms/releases) page. Use that file, not the "Source code" downloads, which can't run on their own.
2. Unzip it. Upload everything inside the `light-cms` folder into your site's web folder (usually `public_html`), or into a subfolder of it.
3. Open the site in your browser, using `https://` if your site has a certificate.
4. Fill in the setup screen: the site's name, and your name, email address and password. Do this straight after uploading, since whoever fills it in first becomes the admin.

The admin is at **`/admin`**. See [Getting started](docs/getting-started.md) for what to do next.

## Upgrading

1. On the **Backup** screen, download a backup.
2. Upload the new release's files over the old ones.
3. Open the site. On that first visit it updates its database by itself, after saving a copy of it. If that can't be done, the site keeps working as it was and the admin shows why; see [Upgrading](docs/upgrading.md).

Your pages, images, settings and accounts aren't in the release zip, so uploading a new version never overwrites them.

## Documentation

How each part of the admin works: **[docs](docs/README.md)**.

## License

Light CMS is free and open-source software under the [MIT License](LICENSE). Copyright (c) 2026 Dale Franks.

## For developers

Light CMS is built with Laravel and Filament, using SQLite.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan test
```

Releases are built by GitHub Actions when a tag starting with `v` is pushed: the tests and a package security check run, then the zip (with the `vendor` folder) is published. A tag with a hyphen, such as `v0.4.0-alpha`, is published as a pre-release.
