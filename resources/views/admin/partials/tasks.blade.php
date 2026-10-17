@php
    $taskFilters = [
        'all' => 'All',
        'pending' => 'Pending',
        'assigned' => 'Assigned',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'overdue' => 'Overdue',
    ];
    $taskOverview = [
        ['key' => 'pending', 'label' => 'Pending', 'icon' => 'fa-clock', 'iconBg' => 'bg-amber-50 dark:bg-amber-950', 'iconColor' => 'text-amber-500 dark:text-amber-300'],
        ['key' => 'assigned', 'label' => 'Assigned', 'icon' => 'fa-user-check', 'iconBg' => 'bg-sky-50 dark:bg-sky-950', 'iconColor' => 'text-sky-600 dark:text-sky-300'],
        ['key' => 'in_progress', 'label' => 'In Progress', 'icon' => 'fa-spinner', 'iconBg' => 'bg-indigo-50 dark:bg-indigo-950', 'iconColor' => 'text-indigo-500 dark:text-indigo-300'],
        ['key' => 'completed', 'label' => 'Completed', 'icon' => 'fa-circle-check', 'iconBg' => 'bg-emerald-50 dark:bg-emerald-950', 'iconColor' => 'text-emerald-600 dark:text-emerald-300'],
    ];
    $taskEvent = $taskEventFilter ? $postedEvents->firstWhere('id', $taskEventFilter) : null;
    $activeTaskOrg = $selectedTaskOrg ?: (int) ($taskEvent->organization_id ?? 0);
    $selectedTaskOrganization = $organizations->firstWhere('id', $activeTaskOrg);
    $scopedTasks = $postedTasks
        ->when($activeTaskOrg, fn ($tasks) => $tasks->filter(fn ($task) => (int) optional($task->event)->organization_id === $activeTaskOrg))
        ->when($taskEventFilter, fn ($tasks) => $tasks->filter(fn ($task) => (int) $task->event_id === $taskEventFilter));
    $visibleTasks = $scopedTasks
        ->filter(function ($task) use ($taskStatusFilter) {
            if ($taskStatusFilter === 'all') return true;
            if ($taskStatusFilter === 'overdue') return $task->isOverdue();
            return $task->oversightStatus() === $taskStatusFilter;
        })
        ->sortBy(fn ($task) => sprintf('%d-%s', $task->isOverdue() ? 0 : 1, optional($task->due_date)->format('Y-m-d H:i:s') ?? '9999'))
        ->values();
@endphp

