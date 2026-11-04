<?php

namespace Tests\Feature;

use App\Backup;
use App\DatabaseUpdate;
use App\Filament\Pages\Backup as BackupPage;
use App\Models\Entry;
use App\Models\Image;
use App\Models\User;
use App\Version;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PDO;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;
use ZipArchive;

class BackupTest extends TestCase
{
    /** Not RefreshDatabase: its wrapping transaction would stop SQLite taking the snapshot. */
    use DatabaseMigrations;

    private ?string $zipPath = null;

    protected function tearDown(): void
    {
        if ($this->zipPath !== null) {
            File::delete($this->zipPath);
        }

        parent::tearDown();
    }

    public function test_the_backup_holds_the_database_uploads_settings_and_instructions(): void
    {
        Storage::fake(Image::DISK);
        Storage::disk(Image::DISK)->put('images/team.jpg', 'photo');
        Storage::disk(Image::DISK)->put('theme/logo.png', 'logo');
        Entry::factory()->published()->create(['slug' => 'about', 'title' => 'About us']);

        $this->zipPath = Backup::create();

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($this->zipPath));
        $this->assertSame('photo', $zip->getFromName('public/uploads/images/team.jpg'));
        $this->assertSame('logo', $zip->getFromName('public/uploads/theme/logo.png'));
        $this->assertStringContainsString('To restore the site', (string) $zip->getFromName('RESTORING.txt'));
        $this->assertStringContainsString('Made with '.Version::label().'.', (string) $zip->getFromName('RESTORING.txt'));
        $this->assertSame(DatabaseUpdate::appliedVersion()."\n", $zip->getFromName('storage/app/database-version'));
        $this->assertSame(File::exists(base_path('.env')), $zip->locateName('.env') !== false);

        $database = tempnam(sys_get_temp_dir(), 'backup-test');
        file_put_contents($database, $zip->getFromName('database/database.sqlite'));
        $zip->close();

        $titles = (new PDO('sqlite:'.$database))->query('select title from entries')->fetchAll(PDO::FETCH_COLUMN);
        unlink($database);

        $this->assertSame(['About us'], $titles);
    }

    public function test_the_backup_file_is_named_after_the_site_and_the_time(): void
    {
        config(['app.url' => 'https://www.example.com']);
        $this->travelTo('2026-11-01 15:30:00');

        $this->assertSame('backup-www.example.com-2026-11-01-1530.zip', Backup::filename());
    }

    public function test_an_admin_downloads_the_backup_from_the_backup_screen(): void
    {
        Storage::fake(Image::DISK);
        Storage::disk(Image::DISK)->put('images/team.jpg', 'photo');
        $this->freezeTime();
        $this->actingAs(User::factory()->create());

        $this->get('/admin/backup')
            ->assertOk()
            ->assertSee('Download backup');

        // The zip must never travel through the button's own response, which is held
        // in memory: that's what ran out of memory on sites with many images.
        $button = Livewire::test(BackupPage::class)
            ->callAction('download')
            ->assertNoFileDownloaded();

        $downloadAddress = $button->effects['redirect'];
        $this->zipPath = Backup::folder().'/'.basename($downloadAddress);
        $this->assertFileExists($this->zipPath);
        $this->assertSame([], glob(storage_path('app/backups/*.sqlite')));

        $download = $this->get($downloadAddress)->assertDownload(Backup::filename());

        $this->assertInstanceOf(BinaryFileResponse::class, $download->baseResponse);

        ob_start();
        $download->baseResponse->sendContent();
        $sentZip = (string) ob_get_clean();

        $this->assertStringStartsWith('PK', $sentZip);
        $this->assertFileDoesNotExist($this->zipPath);
    }

    public function test_visitors_cant_download_a_backup(): void
    {
        Storage::fake(Image::DISK);
        $this->zipPath = Backup::create();

        $this->get(route('filament.admin.backup.download', ['file' => basename($this->zipPath)]))
            ->assertRedirect('/admin/login');

        $this->assertFileExists($this->zipPath);
    }

    public function test_only_backup_zips_that_exist_can_be_downloaded(): void
    {
        $this->actingAs(User::factory()->create());

        File::ensureDirectoryExists(Backup::folder());
        $otherFile = Backup::folder().'/before-update-2026-11-01-000000.sqlite';
        File::put($otherFile, 'database');

        try {
            $this->get('/admin/backup/download/'.basename($otherFile))->assertNotFound();
            $this->get('/admin/backup/download/backup-'.str_repeat('a', 32).'.zip')->assertNotFound();
            $this->assertFileExists($otherFile);
        } finally {
            File::delete($otherFile);
        }
    }

    public function test_making_a_backup_clears_out_zips_that_were_never_downloaded(): void
    {
        Storage::fake(Image::DISK);
        File::ensureDirectoryExists(Backup::folder());

        $abandonedZip = Backup::folder().'/backup-example.com-2026-11-01-1530.zip';
        $recentZip = Backup::folder().'/backup-'.str_repeat('b', 32).'.zip';
        $databaseCopy = Backup::folder().'/before-update-2026-11-01-000000.sqlite';

        foreach ([$abandonedZip, $recentZip, $databaseCopy] as $path) {
            File::put($path, 'old');
        }

        touch($abandonedZip, time() - 7200);
        touch($databaseCopy, time() - 7200);

        try {
            $this->zipPath = Backup::create();

            $this->assertFileDoesNotExist($abandonedZip);
            $this->assertFileExists($recentZip);
            $this->assertFileExists($databaseCopy);
        } finally {
            File::delete([$abandonedZip, $recentZip, $databaseCopy]);
        }
    }

    public function test_visitors_cant_reach_the_backup_screen(): void
    {
        $this->get('/admin/backup')->assertRedirect('/admin/login');
    }
}
