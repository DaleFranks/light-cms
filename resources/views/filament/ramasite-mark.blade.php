{{--
    The RamaSite mark: two brackets around a capsule with a band across it.
    Inline in the admin it takes its colors from filament.brand, which switches
    them in dark mode. $standalone makes a complete SVG file (for the tab icon)
    with its own colors, switching with the computer's light or dark setting.
    The gaps in the capsule are cut out with a mask, so they show the background.
--}}
@php
    $standalone ??= false;
    $maskId = 'ramasite-gaps-'.\Illuminate\Support\Str::random(8);
@endphp
<svg @if ($standalone) xmlns="http://www.w3.org/2000/svg" @else class="ramasite-mark" aria-hidden="true" focusable="false" @endif viewBox="23 24 1014 545">
    @if ($standalone)
        <style>.rs-bracket{fill:#2C6098}.rs-capsule{fill:#597387}.rs-band{fill:#11CDF4}@media (prefers-color-scheme: dark){.rs-bracket{fill:#46C8E4}.rs-capsule{fill:#BEC7CD}.rs-band{fill:#4CA8C5}}</style>
    @endif
    <defs>
        <mask id="{{ $maskId }}" maskUnits="userSpaceOnUse" x="0" y="0" width="1060" height="600">
            <rect width="1060" height="600" fill="#fff"/>
            <g fill="none" stroke="#000" stroke-width="13">
                <path d="M411.5,100 A280,280 0 0,0 411.5,493"/>
                <path d="M648.5,100 A280,280 0 0,1 648.5,493"/>
                <path d="M355,182.5 H705" stroke-width="12"/>
                <path d="M355,411 H705" stroke-width="12"/>
            </g>
        </mask>
    </defs>
    <path class="rs-bracket" d="M29,296.5 L271.5,30 L431,30 L360.9,107 L298.4,107 L126,296.5 L298.4,486 L360.9,486 L431,563 L271.5,563 Z M1031,296.5 L788.5,30 L629,30 L699.1,107 L761.6,107 L934,296.5 L761.6,486 L699.1,486 L629,563 L788.5,563 Z"/>
    <g mask="url(#{{ $maskId }})">
        <path class="rs-capsule" d="M321,134 H739 C748,134 834,184.5 834,296.5 C834,408.5 748,459 739,459 H321 C312,459 226,408.5 226,296.5 C226,184.5 312,134 321,134 Z"/>
        <rect class="rs-band" x="330" y="277" width="400" height="39"/>
    </g>
</svg>
