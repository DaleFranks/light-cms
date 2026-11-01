<?php

namespace Tests\Feature;

use App\Markdown;
use App\Models\Entry;
use App\Models\Image;
use App\Models\Module;
use App\Models\Slider;
use App\SliderAnimation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SliderTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_slider_is_shown_where_its_shortcode_is_written(): void
    {
        $slider = Slider::factory()->withSlides(2)->create(['name' => 'Highlights', 'slug' => 'highlights', 'seconds_per_slide' => 5]);
        [$first, $second] = $slider->slides->load('image')->all();
        Entry::factory()->published()->create(['slug' => 'home', 'body' => "Intro.\n\n[slider:highlights]\n\nOutro."]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder([
                '<p>Intro.</p>',
                '<div',
                'class="slider animation-slide"',
                'data-seconds="5"',
                'aria-label="Highlights"',
                'src="'.$first->image->url().'"',
                'alt="'.e($first->image->alt_text).'"',
                '<figcaption class="slide-caption">Caption 1</figcaption>',
                'src="'.$second->image->url().'"',
                'loading="lazy"',
                'class="slider-prev"',
                'js/slider.js',
                '<p>Outro.</p>',
            ], false);
    }

    public function test_slides_are_shown_in_their_saved_order(): void
    {
        $slider = Slider::factory()->create(['slug' => 'ordered']);
        $slider->slides()->create(['image_id' => Image::factory()->create(['filename' => 'second.jpg'])->id, 'sort' => 2]);
        $slider->slides()->create(['image_id' => Image::factory()->create(['filename' => 'first.jpg'])->id, 'sort' => 1]);

        $this->assertLessThan(strpos($slider->html(), 'second.jpg'), strpos($slider->html(), 'first.jpg'));
    }

    public function test_the_fade_animation_and_no_timer_are_marked_on_the_slider(): void
    {
        $slider = Slider::factory()->withSlides(2)->create(['animation' => SliderAnimation::Fade]);

        $html = $slider->html();

        $this->assertStringContainsString('class="slider animation-fade"', $html);
        $this->assertStringNotContainsString('data-seconds', $html);
    }

    public function test_a_single_slide_has_no_buttons_or_script(): void
    {
        $html = Slider::factory()->withSlides(1)->create()->html();

        $this->assertStringNotContainsString('slider-prev', $html);
        $this->assertStringNotContainsString('slider.js', $html);
    }

    public function test_shortcodes_for_missing_or_empty_sliders_are_left_as_written(): void
    {
        Slider::factory()->create(['slug' => 'empty']);

        $html = Markdown::toHtml("[slider:empty]\n\n[slider:nope]", withBlocks: true);

        $this->assertStringContainsString('<p>[slider:empty]</p>', $html);
        $this->assertStringContainsString('<p>[slider:nope]</p>', $html);
    }

    public function test_sliders_cannot_be_placed_inside_modules(): void
    {
        Slider::factory()->withSlides(2)->create(['slug' => 'inner']);
        $module = Module::factory()->create(['body' => '[slider:inner]']);

        $this->assertStringContainsString('[slider:inner]', $module->bodyHtml());
    }

    public function test_an_image_in_a_slider_counts_as_in_use(): void
    {
        $slider = Slider::factory()->withSlides(1)->create(['name' => 'Highlights']);
        $image = $slider->slides()->first()->image;

        $this->assertSame(['Slider: Highlights'], $image->usages()->all());

        $image->delete();

        $this->assertModelExists($image);
    }

    public function test_deleting_a_slider_removes_its_slides_but_keeps_the_images(): void
    {
        $slider = Slider::factory()->withSlides(2)->create();
        $images = $slider->slides->pluck('image_id');

        $slider->delete();

        $this->assertDatabaseCount('slides', 0);
        $this->assertSame(2, Image::query()->whereKey($images)->count());
    }
}
