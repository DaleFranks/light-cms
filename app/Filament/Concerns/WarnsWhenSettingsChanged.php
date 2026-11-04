<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;

/**
 * Saving a settings screen writes every box on it, so a browser tab left open
 * would put its old values back over anything changed since. This stops that
 * save and asks which version to keep.
 */
trait WarnsWhenSettingsChanged
{
    /**
     * What the saved settings were when this screen last loaded or saved them.
     */
    #[Locked]
    public ?string $loadedSettings = null;

    /**
     * The settings as they're saved right now.
     */
    abstract protected function savedSettings(): Model;

    /**
     * Fill the form with the settings as they're saved right now.
     */
    abstract protected function fillFormWithSavedSettings(): void;

    /**
     * Write the form to the saved settings and say so.
     */
    abstract protected function saveSettings(): void;

    public function save(): void
    {
        $this->form->validate();

        if ($this->settingsChangedSinceLoading()) {
            $this->mountAction('settingsChanged');

            return;
        }

        $this->saveSettings();
    }

    public function settingsChangedAction(): Action
    {
        return Action::make('settingsChanged')
            ->requiresConfirmation()
            ->color('warning')
            ->icon(Heroicon::OutlinedExclamationTriangle)
            ->modalHeading('These settings have changed')
            ->modalDescription('They were changed after you opened this screen, in another browser tab or by another admin. Nothing has been saved yet. Loading the latest settings discards what you\'ve changed here. Saving your version replaces everything with what\'s on this screen, including those other changes.')
            ->modalSubmitActionLabel('Save my version anyway')
            ->action(fn () => $this->saveSettings())
            ->extraModalFooterActions([
                Action::make('loadLatestSettings')
                    ->label('Load the latest settings')
                    ->color('gray')
                    ->action(function (): void {
                        $this->fillFormWithSavedSettings();
                        $this->rememberLoadedSettings();

                        Notification::make()
                            ->success()
                            ->title('Latest settings loaded')
                            ->send();
                    })
                    ->cancelParentActions(),
            ]);
    }

    protected function rememberLoadedSettings(): void
    {
        $this->loadedSettings = $this->savedSettingsFingerprint();
    }

    protected function settingsChangedSinceLoading(): bool
    {
        return $this->loadedSettings !== $this->savedSettingsFingerprint();
    }

    /**
     * The saved values themselves rather than the time they were saved, which only
     * counts whole seconds and would miss two saves close together.
     */
    private function savedSettingsFingerprint(): string
    {
        return hash('sha256', (string) json_encode(Arr::except($this->savedSettings()->getAttributes(), ['updated_at'])));
    }
}
