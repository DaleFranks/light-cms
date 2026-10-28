<x-site.layout
    :title="$entry->title"
    :description="$entry->meta_description"
    :keywords="$entry->meta_keywords"
    :current-path="$entry->blog->path"
>
    <h1 class="page-title">{{ $entry->title }}</h1>

    @if ($entry->published_at)
        <div class="post-meta">
            Published on <time datetime="{{ $entry->published_at->toDateString() }}">{{ $entry->published_at->format('F j, Y') }}</time>
        </div>
    @endif

    <article class="prose">
        {!! $entry->bodyHtml() !!}
    </article>

    <div class="post-footer">
        <a href="{{ $entry->blog->url() }}" class="back-link">&laquo; Back to {{ $entry->blog->title }}</a>
    </div>
</x-site.layout>
