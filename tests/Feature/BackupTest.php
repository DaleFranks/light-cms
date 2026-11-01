<?php

namespace Tests\Feature;

use App\Backup;
use App\DatabaseUpdate;
use App\Filament\Pages\Backup as BackupPage;
use App\Models\Entry;
use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PDO;
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
        $this->actingAs(User::factory()->create());

        $this->get('/admin/backup')
            ->assertOk()
            ->assertSee('Download backup');

        Livewire::test(BackupPage::class)
            ->callAction('download')
            ->assertFileDownloaded();

        $this->assertSame([], glob(storage_path('app/backups/*.sqlite')));
    }

    public function test_visitors_cant_reach_the_backup_screen(): void
    {
        $this->get('/admin/backup')->assertRedirect('/admin/login');
    }
}
