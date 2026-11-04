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
}
