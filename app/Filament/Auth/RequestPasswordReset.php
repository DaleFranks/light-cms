<?php

namespace App\Filament\Auth;

use App\Models\MailSettings;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The "Forgot password?" screen. The reset link is sent with the site's own
 * Email settings, and if it can't be sent, the admin is told so (and what to
 * do instead) rather than shown an error page.
 */
class RequestPasswordReset extends BaseRequestPasswordReset
{
    public function request(): void
    {
        try {
            MailSettings::current()->apply();
            parent::request();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('The email couldn\'t be sent')
                ->body('The site\'s email isn\'t working. Ask another admin to set a new password for you on the Users screen.')
                ->danger()
                ->send();
        }
    }
}
