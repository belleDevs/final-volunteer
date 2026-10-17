@php
    $selectedVolunteerOrg = (int) request('volunteer_org', 0);
    $selectedVolunteerOrganization = $organizations->firstWhere('id', $selectedVolunteerOrg);
    $roster = $systemUsers->where('role', 'volunteer')->sortBy('name')->values();
    $joinedStatuses = ['approved', 'submitted', 'completed'];
    $rosterStats = $roster->map(function ($volunteer) use ($joinedStatuses) {
        $joined = $volunteer->assignments->whereIn('status', $joinedStatuses);
        $completed = $volunteer->assignments->where('status', 'completed');
        $member = $volunteer->status !== 'approved'
            ? $volunteer->status
            : ($volunteer->availability === 'inactive' ? 'inactive' : 'active');
        $orgIds = collect([$volunteer->organization_id])
            ->merge($volunteer->assignments->map(fn ($assignment) => $assignment->event?->organization_id))
            ->merge($volunteer->taskApplications->map(fn ($application) => $application->event?->organization_id))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return [
            'volunteer' => $volunteer,
            'member' => $member,
            'orgIds' => $orgIds,
            'events' => $joined->pluck('event_id')->unique()->count(),
            'tasks' => $completed->count(),
            'hours' => $completed->sum('hours_logged'),
            'rewards' => $volunteer->volunteerRewards->count(),
            'certificates' => $volunteer->certificates->count(),
        ];
    });
    $volunteerOverview = [
        ['label' => 'Active', 'value' => $rosterStats->where('member', 'active')->count(), 'icon' => 'fa-user-check', 'iconBg' => 'bg-emerald-50 dark:bg-emerald-950', 'iconColor' => 'text-emerald-600 dark:text-emerald-300'],
        ['label' => 'Events joined', 'value' => $rosterStats->sum('events'), 'icon' => 'fa-calendar-check', 'iconBg' => 'bg-sky-50 dark:bg-sky-950', 'iconColor' => 'text-sky-600 dark:text-sky-300'],
        ['label' => 'Tasks completed', 'value' => $rosterStats->sum('tasks'), 'icon' => 'fa-list-check', 'iconBg' => 'bg-indigo-50 dark:bg-indigo-950', 'iconColor' => 'text-indigo-500 dark:text-indigo-300'],
        ['label' => 'Rewards', 'value' => $rosterStats->sum('rewards'), 'icon' => 'fa-award', 'iconBg' => 'bg-amber-50 dark:bg-amber-950', 'iconColor' => 'text-amber-500 dark:text-amber-300'],
    ];

    $enrichRow = function ($row, $org = null) use ($joinedStatuses) {
        $volunteer = $row['volunteer'];
        if ($org) {
            $orgId = (int) $org->id;
            $orgAssignments = $volunteer->assignments->filter(fn ($assignment) => (int) ($assignment->event?->organization_id ?? 0) === $orgId);
            $row['joinedEvents'] = $orgAssignments->whereIn('status', $joinedStatuses)->unique('event_id')->values();
            $row['completedTasks'] = $orgAssignments->where('status', 'completed')->values();
            $row['orgRewards'] = $volunteer->volunteerRewards->filter(fn ($reward) => (int) $reward->organization_id === $orgId)->values();
            $row['orgCertificates'] = $volunteer->certificates->filter(fn ($certificate) => (int) ($certificate->event?->organization_id ?? 0) === $orgId)->values();
            $row['orgHours'] = $row['completedTasks']->sum('hours_logged');
            $row['scopeLabel'] = $org->name;
        } else {
            $row['joinedEvents'] = $volunteer->assignments->whereIn('status', $joinedStatuses)->unique('event_id')->values();
            $row['completedTasks'] = $volunteer->assignments->where('status', 'completed')->values();
            $row['orgRewards'] = $volunteer->volunteerRewards;
            $row['orgCertificates'] = $volunteer->certificates;
            $row['orgHours'] = $row['hours'];
            $row['scopeLabel'] = 'All organizations';
        }
        $row['initials'] = collect(explode(' ', trim($volunteer->name)))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->join('');
        $row['memberLabel'] = match ($row['member']) {
            'inactive' => 'Inactive',
            'pending' => 'Pending',
            'rejected' => 'Rejected',
            default => 'Active',
        };
        $row['searchText'] = strtolower(trim($volunteer->name.' '.$volunteer->email.' '.$volunteer->skills->pluck('name')->join(' ').$row['scopeLabel']));

        return $row;
    };

    $displayRoster = $selectedVolunteerOrganization
        ? $rosterStats
            ->filter(fn ($row) => $row['orgIds']->contains((int) $selectedVolunteerOrganization->id))
            ->map(fn ($row) => $enrichRow($row, $selectedVolunteerOrganization))
            ->values()
        : $rosterStats->map(fn ($row) => $enrichRow($row))->values();
