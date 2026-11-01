@props([
    'items',
    'style' => 'flat',
])

@if ($items->isNotEmpty())
    @if ($style === 'multi')
        <nav class="footer-nav-multi" aria-label="Footer menu">
            @foreach ($items as $item)
                <div class="footer-nav-col">
                    <h4><a href="{{ $item->url() }}">{{ $item->title }}</a></h4>
                    @if ($item->children->isNotEmpty())
                        <ul>
                            @foreach ($item->children as $child)
                                <li><a href="{{ $child->url() }}">{{ $child->title }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </nav>
    @else
        <nav class="footer-nav-flat" aria-label="Footer menu">
            @foreach ($items as $item)
                <a href="{{ $item->url() }}">{{ $item->title }}</a>
            @endforeach
        </nav>
    @endif
@endif
