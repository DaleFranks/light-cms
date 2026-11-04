{{-- A live look at the chosen font, in regular, bold and italic. --}}
<link rel="stylesheet" href="{{ \App\ThemeFonts::googleFontsUrl([$font]) }}">
<p style="font-family: {{ \App\ThemeFonts::stack($font) }}; font-size: 1.125rem; line-height: 1.5; font-weight: {{ $weight ?? 400 }}; font-synthesis-weight: none;">
    The quick brown fox jumps over the lazy dog. <strong>Bold text.</strong> <em>Italic text.</em> 0123456789
</p>
