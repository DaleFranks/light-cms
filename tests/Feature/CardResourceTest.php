<?php

namespace Tests\Feature;

use App\BoxStyle;
use App\Filament\Resources\Cards\Pages\CreateCard;
use App\Filament\Resources\Cards\Pages\EditCard;
use App\Filament\Resources\Cards\Pages\ListCards;
use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CardResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/admin/cards')->assertRedirect('/admin/login');
    }

    public function test_the_list_shows_cards(): void
    {
        $this->actingAs(User::factory()->create());
        $cards = Card::factory()->count(2)->create();

        Livewire::test(ListCards::class)->assertCanSeeTableRecords($cards);
    }

    public function test_creating_a_card_fills_the_slug_from_the_name(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateCard::class)
            ->fillForm(['name' => 'Web Development', 'body' => 'Fast sites.', 'style' => BoxStyle::Light, 'featured' => true])
            ->assertSchemaStateSet(['slug' => 'web-development'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('cards', ['slug' => 'web-development', 'style' => 'light', 'featured' => true]);
    }

    public function test_slugs_must_be_unique_and_well_formed(): void
    {
        $this->actingAs(User::factory()->create());
        Card::factory()->create(['slug' => 'seo']);

        Livewire::test(CreateCard::class)
            ->fillForm(['name' => 'SEO', 'slug' => 'seo'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);

        Livewire::test(CreateCard::class)
            ->fillForm(['name' => 'SEO', 'slug' => 'Not OK'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'regex']);
    }

    public function test_a_card_can_be_edited(): void
    {
        $this->actingAs(User::factory()->create());
        $card = Card::factory()->create();

        Livewire::test(EditCard::class, ['record' => $card->getRouteKey()])
            ->fillForm(['body' => 'New text.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New text.', $card->refresh()->body);
    }
}
