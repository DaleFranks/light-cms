<x-site.layout
    :title="$entry->title"
    :description="$entry->meta_description"
    :keywords="$entry->meta_keywords"
    :current-path="$entry->path"
>
    <h1 class="page-title">{{ $entry->title }}</h1>

    <article class="prose">
        {!! $entry->bodyHtml() !!}
    </article>
</x-site.layout>
