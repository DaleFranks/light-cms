<?php

namespace App\Providers;

use App\Filament\InsertFileAction;
use App\Filament\InsertImageAction;
use App\Http\Controllers\FormSubmissionController;
use App\Models\AnalyticsSettings;
use App\Models\Entry;
use App\Models\Form;
use App\Models\Module;
use App\Models\Theme;
use App\Version;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        config(['view.compiled' => Version::compiledViewsPath(storage_path('framework/views'))]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Every content editor adds images and file links through the Insert image and
        // Insert file popups, so they go through the libraries. Without its own
        // button, the editor also takes no images dragged or pasted in.
        MarkdownEditor::configureUsing(function (MarkdownEditor $editor): void {
            $editor
                ->disableToolbarButtons(['attachFiles'])
                ->hintActions([InsertImageAction::make(), InsertFileAction::make()]);
        });

        // A visitor may send any one form five times a minute. Past that, the
        // form says so, rather than the visitor landing on an error page.
        RateLimiter::for('form-submissions', function (Request $request): Limit {
            $form = $request->route('form');
            $slug = $form instanceof Form ? $form->slug : (string) $form;

            return Limit::perMinute(5)
                ->by($slug.'|'.$request->ip())
                ->response(fn () => FormSubmissionController::backToForm($slug)
                    ->withErrors(['form' => 'You\'ve sent this form several times in a row. Please wait a minute and try again.'], 'form-'.$slug)
                    ->withInput());
        });

        View::composer('components.site.layout', function (ViewInstance $view): void {
            $view->with([
                'theme' => Theme::current(),
                'menu' => Entry::menu(),
                'footer' => Module::footer(),
                'feeds' => Entry::blogsWithFeeds(),
                // Empty while a failed database update leaves the table missing
                'analytics' => rescue(fn (): AnalyticsSettings => AnalyticsSettings::current(), fn (): AnalyticsSettings => new AnalyticsSettings, report: false),
            ]);
        });
    }
}
