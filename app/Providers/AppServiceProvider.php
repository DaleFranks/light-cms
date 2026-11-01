<?php

namespace App\Providers;

use App\Models\Entry;
use App\Models\Image;
use App\Models\Module;
use App\Models\Theme;
use Filament\Forms\Components\MarkdownEditor;
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
                ->getFileAttachmentUrlUsing(fn (Image|string $file): string => $file instanceof Image ? $file->url() : '/storage/'.$file);
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
