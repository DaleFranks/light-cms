<?php

/**
 * Gets a freshly uploaded copy of RamaSite ready to start, before Composer or
 * Laravel are loaded.
 *
 * On the first visit there's no .env settings file yet. This checks that the
 * host can run the CMS and, if it can, writes .env (with a new app key and the
 * site's address) and creates the empty SQLite database. If the host can't,
 * it shows a plain page saying what's missing, in words a site owner can take
 * to their host.
 *
 * It's written for PHP 7.4 and newer (so without constructor property
 * promotion), so it can still explain the problem on a host whose PHP is too
 * old for the rest of the CMS.
 */
final class FirstRunSetup
{
    /**
     * The oldest PHP the CMS's packages run on.
     */
    public const MINIMUM_PHP_VERSION = '8.4.1';

    /**
     * The oldest SQLite the CMS is tested on (the one in Red Hat and
     * AlmaLinux 8, common on cPanel hosts).
     */
    public const MINIMUM_SQLITE_VERSION = '3.26.0';

    /**
     * PHP extensions the CMS needs, with a plain description of what each is for.
     *
     * @var array<string, string>
     */
    public const REQUIRED_EXTENSIONS = [
        'pdo_sqlite' => 'the SQLite database',
        'mbstring' => 'handling text',
        'intl' => 'the admin panel',
        'openssl' => 'encryption',
        'fileinfo' => 'checking uploaded files',
        'tokenizer' => 'the page templates',
        'ctype' => 'checking text',
        'dom' => 'reading HTML',
        'xmlreader' => 'reading XML',
        'filter' => 'checking addresses',
        'session' => 'logging in',
    ];

    /**
     * Folders the CMS writes to, relative to its own folder.
     *
     * @var list<string>
     */
    public const WRITABLE_FOLDERS = [
        '',
        'bootstrap/cache',
        'database',
        'public',
        'storage',
        'storage/app',
        'storage/framework',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
    ];

    /**
     * The CMS's own folder.
     */
    private string $root;

    /**
     * The running PHP version.
     */
    private string $phpVersion;

    /**
     * Checks whether a PHP extension is available.
     *
     * @var callable(string): bool
     */
    private $isExtensionLoaded;

    /**
     * The SQLite version PHP uses, or null to look it up.
     *
     * @var string|null
     */
    private $sqliteVersion;

    /**
     * @param  callable(string): bool|null  $isExtensionLoaded
     */
    public function __construct(string $root, string $phpVersion = PHP_VERSION, ?callable $isExtensionLoaded = null, ?string $sqliteVersion = null)
    {
        $this->root = $root;
        $this->phpVersion = $phpVersion;
        $this->isExtensionLoaded = $isExtensionLoaded ?? 'extension_loaded';
        $this->sqliteVersion = $sqliteVersion;
    }

    /**
     * The version of the SQLite library PHP uses, such as "3.26.0".
     */
    private function sqliteVersion(): string
    {
        if ($this->sqliteVersion === null) {
            $this->sqliteVersion = (string) (new PDO('sqlite::memory:'))->query('select sqlite_version()')->fetchColumn();
        }

        return $this->sqliteVersion;
    }

    /**
     * Prepare the CMS on its first visit. Returns once it's ready to start;
     * otherwise shows what's missing and stops.
     *
     * @param  array<string, mixed>  $server  The request's $_SERVER values.
     */
    public static function run(string $root, array $server): void
    {
        if (is_file($root.'/.env')) {
            return;
        }

        $setup = new self($root);
        $problems = $setup->problems();

        if ($problems === []) {
            $setup->prepare(self::siteAddress($server));

            return;
        }

        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo self::problemsPage($problems);

        exit;
    }

