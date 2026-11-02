<?php

namespace Tests\Feature;

use App\DatabaseCopy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;
use Tests\TestCase;

class DatabaseCopyTest extends TestCase
{
    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        // A database file of its own, like a real site's, rather than the tests' in-memory one
        $this->folder = sys_get_temp_dir().'/light-cms-copy-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->folder);
        touch($this->folder.'/site.sqlite');
        config(['database.connections.sqlite.database' => $this->folder.'/site.sqlite']);
        DB::purge('sqlite');

        DB::statement('create table notes (body text)');
        DB::table('notes')->insert(['body' => 'kept']);
    }

    protected function tearDown(): void
    {
        DatabaseCopy::$useVacuumInto = null;
        DB::purge('sqlite');
        File::deleteDirectory($this->folder);

        parent::tearDown();
    }

    private function notesIn(string $path): array
    {
        return (new PDO('sqlite:'.$path))->query('select body from notes')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function test_an_older_sqlite_copies_the_file_while_holding_back_changes(): void
    {
        DatabaseCopy::$useVacuumInto = false;

        DatabaseCopy::to($this->folder.'/copy.sqlite');

        $this->assertSame(['kept'], $this->notesIn($this->folder.'/copy.sqlite'));

        // Changes go through again afterwards
        DB::table('notes')->insert(['body' => 'later']);
        $this->assertSame(2, DB::table('notes')->count());
    }

    public function test_a_newer_sqlite_copies_with_vacuum_into(): void
    {
        DatabaseCopy::$useVacuumInto = true;

        DatabaseCopy::to($this->folder.'/copy.sqlite');

        $this->assertSame(['kept'], $this->notesIn($this->folder.'/copy.sqlite'));
    }

    public function test_the_method_is_chosen_by_the_sqlite_version(): void
    {
        DatabaseCopy::to($this->folder.'/copy.sqlite');

        $this->assertSame(['kept'], $this->notesIn($this->folder.'/copy.sqlite'));
        $this->assertMatchesRegularExpression('/^3\.\d+\.\d+$/', DatabaseCopy::sqliteVersion());
    }

    public function test_a_database_that_is_not_a_file_cant_be_copied_the_older_way(): void
    {
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DatabaseCopy::$useVacuumInto = false;

        $this->expectException(RuntimeException::class);

        DatabaseCopy::to($this->folder.'/copy.sqlite');
    }
}