<div class="space-y-5">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="bg-gradient-to-r from-jci-blue to-jci-dark px-5 py-5 text-white sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/70">Oversight</p>
                    <h4 class="mt-1 text-xl font-extrabold tracking-tight">{{ number_format($postedTasks->count()) }} {{ \Illuminate\Support\Str::plural('task', $postedTasks->count()) }}</h4>
                    <p class="mt-1 text-sm text-white/75">Deadlines, assignments, and completion across organizations.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if($taskEvent)
                        <a href="{{ route('admin.dashboard', ['tab' => 'tasks', 'task_org' => $activeTaskOrg]) }}" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-3 py-2 text-xs font-semibold text-white hover:bg-white/25">
                            <i class="fa-solid fa-xmark"></i> Clear event filter
                        </a>
                    @endif
                    @if($selectedTaskOrganization)
                        <a href="{{ route('admin.dashboard', array_filter(['tab' => 'tasks', 'task_status' => $taskStatusFilter === 'all' ? null : $taskStatusFilter])) }}" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-3 py-2 text-xs font-semibold text-white hover:bg-white/25">
                            <i class="fa-solid fa-building"></i> All organizations
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-2 gap-3 xl:grid-cols-5">
        @foreach($taskOverview as $card)
            <a href="{{ route('admin.dashboard', array_filter(['tab' => 'tasks', 'task_org' => $activeTaskOrg ?: null, 'task_status' => $card['key'], 'task_event' => $taskEventFilter ?: null])) }}"
               class="rounded-2xl border bg-white p-4 shadow-sm transition dark:bg-slate-900 {{ $taskStatusFilter === $card['key'] ? 'border-jci-blue ring-1 ring-jci-blue/20' : 'border-slate-200 hover:border-sky-300 dark:border-slate-800' }}">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }}">
                    <i class="fa-solid {{ $card['icon'] }} text-sm"></i>
                </span>
                <p class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ number_format($scopedTasks->filter(fn ($task) => $task->oversightStatus() === $card['key'])->count()) }}</p>
                <p class="mt-0.5 text-xs font-medium text-slate-400">{{ $card['label'] }}</p>
            </a>
        @endforeach
        <a href="{{ route('admin.dashboard', array_filter(['tab' => 'tasks', 'task_org' => $activeTaskOrg ?: null, 'task_status' => 'overdue', 'task_event' => $taskEventFilter ?: null])) }}"
           class="rounded-2xl border bg-white p-4 shadow-sm transition dark:bg-slate-900 {{ $taskStatusFilter === 'overdue' ? 'border-rose-400 ring-1 ring-rose-200' : 'border-slate-200 hover:border-rose-300 dark:border-slate-800' }}">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-500 dark:bg-rose-950 dark:text-rose-300">
                <i class="fa-solid fa-triangle-exclamation text-sm"></i>
            </span>
            <p class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ number_format($scopedTasks->filter(fn ($task) => $task->isOverdue())->count()) }}</p>
            <p class="mt-0.5 text-xs font-medium text-slate-400">Overdue</p>
        </a>
    </div>

    @if($taskEvent)
        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 dark:border-sky-900 dark:bg-sky-950/40">
            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Tasks for {{ $taskEvent->title }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $taskEvent->organization->name ?? 'Organization removed' }}</p>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2">
            @foreach($taskFilters as $key => $label)
                <a href="{{ route('admin.dashboard', array_filter(['tab' => 'tasks', 'task_org' => $activeTaskOrg ?: null, 'task_status' => $key === 'all' ? null : $key, 'task_event' => $taskEventFilter ?: null])) }}"
                   class="rounded-full px-3 py-1.5 text-xs font-bold transition {{ $taskStatusFilter === $key ? 'bg-jci-blue text-white' : 'border border-slate-200 bg-white text-slate-500 hover:border-sky-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <label class="relative w-full sm:w-64">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-300"></i>
            <input id="admin-task-search" type="search" placeholder="Search tasks, events, orgs..."
                   class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs text-slate-700 placeholder:text-slate-400 focus:border-jci-blue focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
        </label>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-4">
            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Organizations</h4>
            <p class="mt-0.5 text-xs text-slate-400">Focus tasks by organization, or browse the full list below.</p>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse($organizations as $org)
                @php
                    $orgTasks = $postedTasks->filter(fn ($task) => (int) optional($task->event)->organization_id === (int) $org->id);
                    $isSelected = (int) $activeTaskOrg === (int) $org->id;
                    $overdueCount = $orgTasks->filter(fn ($task) => $task->isOverdue())->count();
                    $orgInitials = collect(explode(' ', $org->name))->map(fn ($part) => substr($part, 0, 1))->take(2)->join('');
                @endphp
                <a href="{{ route('admin.dashboard', array_filter(['tab' => 'tasks', 'task_org' => $org->id, 'task_status' => $taskStatusFilter === 'all' ? null : $taskStatusFilter])) }}"
                   data-task-org-card
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
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                        <span><span class="font-bold text-slate-800 dark:text-slate-200">{{ $orgTasks->count() }}</span> tasks</span>
                        @if($overdueCount > 0)
                            <span class="font-semibold text-rose-600">{{ $overdueCount }} overdue</span>
                        @endif
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
                {{ $selectedTaskOrganization ? $selectedTaskOrganization->name : 'All tasks' }}
            </h4>
            <p class="mt-0.5 text-xs text-slate-400">{{ $visibleTasks->count() }} {{ \Illuminate\Support\Str::plural('task', $visibleTasks->count()) }} in this view</p>
        </div>
    </div>

    @if($visibleTasks->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                <i class="fa-solid fa-list-check"></i>
            </span>
            <p class="mt-3 text-sm text-slate-400">No tasks match this filter.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2" id="admin-task-list">
            @foreach($visibleTasks as $task)
                @php
                    $searchBlob = strtolower(trim($task->title.' '.($task->event->title ?? '').' '.($task->event->organization->name ?? '').' '.$task->assignedVolunteerNames().' '.$task->oversightLabel()));
                @endphp
                <article data-task-card data-search="{{ $searchBlob }}" class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h5 class="font-bold leading-snug text-slate-900 dark:text-white">{{ $task->title }}</h5>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $task->event->title ?? 'Event removed' }} · {{ $task->event->organization->name ?? 'Organization removed' }}</p>
                        </div>
                        <span class="inline-flex flex-wrap justify-end gap-1">
                            <x-ui.status-badge :label="$task->oversightLabel()" />
                            @if($task->isOverdue())
                                <x-ui.status-badge label="Overdue" />
                            @endif
                        </span>
                    </div>
                    <div class="mt-3 space-y-1.5 text-xs text-slate-500">
                        <p><i class="fa-regular fa-clock mr-1.5 text-slate-300"></i>{{ $task->due_date ? 'Due '.$task->due_date->format('M d, Y g:i A') : 'No deadline' }}</p>
                        <p><i class="fa-solid fa-user mr-1.5 text-slate-300"></i>{{ $task->assignedVolunteerNames() }}</p>
                        <p class="text-slate-400">{{ $task->oversightStatus() === 'completed' ? 'Completed' : 'Not completed yet' }}</p>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <button type="button" data-panel-toggle="task-detail-{{ $task->id }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-300">Details</button>
                        @if($task->isOverdue())
                            @php
                                $notifyOrganization = $task->event->organization->name ?? 'the organization';
                                $notifyVolunteer = $task->assignedVolunteerNames();
                                $notifyCopy = $notifyVolunteer === 'Unassigned'
                                    ? 'This sends a notice to '.$notifyOrganization.'. It tells them that "'.$task->title.'" is overdue and no volunteer is assigned, and asks them to assign someone who can finish it. The volunteer is not chosen from here.'
                                    : 'This sends a notice to '.$notifyOrganization.'. It tells them that "'.$task->title.'" is overdue and is still with '.$notifyVolunteer.', and asks them to reassign it. The volunteer is not changed from here.';
                            @endphp
                            <button type="button"
                                    data-notify-open
                                    data-notify-action="{{ route('admin.tasks.notify', $task) }}"
                                    data-notify-copy="{{ $notifyCopy }}"
                                    class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">Notify org</button>
                        @endif
                    </div>
                    <div id="task-detail-{{ $task->id }}" class="mt-3 hidden space-y-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                        <p class="text-xs leading-relaxed text-slate-500">{{ $task->description ?: 'No task description was added.' }}</p>
                        <p class="text-[11px] text-slate-500">Priority: {{ ucfirst($task->priority) }} · {{ $task->applications->where('status', 'pending')->count() }} waiting to be assigned</p>
                        @php
                            $taskRole = $task->skills->pluck('name')->filter()->join(', ');
                            if ($taskRole === '') {
                                $taskRole = $task->title;
                            }
                        @endphp
                        <div class="space-y-2 pt-1">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Assigned volunteers</p>
                            @forelse($task->assignments as $assignment)
                                <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 dark:border-slate-800 dark:bg-slate-950">
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $assignment->user->name ?? 'Former volunteer' }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">Role: {{ $taskRole }}</p>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400">No volunteer is assigned to this task yet.</p>
                            @endforelse
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <p id="admin-task-empty" class="hidden py-8 text-center text-sm text-slate-400">No tasks match your search.</p>
    @endif

    <div id="notify-overdue-dialog" class="fixed inset-0 z-[999] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <h3 class="font-black text-slate-900 dark:text-white">Notify the organization</h3>
            <p id="notify-overdue-copy" class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300"></p>
            <form id="notify-overdue-form" method="POST" class="mt-5 flex justify-end gap-2">
                @csrf
                <button type="button" id="notify-overdue-cancel" class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">Cancel</button>
                <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2 text-xs font-bold text-white hover:bg-jci-dark">Yes</button>
            </form>
        </div>
    </div>
