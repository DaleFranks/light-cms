<?php

namespace App\Http\Controllers;

use App\EntryType;
use App\Models\Entry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntryController extends Controller
{
    /**
     * Show the home page, which is the entry with the slug "home".
     */
    public function home(): View
    {
        return $this->render($this->findPublished(Entry::HOME_PATH));
    }

    /**
     * Show the entry at the requested address.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $path = trim($request->path(), '/');

        if ($path === Entry::HOME_PATH) {
            return redirect()->route('home', status: 301);
        }

        return $this->render($this->findPublished($path));
    }

    private function findPublished(string $path): Entry
    {
        $entry = Entry::query()
            ->published()
            ->where('path', $path)
            ->with('blog')
            ->firstOrFail();

        if ($entry->type === EntryType::Post && ! Entry::query()->published()->whereKey($entry->blog_id)->exists()) {
            abort(404);
        }

        return $entry;
    }

    private function render(Entry $entry): View
    {
        return match ($entry->type) {
            EntryType::Page => view('site.page', ['entry' => $entry]),
            EntryType::Post => view('site.post', ['entry' => $entry]),
            EntryType::Blog => view('site.blog', [
                'entry' => $entry,
                'posts' => $entry->posts()
                    ->published()
                    ->orderByRaw('coalesce(published_at, created_at) desc')
                    ->paginate(10),
            ]),
        };
    }
}
