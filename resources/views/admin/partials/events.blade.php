@php
    $eventRank = ['upcoming' => 0, 'ongoing' => 1, 'ended' => 2, 'completed' => 3, 'cancelled' => 4];
    $eventFilters = [
        'all' => 'All',
        'upcoming' => 'Upcoming',
        'ongoing' => 'Ongoing',
        'ended' => 'Ended',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
    $eventOverview = [
        ['key' => 'upcoming', 'label' => 'Upcoming', 'icon' => 'fa-calendar-day', 'iconBg' => 'bg-sky-50 dark:bg-sky-950', 'iconColor' => 'text-sky-600 dark:text-sky-300'],
        ['key' => 'ongoing', 'label' => 'Ongoing', 'icon' => 'fa-person-running', 'iconBg' => 'bg-teal-50 dark:bg-teal-950', 'iconColor' => 'text-teal-600 dark:text-teal-300'],
        ['key' => 'ended', 'label' => 'Ended', 'icon' => 'fa-flag-checkered', 'iconBg' => 'bg-slate-100 dark:bg-slate-800', 'iconColor' => 'text-slate-500 dark:text-slate-300'],
        ['key' => 'completed', 'label' => 'Completed', 'icon' => 'fa-circle-check', 'iconBg' => 'bg-emerald-50 dark:bg-emerald-950', 'iconColor' => 'text-emerald-600 dark:text-emerald-300'],
        ['key' => 'cancelled', 'label' => 'Cancelled', 'icon' => 'fa-ban', 'iconBg' => 'bg-rose-50 dark:bg-rose-950', 'iconColor' => 'text-rose-500 dark:text-rose-300'],
    ];
    $selectedOrganization = $organizations->firstWhere('id', $selectedEventOrg);
    $visibleEvents = $postedEvents
        ->sortBy(fn ($event) => sprintf('%d-%s', $eventRank[$event->oversightStatus()] ?? 9, optional($event->start_time)->format('Y-m-d H:i:s')))
        ->when($eventStatusFilter !== 'all', fn ($events) => $events->filter(fn ($event) => $event->oversightStatus() === $eventStatusFilter))
        ->when($selectedOrganization, fn ($events) => $events->filter(fn ($event) => (int) $event->organization_id === (int) $selectedOrganization->id))
        ->values();
@endphp

<div class="space-y-5">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="bg-gradient-to-r from-jci-blue to-jci-dark px-5 py-5 text-white sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/70">Oversight</p>
                    <h4 class="mt-1 text-xl font-extrabold tracking-tight">{{ number_format($postedEvents->count()) }} {{ \Illuminate\Support\Str::plural('event', $postedEvents->count()) }}</h4>
                    <p class="mt-1 text-sm text-white/75">Posted by approved organizations. Filter by status or open an organization.</p>
                </div>
                @if($selectedOrganization)
                    <a href="{{ route('admin.dashboard', array_filter(['tab' => 'events', 'event_status' => $eventStatusFilter === 'all' ? null : $eventStatusFilter])) }}" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-3 py-2 text-xs font-semibold text-white hover:bg-white/25">
                        <i class="fa-solid fa-xmark"></i> Clear organization
                    </a>
                @endif
            </div>
        </div>
    </section>

    <div class="grid grid-cols-2 gap-3 xl:grid-cols-5">
        @foreach($eventOverview as $card)
            <a href="{{ route('admin.dashboard', array_filter(['tab' => 'events', 'org' => $selectedEventOrg ?: null, 'event_status' => $card['key']])) }}"
               class="rounded-2xl border bg-white p-4 shadow-sm transition dark:bg-slate-900 {{ $eventStatusFilter === $card['key'] ? 'border-jci-blue ring-1 ring-jci-blue/20' : 'border-slate-200 hover:border-sky-300 dark:border-slate-800' }}">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }}">
                    <i class="fa-solid {{ $card['icon'] }} text-sm"></i>
                </span>
                <p class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ number_format($postedEvents->filter(fn ($event) => $event->oversightStatus() === $card['key'])->count()) }}</p>
                <p class="mt-0.5 text-xs font-medium text-slate-400">{{ $card['label'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2">
            @foreach($eventFilters as $key => $label)
                <a href="{{ route('admin.dashboard', array_filter(['tab' => 'events', 'org' => $selectedEventOrg ?: null, 'event_status' => $key === 'all' ? null : $key])) }}"
                   class="rounded-full px-3 py-1.5 text-xs font-bold transition {{ $eventStatusFilter === $key ? 'bg-jci-blue text-white' : 'border border-slate-200 bg-white text-slate-500 hover:border-sky-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <label class="relative w-full sm:w-64">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-300"></i>
            <input id="admin-event-search" type="search" placeholder="Search events or orgs..."
                   class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs text-slate-700 placeholder:text-slate-400 focus:border-jci-blue focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
        </label>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Organizations</h4>
                <p class="mt-0.5 text-xs text-slate-400">Select one to focus the list, or browse everything below.</p>
            </div>
            @if($selectedOrganization)
                <p class="text-xs font-semibold text-jci-blue">Showing {{ $selectedOrganization->name }}</p>
            @endif
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse($organizations as $org)
                @php
                    $orgEvents = $postedEvents->where('organization_id', $org->id);
                    $orgAccepted = $orgEvents->flatMap->tasks->flatMap->assignments->pluck('user_id')->unique()->count();
                    $isSelected = (int) $selectedEventOrg === (int) $org->id;
                    $orgInitials = collect(explode(' ', $org->name))->map(fn ($part) => substr($part, 0, 1))->take(2)->join('');
                @endphp
                <a href="{{ route('admin.dashboard', array_filter(['tab' => 'events', 'org' => $org->id, 'event_status' => $eventStatusFilter === 'all' ? null : $eventStatusFilter])) }}"
                   data-event-org-card
                   data-search="{{ strtolower(trim($org->name.' '.($org->organizationTypeLabel() ?? ''))) }}"
                   class="flex flex-col rounded-2xl border px-4 py-4 transition {{ $isSelected ? 'border-jci-blue bg-sky-50 shadow-sm dark:bg-sky-950/30' : 'border-slate-200 hover:border-sky-300 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800' }}">
                    <div class="flex items-start gap-3">
                        @if($org->profile_photo_path)
                            <img src="{{ asset('storage/'.$org->profile_photo_path) }}" alt="" class="h-10 w-10 rounded-xl object-cover">
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-jci-blue text-xs font-bold text-white">{{ strtoupper($orgInitials) }}</span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold text-slate-800 dark:text-slate-100">{{ $org->name }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-slate-400">{{ $org->organizationTypeLabel() ?? 'Type not set' }}</p>
                        </div>
                        <i class="fa-solid fa-chevron-right mt-1 text-[10px] {{ $isSelected ? 'text-jci-blue' : 'text-slate-300' }}"></i>
                    </div>
                    <div class="mt-3 flex items-center gap-3 text-xs text-slate-500">
                        <span><span class="font-bold text-slate-800 dark:text-slate-200">{{ $orgEvents->count() }}</span> events</span>
                        <span><span class="font-bold text-slate-800 dark:text-slate-200">{{ $orgAccepted }}</span> volunteers</span>
                    </div>
                </a>
            @empty
                <p class="text-sm text-slate-400">No organizations are registered yet.</p>
            @endforelse
        </div>
    </section>

    <div class="flex items-center justify-between gap-3">
        <div>
            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">
                {{ $selectedOrganization ? $selectedOrganization->name : 'All events' }}
            </h4>
            <p class="mt-0.5 text-xs text-slate-400">{{ $visibleEvents->count() }} {{ \Illuminate\Support\Str::plural('event', $visibleEvents->count()) }} in this view</p>
        </div>
    </div>

    @if($visibleEvents->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                <i class="fa-solid fa-calendar-xmark"></i>
            </span>
            <p class="mt-3 text-sm text-slate-400">No events match this filter.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2" id="admin-event-list">
            @foreach($visibleEvents as $event)
                @php
                    $accepted = $event->tasks->flatMap->assignments;
                    $volunteerCount = $accepted->pluck('user_id')->unique()->count();
                    $openSlots = $event->capacity === null ? null : max((int) $event->capacity - $volunteerCount, 0);
                    $volunteerLimit = $event->capacity === null
                        ? $volunteerCount.' volunteers · no slot limit'
                        : $volunteerCount.' of '.$event->capacity.' volunteers · '.$openSlots.' slots left';
                    $when = \App\Support\ScheduleClock::range($event->start_time, $event->end_time);
                    $statusLabel = $event->oversightLabel();
                    $duties = $event->tasks->map(function ($task) use ($event) {
                        $assignment = $task->assignments->first(fn ($row) => $row->status !== 'cancelled');

                        return [
                            'title' => $task->title,
                            'status' => $task->oversightLabel(),
                            'description' => \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', (string) ($task->description ?? '')), 180),
                            'assignee' => $assignment?->user?->name ?? 'Unassigned',
                            'due' => \App\Support\ScheduleClock::format($task->due_date ?? $event->start_time),
                            'hours' => $assignment && $assignment->status === 'completed'
                                ? number_format((float) $assignment->hours_logged, 1)
                                : '',
                        ];
                    })->values();
                    $tasksUrl = route('admin.dashboard', ['tab' => 'tasks', 'task_org' => $event->organization_id, 'task_event' => $event->id]);
                    $searchBlob = strtolower(trim($event->title.' '.($event->organization->name ?? '').' '.($event->location ?? '').' '.$statusLabel));
                @endphp
                <article data-event-card data-search="{{ $searchBlob }}" class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h5 class="font-bold leading-snug text-slate-900 dark:text-white">{{ $event->title }}</h5>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $event->organization->name ?? 'Organization removed' }}</p>
                        </div>
                        <x-ui.status-badge :label="$statusLabel" />
                    </div>
                    <div class="mt-3 space-y-1.5 text-xs text-slate-500">
                        <p><i class="fa-regular fa-clock mr-1.5 text-slate-300"></i>{{ $when }}</p>
                        <p><i class="fa-solid fa-location-dot mr-1.5 text-slate-300"></i>{{ $event->location }}</p>
                        <p><i class="fa-solid fa-users mr-1.5 text-slate-300"></i>{{ $volunteerLimit }}</p>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <span class="rounded-full bg-slate-50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-300">{{ $event->tasks->count() }} {{ \Illuminate\Support\Str::plural('task', $event->tasks->count()) }}</span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <button type="button"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-300"
                                data-modal-id="modal-admin-event-details"
                                data-title="{{ $event->title }}"
                                data-when="{{ $when }}"
                                data-where="{{ $event->location }}"
                                data-status="{{ $statusLabel }}"
                                data-volunteers="{{ $volunteerLimit }}"
                                data-organization="{{ $event->organization->name ?? 'Organization removed' }}"
                                data-note="{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', (string) $event->description), 400) }}"
                                data-duties='@json($duties)'
                                data-extra-html='<a href="{{ $tasksUrl }}" class="inline-block text-xs font-bold text-jci-blue hover:underline">Open this event in Tasks</a>'
                                onclick="openSharedEventDetails(this)">View details</button>
                        <a href="{{ $tasksUrl }}" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200">Tasks</a>
                    </div>
                </article>
            @endforeach
        </div>
        <p id="admin-event-empty" class="hidden py-8 text-center text-sm text-slate-400">No events match your search.</p>
        <x-ui.event-details-modal id="modal-admin-event-details" duties-heading="Task assignments" />
    @endif
</div>

@if($activeTab === 'events')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const search = document.getElementById('admin-event-search');
        const cards = Array.from(document.querySelectorAll('[data-event-card]'));
        const orgCards = Array.from(document.querySelectorAll('[data-event-org-card]'));
        const empty = document.getElementById('admin-event-empty');

        function applySearch() {
            const query = (search?.value || '').trim().toLowerCase();
            let visible = 0;
            cards.forEach(function (card) {
                const show = query === '' || (card.dataset.search || '').includes(query);
                card.classList.toggle('hidden', !show);
                if (show) visible += 1;
            });
            orgCards.forEach(function (card) {
                if (query === '') {
                    card.classList.remove('hidden');
                    return;
                }
                card.classList.toggle('hidden', !(card.dataset.search || '').includes(query));
            });
            if (empty) empty.classList.toggle('hidden', visible !== 0 || cards.length === 0);
        }

        search?.addEventListener('input', applySearch);
    });
</script>
@endif
