<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ $title }}</title>
        <link>{{ $url }}</link>
        <atom:link href="{{ $feedUrl }}" rel="self" type="application/rss+xml"/>
        <description>{{ $description }}</description>
        <language>{{ str_replace('_', '-', app()->getLocale()) }}</language>
@if ($items->isNotEmpty())
        <lastBuildDate>{{ $items->first()['date'] }}</lastBuildDate>
@endif
@foreach ($items as $item)
        <item>
            <title>{{ $item['title'] }}</title>
            <link>{{ $item['url'] }}</link>
            <guid isPermaLink="true">{{ $item['url'] }}</guid>
            <pubDate>{{ $item['date'] }}</pubDate>
            <description>{{ $item['description'] }}</description>
        </item>
@endforeach
    </channel>
</rss>