@endphp

<div class="space-y-5">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="bg-gradient-to-r from-jci-blue to-jci-dark px-5 py-5 text-white sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/70">Roster</p>
                    <h4 class="mt-1 text-xl font-extrabold tracking-tight">{{ number_format($roster->count()) }} {{ \Illuminate\Support\Str::plural('volunteer', $roster->count()) }}</h4>
                    <p class="mt-1 text-sm text-white/75">Membership, event participation, completed work, and rewards.</p>
                </div>
                @if($selectedVolunteerOrganization)
                    <a href="{{ route('admin.dashboard', ['tab' => 'volunteers']) }}" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-3 py-2 text-xs font-semibold text-white hover:bg-white/25">
                        <i class="fa-solid fa-xmark"></i> Clear organization
                    </a>
                @endif
            </div>
        </div>
    </section>

    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach($volunteerOverview as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }}">
                    <i class="fa-solid {{ $card['icon'] }} text-sm"></i>
                </span>
                <p class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ number_format($card['value']) }}</p>
                <p class="mt-0.5 text-xs font-medium text-slate-400">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Organizations</h4>
                <p class="mt-0.5 text-xs text-slate-400">Filter the roster by organization, or browse everyone below.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="flex flex-wrap gap-2">
                    @foreach(['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $key => $label)
                        <button type="button" data-volunteer-filter="{{ $key }}" class="rounded-full px-3 py-1.5 text-xs font-bold {{ $key === 'all' ? 'bg-jci-blue text-white' : 'border border-slate-200 bg-slate-50 text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">{{ $label }}</button>
                    @endforeach
                </div>
                <label class="relative w-full sm:w-56">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-300"></i>
                    <input id="volunteer-search" type="search" placeholder="Search volunteers..."
                           class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs focus:border-jci-blue focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                </label>
            </div>
        </div>
        <div class="grid grid-cols-1 items-stretch gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse($organizations as $org)
                @php
                    $linked = $rosterStats->filter(fn ($row) => $row['orgIds']->contains((int) $org->id));
                    $isSelected = (int) $selectedVolunteerOrg === (int) $org->id;
                    $orgInitials = collect(explode(' ', $org->name))->map(fn ($part) => substr($part, 0, 1))->take(2)->join('');
                @endphp
                <a href="{{ route('admin.dashboard', ['tab' => 'volunteers', 'volunteer_org' => $org->id]) }}"
                   class="flex min-h-[6.5rem] flex-col rounded-2xl border px-4 py-4 transition {{ $isSelected ? 'border-jci-blue bg-sky-50 shadow-sm dark:bg-sky-950/30' : 'border-slate-200 hover:border-sky-300 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800' }}">
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
                    <div class="mt-auto flex items-center gap-3 pt-3 text-xs text-slate-500">
                        <span><span class="font-bold text-slate-800 dark:text-slate-200">{{ $linked->count() }}</span> volunteers</span>
                        <span><span class="font-bold text-slate-800 dark:text-slate-200">{{ $linked->where('member', 'active')->count() }}</span> active</span>
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
                {{ $selectedVolunteerOrganization ? $selectedVolunteerOrganization->name : 'All volunteers' }}
            </h4>
            <p class="mt-0.5 text-xs text-slate-400">{{ $displayRoster->count() }} {{ \Illuminate\Support\Str::plural('volunteer', $displayRoster->count()) }} in this view</p>
        </div>
    </div>

    @if($displayRoster->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                <i class="fa-solid fa-user-group"></i>
            </span>
            <p class="mt-3 text-sm text-slate-400">No volunteers in this view yet.</p>
        </div>
    @else
        <div class="grid grid-cols-1 items-stretch gap-4 md:grid-cols-2 xl:grid-cols-3" id="volunteer-roster">
            @foreach($displayRoster as $row)
                @php $volunteer = $row['volunteer']; @endphp
                <article data-volunteer-card data-volunteer-id="{{ $volunteer->id }}" data-member="{{ $row['member'] }}" data-search="{{ $row['searchText'] }}"
                         class="flex h-full min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-11 shrink-0 items-center gap-3">
                        @if($volunteer->profile_photo_path)
                            <img src="{{ asset('storage/'.$volunteer->profile_photo_path) }}" alt="{{ $volunteer->name }}" class="h-11 w-11 shrink-0 rounded-full object-cover">
                        @else
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-jci-blue text-xs font-black text-white">{{ $row['initials'] }}</span>
                        @endif
                        <div class="min-w-0">
                            <h5 class="truncate font-bold text-slate-900 dark:text-white">{{ $volunteer->name }}</h5>
                            <div class="mt-0.5">
                                <x-ui.status-badge :label="$row['memberLabel']" />
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 flex h-12 shrink-0 flex-wrap content-start gap-1.5 overflow-hidden">
                        @forelse($volunteer->skills->take(3) as $skill)
                            <span class="max-w-full truncate rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $skill->name }}</span>
                        @empty
                            <span class="text-[10px] text-slate-400">No skills listed</span>
                        @endforelse
                        @if($volunteer->skills->count() > 3)
                            <span class="px-1 text-[10px] font-semibold text-slate-400">+{{ $volunteer->skills->count() - 3 }}</span>
                        @endif
                    </div>
                    <div class="mt-auto grid grid-cols-3 gap-2 pt-4 text-center">
                        <div class="flex h-16 flex-col items-center justify-center rounded-xl bg-slate-50 px-2 dark:bg-slate-950">
                            <p class="text-lg font-black leading-none text-slate-900 dark:text-white">{{ $row['joinedEvents']->count() }}</p>
                            <p class="mt-1 text-[10px] font-semibold text-slate-400">Events</p>
                        </div>
                        <div class="flex h-16 flex-col items-center justify-center rounded-xl bg-slate-50 px-2 dark:bg-slate-950">
                            <p class="text-lg font-black leading-none text-slate-900 dark:text-white">{{ $row['completedTasks']->count() }}</p>
                            <p class="mt-1 text-[10px] font-semibold text-slate-400">Tasks</p>
                        </div>
                        <div class="flex h-16 flex-col items-center justify-center rounded-xl bg-slate-50 px-2 dark:bg-slate-950">
                            <p class="text-lg font-black leading-none text-slate-900 dark:text-white">{{ $row['orgRewards']->count() }}</p>
                            <p class="mt-1 text-[10px] font-semibold text-slate-400">Rewards</p>
                        </div>
                    </div>
                    <p class="mt-2 h-5 shrink-0 truncate text-center text-[11px] text-slate-400">{{ rtrim(rtrim(number_format((float) $row['orgHours'], 1), '0'), '.') }}h · {{ $row['orgCertificates']->count() }} {{ \Illuminate\Support\Str::plural('certificate', $row['orgCertificates']->count()) }}</p>
                    <button type="button" data-panel-toggle="volunteer-detail-{{ $volunteer->id }}" class="mt-3 inline-flex h-10 w-full shrink-0 items-center justify-center rounded-xl bg-jci-blue text-xs font-bold text-white transition hover:bg-jci-dark">View</button>
                </article>
            @endforeach
        </div>

        <div id="volunteer-details" class="space-y-4">
            @foreach($displayRoster as $row)
                @php $volunteer = $row['volunteer']; @endphp
                <div id="volunteer-detail-{{ $volunteer->id }}" data-volunteer-detail="{{ $volunteer->id }}" class="hidden rounded-2xl border border-sky-100 bg-white p-5 shadow-sm dark:border-sky-900 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h5 class="font-bold text-slate-900 dark:text-white">{{ $volunteer->name }}</h5>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $row['scopeLabel'] }} · {{ $row['memberLabel'] }}</p>
                        </div>
                        <button type="button" data-panel-toggle="volunteer-detail-{{ $volunteer->id }}" class="shrink-0 text-xs font-bold text-slate-400 hover:text-slate-700">Close</button>
                    </div>
                    <div class="mt-4 grid grid-cols-1 items-stretch gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div class="flex min-h-56 min-w-0 flex-col rounded-2xl bg-slate-50 p-3 dark:bg-slate-950">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Profile</p>
                            <p class="mt-2 break-all text-xs text-slate-700 dark:text-slate-200">{{ $volunteer->email }}</p>
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ $volunteer->phone ?: 'No phone on record' }}</p>
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">Joined {{ $volunteer->created_at?->format('M d, Y') }}</p>
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ rtrim(rtrim(number_format((float) $row['orgHours'], 1), '0'), '.') }} hours in this view</p>
                            @if($volunteer->skills->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($volunteer->skills as $skill)
                                        <span class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-900">{{ $skill->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if($volunteer->bio)
                                <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-300">{{ $volunteer->bio }}</p>
                            @endif
                        </div>
                        <div class="flex min-h-56 min-w-0 flex-col rounded-2xl bg-slate-50 p-3 dark:bg-slate-950">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Events joined</p>
                            <div class="mt-2 flex-1 space-y-2">
                                @forelse($row['joinedEvents']->take(8) as $assignment)
                                    <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-900">
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $assignment->event->title ?? 'Event removed' }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $assignment->event?->start_time?->format('M d, Y') ?? 'Date unavailable' }}</p>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400">No accepted events yet.</p>
                                @endforelse
                            </div>
                        </div>
                        <div class="flex min-h-56 min-w-0 flex-col rounded-2xl bg-slate-50 p-3 dark:bg-slate-950">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Tasks completed</p>
                            <div class="mt-2 flex-1 space-y-2">
                                @forelse($row['completedTasks']->take(8) as $assignment)
                                    <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-900">
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $assignment->task->title ?? 'Task removed' }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $assignment->event->title ?? 'Event removed' }}</p>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400">No completed tasks yet.</p>
                                @endforelse
                            </div>
                        </div>
                        <div class="flex min-h-56 min-w-0 flex-col rounded-2xl bg-slate-50 p-3 dark:bg-slate-950">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Rewards & certificates</p>
                            <div class="mt-2 flex-1 space-y-2">
                                @forelse($row['orgRewards']->take(6) as $reward)
                                    <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-900">
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $reward->label() ?? 'Reward' }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $reward->reward_scope === 'event' ? ($reward->event->title ?? 'Event reward') : ($reward->task->title ?? 'Task reward') }}</p>
                                    </div>
                                @empty
                                    @if($row['orgCertificates']->isEmpty())
                                        <p class="text-xs text-slate-400">No reward recorded yet.</p>
                                    @endif
                                @endforelse
                                @foreach($row['orgCertificates']->take(6) as $certificate)
                                    <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-900">
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-100">Certificate {{ $certificate->certificate_code }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $certificate->event->title ?? 'Event removed' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <p id="volunteer-empty" class="hidden py-8 text-center text-sm text-slate-400">No volunteers match this view.</p>
    @endif
