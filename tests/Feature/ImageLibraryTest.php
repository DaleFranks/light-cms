<?php

namespace Tests\Feature;

use App\EntryType;
use App\Models\Card;
use App\Models\Entry;
use App\Models\Image;
use App\Models\Module;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Tests\TestCase;

class ImageLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Image::DISK);
    }

    public function test_an_upload_keeps_a_tidied_version_of_its_name(): void
    {
        $image = Image::storeUpload(UploadedFile::fake()->image('Team Photo.JPG', 800, 600));

        $this->assertSame('team-photo.jpg', $image->filename);
        $this->assertSame([800, 600], [$image->width, $image->height]);
        $this->assertSame('image/jpeg', $image->mime_type);
        $this->assertSame('/storage/images/team-photo.jpg', $image->url());
        Storage::disk(Image::DISK)->assertExists('images/team-photo.jpg');
    }

    public function test_an_upload_never_replaces_an_image_with_the_same_name(): void
    {
        $first = Image::storeUpload(UploadedFile::fake()->image('team.png', 10, 10));
        $second = Image::storeUpload(UploadedFile::fake()->image('team.png', 20, 20));
        $third = Image::storeUpload(UploadedFile::fake()->image('Team.png', 30, 30));

        $this->assertSame(['team.png', 'team-2.png', 'team-3.png'], [$first->filename, $second->filename, $third->filename]);
        $this->assertSame(10, getimagesize(Storage::disk(Image::DISK)->path('images/team.png'))[0]);
    }

    public function test_a_file_already_in_the_folder_is_not_replaced_either(): void
    {
        Storage::disk(Image::DISK)->put('images/logo.png', 'copied in by hand');

        $image = Image::storeUpload(UploadedFile::fake()->image('logo.png', 10, 10));

        $this->assertSame('logo-2.png', $image->filename);
        $this->assertSame('copied in by hand', Storage::disk(Image::DISK)->get('images/logo.png'));
    }

    public function test_images_are_stored_exactly_as_uploaded(): void
    {
        $upload = UploadedFile::fake()->image('wide.png', 2400, 600);
        $original = file_get_contents($upload->getRealPath());

        $image = Image::storeUpload($upload);

        $this->assertSame($original, Storage::disk(Image::DISK)->get('images/wide.png'));
        $this->assertSame([2400, 600], [$image->width, $image->height]);
    }

    public function test_usages_name_the_content_that_uses_the_image(): void
    {
        $image = Image::factory()->create(['filename' => 'team.jpg']);
        Entry::factory()->create(['title' => 'About us', 'body' => 'Meet us: [image:team.jpg|right]']);
        Entry::factory()->create(['type' => EntryType::Blog, 'title' => 'News', 'body' => '![](/storage/images/team.jpg)']);
        Module::factory()->create(['name' => 'Hero', 'body' => '![The team](team.jpg)']);
        Card::factory()->create(['name' => 'Staff', 'body' => '[image:TEAM.jpg]']);
        Entry::factory()->create(['title' => 'Unrelated', 'body' => '[image:my-team.jpg] [image:team.jpeg] [image:team.jpg.bak]']);

        $this->assertEqualsCanonicalizing(
            ['Page: About us', 'Blog: News', 'Module: Hero', 'Card: Staff'],
            $image->usages()->all(),
        );
    }

    public function test_an_image_in_use_cannot_be_deleted(): void
    {
        $image = Image::storeUpload(UploadedFile::fake()->image('team.jpg', 10, 10));
        Entry::factory()->create(['body' => '[image:team.jpg]']);

        $image->delete();

        $this->assertModelExists($image);
        Storage::disk(Image::DISK)->assertExists('images/team.jpg');
    }

    public function test_deleting_an_unused_image_removes_its_file(): void
    {
        $image = Image::storeUpload(UploadedFile::fake()->image('team.jpg', 10, 10));

        $image->delete();

        $this->assertModelMissing($image);
        Storage::disk(Image::DISK)->assertMissing('images/team.jpg');
    }

    public function test_images_uploaded_in_the_content_editor_go_into_the_library(): void
    {
        $editor = MarkdownEditor::make('body');
        $upload = self::temporaryUpload(UploadedFile::fake()->image('Editor Shot.png', 50, 50));

        $saved = $editor->saveUploadedFileAttachment($upload);

        $this->assertInstanceOf(Image::class, $saved);
        $this->assertSame('editor-shot.png', $saved->filename);
        $this->assertSame('/storage/images/editor-shot.png', $editor->getFileAttachmentUrl($saved));
    }

    /**
     * A file as Livewire holds it between being uploaded and the form being saved.
     */
    private static function temporaryUpload(UploadedFile $file): TemporaryUploadedFile
    {
        Storage::fake(FileUploadConfiguration::disk());

        $name = TemporaryUploadedFile::generateHashNameWithOriginalNameEmbedded($file);
        $file->storeAs(FileUploadConfiguration::directory(), $name, ['disk' => FileUploadConfiguration::disk()]);

        return TemporaryUploadedFile::createFromLivewire($name);
    }
}
