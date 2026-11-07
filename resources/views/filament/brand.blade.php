{{-- The RamaSite mark beside "RamaSite Admin: <site name>", at the top of the admin and on the sign-in screen. --}}
<style>
    .ramasite-brand { display: flex; align-items: center; gap: 0.625rem; }
    .ramasite-brand .ramasite-mark { height: 1.75rem; width: auto; flex: none; }
    /* On the sign-in screen, a larger mark centered above the name */
    .fi-simple-header .ramasite-brand { flex-direction: column; gap: 0.875rem; margin-inline: auto; }
    .fi-simple-header .ramasite-brand .ramasite-mark { height: 3.5rem; }
    .ramasite-mark .rs-bracket { fill: #2C6098; }
    .ramasite-mark .rs-capsule { fill: #597387; }
    .ramasite-mark .rs-band { fill: #11CDF4; }
    .dark .ramasite-mark .rs-bracket { fill: #46C8E4; }
    .dark .ramasite-mark .rs-capsule { fill: #BEC7CD; }
    .dark .ramasite-mark .rs-band { fill: #4CA8C5; }
</style>
<span class="ramasite-brand">
    @include('filament.ramasite-mark')
    <span>{{ $brandName }}</span>
</span>
