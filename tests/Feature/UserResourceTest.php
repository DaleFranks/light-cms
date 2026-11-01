<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\Pages\EditProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_add_another_admin_who_can_then_log_in(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: [
                'name' => 'Chris',
                'email' => 'chris@example.com',
                'password' => 'secret-pass',
                'password_confirmation' => 'secret-pass',
            ])
            ->assertHasNoActionErrors();

        $chris = User::query()->where('email', 'chris@example.com')->sole();
        $this->assertTrue(Hash::check('secret-pass', $chris->password));

        auth()->logout();
        $this->actingAs($chris)->get('/admin')->assertOk();
    }

    public function test_a_new_admin_needs_a_unique_email_and_a_confirmed_password_of_eight_characters(): void
    {
        $this->actingAs(User::factory()->create(['email' => 'taken@example.com']));

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: ['name' => 'A', 'email' => 'taken@example.com', 'password' => 'secret-pass', 'password_confirmation' => 'secret-pass'])
            ->assertHasActionErrors(['email']);

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: ['name' => 'A', 'email' => 'a@example.com', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertHasActionErrors(['password']);

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: ['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret-pass', 'password_confirmation' => 'different'])
            ->assertHasActionErrors(['password']);

        $this->assertSame(1, User::query()->count());
    }

    public function test_an_admin_can_set_a_new_password_for_another_admin_or_leave_it_unchanged(): void
    {
        $this->actingAs(User::factory()->create());
        $other = User::factory()->create(['password' => 'old-password']);

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make(EditAction::class)->table($other), data: ['name' => 'Renamed'])
            ->assertHasNoActionErrors();

        $this->assertSame('Renamed', $other->refresh()->name);
        $this->assertTrue(Hash::check('old-password', $other->password));

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make(EditAction::class)->table($other), data: ['password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->assertHasNoActionErrors();

        $this->assertTrue(Hash::check('new-password', $other->refresh()->password));
    }

    public function test_an_admin_can_delete_another_admin_but_not_themselves(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($me);

        Livewire::test(ManageUsers::class)
            ->assertActionHidden(TestAction::make(DeleteAction::class)->table($me))
            ->callAction(TestAction::make(DeleteAction::class)->table($other));

        $this->assertModelMissing($other);
        $this->assertModelExists($me);
    }

    public function test_an_admin_changes_their_own_password_on_their_profile_with_the_current_one(): void
    {
        $me = User::factory()->create(['password' => 'old-password']);
        $this->actingAs($me);

        $this->get('/admin/profile')->assertOk();

        Livewire::test(EditProfile::class)
            ->fillForm(['password' => 'new-password', 'passwordConfirmation' => 'new-password', 'currentPassword' => 'wrong-password'])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        $this->assertTrue(Hash::check('old-password', $me->refresh()->password));

        Livewire::test(EditProfile::class)
            ->fillForm(['password' => 'new-password', 'passwordConfirmation' => 'new-password', 'currentPassword' => 'old-password'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('new-password', $me->refresh()->password));
    }
}
