<?php

namespace App\Http\Controllers;

use App\EntryType;
use App\Filament\Resources\Entries\EntryResource;
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
        return $this->render($this->findViewable(Entry::HOME_PATH) ?? abort(404));
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

        $entry = $this->findViewable($path);

        // An address with no page may have been redirected, such as an old page's address
        if ($entry === null) {
            $redirect = Redirect::findFor($path);
            abort_if($redirect === null, 404);

            return $redirect->follow($request);
        }

        return $this->render($entry);
    }

    /**
     * The entry at a path, if the person asking may see it: visitors only see
     * published entries, while a signed-in admin also sees drafts and
     * scheduled entries, to preview them.
     */
    private function findViewable(string $path): ?Entry
    {
        $entry = Entry::query()
            ->where('path', $path)
            ->with('blog')
            ->first();

        return $entry?->isVisibleToVisitors() || auth()->check() ? $entry : null;
    }

    /**
     * Show an entry, with a notice and a link back to its editor when it's a
     * preview of something visitors can't see.
     */
    private function render(Entry $entry): View
    {
        $previewNotice = $entry->previewNotice();
        $preview = [
            'previewNotice' => $previewNotice,
            'editUrl' => $previewNotice !== null ? EntryResource::getUrl('edit', ['record' => $entry]) : null,
        ];

        return match ($entry->type) {
            EntryType::Page => view('site.page', ['entry' => $entry, ...$preview]),
            EntryType::Post => view('site.post', ['entry' => $entry, ...$preview]),
            EntryType::Blog => $this->renderBlog($entry, $preview),
        };
    }

    /**
     * @param  array{previewNotice: ?string, editUrl: ?string}  $preview
     */
    private function renderBlog(Entry $blog, array $preview): View
    {
        $posts = $blog->posts()
            ->published()
            ->orderByRaw('coalesce(published_at, created_at) desc')
            ->paginate($blog->posts_per_page ?: Entry::DEFAULT_POSTS_PER_PAGE);

        return view('site.blog', [
            'entry' => $blog,
            'posts' => $posts,
            'previewImages' => Entry::previewImageUrls($posts->items()),
            ...$preview,
        ]);
    }
}
