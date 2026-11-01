<?php

namespace Tests\Feature;

use App\EntryStatus;
use App\EntryType;
use App\Filament\Resources\Entries\EntryResource;
use App\Filament\Resources\Entries\Pages\CreateEntry;
use App\Filament\Resources\Forms\FormResource;
use App\Filament\Resources\Images\ImageResource;
use App\Filament\Resources\Sliders\SliderResource;
use App\Filament\Widgets\QuickActions;
use App\Filament\Widgets\RecentlyEdited;
use App\Filament\Widgets\RecentSubmissions;
use App\Filament\Widgets\SiteOverview;
use App\Models\Card;
use App\Models\Entry;
use App\Models\Form;
use App\Models\Image;
use App\Models\Module;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_the_dashboard_shows_the_sites_widgets_and_not_filaments(): void
    {
        $this->get('/admin')
            ->assertOk()
            ->assertSeeLivewire(QuickActions::class)
            ->assertSeeLivewire(SiteOverview::class)
            ->assertSeeLivewire(RecentSubmissions::class)
            ->assertSeeLivewire(RecentlyEdited::class)
            ->assertDontSee('filamentphp.com');
    }

    public function test_quick_actions_link_to_new_pages_and_posts_images_and_the_site(): void
    {
        Livewire::test(QuickActions::class)
            ->assertSee('New page')
            ->assertSee(url('/admin/entries/create?type=post'), false)
            ->assertSee('Upload images')
            ->assertSee('target="_blank"', false);
    }

    public function test_a_new_post_link_starts_the_form_as_a_post(): void
    {
        $this->get('/admin/entries/create?type=post')->assertOk();

        Livewire::withQueryParams(['type' => 'post'])
            ->test(CreateEntry::class)
            ->assertSchemaStateSet(['type' => EntryType::Post]);
    }

    public function test_without_a_type_the_form_starts_as_a_page(): void
    {
        Livewire::test(CreateEntry::class)->assertSchemaStateSet(['type' => EntryType::Page]);
    }

    public function test_the_overview_counts_each_kind_of_content_with_its_details(): void
    {
        $blog = Entry::factory()->published()->create(['type' => EntryType::Blog]);
        Entry::factory()->published()->count(2)->create(['type' => EntryType::Page]);
        Entry::factory()->create(['type' => EntryType::Page, 'status' => EntryStatus::Draft]);
        Entry::factory()->published()->create(['type' => EntryType::Post, 'blog_id' => $blog->id]);
        Entry::factory()->published()->create(['type' => EntryType::Post, 'blog_id' => $blog->id, 'published_at' => now()->addWeek()]);
        Image::factory()->create(['size' => 1_000_000]);
        Image::factory()->create(['size' => 500_000]);
        Slider::factory()->withSlides(3)->create();
        Module::factory()->footer()->create();
        Card::factory()->count(2)->create();
        $form = Form::factory()->create();
        $form->submissions()->create(['answers' => []]);
        $this->travel(-10)->days();
        $form->submissions()->create(['answers' => []]);
        $this->travelBack();

        Livewire::test(SiteOverview::class)
            ->assertSeeInOrder([
                'Pages', '3', '2 published · 1 draft',
                'Blogs', '1', '1 published',
                'Posts', '2', '1 published · 1 scheduled',
                'Images', '5', Number::fileSize((int) Image::query()->sum('size')).' in total',
                'Sliders', '1', '3 slides in all',
                'Modules', '1', 'Including the footer',
                'Cards', '2',
                'Forms', '1', '1 submission this week',
            ]);
    }

    public function test_each_count_opens_its_own_list(): void
    {
        Livewire::test(SiteOverview::class)
            ->assertSee(EntryResource::getUrl('index', ['filters' => ['type' => ['value' => 'post']]]), false)
            ->assertSee(ImageResource::getUrl(), false)
            ->assertSee(SliderResource::getUrl(), false)
            ->assertSee(FormResource::getUrl(), false);
    }

    public function test_recent_submissions_show_the_latest_five_with_a_reminder_of_what_was_sent(): void
    {
        $form = Form::factory()->create(['name' => 'Contact']);
        foreach (range(1, 6) as $number) {
            $this->travel($number)->minutes();
            $form->submissions()->create(['answers' => ['name' => ['label' => 'Name', 'value' => "Visitor {$number}"]]]);
        }

        Livewire::test(RecentSubmissions::class)
            ->assertCanSeeTableRecords($form->submissions()->take(5)->get())
            ->assertSee('Visitor 6')
            ->assertDontSee('Visitor 1')
            ->assertSee(FormResource::getUrl('edit', ['record' => $form]), false);
    }

    public function test_recently_edited_shows_the_latest_five_entries(): void
    {
        $entries = collect(range(1, 6))->map(function (int $number): Entry {
            $this->travel($number)->minutes();

            return Entry::factory()->create(['title' => "Entry number {$number}"]);
        });

        Livewire::test(RecentlyEdited::class)
            ->assertCanSeeTableRecords($entries->slice(1))
            ->assertCanNotSeeTableRecords([$entries->first()]);
    }
}
