<?php

namespace App\Filament\Auth;

use App\Providers\Filament\AdminPanelProvider;
use Filament\Auth\MultiFactor\App\AppAuthentication as BaseAppAuthentication;

/**
 * Two-step login with an authenticator app, listed in the app as
 * "RamaSite Admin - <site name>". The admin's usual name has a colon, which
 * authenticator apps take as the end of the name.
 */
class AppAuthentication extends BaseAppAuthentication
{
    public function getBrandName(): string
    {
        return str_replace(':', ' -', AdminPanelProvider::brandName());
    }
}
