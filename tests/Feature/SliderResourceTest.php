<?php

namespace Tests\Feature;

use App\Filament\Resources\Sliders\Pages\CreateSlider;
use App\Filament\Resources\Sliders\Pages\EditSlider;
use App\Filament\Resources\Sliders\Pages\ListSliders;
use App\Models\Image;
use App\Models\Slider;
use App\Models\User;
use App\SliderAnimation;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SliderResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        auth()->logout();

        $this->get('/admin/sliders')->assertRedirect('/admin/login');
    }

    public function test_the_list_shows_sliders(): void
    {
        $sliders = Slider::factory()->count(2)->withSlides(1)->create();

        Livewire::test(ListSliders::class)->assertCanSeeTableRecords($sliders);
    }

    public function test_a_slider_is_created_with_slides_picked_from_the_library(): void
    {
        $undoRepeaterFake = Repeater::fake();
        $first = Image::factory()->create();
        $second = Image::factory()->create();

        Livewire::test(CreateSlider::class)
            ->fillForm([
                'name' => 'Home Page Highlights',
                'animation' => SliderAnimation::Fade,
                'seconds_per_slide' => 6,
                'slides' => [
                    ['image_id' => $first->id, 'caption' => 'Our team'],
                    ['image_id' => $second->id, 'caption' => null],
                ],
            ])
            ->assertSchemaStateSet(['slug' => 'home-page-highlights'])
            ->call('create')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        $slider = Slider::query()->where('slug', 'home-page-highlights')->sole();
        $this->assertSame(SliderAnimation::Fade, $slider->animation);
        $this->assertSame(6, $slider->seconds_per_slide);
        $this->assertSame([$first->id, $second->id], $slider->slides->pluck('image_id')->all());
        $this->assertSame('Our team', $slider->slides->first()->caption);
    }

    public function test_a_slider_needs_at_least_one_slide(): void
    {
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreateSlider::class)
            ->fillForm(['name' => 'Empty', 'slides' => []])
            ->call('create')
            ->assertHasFormErrors(['slides']);

        $undoRepeaterFake();
    }

    public function test_slides_can_be_reordered(): void
    {
        $undoRepeaterFake = Repeater::fake();
        $slider = Slider::factory()->withSlides(2)->create();
        [$first, $second] = $slider->slides->pluck('image_id')->all();

        Livewire::test(EditSlider::class, ['record' => $slider->getRouteKey()])
            ->fillForm(['slides' => [
                ['image_id' => $second, 'caption' => null],
                ['image_id' => $first, 'caption' => null],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        $this->assertSame([$second, $first], $slider->refresh()->slides->pluck('image_id')->all());
    }
}
