<?php

namespace App;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Whether the site has been set up, which happens once, on the setup screen.
 *
 * A marker file records it, so checking costs one file lookup per visit. A
 * site set up before the marker existed (it has an admin account) gets the
 * marker the first time it's checked.
 */
class Installation
{
    public static function isComplete(): bool
    {
        if (File::exists(self::markerPath())) {
            return true;
        }

        if (self::hasAdminAccount()) {
            self::markComplete();

            return true;
        }

        return false;
    }

    public static function markComplete(): void
    {
        File::put(self::markerPath(), now()->toIso8601String()."\n");
    }

    /**
     * Whether visitors are sent to the setup screen until setup is done.
     * Switched off for tests, which set up their own data.
     */
    public static function isChecked(): bool
    {
        return (bool) config('app.installation_check', true);
    }

    private static function markerPath(): string
    {
        return storage_path('app/installed');
    }

    private static function hasAdminAccount(): bool
    {
        try {
            return Schema::hasTable('users') && User::query()->exists();
        } catch (Throwable) {
            return false;
        }
    }
}
