<?php

namespace Tests\Feature;

use App\Filament\Resources\Images\Pages\ManageImages;
use App\Models\Entry;
use App\Models\Image;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class ImageResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Image::DISK, ['url' => '/uploads']);
        $this->actingAs(User::factory()->create());
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        auth()->logout();

        $this->get('/admin/images')->assertRedirect('/admin/login');
    }

    public function test_the_library_lists_images(): void
    {
        $images = Image::factory()->count(3)->create();

        Livewire::test(ManageImages::class)->assertCanSeeTableRecords($images);
    }

    public function test_previews_stay_inside_their_cards(): void
    {
        Image::factory()->create(['filename' => 'very-wide.jpg', 'width' => 3000, 'height' => 500]);

        Livewire::test(ManageImages::class)
            ->loadTable()
            ->assertSeeHtml('style="max-width: 100%; object-fit: contain; height: 160px;"');
    }

    public function test_several_images_can_be_uploaded_at_once(): void
    {
        Image::factory()->create(['filename' => 'team.jpg']);

        Livewire::test(ManageImages::class)
            ->callAction('upload', data: [
                'files' => [
                    UploadedFile::fake()->image('Team.jpg', 100, 100),
                    UploadedFile::fake()->image('office.png', 100, 100),
                ],
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertDatabaseHas('images', ['filename' => 'team-2.jpg']);
        $this->assertDatabaseHas('images', ['filename' => 'office.png']);
        Storage::disk(Image::DISK)->assertExists(['images/team-2.jpg', 'images/office.png']);
    }

    public function test_the_description_can_be_edited(): void
    {
        $image = Image::factory()->create();

        Livewire::test(ManageImages::class)
            ->callAction(TestAction::make(EditAction::class)->table($image), data: ['alt_text' => 'Our office at night'])
            ->assertHasNoActionErrors();

        $this->assertSame('Our office at night', $image->refresh()->alt_text);
    }

    public function test_an_unused_image_can_be_deleted(): void
    {
        $image = Image::factory()->create();

        Livewire::test(ManageImages::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($image));

        $this->assertModelMissing($image);
    }

    public function test_deleting_an_image_in_use_is_refused_with_the_pages_that_use_it(): void
    {
        $image = Image::factory()->create(['filename' => 'team.jpg']);
        Entry::factory()->create(['title' => 'About us', 'body' => '[image:team.jpg|right]']);

        Livewire::test(ManageImages::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($image))
            ->assertNotified('team.jpg is in use and can\'t be deleted');

        $this->assertModelExists($image);
    }

    private function libraryImage(string $filename = 'team.jpg', int $width = 800, int $height = 600): Image
    {
        $file = UploadedFile::fake()->image($filename, $width, $height);

        Storage::disk(Image::DISK)->putFileAs(Image::DIRECTORY, $file, $filename);

        return Image::factory()->create([
            'filename' => $filename,
            'width' => $width,
            'height' => $height,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'alt_text' => 'Our team',
        ]);
    }

    public function test_replacing_an_image_keeps_its_name_and_description_and_updates_its_details(): void
    {
        $image = $this->libraryImage();
        $this->travelTo('2026-11-03 12:00:00');

        $image->replaceWith(UploadedFile::fake()->image('anything.jpg', 400, 200));

        $image->refresh();
        $this->assertSame('team.jpg', $image->filename);
        $this->assertSame('Our team', $image->alt_text);
        $this->assertSame(400, $image->width);
        $this->assertSame(200, $image->height);
        $this->assertSame((int) filesize(Storage::disk(Image::DISK)->path('images/team.jpg')), $image->size);
        $this->assertSame([400, 200], array_slice(getimagesize(Storage::disk(Image::DISK)->path('images/team.jpg')), 0, 2));
        $this->assertStringEndsWith('images/team.jpg?v='.now()->getTimestamp(), $image->url());
        $this->assertSame(['team.jpg'], array_map('basename', Storage::disk(Image::DISK)->allFiles(Image::DIRECTORY)));
    }

    public function test_an_image_that_was_never_replaced_keeps_its_plain_address(): void
    {
        $this->assertStringEndsWith('images/team.jpg', $this->libraryImage()->url());
    }

    public function test_a_different_type_of_file_is_refused_and_the_image_is_untouched(): void
    {
        $image = $this->libraryImage();
        $before = Storage::disk(Image::DISK)->get('images/team.jpg');

        try {
            $image->replaceWith(UploadedFile::fake()->image('new.png', 400, 200));
            $this->fail('A PNG should not replace a JPG.');
        } catch (RuntimeException $exception) {
            $this->assertSame('This image is a JPG, so the new file must be too. To use a PNG, upload it as a new image.', $exception->getMessage());
        }

        $this->assertSame($before, Storage::disk(Image::DISK)->get('images/team.jpg'));
        $this->assertSame(800, $image->fresh()->width);
        $this->assertNull($image->fresh()->replaced_at);
    }

    public function test_a_jpeg_named_image_can_be_replaced_by_a_jpg(): void
    {
        $image = $this->libraryImage('old.jpeg');

        $image->replaceWith(UploadedFile::fake()->image('new.jpg', 300, 300));

        $this->assertSame(300, $image->fresh()->width);
    }

    public function test_a_file_that_isnt_an_image_is_refused(): void
    {
        $image = $this->libraryImage();

        $this->expectException(RuntimeException::class);

        $image->replaceWith(UploadedFile::fake()->createWithContent('new.jpg', 'not an image'));
    }

    public function test_an_image_can_be_replaced_from_the_screen_and_pages_show_the_new_size(): void
    {
        $image = $this->libraryImage();
        Entry::factory()->published()->create(['slug' => 'about', 'body' => '[image:team.jpg]']);

        Livewire::test(ManageImages::class)
            ->callAction(TestAction::make('replace')->table($image), data: ['file' => UploadedFile::fake()->image('new.jpg', 400, 200)])
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(400, $image->fresh()->width);
        $this->get('/about')
            ->assertSee('width="400"', false)
            ->assertSee('team.jpg?v=', false);
    }

    public function test_refresh_updates_only_images_whose_files_changed_and_lists_missing_ones(): void
    {
        $changed = $this->libraryImage('changed.jpg');
        $same = $this->libraryImage('same.jpg');
        $missing = Image::factory()->create(['filename' => 'gone.jpg']);
        Storage::disk(Image::DISK)->putFileAs(Image::DIRECTORY, UploadedFile::fake()->image('x.jpg', 123, 45), 'changed.jpg');

        Livewire::test(ManageImages::class)
            ->callAction('refresh')
            ->assertNotified();

        $this->assertSame(123, $changed->fresh()->width);
        $this->assertNotNull($changed->fresh()->replaced_at);
        $this->assertNull($same->fresh()->replaced_at);
        $this->assertNull($missing->fresh()->replaced_at);
    }

    /**
     * A real image file over the 500 KB limit, with padding after the image data.
     */
    private function largeUpload(): UploadedFile
    {
        $file = UploadedFile::fake()->image('huge.jpg', 3000, 2000);
        file_put_contents($file->getPathname(), str_repeat("\0", 600 * 1024), FILE_APPEND);

        return $file;
    }

    public function test_an_image_is_large_above_500_kb_whatever_its_dimensions(): void
    {
        $this->assertFalse(Image::factory()->make(['width' => 5000, 'height' => 4000, 'size' => 500 * 1024])->isLarge());
        $this->assertTrue(Image::factory()->make(['width' => 100, 'height' => 100, 'size' => 500 * 1024 + 1])->isLarge());
    }

    public function test_only_large_files_are_labelled_in_the_library(): void
    {
        Image::factory()->create(['filename' => 'small.jpg', 'size' => 1000]);

        Livewire::test(ManageImages::class)->loadTable()->assertDontSee('Large file');

        Image::factory()->create(['filename' => 'big.jpg', 'size' => 900 * 1024]);

        Livewire::test(ManageImages::class)->loadTable()->assertSee('Large file');
    }

    public function test_uploading_a_large_file_gives_a_notice_and_keeps_it_as_uploaded(): void
    {
        Livewire::test(ManageImages::class)
            ->callAction('upload', data: ['files' => [$this->largeUpload()]])
            ->assertNotified('This image may load slowly');

        $this->assertSame(3000, Image::query()->where('filename', 'huge.jpg')->value('width'));
    }

    public function test_uploading_an_ordinary_file_gives_no_notice_whatever_its_dimensions(): void
    {
        Livewire::test(ManageImages::class)
            ->callAction('upload', data: ['files' => [UploadedFile::fake()->image('wide.jpg', 3000, 2000)]])
            ->assertNotNotified('This image may load slowly');
    }

    public function test_replacing_with_a_large_image_gives_a_notice(): void
    {
        $image = $this->libraryImage();

        Livewire::test(ManageImages::class)
            ->callAction(TestAction::make('replace')->table($image), data: ['file' => $this->largeUpload()])
            ->assertNotified('This image may load slowly');
    }
}
