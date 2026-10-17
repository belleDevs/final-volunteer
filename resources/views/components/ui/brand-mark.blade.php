@props([
    'size' => 'md', // sm | md | lg
    'round' => false,
])

@php
    $radius = $round ? 'rounded-full' : match ($size) {
        'sm' => 'rounded-[0.7rem]',
        'lg' => 'rounded-2xl',
        default => 'rounded-xl',
    };
    $box = match ($size) {
        'sm' => 'h-9 w-9',
        'lg' => 'h-12 w-12',
        default => 'h-10 w-10',
    };
    $icon = match ($size) {
        'sm' => 'h-4 w-4',
        'lg' => 'h-6 w-6',
        default => 'h-[1.15rem] w-[1.15rem]',
    };
@endphp

<span {{ $attributes->merge(['class' => "relative inline-flex shrink-0 items-center justify-center overflow-hidden {$box} {$radius} bg-gradient-to-br from-sky-400 via-jci-blue to-jci-dark text-white shadow-md shadow-sky-500/25"]) }}>
    <span class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,0.35),transparent_55%)]"></span>
    <svg class="relative {{ $icon }}" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <path d="M6.5 7.25h5.1c.55 0 1.05.3 1.32.78l3.08 5.55 3.08-5.55c.27-.48.77-.78 1.32-.78h5.1c.72 0 1.16.8.78 1.42L18.1 24.1a1.55 1.55 0 0 1-2.7 0L5.72 8.67c-.38-.62.06-1.42.78-1.42Z" fill="currentColor" opacity="0.95"/>
        <path d="M11.2 8.35h2.55c.28 0 .53.15.66.4l1.59 2.86 1.59-2.86c.13-.25.38-.4.66-.4h2.55c.36 0 .58.4.39.71l-4.2 7.45a.78.78 0 0 1-1.36 0l-4.2-7.45c-.19-.31.03-.71.39-.71Z" fill="white" opacity="0.38"/>
    </svg>
    <span class="sr-only">VolunteerHub</span>
</span>
