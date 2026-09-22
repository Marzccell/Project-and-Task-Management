@props(['name', 'size' => 20])
@php
$paths = [
'grid' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
'check' => '<path d="m5 12 4 4L19 6"/>',
'circle' => '<circle cx="12" cy="12" r="9"/>',
'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 11h18"/>',
'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
'arrow' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
'plus' => '<path d="M12 5v14M5 12h14"/>',
'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
'list' => '<path d="M9 6h12M9 12h12M9 18h12M3 6h1M3 12h1M3 18h1"/>',
'board' => '<rect x="3" y="4" width="5" height="16" rx="1"/><rect x="10" y="4" width="5" height="11" rx="1"/><rect x="17" y="4" width="4" height="14" rx="1"/>',
'edit' => '<path d="m15 4 5 5M4 20l5-1L21 7a2 2 0 0 0-5-5L4 14v6Z"/>',
'delete' => '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>',
'logout' => '<path d="M9 4H4v16h5M9 12h12m-4-4 4 4-4 4"/>',
'x' => '<path d="m6 6 12 12M6 18 18 6"/>',
'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1 1m12 12 1 1M5 19l1-1M18 6l1-1"/>',
'flag' => '<path d="M5 21V3c5-3 9 3 14 0v10c-5 3-9-3-14 0"/>',
'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
'spark' => '<path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3Z"/>',
'book' => '<path d="M12 5v16M12 5C9 2 5 2 2 4v15c3-2 7-2 10 2 3-4 7-4 10-2V4c-3-2-7-2-10 1Z"/>',
];
@endphp
<svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['circle'] !!}</svg>
