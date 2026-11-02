<?php

namespace Tests\Feature;

use App\DatabaseCopy;
use App\DatabaseUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseUpdateTest extends TestCase
{
    /** Not RefreshDatabase: its wrapping transaction would stop SQLite copying the database. */
    use DatabaseMigrations;

    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.installation_check' => true]);

        // A storage folder of its own, so the real site's markers and backups are never touched
        $this->storagePath = sys_get_temp_dir().'/light-cms-update-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->storagePath.'/app');
        File::ensureDirectoryExists($this->storagePath.'/framework/cache/data');
        $this->app->useStoragePath($this->storagePath);
    }

    protected function tearDown(): void
    {
        DatabaseCopy::$useVacuumInto = null;
        File::deleteDirectory($this->storagePath);

        parent::tearDown();
    }

    public function test_the_first_visit_after_an_upgrade_applies_the_new_database_changes(): void
    {
        // A site set up with the previous version: the newest change isn't applied yet
        $changeCount = DB::table('migrations')->count();
        Artisan::call('migrate:rollback', ['--step' => 1]);
        $this->assertSame($changeCount - 1, DB::table('migrations')->count());
        File::put($this->storagePath.'/app/database-version', "an-older-version\n");
        User::factory()->create();

        $this->get('/');

        $this->assertSame($changeCount, DB::table('migrations')->count());
        $this->assertSame(DatabaseUpdate::latestVersion(), DatabaseUpdate::appliedVersion());
        $this->assertCount(1, glob($this->storagePath.'/app/backups/before-update-*.sqlite'));
    }

    public function test_a_failed_update_keeps_the_site_running_and_is_retried_later(): void
    {
        Artisan::call('migrate:rollback', ['--step' => 1]);
        $changeCount = DB::table('migrations')->count();
        File::put($this->storagePath.'/app/database-version', "an-older-version\n");
        User::factory()->create();

        // The in-memory test database can't be copied the older way, so the safety copy fails
        DatabaseCopy::$useVacuumInto = false;

        $this->get('/no-such-page')->assertNotFound();
        $this->assertSame($changeCount, DB::table('migrations')->count());
        $failedAt = DatabaseUpdate::failure()['at'];
        $this->assertStringContainsString('isn\'t a file', DatabaseUpdate::failure()['message']);

        // Not tried again straight away
        $this->travel(5)->minutes();
        $this->get('/no-such-page')->assertNotFound();
        $this->assertTrue($failedAt->eq(DatabaseUpdate::failure()['at']));

        // The admin says so
        $this->actingAs(User::query()->first())->get('/admin')->assertOk()
            ->assertSee('The database couldn\'t be updated for this version of Light CMS.', false);

        // Tried again later, and this time it works
        DatabaseCopy::$useVacuumInto = null;
        $this->travel(6)->minutes();
        $this->get('/no-such-page');

        $this->assertNull(DatabaseUpdate::failure());
        $this->assertSame($changeCount + 1, DB::table('migrations')->count());
        $this->get('/admin')->assertDontSee('couldn\'t be updated', false);
    }

    public function test_an_up_to_date_site_does_nothing(): void
    {
        DatabaseUpdate::markApplied();
        User::factory()->create();

        $this->get('/');

        $this->assertDirectoryDoesNotExist($this->storagePath.'/app/backups');
    }

    public function test_a_site_from_before_this_feature_is_checked_once_and_then_marked_up_to_date(): void
    {
        User::factory()->create();
        $this->assertNull(DatabaseUpdate::appliedVersion());

        $this->get('/');

        $this->assertSame(DatabaseUpdate::latestVersion(), DatabaseUpdate::appliedVersion());
    }

    public function test_a_site_not_yet_set_up_is_left_to_the_setup_screen(): void
    {
        $this->get('/')->assertRedirect('/setup');

        $this->assertNull(DatabaseUpdate::appliedVersion());
    }

    public function test_only_the_newest_copies_from_before_updates_are_kept(): void
    {
        User::factory()->create();
        File::ensureDirectoryExists($this->storagePath.'/app/backups');

        foreach (['2026-01-01-000000', '2026-02-01-000000', '2026-03-01-000000'] as $time) {
            File::put($this->storagePath."/app/backups/before-update-{$time}.sqlite", '');
        }

        DatabaseUpdate::run();

        $kept = array_map('basename', glob($this->storagePath.'/app/backups/before-update-*.sqlite'));
        $this->assertCount(DatabaseUpdate::KEPT_COPIES, $kept);
        $this->assertNotContains('before-update-2026-01-01-000000.sqlite', $kept);
    }

    public function test_the_setup_screen_records_the_database_as_up_to_date(): void
    {
        $this->post('/setup', [
            'site_name' => 'Acme Studio',
            'name' => 'Ann Admin',
            'email' => 'ann@acme.test',
            'password' => 'correct-horse',
            'password_confirmation' => 'correct-horse',
        ])->assertRedirect('/admin');

        $this->assertSame(DatabaseUpdate::latestVersion(), DatabaseUpdate::appliedVersion());
    }
}
