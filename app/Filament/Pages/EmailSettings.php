<?php

namespace App\Filament\Pages;

use App\Mail\TestEmail;
use App\Models\MailSettings;
use App\Models\Theme;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * How the site sends email, such as form submissions. It works without any
 * setting up; an SMTP server is only needed if email isn't getting through.
 *
 * @property-read Schema $form
 */
class EmailSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Email';

    protected static ?string $title = 'Email';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(MailSettings::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->model(MailSettings::current())
            ->components([
                Section::make('Sender')
                    ->description('Who email from this site comes from.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('from_address')
                            ->label('From address')
                            ->email()
                            ->maxLength(255)
                            ->placeholder(fn (): string => MailSettings::current()->fromAddress())
                            ->helperText('Best as an address at your site\'s own domain, so email isn\'t marked as spam. Leave empty to use the one shown.'),
                        TextInput::make('from_name')
                            ->label('From name')
                            ->maxLength(255)
                            ->placeholder(fn (): string => Theme::current()->site_name)
                            ->helperText('Leave empty to use the site name.'),
                    ]),
                Section::make('SMTP server')
                    ->description('Email is sent by the web server\'s own mail program, which usually works without any setting up. If email doesn\'t arrive, or lands in spam, send it through your email provider\'s SMTP server instead. Your provider\'s help pages list these details.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('use_smtp')
                            ->label('Send through an SMTP server')
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('smtp_host')
                            ->label('Server')
                            ->placeholder('smtp.example.com')
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => (bool) $get('use_smtp'))
                            ->visible(fn (Get $get): bool => (bool) $get('use_smtp')),
                        Select::make('smtp_encryption')
                            ->label('Security')
                            ->options([
                                'tls' => 'STARTTLS (usually port 587)',
                                'ssl' => 'SSL/TLS (usually port 465)',
                            ])
                            ->default('tls')
                            ->selectablePlaceholder(false)
                            ->visible(fn (Get $get): bool => (bool) $get('use_smtp')),
                        TextInput::make('smtp_port')
                            ->label('Port')
                            ->integer()
                            ->minValue(1)
                            ->maxValue(65535)
                            ->placeholder('587')
                            ->helperText('Leave empty for the usual port.')
                            ->visible(fn (Get $get): bool => (bool) $get('use_smtp')),
                        TextInput::make('smtp_username')
                            ->label('Username')
                            ->maxLength(255)
                            ->autocomplete('off')
                            ->visible(fn (Get $get): bool => (bool) $get('use_smtp')),
                        TextInput::make('smtp_password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->maxLength(255)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->placeholder(fn (): ?string => filled(MailSettings::current()->smtp_password) ? 'Saved — leave empty to keep it' : null)
                            ->visible(fn (Get $get): bool => (bool) $get('use_smtp')),
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
                        $this->sendTestEmailAction(),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        MailSettings::current()->update($this->form->getState());

        Notification::make()
            ->success()
            ->title('Email settings saved')
            ->send();
    }

    /**
     * Save the settings, then send a test email to the logged-in user with them.
     */
    public function sendTestEmailAction(): Action
    {
        return Action::make('sendTestEmail')
            ->label('Save and send a test email')
            ->color('gray')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->action(function (): void {
                $settings = MailSettings::current();
                $settings->update($this->form->getState());
                $address = (string) auth()->user()?->email;

                try {
                    $settings->apply();
                    Mail::to($address)->send(new TestEmail(Theme::current()->site_name));
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->danger()
                        ->title('The test email couldn\'t be sent')
                        ->body($exception->getMessage())
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Test email sent to '.$address)
                    ->body('If it doesn\'t arrive in a few minutes, check your spam folder, then try sending through an SMTP server.')
                    ->persistent()
                    ->send();
            });
    }
}
