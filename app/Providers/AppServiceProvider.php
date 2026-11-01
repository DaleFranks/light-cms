<?php

namespace App\Providers;

use App\Http\Controllers\FormSubmissionController;
use App\Models\Entry;
use App\Models\Form;
use App\Models\Image;
use App\Models\Module;
use App\Models\Theme;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
        // Images uploaded in the content editor go into the image library, named
        // and numbered like any other upload.
        MarkdownEditor::configureUsing(function (MarkdownEditor $editor): void {
            $editor
                ->fileAttachmentsDisk(Image::DISK)
                ->fileAttachmentsDirectory(Image::DIRECTORY)
                ->fileAttachmentsAcceptedFileTypes(array_keys(Image::TYPES))
                ->saveUploadedFileAttachmentUsing(fn (TemporaryUploadedFile $file): Image => Image::storeUpload($file))
                ->getFileAttachmentUrlUsing(fn (Image|string $file): string => $file instanceof Image ? $file->url() : Storage::disk(Image::DISK)->url($file));
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
            ]);
        });
    }
}
