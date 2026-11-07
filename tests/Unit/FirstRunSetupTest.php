<?php

namespace Tests\Unit;

use FirstRunSetup;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../bootstrap/FirstRunSetup.php';

class FirstRunSetupTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/ramasite-first-run-'.bin2hex(random_bytes(4));

        foreach (FirstRunSetup::WRITABLE_FOLDERS as $folder) {
            @mkdir($this->root.'/'.$folder, 0777, true);
        }

        mkdir($this->root.'/vendor');
        touch($this->root.'/vendor/autoload.php');
        file_put_contents($this->root.'/.env.example', implode("\n", [
            'APP_NAME="RamaSite"',
            'APP_ENV=local',
            'APP_KEY=',
            'APP_DEBUG=true',
            'APP_URL=http://localhost:8000',
            'SESSION_DRIVER=database',
            'CACHE_STORE=database',
            'MAIL_MAILER=log',
        ])."\n");
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->root));

        parent::tearDown();
    }

    private function firstRun(string $phpVersion = '8.4.10', array $missingExtensions = [], string $sqliteVersion = '3.26.0'): FirstRunSetup
    {
        return new FirstRunSetup($this->root, $phpVersion, fn (string $extension): bool => ! in_array($extension, $missingExtensions, true), $sqliteVersion);
    }

    public function test_a_host_that_meets_the_requirements_has_no_problems(): void
    {
        $this->assertSame([], $this->firstRun()->problems());
    }

    public function test_too_old_a_php_is_explained(): void
    {
        $problems = $this->firstRun('8.2.12')->problems();

        $this->assertCount(1, $problems);
        $this->assertStringContainsString('PHP 8.4.1 or newer. This site is running PHP 8.2.12.', $problems[0]);
    }

    public function test_too_old_an_sqlite_is_explained_and_3_26_is_accepted(): void
    {
        $this->assertSame([], $this->firstRun(sqliteVersion: '3.26.0')->problems());

        $problems = $this->firstRun(sqliteVersion: '3.7.17')->problems();

        $this->assertCount(1, $problems);
        $this->assertStringContainsString('SQLite 3.26.0 or newer. This server has SQLite 3.7.17.', $problems[0]);
    }

    public function test_the_sqlite_version_is_looked_up_when_not_given(): void
    {
        $this->assertSame([], (new FirstRunSetup($this->root, '8.4.10', fn (): bool => true))->problems());
    }

    public function test_missing_extensions_are_named_with_what_they_are_for(): void
    {
        $problems = $this->firstRun(missingExtensions: ['intl', 'pdo_sqlite'])->problems();

        $this->assertCount(2, $problems);
        $this->assertStringContainsString('"pdo_sqlite" (the SQLite database)', implode(' ', $problems));
        $this->assertStringContainsString('"intl" (the admin panel)', implode(' ', $problems));
    }

    public function test_missing_files_and_folders_are_explained(): void
    {
        unlink($this->root.'/vendor/autoload.php');
        rmdir($this->root.'/storage/logs');

        $problems = implode(' ', $this->firstRun()->problems());

        $this->assertStringContainsString('The "vendor" folder is missing', $problems);
        $this->assertStringContainsString('The folder "storage/logs" is missing', $problems);
    }

    public function test_a_folder_the_site_cannot_write_to_is_explained(): void
    {
        if (function_exists('posix_getuid') && posix_getuid() === 0) {
            $this->markTestSkipped('Every folder is writable when running as root.');
        }

        chmod($this->root.'/database', 0555);

        $problems = implode(' ', $this->firstRun()->problems());

        chmod($this->root.'/database', 0777);

        $this->assertStringContainsString('can\'t save files in the folder "database"', $problems);
    }

    public function test_preparing_writes_production_settings_with_a_new_key_and_creates_the_database(): void
    {
        $this->firstRun()->prepare('https://example.com');

        $env = file_get_contents($this->root.'/.env');

        $this->assertStringContainsString("APP_ENV=production\n", $env);
        $this->assertStringContainsString("APP_DEBUG=false\n", $env);
        $this->assertStringContainsString("APP_URL=https://example.com\n", $env);
        $this->assertStringContainsString("SESSION_DRIVER=file\n", $env);
        $this->assertStringContainsString("CACHE_STORE=file\n", $env);
        $this->assertStringContainsString("MAIL_MAILER=sendmail\n", $env);
        $this->assertStringContainsString("QUEUE_CONNECTION=sync\n", $env);
        $this->assertStringContainsString('APP_NAME="RamaSite"', $env);
        $this->assertMatchesRegularExpression('/^APP_KEY=base64:[A-Za-z0-9+\/]{43}=$/m', $env);
        $this->assertFileExists($this->root.'/database/database.sqlite');
        $this->assertSame([], glob($this->root.'/.env.*.tmp'));
    }

    public function test_each_site_gets_its_own_key(): void
    {
        $this->firstRun()->prepare('https://one.example');
        preg_match('/^APP_KEY=(.+)$/m', file_get_contents($this->root.'/.env'), $first);
        unlink($this->root.'/.env');

        $this->firstRun()->prepare('https://two.example');
        preg_match('/^APP_KEY=(.+)$/m', file_get_contents($this->root.'/.env'), $second);

        $this->assertNotSame($first[1], $second[1]);
    }

    public function test_an_existing_database_is_kept(): void
    {
        file_put_contents($this->root.'/database/database.sqlite', 'existing');

        $this->firstRun()->prepare('https://example.com');

        $this->assertSame('existing', file_get_contents($this->root.'/database/database.sqlite'));
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function visits(): array
    {
        return [
            'site at the domain root' => [['HTTP_HOST' => 'example.com', 'SCRIPT_NAME' => '/index.php'], 'http://example.com'],
            'uploaded into public_html' => [['HTTP_HOST' => 'example.com', 'SCRIPT_NAME' => '/public/index.php', 'HTTPS' => 'on'], 'https://example.com'],
            'in a folder' => [['HTTP_HOST' => 'example.com', 'SCRIPT_NAME' => '/blog/public/index.php', 'HTTPS' => 'on'], 'https://example.com/blog'],
            'behind a proxy' => [['HTTP_HOST' => 'example.com', 'SCRIPT_NAME' => '/index.php', 'HTTP_X_FORWARDED_PROTO' => 'https'], 'https://example.com'],
            'with a port' => [['HTTP_HOST' => 'localhost:8080', 'SCRIPT_NAME' => '/index.php'], 'http://localhost:8080'],
            'odd characters in the host' => [['HTTP_HOST' => 'exa"mple.com<script>', 'SCRIPT_NAME' => '/index.php'], 'http://example.comscript'],
        ];
    }

    #[DataProvider('visits')]
    public function test_the_site_address_is_worked_out_from_the_first_visit(array $server, string $address): void
    {
        $this->assertSame($address, FirstRunSetup::siteAddress($server));
    }

    public function test_the_problems_page_escapes_what_it_shows(): void
    {
        $page = FirstRunSetup::problemsPage(['<b>Bad</b> & worse']);

        $this->assertStringContainsString('<li>&lt;b&gt;Bad&lt;/b&gt; &amp; worse</li>', $page);
        $this->assertStringContainsString('<title>RamaSite can\'t start yet</title>', $page);
    }

    public function test_the_cms_public_folder_is_taken_out_of_the_entry_point_so_laravel_finds_the_site_folder(): void
    {
        $this->assertSame('/blog/index.php', FirstRunSetup::withSiteEntryPoint(['SCRIPT_NAME' => '/blog/public/index.php'])['SCRIPT_NAME']);
        $this->assertSame('/index.php', FirstRunSetup::withSiteEntryPoint(['SCRIPT_NAME' => '/public/index.php'])['SCRIPT_NAME']);
        $this->assertSame('/index.php', FirstRunSetup::withSiteEntryPoint(['SCRIPT_NAME' => '/index.php'])['SCRIPT_NAME']);
        $this->assertSame('/blog/index.php', FirstRunSetup::withSiteEntryPoint(['PHP_SELF' => '/blog/public/index.php'])['PHP_SELF']);
    }
}
