<?php

namespace Tests\Feature;

use App\EntryStatus;
use App\EntryType;
use App\Filament\Resources\Entries\Pages\CreateEntry;
use App\Filament\Resources\Entries\Pages\EditEntry;
use App\Filament\Resources\Entries\Pages\ListEntries;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EntryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/admin/entries')->assertRedirect('/admin/login');
    }

    public function test_the_list_shows_entries(): void
    {
        $this->actingAs(User::factory()->create());
        $entries = Entry::factory()->count(2)->create();

        Livewire::test(ListEntries::class)->assertCanSeeTableRecords($entries);
    }

    public function test_creating_an_entry_saves_it_with_the_logged_in_user_as_author(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CreateEntry::class)
            ->fillForm([
                'title' => 'About Light CMS',
                'slug' => 'about',
                'body' => "## Hello\n\nSome **Markdown**.",
                'type' => EntryType::Page->value,
                'status' => EntryStatus::Published->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $entry = Entry::sole();
        $this->assertSame('about', $entry->slug);
        $this->assertSame("## Hello\n\nSome **Markdown**.", $entry->body);
        $this->assertSame(EntryStatus::Published, $entry->status);
        $this->assertTrue($entry->author->is($user));
    }

    public function test_the_slug_is_filled_in_from_the_title_when_creating(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateEntry::class)
            ->fillForm(['title' => 'Why Flat-File CMS Is the Future'])
            ->assertSchemaStateSet(['slug' => 'why-flat-file-cms-is-the-future']);
    }

    public function test_a_slug_can_contain_slashes(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateEntry::class)
            ->fillForm([
                'title' => 'Post',
                'slug' => 'articles/flat-file-future',
                'type' => EntryType::Post->value,
                'status' => EntryStatus::Draft->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('entries', ['slug' => 'articles/flat-file-future']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidSlugs(): array
    {
        return [
            'uppercase' => ['About'],
            'space' => ['about us'],
            'leading slash' => ['/about'],
            'trailing slash' => ['about/'],
            'double hyphen' => ['about--us'],
        ];
    }

    #[DataProvider('invalidSlugs')]
    public function test_an_invalid_slug_is_rejected(string $slug): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateEntry::class)
            ->fillForm([
                'title' => 'About',
                'slug' => $slug,
                'type' => EntryType::Page->value,
                'status' => EntryStatus::Draft->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'regex']);

        $this->assertDatabaseCount('entries', 0);
    }

    public function test_a_slug_already_used_by_another_entry_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        Entry::factory()->create(['slug' => 'about']);

        Livewire::test(CreateEntry::class)
            ->fillForm([
                'title' => 'About',
                'slug' => 'about',
                'type' => EntryType::Page->value,
                'status' => EntryStatus::Draft->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_an_entry_can_be_saved_without_changing_its_own_slug(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = Entry::factory()->create(['slug' => 'about']);

        Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
            ->fillForm(['title' => 'About Us'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('About Us', $entry->refresh()->title);
        $this->assertSame('about', $entry->slug);
    }
}
