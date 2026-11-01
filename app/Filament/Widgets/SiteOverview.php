<?php

namespace App\Filament\Widgets;

use App\EntryStatus;
use App\EntryType;
use App\Filament\Resources\Cards\CardResource;
use App\Filament\Resources\Entries\EntryResource;
use App\Filament\Resources\Forms\FormResource;
use App\Filament\Resources\Images\ImageResource;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Sliders\SliderResource;
use App\Models\Card;
use App\Models\Entry;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Image;
use App\Models\Module;
use App\Models\Slide;
use App\Models\Slider;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * The site at a glance: one count for each kind of thing the site is built
 * from, each opening its own list.
 */
class SiteOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    /**
     * Two even rows of four on wide screens.
     *
     * @var int|array<string, ?int>|null
     */
    protected int|array|null $columns = ['@xl' => 4, '!@lg' => 4];

    protected function getStats(): array
    {
        $submissionsThisWeek = FormSubmission::query()->where('created_at', '>=', now()->subDays(7))->count();

        return [
            self::entries(EntryType::Page, 'Pages', Heroicon::OutlinedDocumentText),
            self::entries(EntryType::Blog, 'Blogs', Heroicon::OutlinedNewspaper),
            self::entries(EntryType::Post, 'Posts', Heroicon::OutlinedPencilSquare),
            Stat::make('Images', Image::query()->count())
                ->description(Number::fileSize((int) Image::query()->sum('size')).' in total')
                ->icon(Heroicon::OutlinedPhoto)
                ->url(ImageResource::getUrl()),
            Stat::make('Sliders', Slider::query()->count())
                ->description(self::counted(Slide::query()->count(), 'slide').' in all')
                ->icon(Heroicon::OutlinedFilm)
                ->url(SliderResource::getUrl()),
            Stat::make('Modules', Module::query()->count())
                ->description('Including the footer')
                ->icon(Heroicon::OutlinedPuzzlePiece)
                ->url(ModuleResource::getUrl()),
            Stat::make('Cards', Card::query()->count())
                ->icon(Heroicon::OutlinedSquares2x2)
                ->url(CardResource::getUrl()),
            Stat::make('Forms', Form::query()->count())
                ->description(self::counted($submissionsThisWeek, 'submission').' this week')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->url(FormResource::getUrl()),
        ];
    }

    /**
     * How many entries of a type there are, with how many are published,
     * drafts or waiting for their publish date. The count opens the Pages &
     * Posts list filtered to that type.
     */
    private static function entries(EntryType $type, string $label, Heroicon $icon): Stat
    {
        $ofType = fn () => Entry::query()->where('type', $type);

        $details = collect([
            self::counted($ofType()->published()->count(), 'published', 'published'),
            self::counted($ofType()->where('status', EntryStatus::Draft)->count(), 'draft'),
            self::counted($ofType()->where('status', EntryStatus::Published)->where('published_at', '>', now())->count(), 'scheduled', 'scheduled'),
        ])->reject(fn (string $detail, int $index): bool => $index > 0 && str_starts_with($detail, '0 '));

        return Stat::make($label, $ofType()->count())
            ->description($details->implode(' · '))
            ->icon($icon)
            ->url(EntryResource::getUrl('index', ['filters' => ['type' => ['value' => $type->value]]]));
    }

    /**
     * A number with its word, e.g. "1 draft", "3 drafts".
     */
    private static function counted(int $count, string $singular, ?string $plural = null): string
    {
        return $count.' '.($count === 1 ? $singular : ($plural ?? Str::plural($singular)));
    }
}