</div>

@if($activeTab === 'volunteers')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const search = document.getElementById('volunteer-search');
        const empty = document.getElementById('volunteer-empty');
        const cards = Array.from(document.querySelectorAll('[data-volunteer-card]'));
        let memberFilter = 'all';

        function applyRoster() {
            const query = (search?.value || '').trim().toLowerCase();
            let visible = 0;
            cards.forEach(function (card) {
                const matchesMember = memberFilter === 'all' || card.dataset.member === memberFilter;
                const matchesQuery = query === '' || (card.dataset.search || '').includes(query);
                const show = matchesMember && matchesQuery;
                card.classList.toggle('hidden', !show);
                const detail = document.getElementById('volunteer-detail-' + card.dataset.volunteerId);
                if (detail && !show) detail.classList.add('hidden');
                const cardButton = card.querySelector('[data-panel-toggle]');
                if (!show && cardButton) cardButton.textContent = 'View';
                if (show) visible += 1;
            });
            if (empty) empty.classList.toggle('hidden', visible !== 0 || cards.length === 0);
        }

        function closeDetails(exceptId) {
            document.querySelectorAll('[data-volunteer-detail]').forEach(function (panel) {
                if (panel.id !== exceptId) panel.classList.add('hidden');
            });
            document.querySelectorAll('#volunteer-roster [data-panel-toggle]').forEach(function (button) {
                if (button.dataset.panelToggle !== exceptId && button.textContent.trim() === 'Hide') {
                    button.textContent = 'View';
                }
            });
        }

        search?.addEventListener('input', applyRoster);
        document.querySelectorAll('[data-volunteer-filter]').forEach(function (button) {
            button.addEventListener('click', function () {
                memberFilter = button.dataset.volunteerFilter;
                document.querySelectorAll('[data-volunteer-filter]').forEach(function (item) {
                    const selected = item === button;
                    item.classList.toggle('bg-jci-blue', selected);
                    item.classList.toggle('text-white', selected);
                    item.classList.toggle('bg-slate-50', !selected);
                    item.classList.toggle('text-slate-500', !selected);
                    item.classList.toggle('border', !selected);
                    item.classList.toggle('border-slate-200', !selected);
                });
                applyRoster();
            });
        });
        document.querySelectorAll('[data-panel-toggle]').forEach(function (button) {
            if (!button.dataset.panelToggle.startsWith('volunteer-detail-')) return;
            button.addEventListener('click', function () {
                const panel = document.getElementById(button.dataset.panelToggle);
                if (!panel) return;
                const willOpen = panel.classList.contains('hidden');
                closeDetails(willOpen ? panel.id : '');
                panel.classList.toggle('hidden', !willOpen);
                const cardButton = document.querySelector('#volunteer-roster article [data-panel-toggle="' + panel.id + '"]');
                if (cardButton) cardButton.textContent = willOpen ? 'Hide' : 'View';
                if (willOpen) panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        });
    });
</script>
@endif
