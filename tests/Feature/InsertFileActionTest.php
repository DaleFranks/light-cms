<?php

namespace Tests\Feature;

use App\Filament\Resources\Cards\Pages\CreateCard;
use App\Filament\Resources\Entries\Pages\CreateEntry;
use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Models\LibraryFile;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InsertFileActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(LibraryFile::DISK, ['url' => '/uploads']);
        $this->actingAs(User::factory()->create());
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function pagesWithAnEditor(): array
    {
        return [
            'pages and posts' => [CreateEntry::class],
            'cards' => [CreateCard::class],
            'modules' => [CreateModule::class],
        ];
    }

    #[DataProvider('pagesWithAnEditor')]
    public function test_every_content_editor_offers_it_beside_insert_image(string $page): void
    {
        Livewire::test($page)
            ->assertActionExists(self::insertFile())
            ->assertActionExists(TestAction::make('insertImage')->schemaComponent('body'));
    }

    public function test_choosing_a_library_file_inserts_a_link_with_its_details(): void
    {
        $file = LibraryFile::factory()->create(['filename' => 'menu.pdf']);

        Livewire::test(CreateEntry::class)
            ->callAction(self::insertFile(), data: ['source' => 'library', 'file' => $file->id, 'text' => 'Our menu'])
            ->assertHasNoFormErrors()
            ->assertDispatched('insert-into-editor', text: '[file:menu.pdf|Our menu|details]');
    }

    public function test_the_details_can_be_left_off_and_the_text_left_empty(): void
    {
        $file = LibraryFile::factory()->create(['filename' => 'menu.pdf']);

        Livewire::test(CreateEntry::class)
            ->callAction(self::insertFile(), data: ['source' => 'library', 'file' => $file->id, 'text' => '', 'details' => false])
            ->assertHasNoFormErrors()
            ->assertDispatched('insert-into-editor', text: '[file:menu.pdf]');
    }

    public function test_characters_that_would_end_the_shortcode_are_taken_out_of_the_text(): void
    {
        $file = LibraryFile::factory()->create(['filename' => 'menu.pdf']);

        Livewire::test(CreateEntry::class)
            ->callAction(self::insertFile(), data: ['source' => 'library', 'file' => $file->id, 'text' => 'Food | drink]', 'details' => false])
            ->assertDispatched('insert-into-editor', text: '[file:menu.pdf|Food   drink]');
    }

    public function test_a_library_file_must_be_chosen(): void
    {
        Livewire::test(CreateEntry::class)
            ->callAction(self::insertFile(), data: ['source' => 'library'])
            ->assertHasFormErrors(['file' => 'required'])
            ->assertNotDispatched('insert-into-editor');
    }

    public function test_uploading_adds_the_file_to_the_library(): void
    {
        Livewire::test(CreateEntry::class)
            ->callAction(self::insertFile(), data: ['source' => 'upload', 'upload' => FileLibraryTest::pdf('Spring Menu.pdf'), 'text' => 'Spring menu'])
            ->assertHasNoFormErrors()
            ->assertNotified('Added to the file library as spring-menu.pdf')
            ->assertDispatched('insert-into-editor', text: '[file:spring-menu.pdf|Spring menu|details]');

        Storage::disk(LibraryFile::DISK)->assertExists('files/spring-menu.pdf');
    }

    public function test_an_upload_of_a_type_that_isnt_allowed_is_refused(): void
    {
        Livewire::test(CreateEntry::class)
            ->callAction(self::insertFile(), data: ['source' => 'upload', 'upload' => UploadedFile::fake()->createWithContent('logo.svg', '<svg/>')])
            ->assertHasFormErrors(['upload'])
            ->assertNotDispatched('insert-into-editor');

        $this->assertSame(0, LibraryFile::query()->count());
    }

    private static function insertFile(): TestAction
    {
        return TestAction::make('insertFile')->schemaComponent('body');
    }
}
