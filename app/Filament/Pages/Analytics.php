<?php

namespace App\Filament\Pages;

use App\Models\AnalyticsSettings;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
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
 * Visitor tracking and search engine verification: an ID for each common
 * service, and boxes for any other service's code.
 *
 * @property-read Schema $form
 */
class Analytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?string $title = 'Analytics & Verification';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(AnalyticsSettings::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->model(AnalyticsSettings::current())
            ->components([
                Section::make('Analytics')
                    ->description('Count your visitors and see what they do. Paste just the ID, or the whole snippet the service gives you, and the ID is picked out of it. Tracking is left out of previews and of every visit while you\'re signed in to the admin, so only your visitors are counted. To check it\'s working, open your site in a private window.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('tracking_off')
                            ->label('Turn off tracking')
                            ->helperText('Stops the analytics and the code below on every page, without clearing them. Verification stays.')
                            ->columnSpanFull(),
                        $this->idInput(
                            'google_analytics_id',
                            'Google Analytics Measurement ID',
                            'G-AB12CD34EF',
                            'In Google Analytics: Admin → Data streams → your site → Measurement ID. It starts with G-.',
                            AnalyticsSettings::googleAnalyticsIdFrom(...),
                            'That isn\'t a Measurement ID. It starts with G-, like G-AB12CD34EF.',
                        ),
                        $this->idInput(
                            'google_tag_manager_id',
                            'Google Tag Manager Container ID',
                            'GTM-AB12CD3',
                            'In Tag Manager, at the top of your workspace, next to the container name. It starts with GTM-. If your Google Analytics is set up inside Tag Manager, fill in only this one.',
                            AnalyticsSettings::googleTagManagerIdFrom(...),
                            'That isn\'t a Container ID. It starts with GTM-, like GTM-AB12CD3.',
                        ),
                        $this->idInput(
                            'meta_pixel_id',
                            'Meta Pixel ID',
                            '1234567890123456',
                            'In Meta Events Manager: Data sources → your pixel. It\'s a long number.',
                            AnalyticsSettings::metaPixelIdFrom(...),
                            'That isn\'t a Pixel ID. It\'s a long number, like 1234567890123456.',
                        ),
                    ]),
                Section::make('Search engine verification')
                    ->description('Proves to a search engine that the site is yours, so you can see how it does in their results. Paste the code, or the whole meta tag they give you. Always included, even with tracking turned off.')
                    ->columns(2)
                    ->schema([
                        $this->idInput(
                            'google_verification',
                            'Google Search Console',
                            'Paste the meta tag or its code',
                            'In Search Console, add your site as a URL prefix property and choose the HTML tag method.',
                            AnalyticsSettings::googleVerificationFrom(...),
                            'That isn\'t a Google verification code. Paste the whole <meta name="google-site-verification" ...> tag.',
                        ),
                        $this->idInput(
                            'bing_verification',
                            'Bing Webmaster Tools',
                            'Paste the meta tag or its code',
                            'In Bing Webmaster Tools, add your site and choose the HTML Meta Tag method.',
                            AnalyticsSettings::bingVerificationFrom(...),
                            'That isn\'t a Bing verification code. Paste the whole <meta name="msvalidate.01" ...> tag.',
                        ),
                    ]),
                Section::make('Other services')
                    ->description('For Plausible, Fathom, Matomo and similar services, paste the snippet they give you here, where their instructions say. This code runs on every page exactly as pasted, so only paste code from a service you trust. If a page looks or works wrong afterwards, empty the box and save.')
                    ->schema([
                        Textarea::make('head_code')
                            ->label('Head code')
                            ->helperText('Added at the end of every page\'s <head>. Where most services ask for their code.')
                            ->rows(6)
                            ->maxLength(20000)
                            ->extraInputAttributes(['class' => 'font-mono']),
                        Textarea::make('footer_code')
                            ->label('Footer code')
                            ->helperText('Added at the very end of every page, just before </body>. For services that ask for it there, such as chat widgets.')
                            ->rows(6)
                            ->maxLength(20000)
                            ->extraInputAttributes(['class' => 'font-mono']),
                    ]),
            ]);
    }

    /**
     * A box for one service's ID or code, which takes either the ID itself or
     * the snippet the service gives out, and saves just the ID.
     *
     * @param  Closure(?string): ?string  $extractId
     */
    private function idInput(string $name, string $label, string $placeholder, string $hint, Closure $extractId, string $invalidMessage): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->placeholder($placeholder)
            ->helperText($hint)
            ->maxLength(5000)
            ->autocomplete('off')
            ->rules([
                fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($extractId, $invalidMessage): void {
                    if (filled($value) && $extractId($value) === null) {
                        $fail($invalidMessage);
                    }
                },
            ])
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $extractId($state) : null);
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
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $settings = AnalyticsSettings::current();
        $settings->update($this->form->getState());

        // Show just the IDs picked out of any pasted snippets
        $this->form->fill($settings->attributesToArray());

        Notification::make()
            ->success()
            ->title('Analytics settings saved')
            ->send();
    }
}
