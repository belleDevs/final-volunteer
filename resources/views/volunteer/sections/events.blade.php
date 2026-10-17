@php
    $clock = \App\Support\ScheduleClock::class;
    $buckets = [
        'all' => 'All',
        'upcoming' => 'Upcoming',
        'ongoing' => 'Ongoing',
        'ended' => 'Ended',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
@endphp

<div class="space-y-5">
    <div class="relative max-w-md">
        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-300"></i>
        <input id="volunteer-event-search" type="search" value="{{ $eventSearch }}" placeholder="Search events" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-jci-blue/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach($buckets as $value => $label)
            <a href="{{ route('volunteer.dashboard', array_filter(['section' => 'events', 'state' => $value === 'all' ? null : $value, 'q' => $eventSearch !== '' ? $eventSearch : null, 'from' => $eventFrom !== '' ? $eventFrom : null, 'to' => $eventTo !== '' ? $eventTo : null])) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $eventState === $value || ($value === 'all' && in_array($eventState, ['', 'all'], true)) ? 'bg-jci-blue text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-jci-blue dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700' }}">{{ $label }}</a>
        @endforeach
    </div>

    <section class="space-y-3">
        @forelse($eventList as $event)
            @php
                $statusLabel = $event->status_label ?? ($buckets[$event->event_bucket] ?? ucfirst((string) $event->event_bucket));
                $when = $clock::range($event->start_time, $event->end_time);
                $roles = $event->duty_titles ?? collect();
                $duties = collect($event->duty_details ?? []);
                $capacityLabel = $event->capacity ? 'Maximum '.$event->capacity.' volunteers' : 'No volunteer limit';
                $search = strtolower($event->title.' '.$event->description.' '.$event->location.' '.$statusLabel.' '.$roles->join(' ').' '.$event->participation);
            @endphp
            <article data-volunteer-event data-search="{{ $search }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ $event->title }}</h2>
                            <x-ui.status-badge :label="$statusLabel" />
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $when }}@if(filled($event->location)) · {{ $event->location }}@endif</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $capacityLabel }}</p>
                    </div>
                    <button type="button"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200"
                            data-modal-id="modal-volunteer-event-details"
                            data-title="{{ $event->title }}"
                            data-when="{{ $when }}"
                            data-where="{{ $event->location }}"
                            data-status="{{ $statusLabel }}"
                            data-volunteers="{{ $capacityLabel }}"
                            data-note="{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', (string) $event->description), 400) }}"
                            data-duties='@json($duties)'
                            onclick="openVolunteerEventDetails(this)">View details</button>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No events match</p>
                <p class="mt-1 text-xs text-slate-400">Events appear here after you are assigned a task on one.</p>
            </div>
        @endforelse
        <p id="volunteer-event-search-empty" class="hidden rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-400 dark:border-slate-700 dark:bg-slate-900">No events match that search.</p>
    </section>
</div>

<x-ui.event-details-modal id="modal-volunteer-event-details" duties-heading="Your task assignments" />

<script>
    (function () {
        const search = document.getElementById('volunteer-event-search');
        if (!search) {
            return;
        }
        search.addEventListener('input', function () {
            const query = search.value.trim().toLowerCase();
            let shown = 0;
            document.querySelectorAll('[data-volunteer-event]').forEach(function (card) {
                const hit = query === '' || (card.dataset.search || '').includes(query);
                card.classList.toggle('hidden', !hit);
                if (hit) {
                    shown += 1;
                }
            });
            const empty = document.getElementById('volunteer-event-search-empty');
            if (empty) {
                empty.classList.toggle('hidden', shown > 0 || {{ $eventList->isEmpty() ? 'true' : 'false' }});
            }
        });
    })();
</script>
