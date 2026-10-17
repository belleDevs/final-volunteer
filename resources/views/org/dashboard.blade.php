<x-layout.org :active="$section">
    @if($section === 'dashboard')
        <div class="space-y-6">
            @php
                $summaryCards = [
                    ['Total Events', $myEventsCount, 'All event records', 'fa-calendar'],
                    ['Upcoming Events', $upcomingEventsCount, 'Start date still ahead', 'fa-calendar-day'],
                    ['Active Tasks', $activeTasksCount, 'Not yet completed', 'fa-list-check'],
                    ['Assigned Volunteers', $assignedVolunteersCount, 'Across your events', 'fa-user-group'],
                    ['Completed Tasks', $completedTasksCount, $completionRate.'% completion', 'fa-circle-check'],
                    ['Membership Applicants', ($membershipApplications ?? collect())->count(), 'Need interview / approval', 'fa-user-clock'],
                ];
            @endphp
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($summaryCards as [$label, $value, $caption, $icon])
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ $label }}</p>
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-jci-blue dark:bg-sky-950">
                                <i class="fa-solid {{ $icon }} text-sm"></i>
                            </span>
                        </div>
                        <p class="mt-4 text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ number_format($value) }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $caption }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 xl:col-span-2">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Upcoming Events</h2>
                        <a href="{{ route('org.dashboard', ['section' => 'events']) }}" class="text-xs font-semibold text-jci-blue hover:text-jci-dark">View all</a>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse($dashboardUpcoming as $event)
                            @php
                                $registered = $event->assignments->where('status', '!=', 'cancelled')->pluck('user_id')->filter()->unique()->count();
                                $volunteerLimit = $event->capacity ? $registered.' of '.$event->capacity.' volunteers' : 'No volunteer limit';
                                $statusLabel = $event->oversightLabel();
                                $when = \App\Support\ScheduleClock::range($event->start_time, $event->end_time);
                                $duties = $event->tasks->map(function ($task) use ($event) {
                                    $assignment = $event->assignments->first(fn ($row) => (int) $row->task_id === (int) $task->id && $row->status !== 'cancelled');

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
                            @endphp
                            <article class="rounded-2xl border border-slate-100 p-4 dark:border-slate-800">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $event->title }}</h3>
                                    <x-ui.status-badge :label="$statusLabel" />
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $when }} · {{ $event->location }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $volunteerLimit }}</p>
                                <button type="button"
                                        class="mt-3 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200"
                                        data-modal-id="modal-event-details"
                                        data-title="{{ $event->title }}"
                                        data-when="{{ $when }}"
                                        data-where="{{ $event->location }}"
                                        data-status="{{ $statusLabel }}"
                                        data-volunteers="{{ $volunteerLimit }}"
                                        data-note="{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', (string) $event->description), 400) }}"
                                        data-duties='@json($duties)'
                                        onclick="openEventDetails(this)">View details</button>
                            </article>
                        @empty
                            <div class="py-10 text-center">
                                <p class="text-sm text-slate-400">No upcoming events yet.</p>
                                <button type="button" onclick="openCreateEvent()" class="mt-3 text-xs font-semibold text-jci-blue hover:text-jci-dark">Create Event</button>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Task Progress</h2>
                    <p class="mt-1 text-xs text-slate-400">Tasks by status, from your events</p>
                    @php
                        $bars = [
                            ['Pending', $taskStatusCounts['pending'], 'bg-slate-300'],
                            ['Assigned', $taskStatusCounts['assigned'], 'bg-jci-blue'],
                            ['In Progress', $taskStatusCounts['in_progress'], 'bg-amber-400'],
                            ['Completed', $taskStatusCounts['completed'], 'bg-emerald-500'],
                            ['Overdue', $taskStatusCounts['overdue'], 'bg-rose-500'],
                        ];
                        $barMax = max(1, collect($bars)->max(fn ($bar) => $bar[1]));
                    @endphp
                    @if($dashboardTasks->count() === 0)
                        <p class="py-12 text-center text-sm text-slate-400">No tasks recorded yet.</p>
                    @else
                        <div class="mt-4 flex h-40 items-end gap-2">
                            @foreach($bars as [$barLabel, $barValue, $barColor])
                                <div class="flex h-full flex-1 flex-col items-center justify-end">
                                    <span class="mb-1 text-[10px] font-semibold text-slate-500">{{ $barValue }}</span>
                                    <div class="w-full rounded-t-lg {{ $barColor }}" style="height: {{ max(6, (int) round(($barValue / $barMax) * 100)) }}%"></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-2 grid grid-cols-5 gap-1 text-center text-[9px] font-medium leading-tight text-slate-400">
                            @foreach($bars as [$barLabel])
                                <span>{{ $barLabel }}</span>
                            @endforeach
                        </div>
                        <p class="mt-3 text-[10px] text-slate-400">Overdue is a deadline flag. Those tasks also stay in their saved status.</p>
                    @endif
                </section>
            </div>

            <section id="task-overview" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Task Overview</h2>
                        <p class="mt-1 text-xs text-slate-400">Open tasks on your events, with deadlines and assigned volunteers.</p>
                    </div>
                    <a href="{{ route('org.dashboard', ['section' => 'tasks']) }}" class="rounded-xl bg-jci-blue px-3.5 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Manage Tasks</a>
                </div>
                @if($completionReviews->isNotEmpty())
                    <a href="{{ route('org.dashboard', ['section' => 'certificates']) }}#submitted-tasks" class="mt-3 block rounded-xl bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                        {{ $completionReviews->count() }} submitted {{ $completionReviews->count() === 1 ? 'task is' : 'tasks are' }} waiting for completion verification.
                    </a>
                @endif
                <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($openDashboardTasks as $task)
                        <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $task->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $task->event->title ?? 'Event' }} · Due {{ $task->due_date ? \App\Support\OrgPreferences::format($task->due_date, $organization, true) : 'no deadline' }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $task->assignedVolunteerNames() }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <x-ui.status-badge :label="$task->oversightLabel()" />
                                @if($task->isOverdue())
                                    <x-ui.status-badge label="Overdue" />
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-400">No open tasks. Create one and link it to an event.</p>
                    @endforelse
                </div>
            </section>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Volunteer Overview</h2>
                        <a href="{{ route('org.dashboard', ['section' => 'volunteers']) }}" class="rounded-xl bg-jci-blue px-3.5 py-2 text-xs font-semibold text-white hover:bg-jci-dark">View Volunteers</a>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                            <p class="text-xl font-semibold text-slate-900 dark:text-white">{{ $assignedVolunteersCount }}</p>
                            <p class="mt-1 text-[10px] uppercase tracking-wider text-slate-400">Assigned</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                            <p class="text-xl font-semibold text-slate-900 dark:text-white">{{ $taskApplications->count() }}</p>
                            <p class="mt-1 text-[10px] uppercase tracking-wider text-slate-400">New applications</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                            <p class="text-xl font-semibold text-slate-900 dark:text-white">{{ $awaitingAssignmentCount }}</p>
                            <p class="mt-1 text-[10px] uppercase tracking-wider text-slate-400">Awaiting assignment</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Recently joined</p>
                        <div class="mt-2 space-y-2">
                            @forelse($recentVolunteers as $member)
                                @php
                                    $availabilityLabel = $member->availability === 'inactive' ? 'Unavailable' : ($member->status === 'pending' ? 'Pending' : 'Available');
                                @endphp
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-800 dark:text-slate-100">{{ $member->name }}</p>
                                        <p class="truncate text-[11px] text-slate-400">{{ $member->skills->pluck('name')->take(3)->join(', ') ?: 'No skills listed' }}</p>
                                    </div>
                                    <x-ui.status-badge :label="$availabilityLabel" />
                                </div>
                            @empty
                                <p class="text-sm text-slate-400">No volunteers have joined this organization yet.</p>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent Activities</h2>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse($recentActivities as $activity)
                            <div class="border-b border-slate-100 pb-3 last:border-b-0 last:pb-0 dark:border-slate-800">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $activity['title'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $activity['at']?->diffForHumans() }}@if($activity['detail']) · {{ $activity['detail'] }}@endif</p>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-slate-400">No recorded activity for this organization yet.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Notifications & Reminders</h2>
                    <a href="{{ route('org.dashboard', ['section' => 'notifications']) }}" class="text-xs font-semibold text-jci-blue hover:text-jci-dark">View all</a>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                    @forelse($reminders as $reminder)
                        @php
                            $tone = match ($reminder['tone']) {
                                'rose' => 'border-rose-100 bg-rose-50/70',
                                'amber' => 'border-amber-100 bg-amber-50/70',
                                'blue' => 'border-blue-100 bg-blue-50/60',
                                default => 'border-slate-100 bg-slate-50',
                            };
                        @endphp
                        <article class="rounded-xl border px-4 py-3 {{ $tone }}">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-800">{{ $reminder['title'] }}</p>
                                <span class="shrink-0 text-[11px] text-slate-400">{{ $reminder['when'] }}</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-600">{{ $reminder['detail'] }}</p>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-400 md:col-span-2">No alerts right now.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @elseif($section === 'events')
        @php
            $clock = \App\Support\ScheduleClock::class;
            $eventFilters = [
                'all' => 'All',
                'upcoming' => 'Upcoming',
                'ongoing' => 'Ongoing',
                'ended' => 'Ended',
                'cancelled' => 'Cancelled',
                'completed' => 'Archived',
            ];
            $eventGroups = [
                ['key' => 'current', 'title' => null, 'events' => $events->where('status', '!=', 'completed')->sortByDesc('start_time')->values()],
                ['key' => 'archived', 'title' => 'Archived', 'events' => $events->where('status', 'completed')->sortByDesc('start_time')->values()],
            ];
        @endphp
        <div class="space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative min-w-0 flex-1 sm:max-w-md">
                    <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-300"></i>
                    <input id="org-event-search" type="search" placeholder="Search events" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-jci-blue/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                </div>
                <button type="button" onclick="openCreateEvent()" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-sky-500/20 hover:bg-jci-dark">
                    <i class="fa-solid fa-plus text-xs"></i> Create Event
                </button>
            </div>

            <div id="org-event-filters" class="flex flex-wrap gap-2">
                @foreach($eventFilters as $key => $label)
                    <button type="button" data-event-filter="{{ $key }}" class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $key === 'all' ? 'bg-jci-blue text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-jci-blue dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700' }}">{{ $label }}</button>
                @endforeach
            </div>

            <div class="space-y-3">
                @if($events->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No events yet</p>
                        <p class="mt-1 text-xs text-slate-400">Create an event, then add its tasks from Task Assignment.</p>
                    </div>
                @else
                    @foreach($eventGroups as $group)
                        @continue($group['events']->isEmpty())
                        @if($group['title'])
                            <div data-event-group="archived" class="pt-2">
                                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Archived</h2>
                                <p class="mt-1 text-xs text-slate-500">These leave Task Assignment until you restore them.</p>
                            </div>
                        @endif
                        @foreach($group['events'] as $event)
                            @php
                                $eventStatus = $event->oversightStatus();
                                $eventStatusLabel = $event->oversightLabel();
                                $filledVolunteers = $event->assignments->where('status', '!=', 'cancelled')->pluck('user_id')->filter()->unique()->count();
                                $volunteerLimit = $event->capacity ? $filledVolunteers.' of '.$event->capacity.' volunteers' : 'No volunteer limit';
                                $when = $clock::range($event->start_time, $event->end_time);
                                $duties = $event->tasks->map(function ($task) use ($event, $clock) {
                                    $assignment = $event->assignments->first(fn ($row) => (int) $row->task_id === (int) $task->id && $row->status !== 'cancelled');

                                    return [
                                        'title' => $task->title,
                                        'status' => $task->oversightLabel(),
                                        'description' => \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', (string) ($task->description ?? '')), 180),
                                        'assignee' => $assignment?->user?->name ?? 'Unassigned',
                                        'due' => $clock::format($task->due_date ?? $event->start_time),
                                        'hours' => $assignment && $assignment->status === 'completed'
                                            ? number_format((float) $assignment->hours_logged, 1)
                                            : '',
                                    ];
                                })->values();
                                $eventSearch = strtolower($event->title.' '.$event->description.' '.$event->location.' '.$eventStatusLabel.' '.$volunteerLimit.' '.$duties->pluck('title')->join(' ').' '.$duties->pluck('assignee')->join(' '));
                                $eventPayload = [
                                    'updateUrl' => route('org.events.update', $event),
                                    'title' => $event->title,
                                    'location' => $event->location,
                                    'start' => $event->start_time->format('Y-m-d\TH:i'),
                                    'end' => $event->end_time->format('Y-m-d\TH:i'),
                                    'capacity' => $event->capacity,
                                    'description' => $event->description,
                                    'status' => $event->status,
                                ];
                            @endphp
                            <article data-event-card data-filter="{{ $eventStatus }}" data-search="{{ $eventSearch }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ $event->title }}</h2>
                                            <x-ui.status-badge :label="$eventStatusLabel" />
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">{{ $when }}@if(filled($event->location)) · {{ $event->location }}@endif</p>
                                        <p class="mt-1 text-xs text-slate-400">{{ $volunteerLimit }}</p>
                                        <button type="button"
                                                class="mt-3 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200"
                                                data-modal-id="modal-event-details"
                                                data-title="{{ $event->title }}"
                                                data-when="{{ $when }}"
                                                data-where="{{ $event->location }}"
                                                data-status="{{ $eventStatusLabel }}"
                                                data-volunteers="{{ $volunteerLimit }}"
                                                data-note="{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', (string) $event->description), 400) }}"
                                                data-duties='@json($duties)'
                                                onclick="openEventDetails(this)">View details</button>
                                    </div>
                                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                                        <script type="application/json" id="event-payload-{{ $event->id }}">@json($eventPayload)</script>
                                        @if($event->status === 'completed')
                                            <form action="{{ route('org.events.restore', $event) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200">Restore</button>
                                            </form>
                                        @elseif($event->status !== 'cancelled')
                                            <form action="{{ route('org.events.archive', $event) }}" method="POST" onsubmit="return confirm('Move this event to Archived? It will leave Task Assignment.');">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200">Archive</button>
                                            </form>
                                        @endif
                                        <button type="button" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200" data-event-id="{{ $event->id }}" onclick="openEditEvent(this)">
                                            <i class="fa-regular fa-pen-to-square"></i> Edit
                                        </button>
                                        <form action="{{ route('org.events.destroy', $event) }}" method="POST" onsubmit="return confirm('Delete this event? Its tasks will be removed with it.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-rose-300 hover:text-rose-600 dark:border-slate-700 dark:text-slate-200">
                                                <i class="fa-regular fa-trash-can"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    @endforeach
                @endif
                <p id="org-event-search-empty" class="hidden rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-400 dark:border-slate-700 dark:bg-slate-900">No events match this filter.</p>
            </div>
        </div>
    @elseif($section === 'tasks')
        @php
            $roleChoices = \App\Support\RoleCatalog::roles();
            $taskEvents = $events->whereNotIn('status', ['completed', 'cancelled'])->sortByDesc('start_time')->values();
        @endphp
        <div class="space-y-5">
            <div class="flex justify-end">
                <button type="button" onclick="document.getElementById('modal-new-role').classList.remove('hidden')" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-sky-500/20 hover:bg-jci-dark">
                    <i class="fa-solid fa-plus text-xs"></i> New Task
                </button>
            </div>

            @forelse($taskEvents as $event)
                @php
                    $eventTasks = $event->tasks->sortBy(fn ($task) => $task->due_date?->timestamp ?? $event->start_time?->timestamp ?? 0)->values();
                    $eventStatusLabel = $event->oversightLabel();
                    $filledVolunteers = $event->assignments->where('status', '!=', 'cancelled')->pluck('user_id')->filter()->unique()->count();
                    $volunteerLimit = $event->capacity ? $filledVolunteers.' of '.$event->capacity.' volunteers' : 'No volunteer limit';
                @endphp
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ $event->title }}</h2>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $event->start_time->format('M j, Y') }} · {{ $event->location }} · {{ $volunteerLimit }}</p>
                        </div>
                        <x-ui.status-badge :label="$eventStatusLabel" />
                    </div>
                    @if($eventTasks->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-slate-400">No roles added for this event yet. Add only the roles this event needs.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="text-xs text-slate-400">
                                    <tr class="border-b border-slate-100 dark:border-slate-800">
                                        <th class="px-5 py-3 font-medium">Task</th>
                                        <th class="px-5 py-3 font-medium">Assigned Volunteer</th>
                                        <th class="px-5 py-3 font-medium">Deadline</th>
                                        <th class="px-5 py-3 font-medium">Status</th>
                                        <th class="px-5 py-3 font-medium">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($eventTasks as $task)
                                        @php
                                            $assignment = $event->assignments->first(fn ($row) => (int) $row->task_id === (int) $task->id && $row->status !== 'cancelled');
                                            $assignedName = $assignment?->user?->name;
                                            if ($assignment && $assignment->status === 'pending' && $assignedName) {
                                                $assignedName .= ' · waiting to accept';
                                            }
                                            $statusLabel = $task->oversightLabel();
                                        @endphp
                                        <tr class="border-b border-slate-100 last:border-b-0 dark:border-slate-800">
                                            <td class="px-5 py-4 font-medium text-slate-800 dark:text-slate-100">
                                                {{ $task->title }}
                                                @php
                                                    $relatedSkills = collect(\App\Support\RoleCatalog::relatedSkillNames($task->title));
                                                    if ($relatedSkills->isEmpty()) {
                                                        $relatedSkills = $task->skills->pluck('name');
                                                    }
                                                @endphp
                                                @if($relatedSkills->isNotEmpty())
                                                    <p class="mt-0.5 text-[11px] font-normal text-slate-400">Matches {{ $relatedSkills->join(', ') }}</p>
                                                @endif
                                            </td>
                                            <td data-assigned-name data-saved-name="{{ $assignedName ?: 'Unassigned' }}" class="px-5 py-4 text-slate-600 dark:text-slate-300">{{ $assignedName ?: 'Unassigned' }}</td>
                                            <td class="px-5 py-4 text-slate-600 dark:text-slate-300">{{ ($task->due_date ?? $event->start_time)->format('M j, Y') }}</td>
                                            <td class="px-5 py-4">
                                                <x-ui.status-badge :label="$statusLabel" />
                                                @if($task->isOverdue())
                                                    <x-ui.status-badge label="Overdue" />
                                                @endif
                                            </td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $roster = $assignableVolunteers
                                                        ->filter(function ($volunteer) use ($assignment) {
                                                            return (int) $volunteer->organization_id === (int) Auth::id()
                                                                || ($assignment && (int) $assignment->user_id === (int) $volunteer->id);
                                                        })
                                                        ->map(function ($volunteer) use ($task) {
                                                            $matchedSkills = $task->matchedSkillsFor($volunteer);

                                                            return [
                                                                'volunteer' => $volunteer,
                                                                'skills' => $matchedSkills->pluck('name')->join(', '),
                                                                'matched' => $matchedSkills->isNotEmpty(),
                                                            ];
                                                        })
                                                        ->sort(function ($left, $right) {
                                                            if ($left['matched'] !== $right['matched']) {
                                                                return $left['matched'] ? -1 : 1;
                                                            }

                                                            return strcasecmp($left['volunteer']->name, $right['volunteer']->name);
                                                        })
                                                        ->values();
                                                    $matchedVolunteers = $roster->where('matched', true)->values();
                                                    $canReassign = $assignment && !in_array($task->status, ['completed', 'cancelled'], true);
                                                    $assignChoices = $canReassign ? $roster : $matchedVolunteers;
                                                    $assignRoster = $assignChoices->map(fn ($match) => [
                                                        'id' => $match['volunteer']->id,
                                                        'name' => $match['volunteer']->name,
                                                        'skills' => $match['skills'],
                                                        'matched' => $match['matched'],
                                                    ])->values();
                                                @endphp
                                                <div class="flex items-center gap-2">
                                                @if($canReassign || (!$assignment && $matchedVolunteers->isNotEmpty()))
                                                    <script type="application/json" id="assign-roster-{{ $task->id }}">@json($assignRoster)</script>
                                                    <button type="button"
                                                            class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                                            data-assign-open
                                                            data-task-id="{{ $task->id }}"
                                                            data-task-title="{{ $task->title }}"
                                                            data-event-title="{{ $event->title }}"
                                                            data-current-id="{{ $assignment->user_id ?? '' }}"
                                                            data-current-name="{{ $assignment?->user?->name ?? '' }}"
                                                            data-mode="{{ $canReassign ? 'reassign' : 'assign' }}">
                                                        {{ $canReassign ? 'Reassign' : 'Assign' }}
                                                    </button>
                                                @elseif(!$assignment)
                                                    <span class="text-xs text-slate-400">No volunteer matches this role</span>
                                                @endif
                                                @if($task->status !== 'completed')
                                                    <form action="{{ route('org.tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Remove this task?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 hover:border-rose-300 hover:text-rose-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">Delete</button>
                                                    </form>
                                                @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No current events to assign</p>
                    <p class="mt-1 text-xs text-slate-400">Completed events are in Archived on the Events page.</p>
                </div>
            @endforelse

            @if($taskApplications->isNotEmpty())
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Pending applications</h2>
                    <p class="mt-1 text-xs text-slate-400">Volunteers who asked for a role. Approving assigns them to that task.</p>
                    <div class="mt-4 space-y-3">
                        @foreach($taskApplications as $application)
                            <div class="flex flex-col gap-3 rounded-xl border border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $application->user->name ?? 'Volunteer' }}</p>
                                    <p class="text-xs text-slate-400">{{ $application->task->title ?? 'Role' }} · {{ $application->event->title ?? 'Event' }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <form action="{{ route('org.applications.approve', $application->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Approve</button>
                                    </form>
                                    <form action="{{ route('org.applications.reject', $application->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="feedback" value="This application was not selected for the role.">
                                        <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-rose-300 hover:text-rose-600">Reject</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <div id="assign-volunteer-dialog" class="fixed inset-0 z-[999] hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="assign-dialog-title">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 id="assign-dialog-title" class="text-base font-semibold text-slate-900 dark:text-white">Assign volunteer</h3>
                        <p id="assign-dialog-context" class="mt-1 text-sm text-slate-500"></p>
                    </div>
                    <button type="button" data-close-assign class="rounded-lg p-1 text-slate-400 hover:text-slate-600" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <form id="assign-volunteer-form" action="{{ route('org.assignments.store') }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="task_id" id="assign-task-id">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Volunteer
                        <select name="user_id" id="assign-user" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium normal-case tracking-normal text-slate-800 focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"></select>
                    </label>
                    <p id="assign-dialog-hint" class="text-xs text-slate-400"></p>
                    <div class="flex justify-end gap-2">
                        <button type="button" data-close-assign class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                        <button type="submit" id="assign-save" disabled class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <x-ui.modal id="modal-new-role" title="New Task">
            @if($taskEvents->where('status', '!=', 'cancelled')->isEmpty())
                <p class="text-sm text-slate-500">Create a current event first, then add the roles it needs.</p>
            @elseif($assignableVolunteers->isEmpty())
                <p class="text-sm text-slate-500">Approve at least one linked volunteer before creating and assigning a task.</p>
            @else
                @php
                    $newTaskVolunteerRoster = $assignableVolunteers->map(fn ($volunteer) => [
                        'id' => $volunteer->id,
                        'name' => $volunteer->name,
                        'skills' => $volunteer->skills->pluck('name')->values()->all(),
                    ])->values();
                @endphp
                <script type="application/json" id="new-task-volunteer-roster">@json($newTaskVolunteerRoster)</script>
                <form action="{{ route('org.tasks.store') }}" method="POST" class="space-y-4" id="new-task-form">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Event</label>
                        <select name="event_id" required class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                            @foreach($taskEvents->where('status', '!=', 'cancelled') as $eventOption)
                                <option value="{{ $eventOption->id }}">{{ $eventOption->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Role</label>
                        <select name="role" id="new-task-role" required onchange="toggleCustomRole(this); refreshNewTaskVolunteers();" class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                            <option value="" selected disabled>Choose a role for this event</option>
                            @foreach($roleChoices as $roleName => $relatedSkillNames)
                                <option value="{{ $roleName }}" data-skills="{{ implode(', ', $relatedSkillNames) }}">{{ $roleName }}</option>
                            @endforeach
                            <option value="__custom__">Add a role</option>
                        </select>
                    </div>
                    <div id="custom-role-field" class="hidden">
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">New role</label>
                        <input id="custom-role-input" name="custom_role" type="text" maxlength="255" placeholder="Role this event needs" oninput="refreshNewTaskVolunteers()" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Assign volunteer</label>
                        <select name="user_id" id="new-task-user" required class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                            <option value="" selected disabled>Choose a volunteer</option>
                        </select>
                        <p id="new-task-assign-hint" class="mt-1.5 text-[10px] text-slate-400">Matched volunteers are listed first. You can still pick someone else if needed.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Deadline</label>
                        <input name="due_date" type="datetime-local" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                    </div>
                    <p id="role-skill-hint" class="text-[10px] text-slate-400">Create the role and assign a volunteer in one step. You can reassign later from the task list.</p>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('modal-new-role').classList.add('hidden')" class="rounded-xl bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700">Cancel</button>
                        <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-bold text-white hover:bg-jci-dark">Create &amp; assign</button>
                    </div>
                </form>
            @endif
        </x-ui.modal>
    @elseif($section === 'notifications')
        @php
            $noticeFilters = [
                'all' => 'All',
                'unread' => 'Unread',
                'event' => 'Events',
                'task' => 'Tasks',
                'volunteer' => 'Volunteers',
                'account' => 'Account',
                'certificate' => 'Certificates',
                'system' => 'System',
            ];
            $noticeIcons = [
                'event' => 'fa-calendar',
                'task' => 'fa-list-check',
                'volunteer' => 'fa-user',
                'account' => 'fa-id-card',
                'certificate' => 'fa-certificate',
                'system' => 'fa-bell',
            ];
        @endphp
        <div class="space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form method="GET" action="{{ route('org.dashboard') }}" class="w-full sm:max-w-md">
                    <input type="hidden" name="section" value="notifications">
                    @if($orgNoticeBox === 'unread')
                        <input type="hidden" name="box" value="unread">
                    @endif
                    @if($orgNoticeCategory !== '')
                        <input type="hidden" name="category" value="{{ $orgNoticeCategory }}">
                    @endif
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-300"></i>
                        <input name="q" value="{{ $orgNoticeSearch }}" type="search" placeholder="Search notifications" class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-jci-blue/20 dark:border-slate-700 dark:bg-slate-900">
                    </div>
                </form>
                <form action="{{ route('org.notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200">Mark all as read</button>
                </form>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach($noticeFilters as $key => $label)
                    @php
                        $activeFilter = $key === 'all'
                            ? ($orgNoticeBox === 'all' && $orgNoticeCategory === '')
                            : ($key === 'unread' ? $orgNoticeBox === 'unread' && $orgNoticeCategory === '' : $orgNoticeCategory === $key);
                        $href = route('org.dashboard', array_filter([
                            'section' => 'notifications',
                            'box' => $key === 'unread' ? 'unread' : null,
                            'category' => in_array($key, ['all', 'unread'], true) ? null : $key,
                            'q' => $orgNoticeSearch !== '' ? $orgNoticeSearch : null,
                        ]));
                    @endphp
                    <a href="{{ $href }}" class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $activeFilter ? 'bg-jci-blue text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-jci-blue dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700' }}">{{ $label }}@if(($orgNoticeCounts[$key] ?? 0) > 0) · {{ $orgNoticeCounts[$key] }}@endif</a>
                @endforeach
            </div>

            <div class="grid items-start gap-5 xl:grid-cols-3">
                <div class="space-y-3 xl:col-span-2">
                    @forelse($orgNotices as $notice)
                        @php
                            $payload = $notice->payload;
                            $unread = $notice->read_at === null;
                            $category = $payload['category'] ?? 'system';
                            $icon = $noticeIcons[$category] ?? 'fa-bell';
                        @endphp
                        <article class="rounded-2xl border bg-white p-4 shadow-sm dark:bg-slate-900 {{ $unread ? 'border-sky-200 dark:border-sky-900' : 'border-slate-200 dark:border-slate-800' }}">
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs {{ $unread ? 'bg-sky-100 text-jci-blue dark:bg-sky-950' : 'bg-slate-100 text-slate-400 dark:bg-slate-800' }}">
                                    <i class="fa-solid {{ $icon }}"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $payload['title'] ?? 'Notification' }}</h2>
                                        <x-ui.status-badge :label="$unread ? 'Unread' : 'Read'" />
                                    </div>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $payload['message'] ?? '' }}</p>
                                    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2 text-xs">
                                        <span class="text-slate-400">{{ $notice->category_label }} · {{ \App\Support\ScheduleClock::stamp($notice->created_at) }}@if(!empty($payload['related'])) · {{ $payload['related'] }}@endif</span>
                                        @if(!empty($payload['url']))
                                            <a href="{{ route('org.notifications.open', $notice->id) }}" class="font-semibold text-jci-blue hover:underline">{{ $payload['action'] ?? 'Open' }}</a>
                                        @endif
                                        @if($unread)
                                            <form action="{{ route('org.notifications.read', $notice->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="font-semibold text-slate-500 hover:text-jci-blue">Mark as read</button>
                                            </form>
                                        @endif
                                        <form action="{{ route('org.notifications.dismiss', $notice->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="font-semibold text-slate-400 hover:text-rose-600">Dismiss</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                            <i class="fa-solid fa-bell mb-2 text-2xl text-slate-300"></i>
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No notifications in this list</p>
                            <p class="mt-1 text-xs text-slate-400">A notice appears when an event is posted, a volunteer applies, a task is due, or a certificate is issued.</p>
                        </div>
                    @endforelse
                </div>

                <div class="space-y-4">
                    <form action="{{ route('org.notifications.send') }}" method="POST" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        @csrf
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Message volunteers</h2>
                        <p class="mt-1 text-xs text-slate-400">
                            @if($noticeRecipients->isEmpty())
                                No volunteer email is on file yet.
                            @else
                                This reaches {{ $noticeRecipients->count() }} {{ \Illuminate\Support\Str::plural('volunteer', $noticeRecipients->count()) }} on the platform and by email.
                            @endif
                        </p>
                        <div class="mt-4 space-y-3">
                            <div>
                                <label for="notice-type" class="mb-1.5 block text-xs font-medium text-slate-500">Type</label>
                                <select id="notice-type" name="notice_type" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                                    <option value="event" @selected(old('notice_type', 'event') === 'event')>Event</option>
                                    <option value="task" @selected(old('notice_type') === 'task')>Task</option>
                                    <option value="reminder" @selected(old('notice_type') === 'reminder')>Reminder</option>
                                </select>
                            </div>
                            <div>
                                <label for="notice-title" class="mb-1.5 block text-xs font-medium text-slate-500">Title</label>
                                <input id="notice-title" name="title" type="text" required maxlength="120" value="{{ old('title') }}" placeholder="Cleanup reminder" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                            </div>
                            <div>
                                <label for="notice-message" class="mb-1.5 block text-xs font-medium text-slate-500">Message</label>
                                <textarea id="notice-message" name="message" required maxlength="1000" rows="4" placeholder="Write a short notice" class="w-full resize-none rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">{{ old('message') }}</textarea>
                            </div>
                            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-jci-blue py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">
                                <i class="fa-solid fa-paper-plane"></i> Send
                            </button>
                        </div>
                    </form>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Sent</h2>
                        </div>
                        @if($sentNotices->isEmpty())
                            <p class="px-5 py-10 text-center text-sm text-slate-400">No notices sent yet.</p>
                        @else
                            <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                                @foreach($sentNotices as $notice)
                                    <div class="px-5 py-3">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $notice->title }}</p>
                                            <span class="shrink-0 text-[11px] text-slate-400">{{ $notice->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="mt-0.5 text-xs text-slate-400">{{ $notice->typeLabel() }} · {{ $notice->recipients }} reached</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($section === 'volunteers')
        <div class="space-y-5">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Membership applicants</h2>
                    <p class="mt-0.5 text-xs text-slate-400">People who want to join. Schedule a personal interview before approving membership.</p>
                </div>
                @if(($membershipApplications ?? collect())->isEmpty())
                    <p class="px-5 py-10 text-center text-sm text-slate-400">No pending membership applicants right now.</p>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($membershipApplications as $application)
                            @php $applicant = $application->user; @endphp
                            @continue(!$applicant)
                            <article class="px-5 py-4">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $applicant->name }}</h3>
                                            <x-ui.status-badge :label="$application->statusLabel()" />
                                        </div>
                                        <p class="mt-1 text-xs text-slate-400">{{ $applicant->email }}@if($applicant->phone) · {{ $applicant->phone }}@endif</p>
                                        @if(filled($application->message))
                                            <p class="mt-2 text-sm text-slate-500">{{ $application->message }}</p>
                                        @endif
                                        @if($application->status === 'interview_scheduled' && $application->interview_at)
                                            <p class="mt-2 text-xs font-medium text-jci-blue">
                                                Interview {{ \App\Support\ScheduleClock::stamp($application->interview_at) }}
                                                @if(filled($application->interview_place)) · {{ $application->interview_place }}@endif
                                            </p>
                                        @endif
                                        @if($applicant->skills->isNotEmpty())
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                @foreach($applicant->skills as $skill)
                                                    <span class="rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-medium text-jci-blue dark:bg-sky-950 dark:text-sky-300">{{ $skill->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex shrink-0 flex-wrap gap-2">
                                        <button type="button"
                                                class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200"
                                                data-interview-open
                                                data-action="{{ route('org.memberships.interview', $application) }}"
                                                data-name="{{ $applicant->name }}">
                                            {{ $application->status === 'interview_scheduled' ? 'Reschedule interview' : 'Schedule interview' }}
                                        </button>
                                        <form action="{{ route('org.memberships.approve', $application) }}" method="POST" onsubmit="return confirm('Approve {{ $applicant->name }} as a member after interview?');">
                                            @csrf
                                            <button type="submit" class="rounded-xl bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Approve member</button>
                                        </form>
                                        <form action="{{ route('org.memberships.reject', $application) }}" method="POST" onsubmit="return confirm('Decline this membership application?');">
                                            @csrf
                                            <button type="submit" class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Decline</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Members</h2>
                    <p class="mt-0.5 text-xs text-slate-400">Approved volunteers who belong to this organization.</p>
                </div>
                <input id="org-volunteer-search" type="search" value="{{ request('q') }}" placeholder="Search members..." class="w-full max-w-md rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            </div>
            @if($organizationVolunteers->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No members yet</p>
                    <p class="mt-1 text-xs text-slate-400">Approve applicants after their personal interview and they will appear here.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach($organizationVolunteers as $member)
                        @php
                            $parts = preg_split('/\s+/', trim($member->name)) ?: [];
                            $initials = strtoupper(collect($parts)->filter()->take(2)->map(fn ($part) => substr($part, 0, 1))->join(''));
                            $eventCount = $member->assignments->pluck('event_id')->unique()->count();
                            $availabilityLabel = $member->status === 'pending'
                                ? 'Pending'
                                : ($member->availability === 'inactive' ? 'Unavailable' : 'Available');
                            $eventGroups = $member->assignments->groupBy('event_id')->map(function ($rows) {
                                $event = $rows->first()->event;
                                return [
                                    'title' => $event->title ?? 'Event',
                                    'when' => $event?->start_time?->format('M j, Y'),
                                    'location' => $event->location ?? '',
                                    'tasks' => $rows->map(fn ($row) => [
                                        'title' => $row->task->title ?? 'Role',
                                        'status' => ucfirst(str_replace('_', ' ', (string) $row->status)),
                                        'hours' => $row->hours_logged,
                                        'note' => $row->completion_note,
                                    ])->values(),
                                ];
                            })->values();
                            $volunteerPayload = [
                                'name' => $member->name,
                                'initials' => $initials,
                                'photo' => $member->profile_photo_path ? asset('storage/'.$member->profile_photo_path) : null,
                                'email' => $member->email,
                                'phone' => $member->phone ?: 'Not provided',
                                'bio' => $member->bio ?: 'No bio yet.',
                                'availability' => $availabilityLabel,
                                'account' => ucfirst((string) $member->status),
                                'joined' => $member->created_at?->format('M j, Y'),
                                'skills' => $member->skills->pluck('name')->values(),
                                'eventsCount' => $eventCount,
                                'events' => $eventGroups,
                                'certificates' => $member->certificates->map(fn ($certificate) => [
                                    'event' => $certificate->event->title ?? 'Event',
                                    'code' => $certificate->certificate_code,
                                    'issued' => $certificate->issued_at?->format('M j, Y'),
                                ])->values(),
                                'applications' => $member->taskApplications->map(fn ($application) => [
                                    'task' => $application->task->title ?? 'Role',
                                    'event' => $application->event->title ?? 'Event',
                                    'status' => ucfirst((string) $application->status),
                                ])->values(),
                            ];
                        @endphp
                        <script type="application/json" id="volunteer-payload-{{ $member->id }}">@json($volunteerPayload)</script>
                        <button type="button" data-volunteer-card data-volunteer-id="{{ $member->id }}" data-search="{{ strtolower($member->name.' '.$member->email.' '.$member->phone.' '.$member->skills->pluck('name')->join(' ')) }}" onclick="openVolunteer(this)" class="rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-jci-blue dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-start gap-3">
                                    @if($member->profile_photo_path)
                                        <img src="{{ asset('storage/'.$member->profile_photo_path) }}" alt="" class="h-11 w-11 shrink-0 rounded-full object-cover">
                                    @else
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">{{ $initials }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $member->name }}</p>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @forelse($member->skills as $skill)
                                                <span class="rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-medium text-jci-blue dark:bg-sky-950 dark:text-sky-300">{{ $skill->name }}</span>
                                            @empty
                                                <span class="text-[11px] text-slate-400">No skills listed</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                                <x-ui.status-badge class="shrink-0" :label="$availabilityLabel" />
                            </div>
                        </button>
                    @endforeach
                </div>
                <p id="org-volunteer-empty" class="hidden rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-10 text-center text-sm text-slate-400">No volunteers match that search.</p>
            @endif
        </div>

        <x-ui.modal id="modal-membership-interview" title="Schedule personal interview">
            <p id="membership-interview-context" class="mb-4 text-sm text-slate-500"></p>
            <form id="membership-interview-form" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Interview date and time</label>
                    <input type="datetime-local" name="interview_at" required class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Place</label>
                    <input type="text" name="interview_place" required maxlength="255" placeholder="Office, cafe, or meeting room" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Notes <span class="font-normal normal-case tracking-normal text-slate-400">(optional)</span></label>
                    <textarea name="interview_notes" rows="3" maxlength="1000" placeholder="What to bring, who will meet them, etc." class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950"></textarea>
                </div>
                <p class="text-[11px] text-slate-400">This is a personal interview. The applicant is notified in their portal.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-membership-interview').classList.add('hidden')" class="rounded-xl bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-bold text-white hover:bg-jci-dark">Save interview</button>
                </div>
            </form>
        </x-ui.modal>

        <x-ui.modal id="modal-volunteer" title="Volunteer details">
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <img id="volunteer-detail-photo" alt="" class="hidden h-12 w-12 rounded-full object-cover">
                    <span id="volunteer-detail-initials" class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-500"></span>
                    <div class="min-w-0">
                        <h4 id="volunteer-detail-name" class="text-base font-semibold text-slate-900"></h4>
                        <p id="volunteer-detail-availability" class="mt-1 text-xs font-semibold text-jci-blue"></p>
                    </div>
                </div>
                <dl class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2">
                    <div>
                        <dt class="font-semibold uppercase tracking-wider text-slate-400">Email</dt>
                        <dd id="volunteer-detail-email" class="mt-1 text-sm text-slate-700"></dd>
                    </div>
                    <div>
                        <dt class="font-semibold uppercase tracking-wider text-slate-400">Phone</dt>
                        <dd id="volunteer-detail-phone" class="mt-1 text-sm text-slate-700"></dd>
                    </div>
                    <div>
                        <dt class="font-semibold uppercase tracking-wider text-slate-400">Account</dt>
                        <dd id="volunteer-detail-account" class="mt-1 text-sm text-slate-700"></dd>
                    </div>
                    <div>
                        <dt class="font-semibold uppercase tracking-wider text-slate-400">Joined</dt>
                        <dd id="volunteer-detail-joined" class="mt-1 text-sm text-slate-700"></dd>
                    </div>
                </dl>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">About</p>
                    <p id="volunteer-detail-bio" class="mt-1 text-sm leading-relaxed text-slate-600"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Skills</p>
                    <div id="volunteer-detail-skills" class="mt-2 flex flex-wrap gap-1.5"></div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Events</p>
                    <div id="volunteer-detail-events" class="mt-2 space-y-2"></div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Certificates</p>
                    <div id="volunteer-detail-certificates" class="mt-2 space-y-2"></div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Applications</p>
                    <div id="volunteer-detail-applications" class="mt-2 space-y-2"></div>
                </div>
            </div>
        </x-ui.modal>
    @elseif($section === 'messages')
        @php
            $clock = \App\Support\ScheduleClock::class;
            $threadOpen = $withAdmin || $selectedMessageVolunteer;
            $adminPreview = $adminMessages->last();
        @endphp
        <div class="flex h-[calc(100vh-8.5rem)] min-h-[32rem] flex-col">
            <div class="grid min-h-0 flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:grid-cols-[300px_minmax(0,1fr)]">
                <div class="{{ $threadOpen ? 'hidden lg:flex' : 'flex' }} min-h-0 flex-col border-b border-slate-100 dark:border-slate-800 lg:border-b-0 lg:border-r">
                    <div class="p-3">
                        <input id="org-message-search" type="search" placeholder="Search conversations" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Platform</p>
                        <a href="{{ route('org.dashboard', ['section' => 'messages', 'with' => 'admin']) }}" data-message-thread data-search="volunteerhub platform admin" class="flex items-start gap-3 border-l-2 px-3 py-3 {{ $withAdmin ? 'border-jci-blue bg-sky-50 dark:bg-sky-950/40' : 'border-transparent' }}">
                            <x-ui.brand-mark size="sm" :round="true" class="shrink-0 shadow-sm" />
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">VolunteerHub</span>
                                    @if($adminMessageUnread > 0)
                                        <span class="shrink-0 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-600">{{ $adminMessageUnread }}</span>
                                    @endif
                                </span>
                                <span class="mt-0.5 block truncate text-xs text-slate-400">{{ $adminPreview?->body ?: 'No messages yet' }}</span>
                            </span>
                        </a>

                        <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Volunteers</p>
                        @forelse($messageThreads as $thread)
                            @php
                                $activeThread = $selectedMessageVolunteer && (int) $selectedMessageVolunteer->id === (int) $thread->id;
                                $threadState = $thread->needs_takeover ? 'Needs your reply' : ($thread->with_agent ? 'You are replying' : 'Assistant answers');
                            @endphp
                            <a href="{{ route('org.dashboard', ['section' => 'messages', 'volunteer' => $thread->id]) }}" data-message-thread data-search="{{ strtolower($thread->name.' '.$threadState) }}" class="flex items-start gap-3 border-l-2 px-3 py-3 {{ $activeThread ? 'border-jci-blue bg-sky-50 dark:bg-sky-950/40' : 'border-transparent' }}">
                                <x-ui.avatar :user="$thread" />
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $thread->name }}</span>
                                        <span class="shrink-0 text-[10px] text-slate-400">{{ $thread->message_at ? $clock::stamp($thread->message_at) : '' }}</span>
                                    </span>
                                    <span class="mt-0.5 block truncate text-xs text-slate-400">{{ $thread->message_preview }}</span>
                                    <span class="mt-1 inline-flex"><x-ui.status-badge :label="$threadState" /></span>
                                </span>
                            </a>
                        @empty
                            <p class="px-4 py-8 text-center text-xs text-slate-400">No volunteer conversations yet. They appear when someone writes to your organization.</p>
                        @endforelse
                        <p id="org-message-search-empty" class="hidden px-4 py-8 text-center text-xs text-slate-400">No conversations match that search.</p>
                    </div>
                </div>
                <div class="{{ $threadOpen ? 'flex' : 'hidden lg:flex' }} min-h-0 flex-col">
                    @if($withAdmin)
                        <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                            <a href="{{ route('org.dashboard', ['section' => 'messages']) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800" aria-label="Back to conversations">
                                <i class="fa-solid fa-arrow-left text-sm"></i>
                            </a>
                            <x-ui.brand-mark size="sm" :round="true" class="shadow-sm" />
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">VolunteerHub</p>
                                <p class="text-[11px] text-slate-400">Platform messages, separate from volunteer chats</p>
                            </div>
                        </div>
                        <div id="org-message-log" class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-4">
                            @forelse($adminMessages as $entry)
                                <div class="flex items-end gap-2 {{ $entry->from_admin ? 'justify-start' : 'justify-end' }}">
                                    @if($entry->from_admin)
                                        <x-ui.brand-mark size="sm" :round="true" class="shrink-0 shadow-sm" />
                                    @endif
                                    <div class="max-w-[75%]">
                                        <p class="mb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400 {{ $entry->from_admin ? '' : 'text-right' }}">{{ $entry->from_admin ? 'VolunteerHub' : 'You' }}</p>
                                        <div class="rounded-2xl px-3 py-2 text-sm {{ $entry->from_admin ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-100' : 'bg-jci-blue text-white' }}">
                                            {{ $entry->body }}
                                            <span class="mt-1 block text-[10px] {{ $entry->from_admin ? 'text-slate-400' : 'text-right text-sky-100' }}">{{ $clock::stamp($entry->created_at) }}</span>
                                        </div>
                                    </div>
                                    @unless($entry->from_admin)
                                        <x-ui.avatar :user="$organization" size="sm" />
                                    @endunless
                                </div>
                            @empty
                                <p class="py-16 text-center text-sm text-slate-400">No messages from VolunteerHub yet. You can write first.</p>
                            @endforelse
                        </div>
                        <form action="{{ route('org.messages.admin') }}" method="POST" class="flex items-center gap-2 border-t border-slate-100 p-3 dark:border-slate-800">
                            @csrf
                            <input name="message" required maxlength="2000" placeholder="Write to VolunteerHub" class="flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                            <button type="submit" class="flex h-10 w-10 items-center justify-center rounded-xl bg-jci-blue text-white hover:bg-jci-dark" aria-label="Send message">
                                <i class="fa-solid fa-paper-plane text-sm"></i>
                            </button>
                        </form>
                    @elseif(!$selectedMessageVolunteer)
                        <div class="flex flex-1 flex-col items-center justify-center px-8 text-center">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Choose a conversation</p>
                            <p class="mt-1 max-w-sm text-sm text-slate-400">Open a volunteer on the left. A conversation marked Needs your reply is waiting because the assistant could not answer.</p>
                        </div>
                    @else
                        @php
                            $availabilityLabel = $selectedMessageVolunteer->status === 'pending' ? 'Pending' : ($selectedMessageVolunteer->availability === 'inactive' ? 'Unavailable' : 'Available');
                            $replyState = $selectedConversation?->needs_takeover
                                ? 'Needs your reply'
                                : ($selectedConversation?->withAgent() ? 'You are replying' : 'Assistant answers');
                        @endphp
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                            <div class="flex min-w-0 items-center gap-3">
                                <a href="{{ route('org.dashboard', ['section' => 'messages']) }}" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800" aria-label="Back to conversations">
                                    <i class="fa-solid fa-arrow-left text-sm"></i>
                                </a>
                                <x-ui.avatar :user="$selectedMessageVolunteer" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $selectedMessageVolunteer->name }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                        <x-ui.status-badge :label="$availabilityLabel" />
                                        <x-ui.status-badge :label="$replyState" />
                                    </div>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                                @if($selectedConversation?->needs_takeover && !$selectedConversation->withAgent())
                                    <form action="{{ route('org.messages.takeover', $selectedMessageVolunteer) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="rounded-xl bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Reply myself</button>
                                    </form>
                                @endif
                                @if($selectedConversation?->withAgent())
                                    <form action="{{ route('org.messages.release', $selectedMessageVolunteer) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-300">Return to assistant</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @if($selectedConversation?->needs_takeover)
                            <p class="border-b border-amber-100 bg-amber-50 px-4 py-2 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">The assistant could not answer the latest question. Your message goes straight to this volunteer.</p>
                        @elseif($selectedConversation?->withAgent())
                            <p class="border-b border-slate-100 px-4 py-2 text-xs text-slate-500 dark:border-slate-800">You are answering this volunteer. Use <span class="font-semibold">Return to assistant</span> when you want the AI to handle questions again.</p>
                        @else
                            <p class="border-b border-slate-100 px-4 py-2 text-xs text-slate-500 dark:border-slate-800">The assistant answers first. You can still send a message, and that switches the chat to you.</p>
                        @endif
                        <div id="org-message-log" class="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-4">
                            @forelse($messageEntries as $entry)
                                @if($entry->message !== '')
                                    <div class="flex items-end gap-2">
                                        <x-ui.avatar :user="$selectedMessageVolunteer" size="sm" />
                                        <div class="max-w-[75%]">
                                            <p class="mb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $selectedMessageVolunteer->name }}</p>
                                            <div class="rounded-2xl bg-slate-100 px-3 py-2 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                                {{ $entry->message }}
                                                <span class="mt-1 block text-[10px] text-slate-400">{{ $clock::stamp($entry->created_at) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if($entry->answered_by === 'assistant' && $entry->response !== '')
                                    <div class="flex items-end justify-end gap-2">
                                        <div class="max-w-[75%]">
                                            <p class="mb-1 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-400">Assistant</p>
                                            <div class="rounded-2xl border border-slate-200 px-3 py-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                                                {{ $entry->response }}
                                                <span class="mt-1 block text-right text-[10px] text-slate-400">{{ $clock::stamp($entry->created_at) }}</span>
                                            </div>
                                        </div>
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-jci-blue text-white shadow-sm">
                                            <i class="fa-solid fa-robot text-[10px]"></i>
                                        </span>
                                    </div>
                                @elseif($entry->needs_takeover && $entry->response !== '')
                                    <div class="flex items-end justify-end gap-2">
                                        <div class="max-w-[75%]">
                                            <p class="mb-1 text-right text-[10px] font-semibold uppercase tracking-wider text-amber-700">Waiting for you</p>
                                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-slate-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-slate-100">
                                                {{ $entry->response }}
                                                <span class="mt-1 block text-right text-[10px] text-amber-700/80">{{ $clock::stamp($entry->created_at) }}</span>
                                            </div>
                                        </div>
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-200">
                                            <i class="fa-solid fa-robot text-[10px]"></i>
                                        </span>
                                    </div>
                                @endif
                                @if($entry->admin_reply)
                                    <div class="flex items-end justify-end gap-2">
                                        <div class="max-w-[75%]">
                                            <p class="mb-1 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-400">You</p>
                                            <div class="rounded-2xl bg-jci-blue px-3 py-2 text-sm text-white">
                                                {{ $entry->admin_reply }}
                                                <span class="mt-1 block text-right text-[10px] text-sky-100">{{ $clock::stamp($entry->admin_replied_at ?? $entry->created_at) }}</span>
                                            </div>
                                        </div>
                                        <x-ui.avatar :user="$organization" size="sm" />
                                    </div>
                                @endif
                            @empty
                                <p class="py-16 text-center text-sm text-slate-400">No messages in this conversation yet.</p>
                            @endforelse
                        </div>
                        <form action="{{ route('org.messages.reply', $selectedMessageVolunteer) }}" method="POST" class="flex items-center gap-2 border-t border-slate-100 p-3 dark:border-slate-800">
                            @csrf
                            <input name="message" required maxlength="2000" placeholder="Write a reply" class="flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                            <button type="submit" class="flex h-10 w-10 items-center justify-center rounded-xl bg-jci-blue text-white hover:bg-jci-dark" aria-label="Send message">
                                <i class="fa-solid fa-paper-plane text-sm"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        <script>
            const messageSearch = document.getElementById('org-message-search');
            if (messageSearch) {
                messageSearch.addEventListener('input', function () {
                    const query = messageSearch.value.trim().toLowerCase();
                    let visible = 0;
                    document.querySelectorAll('[data-message-thread]').forEach(function (row) {
                        const show = query === '' || (row.dataset.search || '').includes(query);
                        row.classList.toggle('hidden', !show);
                        if (show) visible += 1;
                    });
                    const empty = document.getElementById('org-message-search-empty');
                    if (empty) empty.classList.toggle('hidden', query === '' || visible !== 0);
                });
            }
            const messageLog = document.getElementById('org-message-log');
            if (messageLog) messageLog.scrollTop = messageLog.scrollHeight;
            setInterval(function () {
                fetch("{{ route('org.messages.presence') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                }).catch(function () {});
            }, 45000);
        </script>
    @elseif($section === 'certificates')
        @php
            $certificateVolunteers = $certificateChoices->unique('volunteer_id')->values();
            $duplicateCertificateNames = $certificateChoices->pluck('volunteer_name')->duplicates();
            $issuedPairs = $issuedCertificates->mapWithKeys(fn ($certificate) => [$certificate->user_id.'-'.$certificate->event_id => true]);
            $certificateStatus = [
                'pending' => 'Pending',
                'approved' => 'Accepted',
                'submitted' => 'Submitted for Review',
                'rejected' => 'Rejected',
                'completed' => 'Completed',
            ];
            $certificateWaiting = collect();
            foreach ($events as $event) {
                foreach ($event->assignments->where('status', '!=', 'cancelled')->groupBy('user_id') as $userId => $group) {
                    if ($issuedPairs->has($userId.'-'.$event->id) || $group->every(fn ($assignment) => $assignment->status === 'completed')) {
                        continue;
                    }
                    $volunteer = $group->first()->user;
                    if (!$volunteer) {
                        continue;
                    }
                    $certificateWaiting->push([
                        'name' => $volunteer->name,
                        'event' => $event->title,
                        'tasks' => $group->map(fn ($assignment) => [
                            'title' => $assignment->task->title ?? 'Task',
                            'status' => $certificateStatus[$assignment->status] ?? ucfirst((string) $assignment->status),
                        ])->values(),
                    ]);
                }
            }
        @endphp
        <div class="space-y-6">
            <section id="submitted-tasks" class="space-y-3">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Review</h2>
                        <p class="mt-1 text-xs text-slate-500">Evidence sent from Assigned Tasks.</p>
                    </div>
                    @if($completionReviews->isNotEmpty())
                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-950 dark:text-amber-200">{{ $completionReviews->count() }} waiting</span>
                    @endif
                </div>
                @if($completionReviews->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-10 text-center dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No evidence is waiting</p>
                        <p class="mx-auto mt-1 max-w-md text-xs text-slate-400">A submission appears here after a volunteer accepts a task and sends a note or a file.</p>
                    </div>
                @else
                    @foreach($completionReviews as $review)
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $review->user->name ?? 'Volunteer' }}</h3>
                                        <x-ui.status-badge label="Submitted for Review" />
                                    </div>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $review->task->title ?? 'Task' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $review->event->title ?? 'Event' }}@if($review->submitted_at) · {{ \App\Support\ScheduleClock::stamp($review->submitted_at) }}@endif</p>
                                </div>
                                @if($review->completion_proof_path)
                                    <a href="{{ route('org.assignments.proof', $review->id) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200">
                                        <i class="fa-solid fa-paperclip"></i> View evidence
                                    </a>
                                @endif
                            </div>
                            @if($review->completion_note)
                                <p class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm leading-relaxed text-slate-700 dark:bg-slate-950 dark:text-slate-200">{{ $review->completion_note }}</p>
                            @endif
                            <div class="mt-4 grid gap-4 border-t border-slate-100 pt-4 lg:grid-cols-2 dark:border-slate-800">
                                <form action="{{ route('org.assignments.approve', $review->id) }}" method="POST" class="space-y-2">
                                    @csrf
                                    <label class="block text-xs font-medium text-slate-500">Hours to record</label>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input type="number" name="hours_logged" min="0.25" max="24" step="0.25" required placeholder="0.00" class="w-28 rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                                        <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-semibold text-white hover:bg-jci-dark">Approve</button>
                                    </div>
                                </form>
                                <form action="{{ route('org.assignments.reject', $review->id) }}" method="POST" class="space-y-2">
                                    @csrf
                                    <label class="block text-xs font-medium text-slate-500">Return for revision</label>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input type="text" name="feedback" required maxlength="1000" placeholder="What should they change" class="min-w-0 flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                                        <button type="submit" class="rounded-xl border border-rose-200 px-4 py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:border-rose-900">Return</button>
                                    </div>
                                </form>
                            </div>
                        </article>
                    @endforeach
                @endif
            </section>

            <div class="grid items-start gap-5 lg:grid-cols-2">
                @if($certificateVolunteers->isEmpty())
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Ready to issue</h2>
                        <p class="mt-3 text-sm text-slate-500">Nobody is ready for a certificate yet.</p>
                        <p class="mt-1 text-xs text-slate-400">A volunteer is listed here after you approve every task they were assigned on an event. The hours come from the hours you recorded above.</p>
                    </div>
                @else
                    <form action="{{ route('org.certificates.store') }}" method="POST" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        @csrf
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Ready to issue</h2>
                        <p class="mt-1 text-xs text-slate-500">Hours are filled from the hours you approved. You can change them before issuing.</p>
                        <div class="mt-5 space-y-4">
                            <div>
                                <label for="certificate-volunteer" class="mb-1.5 block text-sm text-slate-600 dark:text-slate-300">Volunteer</label>
                                <select id="certificate-volunteer" name="volunteer_id" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                                    <option value="">Select volunteer</option>
                                    @foreach($certificateVolunteers as $choice)
                                        <option value="{{ $choice['volunteer_id'] }}">
                                            {{ $choice['volunteer_name'] }}@if($duplicateCertificateNames->contains($choice['volunteer_name'])) ({{ $choice['email'] }})@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="certificate-event" class="mb-1.5 block text-sm text-slate-600 dark:text-slate-300">Event</label>
                                <select id="certificate-event" name="event_id" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                                    <option value="">Select event</option>
                                </select>
                                <p id="certificate-task-note" class="mt-1.5 text-[11px] text-slate-400"></p>
                            </div>
                            <div>
                                <label for="certificate-hours" class="mb-1.5 block text-sm text-slate-600 dark:text-slate-300">Hours rendered</label>
                                <input id="certificate-hours" name="hours_rendered" type="number" min="0.25" max="999" step="0.25" required placeholder="Hours" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                            </div>
                            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-jci-blue py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">
                                <i class="fa-solid fa-award"></i> Issue certificate
                            </button>
                        </div>
                    </form>
                @endif

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Issued</h2>
                    </div>
                    @if($issuedCertificates->isEmpty())
                        <p class="px-5 py-12 text-center text-sm text-slate-400">No certificates issued yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="text-xs text-slate-400">
                                    <tr>
                                        <th class="px-5 py-3 font-medium">Volunteer</th>
                                        <th class="px-3 py-3 font-medium">Event</th>
                                        <th class="px-3 py-3 font-medium">Hours</th>
                                        <th class="px-3 py-3 font-medium">Issued</th>
                                        <th class="px-5 py-3 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($issuedCertificates as $certificate)
                                        <tr class="border-t border-slate-100 dark:border-slate-800">
                                            <td class="px-5 py-3 font-medium text-slate-800 dark:text-slate-100">{{ $certificate->user->name ?? 'Volunteer' }}</td>
                                            <td class="px-3 py-3 text-slate-600 dark:text-slate-300">{{ $certificate->event->title ?? 'Event' }}</td>
                                            <td class="whitespace-nowrap px-3 py-3 text-slate-500">{{ $certificate->hours_rendered ? rtrim(rtrim(number_format((float) $certificate->hours_rendered, 2), '0'), '.') : '—' }}</td>
                                            <td class="whitespace-nowrap px-3 py-3 text-slate-500">{{ $certificate->issued_at?->format('M d, Y') }}</td>
                                            <td class="px-5 py-3 text-right">
                                                <a href="{{ route('org.certificates.download', $certificate) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-200">
                                                    <i class="fa-solid fa-download"></i> PDF
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            @if($certificateWaiting->isNotEmpty())
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Still in progress</h2>
                        <p class="mt-1 text-xs text-slate-500">These volunteers are not ready for a certificate until every task below is approved.</p>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($certificateWaiting as $waiting)
                            <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $waiting['name'] }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $waiting['event'] }}</p>
                                </div>
                                <div class="flex flex-col items-start gap-2 sm:items-end">
                                    @foreach($waiting['tasks'] as $task)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs text-slate-600 dark:text-slate-300">{{ $task['title'] }}</span>
                                            <x-ui.status-badge :label="$task['status']" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
        <script type="application/json" id="certificate-choices">@json($certificateChoices)</script>
        <script>
            (function () {
                const choices = JSON.parse(document.getElementById('certificate-choices').textContent || '[]');
                const volunteerSelect = document.getElementById('certificate-volunteer');
                const eventSelect = document.getElementById('certificate-event');
                const hoursInput = document.getElementById('certificate-hours');
                const taskNote = document.getElementById('certificate-task-note');
                if (!volunteerSelect || !eventSelect) {
                    return;
                }

                function fillEvents() {
                    const volunteerId = volunteerSelect.value;
                    const matches = choices.filter(function (choice) {
                        return String(choice.volunteer_id) === String(volunteerId);
                    });
                    eventSelect.innerHTML = '<option value="">Select event</option>';
                    matches.forEach(function (choice) {
                        const option = document.createElement('option');
                        option.value = choice.event_id;
                        option.textContent = choice.event_title;
                        option.dataset.hours = choice.hours || '';
                        option.dataset.tasks = (choice.tasks || []).join(', ');
                        eventSelect.appendChild(option);
                    });
                    if (matches.length === 1) {
                        eventSelect.value = String(matches[0].event_id);
                    }
                    applyEvent();
                }

                function applyEvent() {
                    const option = eventSelect.selectedOptions[0];
                    const hours = option && option.dataset.hours ? option.dataset.hours : '';
                    const tasks = option && option.dataset.tasks ? option.dataset.tasks : '';
                    hoursInput.value = hours;
                    taskNote.textContent = tasks ? 'Completed: ' + tasks : '';
                }

                volunteerSelect.addEventListener('change', fillEvents);
                eventSelect.addEventListener('change', applyEvent);
            })();
        </script>
    @elseif($section === 'officers')
        @include('org.officers')
    @elseif($section === 'gallery')
        @include('org.gallery')
    @elseif($section === 'settings')
        @include('org.settings')
    @else
        <style>
            .org-legacy[data-focus="events"] [data-panel]:not([data-panel="events"]),
            .org-legacy[data-focus="tasks"] [data-panel]:not([data-panel="tasks"]),
            .org-legacy[data-focus="volunteers"] [data-panel]:not([data-panel="volunteers"]),
            .org-legacy[data-focus="matching"] [data-panel]:not([data-panel="matching"]),
            .org-legacy[data-focus="certificates"] [data-panel]:not([data-panel="certificates"]) { display: none; }
            .org-legacy[data-focus="matching"] .org-main-column,
            .org-legacy[data-focus="events"] .org-side-column,
            .org-legacy[data-focus="tasks"] .org-side-column,
            .org-legacy[data-focus="volunteers"] .org-side-column,
            .org-legacy[data-focus="certificates"] .org-side-column { display: none; }
            .org-legacy[data-focus="matching"] .org-side-column,
            .org-legacy[data-focus="events"] .org-main-column,
            .org-legacy[data-focus="tasks"] .org-main-column,
            .org-legacy[data-focus="volunteers"] .org-main-column,
            .org-legacy[data-focus="certificates"] .org-main-column { grid-column: 1 / -1; }
            .org-legacy[data-focus="matching"] .org-side-column .glass-card { max-height: none; }
        </style>
        <div class="org-legacy grid grid-cols-1 gap-6 lg:grid-cols-12" data-focus="{{ $section }}">
            @include('org.operations')
        </div>
    @endif

    <x-ui.modal id="modal-edit-event" title="Edit Event">
        <form id="event-edit-form" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Event Title</label>
                <input name="title" type="text" required maxlength="255" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Location</label>
                <input name="location" type="text" required class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Start</label>
                    <input name="start_time" type="datetime-local" required class="w-full rounded-lg border border-slate-200 p-2 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">End</label>
                    <input name="end_time" type="datetime-local" required class="w-full rounded-lg border border-slate-200 p-2 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Capacity</label>
                    <input name="capacity" type="number" min="1" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Status</label>
                    <select name="status" class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue"></select>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Description</label>
                <textarea name="description" required rows="4" class="w-full resize-none rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue"></textarea>
            </div>
            <p id="event-edit-note" class="text-[10px] text-slate-400"></p>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modal-edit-event').classList.add('hidden')" class="rounded-xl bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700">Cancel</button>
                <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-bold text-white hover:bg-jci-dark">Save Event</button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal id="modal-new-event" title="Create Event">
        <form action="{{ route('org.events.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Event Title</label>
                <input name="title" type="text" required placeholder="e.g., Clean Beach Reschedule" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Location Address</label>
                <input name="location" type="text" required placeholder="e.g., Surigao Boulevard" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Start Date & Time</label>
                    <input name="start_time" type="datetime-local" required class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">End Date & Time</label>
                    <input name="end_time" type="datetime-local" required class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Max Capacity (Vols)</label>
                <input name="capacity" type="number" min="1" placeholder="e.g. 50" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Description</label>
                <textarea name="description" required placeholder="Describe details of the outreach event..." class="w-full border border-slate-200 rounded-lg p-2.5 text-xs h-20 focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
            </div>
            <div class="pt-3 border-t border-slate-100 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[10px] text-slate-400">Your volunteers can see this event right away. Add tasks from Task Assignment.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-new-event').classList.add('hidden')" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition duration-200">Cancel</button>
                    <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white font-bold text-xs px-4 py-2.5 rounded-xl transition duration-200 shadow-md">Create event</button>
                </div>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.event-details-modal id="modal-event-details" duties-heading="Task assignments" />

    <x-ui.modal id="modal-create-task" title="Create Task">
        @if($events->whereNotIn('status', ['completed', 'cancelled'])->count() > 0 && $assignableVolunteers->isNotEmpty())
            <form action="{{ route('org.tasks.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Event</label>
                    <select name="event_id" required class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                        @foreach($events->whereNotIn('status', ['completed', 'cancelled']) as $eventOption)
                            <option value="{{ $eventOption->id }}">{{ $eventOption->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Task title</label>
                    <input name="title" type="text" required maxlength="255" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Assign volunteer</label>
                    <select name="user_id" required class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                        <option value="" selected disabled>Choose a volunteer</option>
                        @foreach($assignableVolunteers as $volunteer)
                            <option value="{{ $volunteer->id }}">{{ $volunteer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Priority</label>
                        <select name="priority" class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Deadline</label>
                        <input name="due_date" type="datetime-local" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs">
                    </div>
                </div>
                <p class="text-[10px] text-slate-400">The volunteer is assigned right away and can be reassigned later if needed.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-create-task').classList.add('hidden')" class="rounded-xl bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-bold text-white hover:bg-jci-dark">Create &amp; assign</button>
                </div>
            </form>
        @else
            <p class="text-sm text-slate-500">Create an event and link an approved volunteer first.</p>
        @endif
    </x-ui.modal>

    <x-ui.modal id="modal-assign-task" title="Assign a Task">
        @if($allOrgTasks->count() > 0 && $assignableVolunteers->count() > 0)
            <form action="{{ route('org.assignments.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Task</label>
                    <select name="task_id" required class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                        @foreach($allOrgTasks as $task)
                            <option value="{{ $task->id }}">{{ $task->title }} ({{ $task->event->title ?? 'Event' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Volunteer</label>
                    <select name="user_id" required class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                        @foreach($assignableVolunteers as $volunteer)
                            <option value="{{ $volunteer->id }}">{{ $volunteer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('modal-assign-task').classList.add('hidden')" class="rounded-xl bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-bold text-white hover:bg-jci-dark">Assign</button>
                </div>
            </form>
        @else
            <p class="text-sm text-slate-500">Add an open task and an approved volunteer before assigning someone.</p>
        @endif
    </x-ui.modal>

    <x-ui.modal id="modal-send-notification" title="Send Notification">
        <form action="{{ route('org.notifications.send') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Type</label>
                <select name="notice_type" required class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                    <option value="event">Event</option>
                    <option value="task">Task</option>
                    <option value="reminder">Reminder</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Title</label>
                <input name="title" type="text" required maxlength="120" placeholder="Schedule update" class="w-full rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Message</label>
                <textarea name="message" required maxlength="1000" rows="4" placeholder="Write a short notice for your volunteers" class="w-full resize-none rounded-lg border border-slate-200 p-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue"></textarea>
            </div>
            <p class="text-[10px] text-slate-400">This reaches the volunteer platform and their email, including their new events and assigned tasks.</p>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modal-send-notification').classList.add('hidden')" class="rounded-xl bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700">Cancel</button>
                <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-bold text-white hover:bg-jci-dark">Send</button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal id="modal-generate-certificate" title="Submitted work">
        @if($completionReviews->count() > 0)
            <p class="mb-3 text-xs text-slate-500">Approve the hours first. Render the certificate from Certificates after every task on the event is finished.</p>
            <div class="space-y-3">
                @foreach($completionReviews->take(3) as $review)
                    <form action="{{ route('org.assignments.approve', $review->id) }}" method="POST" class="space-y-2 rounded-xl border border-slate-200 p-3">
                        @csrf
                        <p class="text-xs font-bold text-slate-800">{{ $review->user->name ?? 'Volunteer' }}</p>
                        <p class="text-[11px] text-slate-400">{{ $review->task->title ?? 'Task' }} · {{ $review->event->title ?? 'Event' }}</p>
                        <input type="number" name="hours_logged" min="0.25" max="24" step="0.25" required placeholder="Hours" class="w-full rounded-lg border border-slate-200 p-2 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                        <button type="submit" class="w-full rounded-lg bg-jci-blue py-2 text-xs font-bold text-white hover:bg-jci-dark">Approve completion</button>
                    </form>
                @endforeach
            </div>
            @if($completionReviews->count() > 3)
                <a href="{{ route('org.dashboard', ['section' => 'certificates']) }}#submitted-tasks" class="mt-3 inline-block text-xs font-bold text-jci-blue">Review all {{ $completionReviews->count() }} submissions</a>
            @endif
        @else
            <p class="text-sm text-slate-500">No submitted work is waiting. Render a certificate after a volunteer finishes every task on an event.</p>
            <a href="{{ route('org.dashboard', ['section' => 'certificates']) }}" class="mt-3 inline-block text-xs font-bold text-jci-blue">Open certificates</a>
        @endif
    </x-ui.modal>

    <script>
        function openEditEvent(button) {
            const event = JSON.parse(document.getElementById('event-payload-' + button.dataset.eventId).textContent);
            const form = document.getElementById('event-edit-form');
            form.action = event.updateUrl;
            form.querySelector('[name="title"]').value = event.title || '';
            form.querySelector('[name="location"]').value = event.location || '';
            form.querySelector('[name="start_time"]').value = event.start || '';
            form.querySelector('[name="end_time"]').value = event.end || '';
            form.querySelector('[name="capacity"]').value = event.capacity || '';
            form.querySelector('[name="description"]').value = event.description || '';
            const status = form.querySelector('[name="status"]');
            const choices = [['published', 'Published'], ['completed', 'Completed'], ['cancelled', 'Cancelled']];
            const currentStatus = event.status === 'draft' ? 'published' : event.status;
            status.innerHTML = '';
            choices.forEach(function (choice) {
                const option = document.createElement('option');
                option.value = choice[0];
                option.textContent = choice[1];
                option.selected = choice[0] === currentStatus;
                status.appendChild(option);
            });
            document.getElementById('event-edit-note').textContent = 'Completed moves this event to Archived and removes it from Task Assignment.';
            document.getElementById('modal-edit-event').classList.remove('hidden');
        }

        function closeAssignDialog() {
            const dialog = document.getElementById('assign-volunteer-dialog');
            if (!dialog) {
                return;
            }
            dialog.classList.add('hidden');
            dialog.classList.remove('flex');
        }

        function refreshAssignDialog() {
            const select = document.getElementById('assign-user');
            const hint = document.getElementById('assign-dialog-hint');
            const save = document.getElementById('assign-save');
            if (!select || !hint || !save) {
                return;
            }
            const option = select.options[select.selectedIndex];
            const changed = select.value !== '' && select.value !== String(select.dataset.saved || '');
            save.disabled = !changed;
            if (option && option.dataset.skills) {
                hint.textContent = 'Matched skills: ' + option.dataset.skills + '.';
            } else if (select.value) {
                hint.textContent = 'This volunteer is not matched to the role. You can still assign them.';
            } else {
                hint.textContent = 'Matched volunteers are listed first.';
            }
        }

        function fillAssignGroup(select, label, rows, currentId) {
            if (!rows.length) {
                return;
            }
            const group = document.createElement('optgroup');
            group.label = label;
            rows.forEach(function (row) {
                const option = document.createElement('option');
                option.value = String(row.id);
                option.textContent = row.name;
                option.dataset.skills = row.skills || '';
                option.selected = String(row.id) === currentId;
                group.appendChild(option);
            });
            select.appendChild(group);
        }

        function openAssignDialog(button) {
            const dialog = document.getElementById('assign-volunteer-dialog');
            const source = document.getElementById('assign-roster-' + button.dataset.taskId);
            const select = document.getElementById('assign-user');
            if (!dialog || !source || !select) {
                return;
            }
            const roster = JSON.parse(source.textContent);
            const currentId = String(button.dataset.currentId || '');
            const currentName = button.dataset.currentName || '';
            document.getElementById('assign-task-id').value = button.dataset.taskId || '';
            document.getElementById('assign-dialog-title').textContent = button.dataset.mode === 'reassign' ? 'Reassign volunteer' : 'Assign volunteer';
            document.getElementById('assign-dialog-context').textContent = (button.dataset.taskTitle || 'Task')
                + ' · ' + (button.dataset.eventTitle || 'Event')
                + '. ' + (currentName ? 'Currently ' + currentName + '.' : 'No one is assigned yet.');
            select.innerHTML = '';
            select.dataset.saved = currentId;
            const matched = roster.filter(function (row) { return row.matched; });
            const others = roster.filter(function (row) { return !row.matched; });
            fillAssignGroup(select, 'Matched skills', matched, currentId);
            fillAssignGroup(select, 'Other volunteers', others, currentId);
            refreshAssignDialog();
            dialog.classList.remove('hidden');
            dialog.classList.add('flex');
        }

        document.addEventListener('click', function (event) {
            const open = event.target.closest('[data-assign-open]');
            if (open) {
                openAssignDialog(open);
                return;
            }
            if (event.target.closest('[data-close-assign]') || event.target.id === 'assign-volunteer-dialog') {
                closeAssignDialog();
            }
        });
        document.addEventListener('change', function (event) {
            if (event.target && event.target.id === 'assign-user') {
                refreshAssignDialog();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAssignDialog();
            }
        });
        const assignDialog = document.getElementById('assign-volunteer-dialog');
        if (assignDialog) {
            document.body.appendChild(assignDialog);
        }

        function toggleCustomRole(select) {
            const field = document.getElementById('custom-role-field');
            const input = document.getElementById('custom-role-input');
            if (!field || !input) {
                return;
            }
            const custom = select.value === '__custom__';
            field.classList.toggle('hidden', !custom);
            input.required = custom;
            if (!custom) {
                input.value = '';
            }
            const hint = document.getElementById('role-skill-hint');
            const option = select.options[select.selectedIndex];
            if (!hint) {
                return;
            }
            if (custom) {
                hint.textContent = 'Type the role this event needs. It is matched to related skills, such as Public Relations.';
            } else if (option && option.dataset.skills) {
                hint.textContent = option.value + ' matches volunteers with ' + option.dataset.skills + '. You can reassign later if needed.';
            } else {
                hint.textContent = 'Create the role and assign a volunteer in one step. You can reassign later from the task list.';
            }
        }

        function newTaskRoleSkills() {
            const roleSelect = document.getElementById('new-task-role');
            const customInput = document.getElementById('custom-role-input');
            if (!roleSelect) {
                return [];
            }
            if (roleSelect.value === '__custom__') {
                return String(customInput?.value || '')
                    .split(/[,\s/&]+/)
                    .map(function (part) { return part.trim().toLowerCase(); })
                    .filter(Boolean);
            }
            const option = roleSelect.options[roleSelect.selectedIndex];
            return String(option?.dataset.skills || '')
                .split(',')
                .map(function (part) { return part.trim().toLowerCase(); })
                .filter(Boolean);
        }

        function refreshNewTaskVolunteers() {
            const select = document.getElementById('new-task-user');
            const hint = document.getElementById('new-task-assign-hint');
            const source = document.getElementById('new-task-volunteer-roster');
            if (!select || !source) {
                return;
            }
            let roster = [];
            try {
                roster = JSON.parse(source.textContent || '[]');
            } catch (error) {
                roster = [];
            }
            const needed = newTaskRoleSkills();
            const current = select.value;
            const ranked = roster.map(function (row) {
                const skills = (row.skills || []).map(function (skill) { return String(skill).toLowerCase(); });
                const matched = needed.length > 0 && needed.some(function (skill) { return skills.indexOf(skill) !== -1; });
                return {
                    id: row.id,
                    name: row.name,
                    skills: (row.skills || []).join(', '),
                    matched: matched
                };
            }).sort(function (left, right) {
                if (left.matched !== right.matched) {
                    return left.matched ? -1 : 1;
                }
                return String(left.name).localeCompare(String(right.name));
            });

            select.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.disabled = true;
            placeholder.selected = !current;
            placeholder.textContent = ranked.length ? 'Choose a volunteer' : 'No approved volunteers yet';
            select.appendChild(placeholder);

            function appendGroup(label, rows) {
                if (!rows.length) {
                    return;
                }
                const group = document.createElement('optgroup');
                group.label = label;
                rows.forEach(function (row) {
                    const option = document.createElement('option');
                    option.value = String(row.id);
                    option.textContent = row.skills ? (row.name + ' · ' + row.skills) : row.name;
                    option.selected = String(row.id) === String(current);
                    group.appendChild(option);
                });
                select.appendChild(group);
            }

            appendGroup('Matched skills', ranked.filter(function (row) { return row.matched; }));
            appendGroup('Other volunteers', ranked.filter(function (row) { return !row.matched; }));

            if (hint) {
                const matchedCount = ranked.filter(function (row) { return row.matched; }).length;
                if (!needed.length) {
                    hint.textContent = 'Choose a role first to sort matched volunteers to the top.';
                } else if (matchedCount > 0) {
                    hint.textContent = matchedCount + ' matched volunteer' + (matchedCount === 1 ? '' : 's') + ' listed first. You can still pick someone else.';
                } else {
                    hint.textContent = 'No skill matches for this role yet. You can still assign any approved volunteer.';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            refreshNewTaskVolunteers();
        });

        function openVolunteer(button) {
            const payload = document.getElementById('volunteer-payload-' + button.dataset.volunteerId);
            if (!payload) {
                return;
            }
            const volunteer = JSON.parse(payload.textContent);
            const photo = document.getElementById('volunteer-detail-photo');
            const initials = document.getElementById('volunteer-detail-initials');
            document.getElementById('volunteer-detail-name').textContent = volunteer.name || '';
            document.getElementById('volunteer-detail-availability').textContent = volunteer.availability || '';
            document.getElementById('volunteer-detail-email').textContent = volunteer.email || '';
            document.getElementById('volunteer-detail-phone').textContent = volunteer.phone || '';
            document.getElementById('volunteer-detail-account').textContent = volunteer.account || '';
            document.getElementById('volunteer-detail-joined').textContent = volunteer.joined || '';
            document.getElementById('volunteer-detail-bio').textContent = volunteer.bio || '';
            if (volunteer.photo) {
                photo.src = volunteer.photo;
                photo.classList.remove('hidden');
                initials.classList.add('hidden');
            } else {
                photo.classList.add('hidden');
                initials.classList.remove('hidden');
                initials.textContent = volunteer.initials || '';
            }
            const skills = document.getElementById('volunteer-detail-skills');
            skills.innerHTML = '';
            (volunteer.skills || []).forEach(function (skill) {
                const pill = document.createElement('span');
                pill.className = 'rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-medium text-jci-blue';
                pill.textContent = skill;
                skills.appendChild(pill);
            });
            if (!volunteer.skills || volunteer.skills.length === 0) {
                skills.textContent = 'No skills listed.';
            }
            const events = document.getElementById('volunteer-detail-events');
            events.innerHTML = '';
            if (!volunteer.events || volunteer.events.length === 0) {
                events.textContent = 'No events with this organization yet.';
            } else {
                volunteer.events.forEach(function (event) {
                    const block = document.createElement('div');
                    block.className = 'rounded-xl border border-slate-100 px-3 py-2';
                    const title = document.createElement('p');
                    title.className = 'text-sm font-semibold text-slate-800';
                    title.textContent = event.title;
                    const meta = document.createElement('p');
                    meta.className = 'text-[11px] text-slate-400';
                    meta.textContent = [event.when, event.location].filter(Boolean).join(' · ');
                    block.appendChild(title);
                    block.appendChild(meta);
                    (event.tasks || []).forEach(function (task) {
                        const line = document.createElement('p');
                        line.className = 'mt-1 text-xs text-slate-600';
                        const hours = Number(task.hours) > 0 ? ' · ' + task.hours + ' hours' : '';
                        line.textContent = task.title + ' · ' + task.status + hours;
                        block.appendChild(line);
                    });
                    events.appendChild(block);
                });
            }
            const certificates = document.getElementById('volunteer-detail-certificates');
            certificates.innerHTML = '';
            if (!volunteer.certificates || volunteer.certificates.length === 0) {
                certificates.textContent = 'No certificates issued yet.';
            } else {
                volunteer.certificates.forEach(function (certificate) {
                    const line = document.createElement('p');
                    line.className = 'text-sm text-slate-700';
                    line.textContent = certificate.event + (certificate.issued ? ' · ' + certificate.issued : '') + (certificate.code ? ' · ' + certificate.code : '');
                    certificates.appendChild(line);
                });
            }
            const applications = document.getElementById('volunteer-detail-applications');
            applications.innerHTML = '';
            if (!volunteer.applications || volunteer.applications.length === 0) {
                applications.textContent = 'No applications.';
            } else {
                volunteer.applications.forEach(function (application) {
                    const line = document.createElement('p');
                    line.className = 'text-sm text-slate-700';
                    line.textContent = application.task + ' · ' + application.event + ' · ' + application.status;
                    applications.appendChild(line);
                });
            }
            document.getElementById('modal-volunteer').classList.remove('hidden');
        }

        const volunteerSearch = document.getElementById('org-volunteer-search');
        if (volunteerSearch) {
            volunteerSearch.addEventListener('input', function () {
                const query = volunteerSearch.value.trim().toLowerCase();
                let shown = 0;
                document.querySelectorAll('[data-volunteer-card]').forEach(function (card) {
                    const hit = (card.dataset.search || '').includes(query);
                    card.classList.toggle('hidden', !hit);
                    if (hit) {
                        shown++;
                    }
                });
                const empty = document.getElementById('org-volunteer-empty');
                if (empty) {
                    empty.classList.toggle('hidden', shown !== 0);
                }
            });
            if (volunteerSearch.value.trim() !== '') {
                volunteerSearch.dispatchEvent(new Event('input'));
            }
        }

        (function () {
            const interviewModal = document.getElementById('modal-membership-interview');
            const interviewForm = document.getElementById('membership-interview-form');
            const interviewContext = document.getElementById('membership-interview-context');
            if (!interviewModal || !interviewForm) {
                return;
            }
            document.body.appendChild(interviewModal);
            document.querySelectorAll('[data-interview-open]').forEach(function (button) {
                button.addEventListener('click', function () {
                    interviewForm.action = button.dataset.action || '';
                    interviewContext.textContent = 'Personal interview with ' + (button.dataset.name || 'applicant');
                    interviewModal.classList.remove('hidden');
                });
            });
        })();

        function openCreateEvent() {
            document.getElementById('modal-new-event').classList.remove('hidden');
        }

        (function () {
            const eventSearch = document.getElementById('org-event-search');
            const filterBar = document.getElementById('org-event-filters');
            if (!eventSearch && !filterBar) {
                return;
            }
            let activeFilter = 'all';

            function paintFilters() {
                if (!filterBar) {
                    return;
                }
                filterBar.querySelectorAll('[data-event-filter]').forEach(function (button) {
                    const on = button.dataset.eventFilter === activeFilter;
                    button.className = on
                        ? 'rounded-full px-3 py-1.5 text-xs font-semibold bg-jci-blue text-white'
                        : 'rounded-full px-3 py-1.5 text-xs font-semibold bg-white text-slate-600 ring-1 ring-slate-200 hover:text-jci-blue dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700';
                });
            }

            function refreshEvents() {
                const query = eventSearch ? eventSearch.value.trim().toLowerCase() : '';
                let shown = 0;
                document.querySelectorAll('[data-event-card]').forEach(function (card) {
                    const filterHit = activeFilter === 'all' || card.dataset.filter === activeFilter;
                    const searchHit = query === '' || (card.dataset.search || '').includes(query);
                    const hit = filterHit && searchHit;
                    card.classList.toggle('hidden', !hit);
                    if (hit) {
                        shown += 1;
                    }
                });
                document.querySelectorAll('[data-event-group="archived"]').forEach(function (heading) {
                    const showHeading = activeFilter === 'all' || activeFilter === 'completed';
                    let groupShown = 0;
                    let node = heading.nextElementSibling;
                    while (node && !node.hasAttribute('data-event-group')) {
                        if (node.matches('[data-event-card]') && !node.classList.contains('hidden')) {
                            groupShown += 1;
                        }
                        node = node.nextElementSibling;
                    }
                    heading.classList.toggle('hidden', !showHeading || groupShown === 0);
                });
                const empty = document.getElementById('org-event-search-empty');
                if (empty) {
                    empty.classList.toggle('hidden', shown > 0);
                }
            }

            if (eventSearch) {
                eventSearch.addEventListener('input', refreshEvents);
            }
            if (filterBar) {
                filterBar.addEventListener('click', function (event) {
                    const button = event.target.closest('[data-event-filter]');
                    if (!button) {
                        return;
                    }
                    activeFilter = button.dataset.eventFilter;
                    paintFilters();
                    refreshEvents();
                });
            }
            paintFilters();
        })();
    </script>
</x-layout.org>