</div>

@if($activeTab === 'tasks')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dialog = document.getElementById('notify-overdue-dialog');
        const copy = document.getElementById('notify-overdue-copy');
        const form = document.getElementById('notify-overdue-form');
        const cancel = document.getElementById('notify-overdue-cancel');
        const search = document.getElementById('admin-task-search');
        const cards = Array.from(document.querySelectorAll('[data-task-card]'));
        const orgCards = Array.from(document.querySelectorAll('[data-task-org-card]'));
        const empty = document.getElementById('admin-task-empty');

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

        document.querySelectorAll('[data-panel-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const panel = document.getElementById(button.dataset.panelToggle);
                if (panel) panel.classList.toggle('hidden');
            });
        });

        document.querySelectorAll('[data-notify-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!dialog || !copy || !form) return;
                copy.textContent = button.dataset.notifyCopy || '';
                form.action = button.dataset.notifyAction || '';
                dialog.classList.remove('hidden');
                dialog.classList.add('flex');
            });
        });

        function closeNotifyDialog() {
            if (!dialog) return;
            dialog.classList.add('hidden');
            dialog.classList.remove('flex');
        }

        if (cancel) cancel.addEventListener('click', closeNotifyDialog);
        if (dialog) {
            dialog.addEventListener('click', function (event) {
                if (event.target === dialog) closeNotifyDialog();
            });
        }
    });
</script>
@endif
