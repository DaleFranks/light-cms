<x-site.layout
    :title="$entry->title"
    :description="$entry->meta_description"
    :keywords="$entry->meta_keywords"
    :share-image="$entry->shareImage()"
    :layout="$entry->layout"
    :canonical-url="$entry->canonicalUrl()"
    :preview-notice="$previewNotice"
    :edit-url="$editUrl"
    :current-path="$entry->path"
>
    @if ($entry->layout->showsTitle())
        <h1 class="page-title">{{ $entry->title }}</h1>
    @endif

    <article class="prose">
        {!! $entry->bodyHtml() !!}
    </article>
</x-site.layout>
