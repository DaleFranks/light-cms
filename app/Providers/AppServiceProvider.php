<?php

namespace App\Providers;

use App\Models\Entry;
use App\Models\Module;
use App\Models\Theme;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.site.layout', function (ViewInstance $view): void {
            $view->with([
                'theme' => Theme::current(),
                'menu' => Entry::menu(),
                'footer' => Module::footer(),
            ]);
        });
    }
}
