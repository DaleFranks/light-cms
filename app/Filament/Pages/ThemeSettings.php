<?php

namespace App\Filament\Pages;

use App\FooterStyle;
use App\LogoPosition;
use App\Models\Theme;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The site's look, edited with ordinary form controls instead of code.
 *
 * @property-read Schema $form
 */
class ThemeSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $navigationLabel = 'Theme';

    protected static ?string $title = 'Theme';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Theme::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->model(Theme::current())
            ->columns(2)
            ->components([
                Section::make('Branding')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('site_name')
                            ->required()
                            ->maxLength(100),
                        Toggle::make('show_site_name')
                            ->label('Show the site name next to the logo')
                            ->inline(false),
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
                            ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'])
                            ->maxSize(512)
                            ->disk('uploads')
                            ->directory('theme'),
                    ]),
                Section::make('Colors')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        self::colorPicker('color_primary', 'Primary', 'Links, buttons and the active menu item.'),
                        self::colorPicker('color_secondary', 'Secondary', 'Headings, menu text and the dark footer.'),
                        self::colorPicker('color_text', 'Text'),
                        self::colorPicker('color_background', 'Page background'),
                        self::colorPicker('color_card_background', 'Card background'),
                        self::colorPicker('color_header_background', 'Header background'),
                    ]),
                Section::make('Fonts')
                    ->columns(2)
                    ->schema([
                        Select::make('font_headings')
                            ->label('Headings')
                            ->options(self::fontOptions())
                            ->required(),
                        Select::make('font_body')
                            ->label('Body text')
                            ->options(self::fontOptions())
                            ->required(),
                    ]),
                Section::make('Layout')
                    ->columns(2)
                    ->schema([
                        TextInput::make('border_radius')
                            ->label('Corner roundness')
                            ->integer()
                            ->minValue(0)
                            ->maxValue(40)
                            ->suffix('px')
                            ->required(),
                        TextInput::make('content_width')
                            ->label('Text width')
                            ->integer()
                            ->minValue(600)
                            ->maxValue(1400)
                            ->suffix('px')
                            ->required(),
                    ]),
                Section::make('Header and footer')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('logo_position')
                            ->options(LogoPosition::class)
                            ->required(),
                        Toggle::make('sticky_header')
                            ->label('Keep the header visible while scrolling')
                            ->inline(false),
                        Select::make('footer_style')
                            ->options(FooterStyle::class)
                            ->required(),
                    ]),
                Section::make('Custom CSS')
                    ->description('Optional. For small tweaks the settings above don\'t cover.')
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('custom_css')
                            ->hiddenLabel()
                            ->rows(10)
                            ->maxLength(20000)
                            ->extraInputAttributes(['class' => 'font-mono']),
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

    public function save(): void
    {
        Theme::current()->update($this->form->getState());

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
            ->modalDescription('All theme settings except the site name go back to the original look, and the logo and browser tab icon are removed.')
            ->action(function (): void {
                $theme = Theme::current();
                $theme->resetToDefaults();
                $this->form->fill($theme->attributesToArray());

                Notification::make()
                    ->success()
                    ->title('Theme reset to defaults')
                    ->send();
            });
    }

    private static function colorPicker(string $name, string $label, ?string $helperText = null): ColorPicker
    {
        return ColorPicker::make($name)
            ->label($label)
            ->helperText($helperText)
            ->hex()
            ->regex('/^#[0-9a-fA-F]{6}$/')
            ->required();
    }

    /**
     * @return array<string, string>
     */
    private static function fontOptions(): array
    {
        return collect(Theme::FONTS)->keys()->mapWithKeys(fn (string $font): array => [$font => $font])->all();
    }
}
