@props(['name', 'class' => 'size-4'])

{{--
    Inline SVG, mitte ikooniteek: null välist sõltuvust, null lisapäringut,
    värvi pärib currentColor kaudu. Lucide stiilis, joonelaius 1.75.
--}}
@php
    $paths = [
        'bolt' => '<path d="M13 2 4.09 12.86a1 1 0 0 0 .77 1.64H11l-1 7.5 8.91-10.86a1 1 0 0 0-.77-1.64H12z"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/>',
        'trend-down' => '<path d="M22 17 13.5 8.5l-5 5L2 7"/><path d="M16 17h6v-6"/>',
        'trend-up' => '<path d="M22 7 13.5 15.5l-5-5L2 17"/><path d="M16 7h6v6"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'alert' => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/>',
        'check' => '<path d="m20 6-11 11-5-5"/>',
        'building' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 8h.01M15 8h.01M9 12h.01M15 12h.01M10 21v-4h4v4"/>',
        'home' => '<path d="m3 10 9-7 9 7v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 21v-7h6v7"/>',
        'flame' => '<path d="M12 2c1.5 4 5 5.5 5 9.5a5 5 0 0 1-10 0C7 9 9 7 9 7s.5 2 1.5 2.5C11 8 12 5 12 2z"/>',
        'receipt' => '<path d="M5 3v18l2-1.5L9 21l2-1.5L13 21l2-1.5L17 21l2-1.5V3z"/><path d="M9 8h6M9 12h6M9 16h4"/>',
        'link' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="M7 15v3M12 9v9M17 12v6"/>',
        'sliders' => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }}
     viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true">
    {!! $paths[$name] ?? '' !!}
</svg>
