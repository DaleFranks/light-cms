<?php

namespace App\Filament\Auth;

use App\Models\MailSettings;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication as BaseEmailAuthentication;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Two-step login codes sent by email, using the site's own Email settings. If
 * a code can't be sent, the admin is told so rather than shown an error page.
 */
class EmailAuthentication extends BaseEmailAuthentication
{
    public function sendCode(HasEmailAuthentication $user): bool
    {
        try {
            MailSettings::current()->apply();

            return parent::sendCode($user);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('The code couldn\'t be emailed')
                ->body('The site\'s email isn\'t working. Use your authenticator app or a recovery code if you have one, or ask another admin to turn off two-step login for you on the Users screen.')
                ->danger()
                ->persistent()
                ->send();

            return false;
        }
    }
}
