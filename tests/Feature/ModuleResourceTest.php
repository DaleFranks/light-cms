<?php

namespace Tests\Feature;

use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\Pages\ListModules;
use App\Models\Module;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModuleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/admin/modules')->assertRedirect('/admin/login');
    }

    public function test_the_list_always_includes_the_footer(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListModules::class)
            ->assertCanSeeTableRecords([Module::footer()]);
    }

    public function test_creating_a_module_fills_the_slug_from_the_name(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateModule::class)
            ->fillForm(['name' => 'Call to Action', 'body' => 'Get in touch.'])
            ->assertSchemaStateSet(['slug' => 'call-to-action'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('modules', ['slug' => 'call-to-action', 'body' => 'Get in touch.']);
    }

    public function test_slugs_must_be_unique(): void
    {
        $this->actingAs(User::factory()->create());
        Module::factory()->create(['slug' => 'newsletter']);

        Livewire::test(CreateModule::class)
            ->fillForm(['name' => 'Newsletter', 'slug' => 'newsletter'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_the_footers_text_can_be_edited_but_not_its_slug_and_it_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $footer = Module::footer();

        Livewire::test(EditModule::class, ['record' => $footer->getRouteKey()])
            ->assertFormFieldDisabled('slug')
            ->assertActionHidden(DeleteAction::class)
            ->fillForm(['body' => '[navigation:multi]'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('[navigation:multi]', $footer->refresh()->body);
        $this->assertSame(Module::FOOTER_SLUG, $footer->slug);
    }
}
