@php
    $clock = \App\Support\ScheduleClock::class;
    $duty = \App\Support\VolunteerDuty::class;
    $hour = (int) $clock::now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $org = $volunteer->primaryOrganization;
    $availabilityLabel = match ($volunteer->availability) {
        'inactive' => 'Unavailable',
        'limited' => 'Busy / Limited',
        default => 'Available',
    };
    $availabilityChip = match ($volunteer->availability) {
        'inactive' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        'limited' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200',
        default => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
    };
    $membershipApplication = $volunteer->pendingOrganizationApplication();
    $membershipOrganization = $membershipApplication?->organization;
    $firstName = collect(explode(' ', (string) $volunteer->name))->first() ?: $volunteer->name;

    $metrics = [
        ['Pending', $pendingResponses->count(), 'Need your response', 'fa-hourglass-half', route('volunteer.dashboard', ['section' => 'tasks', 'task_status' => 'pending']), $pendingResponses->count() > 0],
        ['Active tasks', $activeTasks->count(), 'Accepted and open', 'fa-list-check', route('volunteer.dashboard', ['section' => 'tasks']), false],
        ['Upcoming', $upcomingEvents->count(), $nextEvent ? $clock::format($nextEvent->start_time) : 'None scheduled', 'fa-calendar-days', route('volunteer.dashboard', ['section' => 'events']), false],
        ['Hours', number_format($verifiedHours, 1), 'Approved service hours', 'fa-clock', route('volunteer.dashboard', ['section' => 'certificates']), false],
        ['Certificates', $certificates->count(), $certificatesThisYear.' this year', 'fa-certificate', route('volunteer.dashboard', ['section' => 'certificates']), false],
    ];
@endphp

