<?php

namespace App\Filament\Pages;

use App\BoxStyle;
use App\Filament\Concerns\WarnsWhenSettingsChanged;
use App\Filament\Resources\Modules\ModuleResource;
use App\FooterStyle;
use App\LogoPosition;
use App\Models\Module;
use App\Models\Theme;
use App\SiteNamePosition;
use App\TextAlignment;
use App\ThemeFonts;
use App\ThemeOptions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

/**
 * The site's look, edited with ordinary form controls instead of code.
 *
 * The main settings (colors, fonts, logo) are all most sites need. Every
 * detailed setting from ThemeOptions starts empty, meaning automatic, and
 * shows the value it's using; filling one in overrides just that setting.
 *
 * @property-read Schema $form
 */
class ThemeSettings extends Page
{
    use WarnsWhenSettingsChanged;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $navigationLabel = 'Theme';

    protected static ?string $title = 'Theme';

    private const HEX_PATTERN = '/^#[0-9a-fA-F]{6}$/';

    private const RGBA_PATTERN = '/^(#[0-9a-fA-F]{6}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\))$/';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->fillFormWithSavedSettings();
        $this->rememberLoadedSettings();
    }

    protected function savedSettings(): Model
    {
        return Theme::current();
    }

    protected function fillFormWithSavedSettings(): void
    {
        $this->fillForm(Theme::current());
    }

    /**
     * Toggles have no "empty", so they're shown switched to whatever they're using.
     */
    private function fillForm(Theme $theme): void
    {
        $toggles = collect(ThemeOptions::all())
            ->filter(fn (array $option): bool => $option['type'] === 'toggle')
            ->mapWithKeys(fn (array $option, string $key): array => [$key => (bool) $theme->option($key)]);

        $this->form->fill([
            ...$theme->attributesToArray(),
            'options' => [...($theme->options ?? []), ...$toggles],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->model(Theme::current())
            ->components([
                Tabs::make('Theme')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Branding')->schema([
                            Section::make('Site')->columns(2)->schema([
                                TextInput::make('site_name')
                                    ->required()
                                    ->maxLength(100),
                                FileUpload::make('logo_path')
                                    ->label('Logo')
                                    ->image()
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
                                    ->maxSize(2048)
                                    ->disk('uploads')
                                    ->directory('theme'),
                                FileUpload::make('favicon_path')
                                    ->label('Browser tab icon')
                                    ->helperText('A small square image, e.g. 64 × 64 pixels.')
                                    ->image()
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/x-icon', 'image/vnd.microsoft.icon'])
                                    ->maxSize(512)
                                    ->disk('uploads')
                                    ->directory('theme'),
                            ]),
                            self::optionsSection('Branding', 'Logo'),
                        ]),
                        Tab::make('Colors')->schema([
                            Section::make('Main colors')
                                ->description('Most of the site\'s colors follow these. Leave the other color settings empty and they\'re worked out from them.')
                                ->columns(3)
                                ->schema([
                                    self::colorPicker('color_primary', 'Primary', 'Links, buttons and the current menu item.'),
                                    self::colorPicker('color_secondary', 'Secondary', 'Headings, the site name and the dark footer.'),
                                    self::colorPicker('color_text', 'Text'),
                                    self::colorPicker('color_background', 'Page background'),
                                    ...self::optionFields('Colors', 'Main colors'),
                                ]),
                            self::optionsSection('Colors', 'Text'),
                            self::optionsSection('Colors', 'Code'),
                        ]),
                        Tab::make('Header')->schema([
                            Section::make('Layout')->columns(2)->schema([
                                Select::make('logo_position')
                                    ->options(LogoPosition::class)
                                    ->required(),
                                Toggle::make('sticky_header')
                                    ->label('Keep the header visible while scrolling')
                                    ->inline(false),
                                Select::make('site_name_position')
                                    ->label('Site name in the header')
                                    ->options(SiteNamePosition::class)
                                    ->required()
                                    ->live()
                                    ->helperText('Without a logo, the site name always shows.'),
                                ...self::optionFields('Header', 'Layout'),
                            ]),
                            Section::make('Top bar')
                                ->description('An optional line of text in a thin bar above the logo and menu, such as a phone number. It never takes room from the menu.')
                                ->columns(3)
                                ->schema([
                                    TextInput::make('top_bar_text')
                                        ->label('Text')
                                        ->maxLength(255)
                                        ->placeholder('Call us: (760) 555-0100')
                                        ->helperText('Leave empty for no bar. For a number visitors can tap to call: [(760) 555-0100](tel:+17605550100). **Bold** works too.')
                                        ->columnSpanFull(),
                                    Select::make('top_bar_style')
                                        ->label('Style')
                                        ->options(BoxStyle::class)
                                        ->selectablePlaceholder(false)
                                        ->required(),
                                    Select::make('top_bar_alignment')
                                        ->label('Alignment')
                                        ->options(TextAlignment::class)
                                        ->selectablePlaceholder(false)
                                        ->required(),
                                    Toggle::make('top_bar_stays_visible')
                                        ->label('Keep the top bar visible when scrolled')
                                        ->helperText('When off, it folds away as the header shrinks, and comes back at the top of the page.')
                                        ->inline(false),
                                    ...self::optionFields('Header', 'Top bar'),
                                ]),
                            Section::make('Header bar')->columns(3)->schema([
                                self::colorPicker('color_header_background', 'Background'),
                                ...self::optionFields('Header', 'Header bar'),
                            ]),
                            self::optionsSection('Header', 'Menu on phones'),
                        ]),
                        Tab::make('Fonts')->schema([
                            Section::make('Fonts')
                                ->description('Display and handwriting fonts aren\'t offered for body text because they\'re hard to read in paragraphs.')
                                ->columns(3)
                                ->schema([
                                    self::fontSelect('font_headings', ThemeFonts::forHeadings())
                                        ->label('Headings')
                                        ->required(),
                                    self::fontSelect('font_body', ThemeFonts::forBodyText())
                                        ->label('Body text')
                                        ->required(),
                                    ...self::optionFields('Fonts', 'Fonts'),
                                ]),
                            self::optionsSection('Fonts', 'Text sizes'),
                        ]),
                        Tab::make('Cards')->schema([
                            Section::make('Cards')->columns(3)->schema([
                                self::colorPicker('color_card_background', 'Background', 'For cards with no style. Also used behind the menu on phones and in the Light panel style.'),
                                ...self::optionFields('Cards', 'Cards'),
                            ]),
                        ]),
                        Tab::make('Panel styles')->schema([
                            Section::make('About panel styles')
                                ->description('The colored styles you can choose for a module or card under Style. A card with no style uses the Cards tab instead.'),
                            self::optionsSection('Panel styles', 'Dark'),
                            self::optionsSection('Panel styles', 'Light'),
                            self::optionsSection('Panel styles', 'Primary color'),
                        ]),
                        Tab::make('Footer')->schema([
                            Section::make('Style')->columns(2)->schema([
                                Select::make('footer_style')
                                    ->options(FooterStyle::class)
                                    ->required()
                                    ->live(),
                                Actions::make([
                                    Action::make('editFooter')
                                        ->label('Edit what the footer says')
                                        ->icon(Heroicon::OutlinedPencilSquare)
                                        ->color('gray')
                                        ->url(fn (): string => ModuleResource::getUrl('edit', ['record' => Module::footer()])),
                                ])->verticalAlignment('end'),
                            ]),
                            self::optionsSection('Footer', 'Layout'),
                            Section::make('Colors')
                                ->description(fn (Get $get): string => 'For the '.strtolower((string) self::footerStyle($get)?->getLabel()).' footer style.')
                                ->columns(3)
                                ->schema(self::optionFields('Footer', 'Colors')),
                        ]),
                        Tab::make('Slider')->schema([
                            self::optionsSection('Slider', 'Slider'),
                        ]),
                        Tab::make('Layout')->schema([
                            Section::make('Layout')->columns(2)->schema([
                                TextInput::make('content_width')
                                    ->label('Text width')
                                    ->integer()
                                    ->minValue(600)
                                    ->maxValue(1400)
                                    ->suffix('px')
                                    ->required(),
                                TextInput::make('border_radius')
                                    ->label('Corner roundness')
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(40)
                                    ->suffix('px')
                                    ->required(),
                            ]),
                            Section::make('Custom CSS')
                                ->description('Optional. For small tweaks the settings don\'t cover.')
                                ->schema([
                                    Textarea::make('custom_css')
                                        ->hiddenLabel()
                                        ->rows(10)
                                        ->maxLength(20000)
                                        ->extraInputAttributes(['class' => 'font-mono']),
                                ]),
                        ]),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save')
                            ->submit('save'),
                        $this->resetToDefaultsAction(),
                    ]),
                ]),
        ]);
    }

    protected function saveSettings(): void
    {
        $state = $this->form->getState();
        $state['options'] = array_filter($state['options'] ?? [], 'filled') ?: null;

        Theme::current()->update($state);
        $this->rememberLoadedSettings();

        Notification::make()
            ->success()
            ->title('Theme saved')
            ->send();
    }

    public function resetToDefaultsAction(): Action
    {
        return Action::make('resetToDefaults')
            ->label('Reset to defaults')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('All theme settings except the site name go back to the original look, every detailed setting goes back to automatic, and the logo and browser tab icon are removed.')
            ->action(function (): void {
                $theme = Theme::current();
                $theme->resetToDefaults();
                $this->fillForm($theme);
                $this->rememberLoadedSettings();

                Notification::make()
                    ->success()
                    ->title('Theme reset to defaults')
                    ->send();
            });
    }

    private static function optionsSection(string $tab, string $section): Section
    {
        return Section::make($section)
            ->columns(3)
            ->schema(self::optionFields($tab, $section));
    }

    /**
     * @return list<Field>
     */
    private static function optionFields(string $tab, string $section): array
    {
        return collect(ThemeOptions::inSection($tab, $section))
            ->map(fn (array $option, string $key): Field => self::optionField($key, $option))
            ->values()
            ->all();
    }

    /**
     * The form control for one detailed setting. Empty means automatic, and
     * the grey placeholder shows what that currently is.
     *
     * @param  array<string, mixed>  $option
     */
    private static function optionField(string $key, array $option): Field
    {
        $name = 'options.'.$key;
        $automatic = fn (Get $get): string => 'Automatic: '.self::describe($option, self::previewTheme($get)->automaticOption($key));

        $field = match ($option['type']) {
            'color' => ColorPicker::make($name)->hex()->regex(self::HEX_PATTERN)->placeholder($automatic),
            'rgba' => ColorPicker::make($name)->rgba()->regex(self::RGBA_PATTERN)->placeholder($automatic),
            'px' => TextInput::make($name)->numeric()->minValue($option['min'])->maxValue($option['max'])->suffix('px')->placeholder($automatic),
            'seconds' => TextInput::make($name)->numeric()->minValue($option['min'])->maxValue($option['max'])->suffix('seconds')->placeholder($automatic),
            'font' => self::fontSelect($name, ThemeFonts::forHeadings(), $key)->placeholder($automatic),
            'code_font' => self::fontSelect($name, ThemeFonts::forCode(), $key)->placeholder($automatic),
            'choice' => Select::make($name)->options($option['choices'])->placeholder($automatic),
            'toggle' => Toggle::make($name)->inline(false),
            'image' => FileUpload::make($name)->image()->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
                ->maxSize(4096)->disk('uploads')->directory('theme'),
        };

        if (isset($option['footer_style'])) {
            $field->visible(fn (Get $get): bool => self::footerStyle($get)?->value === $option['footer_style']);
        }

        // Help text would take the place of a font's live sample, so it's only set when there is some
        if (isset($option['help'])) {
            $field->helperText($option['help']);
        }

        return $field->label($option['label']);
    }

    /**
     * A theme made from the form as it stands, so the "Automatic" hints follow
     * the main colors as they're changed, before saving.
     */
    private static function previewTheme(Get $get): Theme
    {
        $saved = Theme::current();
        $theme = $saved->replicate();

        foreach (['color_primary', 'color_secondary', 'color_background', 'color_text', 'color_card_background', 'color_header_background'] as $color) {
            $value = $get($color);
            $theme->{$color} = is_string($value) && preg_match(self::HEX_PATTERN, $value) ? $value : $saved->{$color};
        }

        $theme->font_body = (string) ($get('font_body') ?: $saved->font_body);

        $position = $get('site_name_position');
        $theme->site_name_position = $position instanceof SiteNamePosition ? $position : (SiteNamePosition::tryFrom((string) $position) ?? $saved->site_name_position);
        $theme->options = array_filter((array) $get('options'), 'filled');

        return $theme;
    }

    /**
     * An automatic value as shown in its placeholder.
     *
     * @param  array<string, mixed>  $option
     */
    private static function describe(array $option, mixed $value): string
    {
        return match ($option['type']) {
            'px' => rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.').' px',
            'seconds' => $value.' seconds',
            'choice' => $option['choices'][$value] ?? (string) $value,
            default => (string) $value,
        };
    }

    private static function footerStyle(Get $get): ?FooterStyle
    {
        $style = $get('footer_style');

        return $style instanceof FooterStyle ? $style : FooterStyle::tryFrom((string) $style);
    }

    private static function colorPicker(string $name, string $label, ?string $helperText = null): ColorPicker
    {
        return ColorPicker::make($name)
            ->label($label)
            ->helperText($helperText)
            ->hex()
            ->regex(self::HEX_PATTERN)
            ->required()
            ->live(onBlur: true);
    }

    /**
     * A searchable list of fonts, with a live sample of the chosen one beneath it.
     * For a detailed setting, the sample shows the automatic font while it's empty.
     *
     * @param  array<string, string>|array<string, array<string, string>>  $fonts
     */
    private static function fontSelect(string $name, array $fonts, ?string $optionKey = null): Select
    {
        return Select::make($name)
            ->options($fonts)
            ->searchable()
            ->live()
            ->belowContent(function (Get $get) use ($name, $optionKey): ?View {
                $font = $get($name) ?: ($optionKey ? self::previewTheme($get)->automaticOption($optionKey) : null);

                return filled($font) ? view('filament.font-sample', ['font' => $font]) : null;
            });
    }
}
