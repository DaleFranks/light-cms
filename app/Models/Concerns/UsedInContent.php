<?php

namespace App\Models\Concerns;

use App\Models\Card;
use App\Models\Entry;
use App\Models\Module;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * For things in the libraries (images and files), which content names by
 * their file name: finding the pages, posts, modules and cards that use one.
 */
trait UsedInContent
{
    /**
     * The content whose text names the file, by name, e.g. "Page: About us".
     * The name must stand on its own, so "team.jpg" isn't found in "my-team.jpg".
     *
     * @return Collection<int, string>
     */
    protected function contentUsing(string $filename): Collection
    {
        $pattern = '/(?<![a-z0-9._-])'.preg_quote($filename, '/').'(?!\.?[a-z0-9_-])/i';
        $uses = fn (Model $model): bool => (bool) preg_match($pattern, (string) $model->getAttribute('body'));
        $containing = fn (string $model) => $model::query()->where('body', 'like', '%'.$filename.'%');

        return collect()
            ->merge($containing(Entry::class)->get(['id', 'type', 'title', 'body'])->filter($uses)
                ->map(fn (Entry $entry): string => $entry->type->getLabel().': '.$entry->title))
            ->merge($containing(Module::class)->get(['id', 'name', 'body'])->filter($uses)
                ->map(fn (Module $module): string => 'Module: '.$module->name))
            ->merge($containing(Card::class)->get(['id', 'name', 'body'])->filter($uses)
                ->map(fn (Card $card): string => 'Card: '.$card->name))
            ->values();
    }
}