<div class="space-y-5">
    {{-- Welcome --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="bg-gradient-to-r from-jci-blue via-sky-500 to-jci-dark px-5 py-5 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0 text-white">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-sky-100/90">{{ $greeting }}</p>
                    <h2 class="mt-1 truncate text-2xl font-extrabold tracking-tight">{{ $firstName }}</h2>
                    <p class="mt-1 text-sm text-sky-100/90">
                        @if($org)
                            {{ $org->name }}
                        @elseif($membershipOrganization)
                            Applying to {{ $membershipOrganization->name }}
                        @else
                            Your volunteer workspace
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $availabilityChip }}">{{ $availabilityLabel }}</span>
                    <a href="{{ route('volunteer.dashboard', ['section' => 'availability']) }}" class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold text-white hover:bg-white/25">Update</a>
                    <a href="{{ route('volunteer.dashboard', ['section' => 'messages']) }}" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-jci-blue hover:bg-sky-50">Messages</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Membership --}}
    @if($membershipApplication && $membershipOrganization)
        <section class="rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 dark:border-sky-900 dark:bg-sky-950/40">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Membership with {{ $membershipOrganization->name }}</p>
                    @if($membershipApplication->status === 'interview_scheduled' && $membershipApplication->interview_at)
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                            Interview: {{ $clock::stamp($membershipApplication->interview_at) }}
                            @if(filled($membershipApplication->interview_place)) · {{ $membershipApplication->interview_place }}@endif
                        </p>
                    @else
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Waiting for a personal interview before membership is approved.</p>
                    @endif
                </div>
                <x-ui.status-badge :label="$membershipApplication->statusLabel()" />
            </div>
        </section>
    @elseif(!$volunteer->organization_id)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 dark:border-amber-900 dark:bg-amber-950/30">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">No organization membership yet</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Contact an organization to start a membership interview, or ask in Messages once you are linked.</p>
        </section>
    @endif

    {{-- Metrics --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach($metrics as [$label, $value, $caption, $icon, $href, $urgent])
            <a href="{{ $href }}" class="rounded-2xl border bg-white p-4 shadow-sm transition hover:border-jci-blue dark:bg-slate-900 {{ $urgent ? 'border-amber-300 dark:border-amber-800' : 'border-slate-200 dark:border-slate-800' }}">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                    <i class="fa-solid {{ $icon }} text-xs {{ $urgent ? 'text-amber-500' : 'text-jci-blue' }}"></i>
                </div>
                <p class="mt-2 text-2xl font-extrabold text-slate-900 dark:text-white">{{ $value }}</p>
                <p class="mt-1 text-[11px] text-slate-400">{{ $caption }}</p>
            </a>
        @endforeach
    </div>

    {{-- Next event spotlight --}}
    @if($nextEvent)
        @php
            $matchedNext = $myEvents->firstWhere('id', $nextEvent->id);
            $nextWhen = $clock::range($nextEvent->start_time, $nextEvent->end_time);
            $nextDuties = collect($matchedNext?->duty_details ?? []);
            $nextPhase = $clock::phase($nextEvent->start_time, $nextEvent->end_time, $nextEvent->status);
            $nextPhaseLabel = $nextPhase === 'now' ? 'Happening now' : 'Up next';
        @endphp
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-jci-blue">{{ $nextPhaseLabel }}</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{{ $nextEvent->title }}</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $nextWhen }}
                        @if(filled($nextEvent->location)) · {{ $nextEvent->location }}@endif
                    </p>
                    @if($nextDuties->isNotEmpty())
                        <p class="mt-2 text-xs text-slate-400">
                            Your role{{ $nextDuties->count() === 1 ? '' : 's' }}:
                            {{ $nextDuties->pluck('title')->take(3)->join(', ') }}
                        </p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200"
                            data-modal-id="modal-volunteer-home-event-details"
                            data-title="{{ $nextEvent->title }}"
                            data-when="{{ $nextWhen }}"
                            data-where="{{ $nextEvent->location }}"
                            data-status="{{ $matchedNext?->status_label ?? $nextEvent->oversightLabel() }}"
                            data-volunteers="{{ $nextEvent->capacity ? 'Maximum '.$nextEvent->capacity.' volunteers' : 'No volunteer limit' }}"
                            data-note="{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', (string) $nextEvent->description), 400) }}"
                            data-duties='@json($nextDuties)'
                            onclick="openSharedEventDetails(this)">Details</button>
                    <a href="{{ route('volunteer.dashboard', ['section' => 'events']) }}" class="inline-flex items-center rounded-xl bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark">My Events</a>
                </div>
            </div>
        </section>
    @endif

    {{-- Priority panels --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Needs your response</h2>
                    <p class="mt-0.5 text-xs text-slate-400">Accept or decline assigned roles</p>
                </div>
                <a href="{{ route('volunteer.dashboard', ['section' => 'tasks']) }}" class="text-xs font-semibold text-jci-blue hover:underline">All tasks</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($pendingResponses->take(5) as $assign)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $assign->task->title }}</p>
                            <p class="text-xs text-slate-400">{{ $assign->event->title }} · {{ $clock::format($assign->task->due_date ?? $assign->event->start_time) }}</p>
                        </div>
                        <a href="{{ route('volunteer.dashboard', ['section' => 'tasks']) }}" class="shrink-0 rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-[11px] font-semibold text-amber-800 hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">Respond</a>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-400">You're caught up — nothing waiting for a response.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Upcoming events</h2>
                    <p class="mt-0.5 text-xs text-slate-400">From your accepted assignments</p>
                </div>
                <a href="{{ route('volunteer.dashboard', ['section' => 'events']) }}" class="text-xs font-semibold text-jci-blue hover:underline">View all</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($upcomingEvents->take(4) as $event)
                    @php
                        $matched = $myEvents->firstWhere('id', $event->id);
                        $statusLabel = $matched?->status_label ?? $event->oversightLabel();
                        $when = $clock::range($event->start_time, $event->end_time);
                        $capacityLabel = $event->capacity ? 'Maximum '.$event->capacity.' volunteers' : 'No volunteer limit';
                        $duties = collect($matched?->duty_details ?? []);
                    @endphp
                    <div class="flex flex-wrap items-start justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $event->title }}</p>
                            <p class="text-xs text-slate-400">{{ $when }}{{ $event->location ? ' · '.$event->location : '' }}</p>
                        </div>
                        <button type="button"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200"
                                data-modal-id="modal-volunteer-home-event-details"
                                data-title="{{ $event->title }}"
                                data-when="{{ $when }}"
                                data-where="{{ $event->location }}"
                                data-status="{{ $statusLabel }}"
                                data-volunteers="{{ $capacityLabel }}"
                                data-note="{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', (string) $event->description), 400) }}"
                                data-duties='@json($duties)'
                                onclick="openSharedEventDetails(this)">Details</button>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-400">No upcoming events on your accepted assignments.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Notifications</h2>
                    <p class="mt-0.5 text-xs text-slate-400">
                        @if($unreadNoticeCount > 0)
                            {{ $unreadNoticeCount }} unread
                        @else
                            You're caught up
                        @endif
                    </p>
                </div>
                <a href="{{ route('volunteer.dashboard', ['section' => 'notifications']) }}" class="text-xs font-semibold text-jci-blue hover:underline">Open</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($notices->take(5) as $notice)
                    @php $data = $notice->data; @endphp
                    <a href="{{ route('volunteer.dashboard', ['section' => 'notifications']) }}" class="block py-3 transition hover:opacity-80">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $data['title'] ?? 'Notification' }}</p>
                            @unless($notice->read_at)
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-jci-blue"></span>
                            @endunless
                        </div>
                        <p class="mt-0.5 text-xs text-slate-400">{{ $clock::stamp($notice->created_at) }}</p>
                    </a>
                @empty
                    <p class="py-8 text-center text-sm text-slate-400">No notifications yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Recent activity</h2>
                    <p class="mt-0.5 text-xs text-slate-400">Latest updates on your assignments</p>
                </div>
                <a href="{{ route('volunteer.dashboard', ['section' => 'tasks']) }}" class="text-xs font-semibold text-jci-blue hover:underline">Tasks</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($recentActivity as $assign)
                    <div class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $assign->task->title ?? 'Task' }}</p>
                            <p class="text-xs text-slate-400">{{ $assign->event->title ?? 'Event' }} · {{ $clock::stamp($assign->updated_at) }}</p>
                        </div>
                        <x-ui.status-badge :label="$duty::label($assign->status, $assign->schedule_phase, $assign->feedback)" />
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-400">No activity yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Shortcuts --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Quick links</h2>
        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
            @foreach([
                ['Tasks', 'tasks', 'fa-list-check'],
                ['Events', 'events', 'fa-calendar-days'],
                ['Skills', 'skills', 'fa-wand-magic-sparkles'],
                ['Certificates', 'certificates', 'fa-certificate'],
                ['Availability', 'availability', 'fa-clock'],
                ['Settings', 'settings', 'fa-gear'],
            ] as [$label, $sectionName, $icon])
                <a href="{{ route('volunteer.dashboard', ['section' => $sectionName]) }}"
                   class="flex flex-col items-center gap-2 rounded-xl border border-slate-100 px-3 py-3 text-center transition hover:border-jci-blue hover:bg-sky-50/60 dark:border-slate-800 dark:hover:bg-slate-800">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-jci-blue dark:bg-sky-950/50">
                        <i class="fa-solid {{ $icon }} text-sm"></i>
                    </span>
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <x-ui.event-details-modal id="modal-volunteer-home-event-details" duties-heading="Your task assignments" />
</div>
