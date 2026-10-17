@props([
    'user' => null,
    'name' => null,
    'photo' => null,
    'size' => 'md', // sm | md | lg
])

@php
    $displayName = $name ?? ($user?->name ?? 'User');
    $path = $photo ?? ($user?->profile_photo_path ?? null);
    $src = $path
        ? (str_starts_with((string) $path, 'http') || str_starts_with((string) $path, '/')
            ? $path
            : asset('storage/'.$path))
        : null;
    $initials = collect(preg_split('/\s+/', trim((string) $displayName)) ?: [])
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->join('');
    if ($initials === '') {
        $initials = '?';
    }
    $box = match ($size) {
        'sm' => 'h-7 w-7 text-[10px]',
        'lg' => 'h-11 w-11 text-xs',
        default => 'h-9 w-9 text-[11px]',
    };
@endphp

@if($src)
    <img {{ $attributes->merge(['class' => "{$box} shrink-0 rounded-full object-cover", 'src' => $src, 'alt' => $displayName]) }}>
@else
    <span {{ $attributes->merge(['class' => "{$box} inline-flex shrink-0 items-center justify-center rounded-full bg-slate-100 font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-300"]) }} title="{{ $displayName }}">{{ $initials }}</span>
@endif
