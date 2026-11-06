<?php

namespace App\Http\Controllers;

use App\EntryType;
use App\Models\Entry;
use App\Models\Redirect;
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
        return $this->render($this->findPublished(Entry::HOME_PATH) ?? abort(404));
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

        $entry = $this->findPublished($path);

        // An address with no page may have been redirected, such as an old page's address
        if ($entry === null) {
            $redirect = Redirect::findFor($path);
            abort_if($redirect === null, 404);

            return $redirect->follow($request);
        }

        return $this->render($entry);
    }

    /**
     * The published entry at a path, or nothing (also for a post whose blog isn't published).
     */
    private function findPublished(string $path): ?Entry
    {
        $entry = Entry::query()
            ->published()
            ->where('path', $path)
            ->with('blog')
            ->first();

        if ($entry?->type === EntryType::Post && ! $entry->blog?->isPublished()) {
            return null;
        }

        return $entry;
    }

    private function render(Entry $entry): View
    {
        return match ($entry->type) {
            EntryType::Page => view('site.page', ['entry' => $entry]),
            EntryType::Post => view('site.post', ['entry' => $entry]),
            EntryType::Blog => $this->renderBlog($entry),
        };
    }

    private function renderBlog(Entry $blog): View
    {
        $posts = $blog->posts()
            ->published()
            ->orderByRaw('coalesce(published_at, created_at) desc')
            ->paginate($blog->posts_per_page ?: Entry::DEFAULT_POSTS_PER_PAGE);

        return view('site.blog', [
            'entry' => $blog,
            'posts' => $posts,
            'previewImages' => Entry::previewImageUrls($posts->items()),
        ]);
    }
}
