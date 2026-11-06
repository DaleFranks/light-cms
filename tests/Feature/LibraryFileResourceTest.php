<?php

namespace Tests\Feature;

use App\Filament\Resources\LibraryFiles\Pages\ManageLibraryFiles;
use App\Models\Entry;
use App\Models\LibraryFile;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LibraryFileResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(LibraryFile::DISK, ['url' => '/uploads']);
        $this->actingAs(User::factory()->create());
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        auth()->logout();

        $this->get('/admin/files')->assertRedirect('/admin/login');
    }

    public function test_the_screen_is_in_the_menu_and_lists_files_with_their_shortcode_and_details(): void
    {
        $files = LibraryFile::factory()->count(2)->create();
        LibraryFile::factory()->create(['filename' => 'menu.pdf', 'size' => 245_760]);

        $this->get('/admin')->assertSee('/admin/files', false);

        Livewire::test(ManageLibraryFiles::class)
            ->assertCanSeeTableRecords($files)
            ->assertSee('[file:menu.pdf]')
            ->assertSee('PDF, 240 KB');
    }

    public function test_several_files_can_be_uploaded_at_once(): void
    {
        LibraryFile::factory()->create(['filename' => 'menu.pdf']);

        Livewire::test(ManageLibraryFiles::class)
            ->callAction('upload', data: [
                'files' => [
                    FileLibraryTest::pdf('Menu.pdf'),
                    UploadedFile::fake()->createWithContent('Price List.csv', "item,price\n"),
                ],
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('2 files uploaded');

        $this->assertDatabaseHas('library_files', ['filename' => 'menu-2.pdf']);
        $this->assertDatabaseHas('library_files', ['filename' => 'price-list.csv']);
        Storage::disk(LibraryFile::DISK)->assertExists(['files/menu-2.pdf', 'files/price-list.csv']);
    }

    public function test_a_type_that_isnt_allowed_is_refused_with_what_is(): void
    {
        Livewire::test(ManageLibraryFiles::class)
            ->callAction('upload', data: ['files' => [UploadedFile::fake()->createWithContent('page.html', '<html></html>')]])
            ->assertHasActionErrors(['files']);

        $this->assertSame(0, LibraryFile::query()->count());
        $this->assertSame([], Storage::disk(LibraryFile::DISK)->allFiles());
    }

    public function test_a_file_can_be_replaced_from_the_screen(): void
    {
        $file = LibraryFile::storeUpload(FileLibraryTest::pdf('menu.pdf'));

        Livewire::test(ManageLibraryFiles::class)
            ->callAction(TestAction::make('replace')->table($file), data: ['file' => FileLibraryTest::pdf('new.pdf', "%PDF-1.4\n".str_repeat('x', 3000))])
            ->assertHasNoActionErrors()
            ->assertNotified('menu.pdf replaced');

        $this->assertGreaterThan(3000, $file->refresh()->size);
        $this->assertNotNull($file->replaced_at);
    }

    public function test_replacing_with_another_type_says_why_and_changes_nothing(): void
    {
        $file = LibraryFile::storeUpload(FileLibraryTest::pdf('menu.pdf'));

        Livewire::test(ManageLibraryFiles::class)
            ->callAction(TestAction::make('replace')->table($file), data: ['file' => UploadedFile::fake()->createWithContent('menu.csv', "a,b\n")])
            ->assertNotified('The file wasn\'t replaced');

        $this->assertNull($file->refresh()->replaced_at);
    }

    public function test_an_unused_file_can_be_deleted(): void
    {
        $file = LibraryFile::storeUpload(FileLibraryTest::pdf('menu.pdf'));

        Livewire::test(ManageLibraryFiles::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($file));

        $this->assertModelMissing($file);
        Storage::disk(LibraryFile::DISK)->assertMissing('files/menu.pdf');
    }

    public function test_deleting_a_file_in_use_is_refused_with_the_pages_that_link_to_it(): void
    {
        $file = LibraryFile::factory()->create(['filename' => 'menu.pdf']);
        Entry::factory()->create(['title' => 'Food', 'body' => '[file:menu.pdf|Our menu]']);

        Livewire::test(ManageLibraryFiles::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($file))
            ->assertNotified('menu.pdf is in use and can\'t be deleted');

        $this->assertModelExists($file);
    }
}