    /**
     * What stops the CMS running on this host, in plain words.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        $problems = [];

        if (version_compare($this->phpVersion, self::MINIMUM_PHP_VERSION, '<')) {
            $problems[] = 'Your host needs to run PHP '.self::MINIMUM_PHP_VERSION.' or newer. This site is running PHP '
                .$this->phpVersion.'. The PHP version can usually be changed in your hosting control panel.';
        }

        foreach (self::REQUIRED_EXTENSIONS as $extension => $purpose) {
            if (! ($this->isExtensionLoaded)($extension)) {
                $problems[] = 'The PHP extension "'.$extension.'" ('.$purpose.') isn\'t switched on. It can usually be '
                    .'switched on in your hosting control panel, under PHP settings or extensions.';
            }
        }

        if (($this->isExtensionLoaded)('pdo_sqlite') && version_compare($this->sqliteVersion(), self::MINIMUM_SQLITE_VERSION, '<')) {
            $problems[] = 'Your host\'s PHP needs SQLite '.self::MINIMUM_SQLITE_VERSION.' or newer. This server has SQLite '
                .$this->sqliteVersion().'. Ask your host whether a newer PHP or server version is available.';
        }

        if (! is_file($this->root.'/.env.example')) {
            $problems[] = 'The file ".env.example" is missing from the site\'s folder. Upload all of the CMS\'s files again.';
        }

        if (! is_file($this->root.'/vendor/autoload.php')) {
            $problems[] = 'The "vendor" folder is missing from the site\'s folder. Upload all of the CMS\'s files again.';
        }

        foreach (self::WRITABLE_FOLDERS as $folder) {
            $path = rtrim($this->root.'/'.$folder, '/');

            if (! is_dir($path)) {
                $problems[] = 'The folder "'.($folder ?: 'the site\'s own folder').'" is missing. Upload all of the CMS\'s files again.';
            } elseif (! is_writable($path)) {
                $problems[] = 'The site can\'t save files in the folder "'.($folder ?: 'the site\'s own folder').'". '
                    .'Its permissions need to let the web server write to it (often 755, or 775).';
            }
        }

        return $problems;
    }

    /**
     * Write .env, made from .env.example with production settings, a new app
     * key and the site's address, and create the empty database.
     */
    public function prepare(string $siteAddress): void
    {
        $settings = [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
            'APP_URL' => $siteAddress,
            'LOG_LEVEL' => 'error',
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'sendmail',
        ];

        $env = (string) file_get_contents($this->root.'/.env.example');

        foreach ($settings as $name => $value) {
            $line = $name.'='.$value;
            $pattern = '/^'.preg_quote($name, '/').'=.*$/m';
            $env = preg_match($pattern, $env) ? preg_replace($pattern, $line, $env) : rtrim($env)."\n".$line."\n";
        }

        $database = $this->root.'/database/database.sqlite';

        if (! is_file($database)) {
            touch($database);
        }

        // Written to a temporary file and renamed, so a visit arriving at the same
        // moment never sees a half-written .env.
        $temporary = $this->root.'/.env.'.bin2hex(random_bytes(4)).'.tmp';
        file_put_contents($temporary, $env);
        rename($temporary, $this->root.'/.env');
    }

    /**
     * The request's server details with the CMS's own public folder taken out
     * of the script's path. Visits reach public/index.php through the .htaccess
     * file in the site's folder, so for a site in /blog the script is
     * /blog/public/index.php, which doesn't match the addresses visitors use.
     * Given /blog/index.php instead, Laravel finds the folder the site is in,
     * and every address it makes includes it.
     *
     * @param  array<string, mixed>  $server
     * @return array<string, mixed>
     */
    public static function withSiteEntryPoint(array $server): array
    {
        foreach (['SCRIPT_NAME', 'PHP_SELF'] as $key) {
            if (isset($server[$key]) && is_string($server[$key])) {
                $server[$key] = preg_replace('#/public/index\.php$#', '/index.php', $server[$key]);
            }
        }

        return $server;
    }

    /**
     * The site's address, such as "https://example.com" or
     * "https://example.com/blog" when it's in a folder, worked out from the
     * first visit. The CMS's own public folder is left off the end.
     *
     * @param  array<string, mixed>  $server
     */
    public static function siteAddress(array $server): string
    {
        $isSecure = (! empty($server['HTTPS']) && strtolower((string) $server['HTTPS']) !== 'off')
            || strtolower((string) ($server['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
            || (string) ($server['SERVER_PORT'] ?? '') === '443';

        $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($server['HTTP_HOST'] ?? 'localhost')) ?: 'localhost';
        $folder = str_replace('\\', '/', dirname((string) ($server['SCRIPT_NAME'] ?? '/index.php')));
        $folder = preg_replace('#/public$#', '', rtrim($folder, '/'));

        return ($isSecure ? 'https' : 'http').'://'.$host.$folder;
    }

    /**
     * A plain page listing what's stopping the CMS from running.
     *
     * @param  list<string>  $problems
     */
    public static function problemsPage(array $problems): string
    {
        $items = '';

        foreach ($problems as $problem) {
            $items .= '<li>'.htmlspecialchars($problem, ENT_QUOTES, 'UTF-8').'</li>';
        }

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>RamaSite can't start yet</title>
                <style>
                    body { margin: 0; padding: 48px 16px; background: #f8fafc; color: #1e293b; font: 16px/1.6 system-ui, sans-serif; }
                    main { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 32px; }
                    h1 { margin-top: 0; font-size: 1.5rem; color: #0f172a; }
                    li { margin-bottom: 12px; }
                </style>
            </head>
            <body>
                <main>
                    <h1>RamaSite can't start yet</h1>
                    <p>Your web host needs a few changes before the site can be set up:</p>
                    <ul>{$items}</ul>
                    <p>Once they're made, reload this page.</p>
                </main>
            </body>
            </html>
            HTML;
    }
}
