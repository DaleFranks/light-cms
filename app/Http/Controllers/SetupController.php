<?php

namespace App\Http\Controllers;

use App\DatabaseUpdate;
use App\Installation;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\HomePageSeeder;
use Database\Seeders\StarterFormsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The one-time setup screen: creates the database tables, then the site name
 * and first admin account. Once setup is done, it's gone.
 */
class SetupController extends Controller
{
    public function show(): View
    {
        abort_if(Installation::isComplete(), 404);

        self::createTables();

        return view('setup');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(Installation::isComplete(), 404);

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], attributes: [
            'site_name' => 'site name',
            'name' => 'your name',
            'email' => 'email address',
        ]);

        self::createTables();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        Theme::current()->update(['site_name' => $validated['site_name']]);
        (new HomePageSeeder)->run();
        (new StarterFormsSeeder)->run();

        Installation::markComplete();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect('/admin');
    }

    /**
     * Create (or bring up to date) the database tables.
     */
    private static function createTables(): void
    {
        Artisan::call('migrate', ['--force' => true]);
        DatabaseUpdate::markApplied();
    }
}
