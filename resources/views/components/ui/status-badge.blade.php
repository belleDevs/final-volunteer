@props(['label'])
@php
    $parts = collect(preg_split('/\s*·\s*/', trim((string) $label)) ?: [])
        ->map(fn ($part) => trim((string) $part))
        ->filter()
        ->values();
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 flex-wrap items-center gap-1']) }}>
    @foreach($parts as $part)
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ \App\Support\StatusBadge::classes($part) }}">{{ $part }}</span>
    @endforeach
</span>
