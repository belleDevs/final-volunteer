<x-layout.admin :activeTab="$activeTab">

    <!-- TAB 1: ADMIN DASHBOARD -->
    <div class="{{ $activeTab === 'dashboard' ? '' : 'hidden' }} space-y-5">
        @php
            $pendingOrgCount = $pendingOrgs->count();
            $reviewWork = $pendingOrgCount + $pendingCompletionReviews;
            $activityItems = [
                ['label' => 'Completion Reviews', 'value' => $pendingCompletionReviews, 'caption' => 'Waiting for organization approval', 'color' => '#f59e0b'],
                ['label' => 'Issued Certificates', 'value' => $issuedCertificates, 'caption' => 'Generated after verified tasks', 'color' => '#10b981'],
                ['label' => 'Organization Messages', 'value' => $organizationMessageCount, 'caption' => 'Messages between admin and organizations', 'color' => '#0097d6'],
                ['label' => 'System Notices', 'value' => $broadcastLogCount, 'caption' => 'Portal announcements logged', 'color' => '#a855f7'],
            ];
            $activityTotal = array_sum(array_column($activityItems, 'value'));
        @endphp

        <div class="rounded-3xl bg-gradient-to-r from-jci-blue to-sky-400 text-white px-5 py-5 sm:px-7 sm:py-6 shadow-lg shadow-sky-500/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start gap-4 min-w-0">
                <span class="h-11 w-11 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                </span>
                <div class="min-w-0">
                    @if($reviewWork > 0)
                        <h2 class="text-lg sm:text-xl font-extrabold tracking-tight">{{ $reviewWork }} {{ $reviewWork === 1 ? 'item still needs' : 'items still need' }} review</h2>
                        <p class="text-sm text-white/85 mt-1">{{ $pendingOrgCount }} organization {{ Str::plural('registration', $pendingOrgCount) }} and {{ $pendingCompletionReviews }} completion {{ Str::plural('review', $pendingCompletionReviews) }} are waiting.</p>
                    @else
                        <h2 class="text-lg sm:text-xl font-extrabold tracking-tight">Nothing is waiting for review</h2>
                        <p class="text-sm text-white/85 mt-1">{{ $approvedOrgs }} approved {{ Str::plural('organization', $approvedOrgs) }}, {{ $issuedCertificates }} issued {{ Str::plural('certificate', $issuedCertificates) }}, and {{ $broadcastLogCount }} system {{ Str::plural('notice', $broadcastLogCount) }} are on record.</p>
                    @endif
                </div>
            </div>
            @if($pendingOrgCount > 0)
                <a href="{{ route('admin.dashboard', ['tab' => 'audits']) }}" class="shrink-0 inline-flex items-center justify-center bg-white/20 hover:bg-white/30 border border-white/20 text-white text-sm font-bold px-5 py-2.5 rounded-full transition">
                    Review registrations
                </a>
            @elseif($reviewWork === 0)
                <a href="{{ route('admin.dashboard', ['tab' => 'broadcast']) }}" class="shrink-0 inline-flex items-center justify-center bg-white/20 hover:bg-white/30 border border-white/20 text-white text-sm font-bold px-5 py-2.5 rounded-full transition">
                    Open notices
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach([
                ['key' => 'volunteers', 'label' => 'Approved Volunteers', 'value' => $totalVolunteers, 'icon' => 'fa-users', 'iconBg' => 'bg-blue-50', 'iconColor' => 'text-jci-blue'],
                ['key' => 'events', 'label' => 'Current Events', 'value' => $activeEvents, 'icon' => 'fa-calendar-check', 'iconBg' => 'bg-emerald-50', 'iconColor' => 'text-emerald-600'],
                ['key' => 'orgs', 'label' => 'Approved Orgs', 'value' => $approvedOrgs, 'icon' => 'fa-building', 'iconBg' => 'bg-purple-50', 'iconColor' => 'text-purple-600'],
                ['key' => 'tasks', 'label' => 'Current Open Tasks', 'value' => $openTasks, 'icon' => 'fa-list-check', 'iconBg' => 'bg-amber-50', 'iconColor' => 'text-amber-600'],
            ] as $card)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-5">
                    <div class="flex items-start justify-between gap-3">
                        <span class="h-10 w-10 rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }} flex items-center justify-center">
                            <i class="fa-solid {{ $card['icon'] }}"></i>
                        </span>
                        @if($metricTrends[$card['key']])
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-1 rounded-full {{ $metricTrends[$card['key']]['direction'] === 'up' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                <i class="fa-solid {{ $metricTrends[$card['key']]['direction'] === 'up' ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }} text-[10px]"></i>
                                {{ $metricTrends[$card['key']]['label'] }}
                            </span>
                        @endif
                    </div>
                    <p class="mt-5 text-3xl font-black text-slate-900 tracking-tight">{{ number_format($card['value']) }}</p>
                    <p class="mt-1 text-sm text-slate-400 font-medium">{{ $card['label'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="xl:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h4 class="font-extrabold text-slate-900">Volunteer Growth</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Approved signups over the last 6 months</p>
                    </div>
                    @if($volunteerGrowthTrend)
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full shrink-0 {{ $volunteerGrowthTrend['direction'] === 'up' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                            <i class="fa-solid {{ $volunteerGrowthTrend['direction'] === 'up' ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }} text-[10px]"></i>
                            {{ $volunteerGrowthTrend['label'] }} MoM
                        </span>
                    @endif
                </div>
                <div class="h-72">
                    <canvas id="volunteerGrowthChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6">
                <div class="mb-2">
                    <h4 class="font-extrabold text-slate-900">System Activity</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Live snapshot</p>
                </div>
                <div class="h-52 relative">
                    <canvas id="activitySnapshotChart"></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-black text-slate-900">{{ number_format($activityTotal) }}</span>
                        <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Logged</span>
                    </div>
                </div>
                <ul class="mt-4 space-y-2.5">
                    @foreach($activityItems as $item)
                        <li class="flex items-start gap-2.5">
                            <span class="mt-1.5 h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $item['color'] }}"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <span class="text-xs font-bold text-slate-700">{{ $item['label'] }}</span>
                                    <span class="text-xs font-black text-slate-900">{{ number_format($item['value']) }}</span>
                                </div>
                                <p class="text-[10px] text-slate-400">{{ $item['caption'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        @php
            $eventStatusTotal = array_sum(array_column($dashboardEventStatus, 'value'));
            $taskStatusTotal = collect($dashboardTaskStatus)->where('key', '!=', 'overdue')->sum('value');
        @endphp

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div>
                        <h4 class="font-extrabold text-slate-900">Event status</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Counted from each event's saved status</p>
                    </div>
                    <a href="{{ route('admin.dashboard', ['tab' => 'events']) }}" class="text-xs font-bold text-jci-blue hover:underline shrink-0">Open events</a>
                </div>
                @if($eventStatusTotal === 0)
                    <p class="py-16 text-center text-sm text-slate-400">No events are on record yet.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-[220px_1fr] gap-4 items-center">
                        <div class="h-52 relative">
                            <canvas id="eventStatusChart"></canvas>
                            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-black text-slate-900">{{ number_format($eventStatusTotal) }}</span>
                                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Events</span>
                            </div>
                        </div>
                        <ul class="space-y-2">
                            @foreach($dashboardEventStatus as $item)
                                <li>
                                    <a href="{{ route('admin.dashboard', ['tab' => 'events', 'event_status' => $item['key']]) }}" class="flex items-center justify-between gap-3 rounded-xl px-2 py-1.5 hover:bg-slate-50">
                                        <span class="flex items-center gap-2 min-w-0">
                                            <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $item['color'] }}"></span>
                                            <span class="text-xs font-bold text-slate-700 truncate">{{ $item['label'] }}</span>
                                        </span>
                                        <span class="text-xs font-black text-slate-900">{{ number_format($item['value']) }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div>
                        <h4 class="font-extrabold text-slate-900">Task status</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Overdue is a deadline flag. Those tasks also stay in their saved status.</p>
                    </div>
                    <a href="{{ route('admin.dashboard', ['tab' => 'tasks']) }}" class="text-xs font-bold text-jci-blue hover:underline shrink-0">Open tasks</a>
                </div>
                @if($taskStatusTotal === 0)
                    <p class="py-16 text-center text-sm text-slate-400">No tasks are on record yet.</p>
                @else
                    <div class="h-64">
                        <canvas id="taskStatusChart"></canvas>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">
            <div class="xl:col-span-3 bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6">
                <h4 class="font-extrabold text-slate-900">Participation</h4>
                <p class="text-xs text-slate-400 mt-0.5">Events that started in each of the last 6 months, and the volunteers recorded on those events</p>
                <div class="h-72 mt-4">
                    <canvas id="participationChart"></canvas>
                </div>
            </div>

            <div class="xl:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6">
                <h4 class="font-extrabold text-slate-900">Organizations</h4>
                <p class="text-xs text-slate-400 mt-0.5">Events, tasks, and volunteers recorded for each organization</p>
                @if($dashboardOrganizations->isEmpty())
                    <p class="py-16 text-center text-sm text-slate-400">No organizations are on record yet.</p>
                @else
                    <div class="h-56 mt-4">
                        <canvas id="organizationChart"></canvas>
                    </div>
                    <ul class="mt-4 space-y-2">
                        @foreach($dashboardOrganizations as $org)
                            <li>
                                <a href="{{ route('admin.dashboard', ['tab' => 'events', 'org' => $org['id']]) }}" class="flex items-center justify-between gap-3 rounded-xl px-2 py-1.5 hover:bg-slate-50">
                                    <span class="text-xs font-bold text-slate-700 truncate">{{ $org['name'] }}</span>
                                    <span class="text-[11px] font-semibold text-slate-400 shrink-0">{{ $org['events'] }} events · {{ $org['tasks'] }} tasks · {{ $org['volunteers'] }} volunteers</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    @if($activeTab === 'dashboard')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const growthCanvas = document.getElementById('volunteerGrowthChart');
                const growthGradient = growthCanvas.getContext('2d').createLinearGradient(0, 0, 0, 280);
                growthGradient.addColorStop(0, 'rgba(0, 151, 214, 0.28)');
                growthGradient.addColorStop(1, 'rgba(0, 151, 214, 0)');

                new Chart(growthCanvas, {
                    type: 'line',
                    data: {
                        labels: @json($volunteerGrowthLabels),
                        datasets: [{
                            label: 'Approved signups',
                            data: @json($volunteerGrowthValues),
                            borderColor: '#0097d6',
                            backgroundColor: growthGradient,
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointBackgroundColor: '#0097d6',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { displayColors: false },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#94a3b8', font: { size: 11 } },
                                border: { display: false },
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { color: '#94a3b8', font: { size: 11 }, precision: 0 },
                                grid: { color: 'rgba(148, 163, 184, 0.18)' },
                                border: { display: false },
                            },
                        },
                    },
                });

                const activityItems = @json($activityItems);
                const activityTotal = activityItems.reduce((sum, item) => sum + Number(item.value), 0);

                new Chart(document.getElementById('activitySnapshotChart'), {
                    type: 'doughnut',
                    data: {
                        labels: activityTotal === 0 ? ['No activity yet'] : activityItems.map(item => item.label),
                        datasets: [{
                            data: activityTotal === 0 ? [1] : activityItems.map(item => item.value),
                            backgroundColor: activityTotal === 0 ? ['#e2e8f0'] : activityItems.map(item => item.color),
                            borderWidth: 0,
                            hoverOffset: 4,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                enabled: activityTotal !== 0,
                                displayColors: false,
                            },
                        },
                    },
                });

                const axis = {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 } },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94a3b8', font: { size: 11 }, precision: 0 },
                        grid: { color: 'rgba(148, 163, 184, 0.18)' },
                        border: { display: false },
                    },
                };

                const eventStatusCanvas = document.getElementById('eventStatusChart');
                if (eventStatusCanvas) {
                    const slices = @json(collect($dashboardEventStatus)->where('value', '>', 0)->values());
                    new Chart(eventStatusCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: slices.map(item => item.label),
                            datasets: [{
                                data: slices.map(item => item.value),
                                backgroundColor: slices.map(item => item.color),
                                borderWidth: 0,
                                hoverOffset: 4,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '72%',
                            plugins: { legend: { display: false } },
                        },
                    });
                }

                const taskStatusCanvas = document.getElementById('taskStatusChart');
                if (taskStatusCanvas) {
                    const bars = @json($dashboardTaskStatus);
                    new Chart(taskStatusCanvas, {
                        type: 'bar',
                        data: {
                            labels: bars.map(item => item.label),
                            datasets: [{
                                label: 'Tasks',
                                data: bars.map(item => item.value),
                                backgroundColor: bars.map(item => item.color),
                                borderRadius: 6,
                                maxBarThickness: 28,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: axis.y,
                                y: { ...axis.x, ticks: { color: '#475569', font: { size: 11, weight: '600' } } },
                            },
                        },
                    });
                }

                const participationCanvas = document.getElementById('participationChart');
                if (participationCanvas) {
                    new Chart(participationCanvas, {
                        type: 'bar',
                        data: {
                            labels: @json($dashboardMonthLabels),
                            datasets: [
                                { label: 'Events', data: @json($dashboardMonthEvents), backgroundColor: '#0097d6', borderRadius: 6, maxBarThickness: 28 },
                                { label: 'Volunteers', data: @json($dashboardMonthVolunteers), backgroundColor: '#7dd3fc', borderRadius: 6, maxBarThickness: 28 },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                            scales: axis,
                        },
                    });
                }

                const organizationCanvas = document.getElementById('organizationChart');
                if (organizationCanvas) {
                    const organizations = @json($dashboardOrganizations);
                    new Chart(organizationCanvas, {
                        type: 'bar',
                        data: {
                            labels: organizations.map(org => org.name),
                            datasets: [
                                { label: 'Events', data: organizations.map(org => org.events), backgroundColor: '#0097d6', borderRadius: 6, maxBarThickness: 16 },
                                { label: 'Tasks', data: organizations.map(org => org.tasks), backgroundColor: '#0284c7', borderRadius: 6, maxBarThickness: 16 },
                                { label: 'Volunteers', data: organizations.map(org => org.volunteers), backgroundColor: '#7dd3fc', borderRadius: 6, maxBarThickness: 16 },
                            ],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                            scales: {
                                x: axis.y,
                                y: { grid: { display: false }, ticks: { color: '#475569', font: { size: 11 } }, border: { display: false } },
                            },
                        },
                    });
                }
            });
        </script>
    @endif

    <!-- TAB 2: ORG AUDITS -->
    <div class="{{ $activeTab === 'audits' ? '' : 'hidden' }} glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-building-circle-check text-jci-blue"></i> Organization Registration Audits
            </h4>
            <span class="bg-amber-100 text-amber-800 text-xs px-2.5 py-0.5 rounded-full font-semibold">
                {{ $pendingOrgs->count() }} Pending Reviews
            </span>
        </div>

        @if($pendingOrgs->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($pendingOrgs as $org)
                    <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-200/80 hover-lift premium-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h5 class="font-bold text-sm text-slate-800">{{ $org->name }}</h5>
                                <p class="text-[10px] text-slate-400">Email: {{ $org->email }}</p>
                                <p class="text-[10px] text-jci-blue font-bold mt-1">{{ $org->organizationTypeLabel() ?? 'Type not set' }}</p>
                            </div>
                            <span class="bg-amber-500 text-white text-[9px] px-2 py-0.5 rounded font-bold">Pending Review</span>
                        </div>
                        <p class="text-xs text-slate-600 mb-3 leading-relaxed">
                            {{ $org->bio ?? 'No description provided.' }}
                        </p>
                        <div class="flex space-x-2">
                            <!-- Approve Action -->
                            <form action="{{ route('admin.orgs.approve', $org->id) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full bg-jci-blue hover:bg-jci-dark text-white text-xs py-1.5 rounded-lg font-bold transition">
                                    Approve
                                </button>
                            </form>
                            <!-- Reject Action -->
                            <form action="{{ route('admin.orgs.reject', $org->id) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full text-slate-500 hover:text-red-600 bg-slate-200 hover:bg-slate-300 text-xs py-1.5 rounded-lg font-semibold transition">
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                <i class="fa-solid fa-circle-check text-slate-300 text-4xl mb-2"></i>
                <p class="text-xs text-slate-400 font-medium">No pending organization registrations to review.</p>
            </div>
        @endif
    </div>

    <!-- TAB: ORGANIZATIONS -->
    <div class="{{ $activeTab === 'organizations' ? '' : 'hidden' }} space-y-5">
        @php
            $approvedOrganizationCount = $organizations->count();
            $pendingOrganizationCount = $pendingOrgs->count();
            $rejectedOrganizationCount = $rejectedOrgCount;
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Approved', 'value' => $approvedOrganizationCount, 'icon' => 'fa-circle-check', 'iconBg' => 'bg-emerald-50', 'iconColor' => 'text-emerald-600'],
                ['label' => 'Pending', 'value' => $pendingOrganizationCount, 'icon' => 'fa-clock', 'iconBg' => 'bg-amber-50', 'iconColor' => 'text-amber-500'],
                ['label' => 'Rejected', 'value' => $rejectedOrganizationCount, 'icon' => 'fa-circle-xmark', 'iconBg' => 'bg-rose-50', 'iconColor' => 'text-rose-500'],
                ['label' => 'Total Orgs', 'value' => $organizations->count(), 'icon' => 'fa-building', 'iconBg' => 'bg-blue-50', 'iconColor' => 'text-jci-blue'],
            ] as $card)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-5">
                    <span class="h-10 w-10 rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }} flex items-center justify-center">
                        <i class="fa-solid {{ $card['icon'] }}"></i>
                    </span>
                    <p class="mt-4 text-3xl font-black text-slate-900 tracking-tight">{{ number_format($card['value']) }}</p>
                    <p class="mt-1 text-sm text-slate-400 font-medium">{{ $card['label'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 p-5 border-b border-slate-100">
                <div>
                    <h4 class="font-extrabold text-slate-900">Organization Directory</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Approved organizations only. Applications still waiting for review stay in Org Registrations.</p>
                </div>
                <label class="relative w-full lg:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input id="org-list-search" type="search" placeholder="Search organizations..."
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-jci-blue/30 focus:border-jci-blue">
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-[11px] uppercase tracking-wider text-slate-400">
                        <tr class="border-b border-slate-100">
                            <th class="px-5 py-3 font-semibold">Organization</th>
                            <th class="px-4 py-3 font-semibold">Type</th>
                            <th class="px-4 py-3 font-semibold">Events</th>
                            <th class="px-4 py-3 font-semibold">Volunteers</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($organizations as $org)
                            @php
                                $typeLabel = $org->organizationTypeLabel();
                                $location = $organizationLocations[$org->id] ?? null;
                                $volunteerCount = $organizationVolunteerCounts[$org->id] ?? 0;
                                $initials = collect(explode(' ', $org->name))->map(fn($part) => substr($part, 0, 1))->take(2)->join('');
                                $searchText = strtolower(trim($org->name . ' ' . $org->email . ' ' . ($typeLabel ?? '') . ' ' . ($location ?? '')));
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition" data-org-row data-search="{{ $searchText }}">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3 min-w-56">
                                        @if($org->profile_photo_path)
                                            <img src="{{ asset('storage/' . $org->profile_photo_path) }}" alt="{{ $org->name }}" class="h-9 w-9 rounded-full object-cover shrink-0">
                                        @else
                                            <span class="h-9 w-9 rounded-full bg-jci-blue text-white flex items-center justify-center text-[11px] font-bold shrink-0">
                                                {{ strtoupper($initials) }}
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-800 truncate">{{ $org->name }}</div>
                                            <div class="text-xs text-slate-400 truncate flex items-center gap-1">
                                                @if($location)
                                                    <i class="fa-solid fa-location-dot text-[10px]"></i> {{ $location }}
                                                @else
                                                    <i class="fa-solid fa-envelope text-[10px]"></i> {{ $org->email }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600 whitespace-nowrap">{{ $typeLabel ?? 'Not set' }}</td>
                                <td class="px-4 py-3.5 text-slate-700 font-semibold">{{ number_format($org->events_count) }}</td>
                                <td class="px-4 py-3.5 text-slate-700 font-semibold">{{ number_format($volunteerCount) }}</td>
                                <td class="px-4 py-3.5">
                                    <x-ui.status-badge :label="ucfirst((string) $org->status)" />
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center justify-end">
                                        <button type="button" data-org-toggle="{{ $org->id }}" title="View details" class="h-8 w-8 rounded-full border border-slate-200 text-slate-400 hover:text-jci-blue hover:border-jci-blue flex items-center justify-center transition">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr id="org-detail-{{ $org->id }}" class="hidden bg-slate-50/80" data-org-detail>
                                <td colspan="6" class="px-5 py-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs text-slate-600">
                                        <div>
                                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Email</span>
                                            {{ $org->email }}
                                        </div>
                                        <div>
                                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Phone</span>
                                            {{ $org->phone ?: 'No phone listed' }}
                                        </div>
                                        <div>
                                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Socio-civic type</span>
                                            {{ $typeLabel ?? 'Not set' }}
                                        </div>
                                        <div class="md:col-span-3">
                                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Mission</span>
                                            {{ $org->bio ?: 'No mission statement provided.' }}
                                        </div>
                                        <form action="{{ route('admin.orgs.documents', $org->id) }}" method="POST" class="md:col-span-3 grid gap-2 md:grid-cols-2">
                                            @csrf
                                            <div class="md:col-span-2">
                                                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Request documents</span>
                                                <p class="text-[11px] text-slate-400">Only an administrator can change verification. This asks the organization for more information.</p>
                                            </div>
                                            <textarea name="documents_requested" required maxlength="1000" rows="2" placeholder="Document to submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">{{ $org->documents_requested }}</textarea>
                                            <textarea name="feedback" maxlength="1000" rows="2" placeholder="Feedback for the organization" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">{{ $org->verification_feedback }}</textarea>
                                            <div>
                                                <button type="submit" class="rounded-lg bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Request documents</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">No organizations found.</td>
                            </tr>
                        @endforelse
                        <tr id="org-list-empty" class="hidden">
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">No organizations match this search.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($activeTab === 'organizations')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const search = document.getElementById('org-list-search');
                const rows = Array.from(document.querySelectorAll('[data-org-row]'));
                const empty = document.getElementById('org-list-empty');

                function applySearch() {
                    const query = search.value.trim().toLowerCase();
                    let visible = 0;
                    rows.forEach(function (row) {
                        const show = query === '' || (row.dataset.search || '').includes(query);
                        row.classList.toggle('hidden', !show);
                        const detailId = row.querySelector('[data-org-toggle]')?.dataset.orgToggle;
                        const detail = detailId ? document.getElementById('org-detail-' + detailId) : null;
                        if (detail && !show) detail.classList.add('hidden');
                        if (show) visible += 1;
                    });
                    if (empty) empty.classList.toggle('hidden', visible !== 0 || rows.length === 0);
                }

                search.addEventListener('input', applySearch);

                document.querySelectorAll('[data-org-toggle]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const detail = document.getElementById('org-detail-' + button.dataset.orgToggle);
                        if (detail) detail.classList.toggle('hidden');
                    });
                });
            });
        </script>
    @endif

    <!-- TAB: EVENTS -->
    <div class="{{ $activeTab === 'events' ? '' : 'hidden' }}">
        @include('admin.partials.events')
    </div>

    <!-- TAB: TASKS -->
    <div class="{{ $activeTab === 'tasks' ? '' : 'hidden' }}">
        @include('admin.partials.tasks')
    </div>

    <!-- TAB: VOLUNTEERS -->
    <div class="{{ $activeTab === 'volunteers' ? '' : 'hidden' }}">
        @include('admin.partials.volunteers')
    </div>

    <!-- TAB 3: USER LISTS -->
    <div class="{{ $activeTab === 'users' ? '' : 'hidden' }} space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Total Users', 'value' => $systemUsers->count(), 'icon' => 'fa-users', 'iconBg' => 'bg-blue-50', 'iconColor' => 'text-jci-blue'],
                ['label' => 'Volunteers', 'value' => $systemUsers->where('role', 'volunteer')->count(), 'icon' => 'fa-user', 'iconBg' => 'bg-sky-50', 'iconColor' => 'text-sky-600'],
                ['label' => 'Organizations', 'value' => $systemUsers->where('role', 'organization')->count(), 'icon' => 'fa-building', 'iconBg' => 'bg-violet-50', 'iconColor' => 'text-violet-600'],
                ['label' => 'Approved Accounts', 'value' => $systemUsers->where('status', 'approved')->count(), 'icon' => 'fa-circle-check', 'iconBg' => 'bg-emerald-50', 'iconColor' => 'text-emerald-600'],
            ] as $card)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-5">
                    <span class="h-10 w-10 rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }} flex items-center justify-center">
                        <i class="fa-solid {{ $card['icon'] }}"></i>
                    </span>
                    <p class="mt-4 text-3xl font-black text-slate-900 tracking-tight">{{ number_format($card['value']) }}</p>
                    <p class="mt-1 text-sm text-slate-400 font-medium">{{ $card['label'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3 p-4 border-b border-slate-100">
                <label class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input id="user-list-search" type="search" placeholder="Search by name or email..."
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-jci-blue/30 focus:border-jci-blue">
                </label>
                <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl p-1" id="user-list-filters">
                    @foreach(['all' => 'All', 'volunteer' => 'Volunteer', 'organization' => 'Organization'] as $value => $label)
                        <button type="button" data-role-filter="{{ $value }}"
                                class="user-role-filter px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $value === 'all' ? 'bg-jci-blue text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-[11px] uppercase tracking-wider text-slate-400">
                        <tr class="border-b border-slate-100">
                            <th class="px-5 py-3 font-semibold">User</th>
                            <th class="px-4 py-3 font-semibold">Role</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Joined</th>
                            <th class="px-4 py-3 font-semibold">Organization Links</th>
                            <th class="px-4 py-3 font-semibold">Activity</th>
                            <th class="px-5 py-3 font-semibold">Skills</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($systemUsers as $user)
                            @php
                                $linkedOrganizations = collect();

                                if ($user->role === 'organization') {
                                    $linkedOrganizations = collect([$user->name]);
                                } else {
                                    if ($user->primaryOrganization) {
                                        $linkedOrganizations->push($user->primaryOrganization->name);
                                    }

                                    $assignmentOrgs = $user->assignments
                                        ->map(fn($assignment) => $assignment->event?->organization?->name)
                                        ->filter();
                                    $applicationOrgs = $user->taskApplications
                                        ->map(fn($application) => $application->event?->organization?->name)
                                        ->filter();
                                    $linkedOrganizations = $assignmentOrgs->merge($applicationOrgs)->unique()->values();
                                }

                                $initials = collect(explode(' ', $user->name))->map(fn($part) => substr($part, 0, 1))->take(2)->join('');
                                $searchText = strtolower(trim($user->name . ' ' . $user->email . ' ' . ($user->phone ?? '')));
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition" data-user-row data-role="{{ $user->role }}" data-search="{{ $searchText }}">
                                <td class="px-5 py-3.5 align-middle">
                                    <div class="flex items-center gap-3 min-w-52">
                                        @if($user->profile_photo_path)
                                            <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="{{ $user->name }}" class="h-9 w-9 rounded-full object-cover shrink-0">
                                        @else
                                            <span class="h-9 w-9 rounded-full bg-jci-blue text-white flex items-center justify-center text-[11px] font-bold shrink-0">
                                                {{ strtoupper($initials) }}
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-800 truncate">{{ $user->name }}</div>
                                            <div class="text-xs text-slate-400 truncate">{{ $user->email }}</div>
                                            @if($user->phone)
                                                <div class="text-[11px] text-slate-400">{{ $user->phone }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 align-middle text-slate-600 capitalize whitespace-nowrap">{{ $user->role }}</td>
                                <td class="px-4 py-3.5 align-middle">
                                    <x-ui.status-badge :label="ucfirst((string) $user->status)" />
                                </td>
                                <td class="px-4 py-3.5 align-middle text-slate-500 whitespace-nowrap text-xs">
                                    {{ $user->created_at?->format('M d, Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3.5 align-middle min-w-48">
                                    @if($linkedOrganizations->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($linkedOrganizations->take(4) as $orgName)
                                                <span class="bg-slate-100 border border-slate-200 text-slate-600 text-[10px] font-semibold px-2 py-1 rounded-lg">
                                                    {{ $orgName }}
                                                </span>
                                            @endforeach
                                            @if($linkedOrganizations->count() > 4)
                                                <span class="text-[10px] text-slate-400 font-bold px-1">+{{ $linkedOrganizations->count() - 4 }} more</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">No linked organization yet</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 align-middle">
                                    @if($user->role === 'volunteer')
                                        <div class="text-xs text-slate-500 space-y-0.5 whitespace-nowrap">
                                            <div><span class="font-bold text-slate-700">{{ $user->assignments->count() }}</span> assignment(s)</div>
                                            <div><span class="font-bold text-slate-700">{{ $user->certificates->count() }}</span> certificate(s)</div>
                                            <div><span class="font-bold text-slate-700">{{ $user->taskApplications->count() }}</span> application(s)</div>
                                        </div>
                                    @else
                                        <div class="text-xs text-slate-500 whitespace-nowrap">Partner organization account</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 align-middle min-w-40">
                                    @if($user->skills->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($user->skills->take(4) as $skill)
                                                <span class="bg-blue-50 text-jci-blue border border-blue-100 text-[10px] font-semibold px-2 py-0.5 rounded-lg">
                                                    {{ $skill->name }}
                                                </span>
                                            @endforeach
                                            @if($user->skills->count() > 4)
                                                <span class="text-[10px] text-slate-400 font-bold">+{{ $user->skills->count() - 4 }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">None listed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-400">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="user-list-empty" class="hidden">
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-400">
                                No users match this search.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($activeTab === 'users')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const search = document.getElementById('user-list-search');
                const rows = Array.from(document.querySelectorAll('[data-user-row]'));
                const empty = document.getElementById('user-list-empty');
                const filters = Array.from(document.querySelectorAll('[data-role-filter]'));
                let role = 'all';

                function paintFilters() {
                    filters.forEach(function (button) {
                        const active = button.dataset.roleFilter === role;
                        button.classList.toggle('bg-jci-blue', active);
                        button.classList.toggle('text-white', active);
                        button.classList.toggle('shadow-sm', active);
                        button.classList.toggle('text-slate-500', !active);
                    });
                }

                function applyFilters() {
                    const query = search.value.trim().toLowerCase();
                    let visible = 0;
                    rows.forEach(function (row) {
                        const matchesRole = role === 'all' || row.dataset.role === role;
                        const matchesQuery = query === '' || (row.dataset.search || '').includes(query);
                        const show = matchesRole && matchesQuery;
                        row.classList.toggle('hidden', !show);
                        if (show) visible += 1;
                    });
                    if (empty) empty.classList.toggle('hidden', visible !== 0 || rows.length === 0);
                }

                const presetQuery = new URLSearchParams(window.location.search).get('q');
                if (presetQuery) {
                    search.value = presetQuery;
                }
                search.addEventListener('input', applyFilters);
                if (presetQuery) {
                    applyFilters();
                }
                filters.forEach(function (button) {
                    button.addEventListener('click', function () {
                        role = button.dataset.roleFilter;
                        paintFilters();
                        applyFilters();
                    });
                });
            });
        </script>
    @endif

    <!-- TAB 4: CHATBOT CONFIG -->
    <div class="{{ $activeTab === 'chatbot' ? '' : 'hidden' }} glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-robot text-jci-blue"></i> Chatbot Rule & Intent Configurator
            </h4>
            <span class="text-xs text-slate-400">Rule-Based Intent System</span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Rule Input Form -->
            <form action="{{ route('admin.chatbot.rules.store') }}" method="POST" class="bg-slate-50/50 rounded-2xl p-4 border border-slate-200/80 space-y-4 h-fit premium-shadow">
                @csrf
                <h5 class="font-bold text-xs text-slate-700">Add New Direct Intent Rule</h5>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Trigger Keyword</label>
                    <input name="keyword" type="text" required placeholder="e.g., registration" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Response Match</label>
                    <textarea name="response" required placeholder="Enter predefined template response..." class="w-full border border-slate-200 rounded-lg p-2.5 text-xs h-24 focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-jci-accent hover:bg-amber-600 text-jci-dark font-black text-xs py-2.5 rounded-xl transition-all flex items-center justify-center gap-1">
                    <i class="fa-solid fa-plus"></i> Save Pattern
                </button>
            </form>

            <!-- Active Rules List -->
            <div class="md:col-span-2 space-y-2">
                <h5 class="font-bold text-xs text-slate-700 mb-2">Active Rules Index</h5>
                
                @if($chatbotRules->count() > 0)
                    <div class="space-y-2 max-h-[350px] overflow-y-auto custom-scrollbar pr-1">
                        @foreach($chatbotRules as $rule)
                            <div class="p-3 bg-slate-50/30 rounded-2xl border border-slate-200/60 flex items-start justify-between gap-4 hover-lift premium-shadow">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="bg-blue-100 text-jci-blue text-[9px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">
                                            Keyword: {{ $rule->keyword }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-600 italic">"{{ $rule->response }}"</p>
                                </div>
                                
                                <form action="{{ route('admin.chatbot.rules.destroy', $rule->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Are you sure you want to delete this rule?')" class="text-slate-400 hover:text-rose-600 p-1.5 transition">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <p class="text-xs text-slate-400">No chatbot rules created yet. Add one using the form on the left.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="pt-5 border-t border-slate-100 space-y-3">
            <div class="flex justify-between items-center">
                <h5 class="font-bold text-xs text-slate-700 flex items-center gap-2">
                    <i class="fa-solid fa-comments text-jci-blue"></i> Recent Volunteer Chatbot Conversations
                </h5>
                <span class="text-[10px] text-slate-400 font-semibold">Latest 30 messages</span>
            </div>

            @if($chatbotLogs->count() > 0)
                <div class="space-y-2 max-h-[420px] overflow-y-auto custom-scrollbar pr-1">
                    @foreach($chatbotLogs as $log)
                        <div class="p-4 bg-slate-50/40 rounded-2xl border border-slate-200/70 premium-shadow">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <x-ui.avatar :user="$log->user" size="sm" />
                                    <div class="min-w-0">
                                        <h6 class="truncate text-xs font-black text-slate-800">
                                            {{ $log->user->name ?? 'Deleted volunteer' }}
                                        </h6>
                                        <p class="text-[10px] text-slate-400">
                                            {{ $log->user->email ?? 'No account attached' }} &middot; {{ $log->created_at->format('M d, Y g:i A') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    @if($log->intent)
                                        <span class="bg-blue-100 text-jci-blue text-[9px] font-extrabold px-2 py-0.5 rounded-full uppercase">
                                            {{ $log->intent }}
                                        </span>
                                    @endif
                                    @if(!is_null($log->confidence))
                                        <span class="bg-slate-200 text-slate-600 text-[9px] font-bold px-2 py-0.5 rounded-full">
                                            {{ round($log->confidence * 100) }}%
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="bg-white border border-slate-200 rounded-xl p-3">
                                    <span class="block text-[9px] font-black uppercase text-slate-400 mb-1">Volunteer asked</span>
                                    <p class="text-xs text-slate-700 leading-relaxed">{{ $log->message }}</p>
                                </div>
                                <div class="bg-blue-50/60 border border-blue-100 rounded-xl p-3">
                                    <span class="block text-[9px] font-black uppercase text-jci-blue mb-1">Bot replied</span>
                                    <p class="text-xs text-slate-700 leading-relaxed">{{ $log->response }}</p>
                                </div>
                            </div>
                            <div class="mt-3 bg-white border border-slate-200 rounded-xl p-3">
                                @if($log->admin_reply)
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <span class="block text-[9px] font-black uppercase text-emerald-600 mb-1">Admin reply</span>
                                            <p class="text-xs text-slate-700 leading-relaxed">{{ $log->admin_reply }}</p>
                                            <p class="text-[9px] text-slate-400 mt-1">
                                                Sent {{ $log->admin_replied_at?->format('M d, Y g:i A') }}
                                            </p>
                                        </div>
                                        <span class="bg-emerald-100 text-emerald-700 text-[9px] font-black px-2 py-1 rounded uppercase shrink-0">Answered</span>
                                    </div>
                                @else
                                    <form action="{{ route('admin.chatbot.conversations.reply', $log->id) }}" method="POST" class="space-y-2">
                                        @csrf
                                        <label class="block text-[9px] font-black uppercase text-slate-400">Admin reply to volunteer</label>
                                        <textarea name="admin_reply" rows="2" maxlength="2000" required
                                                  placeholder="Write a clear answer or follow-up instruction..."
                                                  class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
                                        <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white text-[10px] font-black px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1.5">
                                            <i class="fa-solid fa-reply"></i> Send Admin Reply
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    <i class="fa-solid fa-comment-slash text-slate-300 text-3xl mb-2"></i>
                    <p class="text-xs text-slate-400">No volunteer chatbot conversations have been recorded yet.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- TAB: NOTIFICATIONS -->
    <div class="{{ $activeTab === 'notifications' ? '' : 'hidden' }}">
        @include('admin.partials.notifications')
    </div>

    <!-- TAB 4: SYSTEM NOTICES -->
    @if($activeTab === 'messages')
    <div class="space-y-4">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900">Organization messages</h2>
            <p class="mt-1 text-sm text-slate-500">These conversations are between you and an organization. Volunteer chats stay on the organization account.</p>
        </div>
        <div class="grid min-h-[32rem] overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm lg:grid-cols-[280px_minmax(0,1fr)]">
            <div class="border-b border-slate-100 lg:border-b-0 lg:border-r">
                <div class="max-h-[32rem] overflow-y-auto">
                    @forelse($messageOrganizations as $organization)
                        @php
                            $activeOrg = $selectedMessageOrg && (int) $selectedMessageOrg->id === (int) $organization->id;
                        @endphp
                        <a href="{{ route('admin.dashboard', ['tab' => 'messages', 'org' => $organization->id]) }}" class="flex items-start gap-3 px-3 py-3 {{ $activeOrg ? 'bg-sky-50' : '' }}">
                            <x-ui.avatar :user="$organization" />
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="truncate text-sm font-semibold text-slate-800">{{ $organization->name }}</span>
                                    @if($organization->message_unread > 0)
                                        <span class="shrink-0 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-600">{{ $organization->message_unread }}</span>
                                    @endif
                                </span>
                                <span class="mt-0.5 block truncate text-xs text-slate-400">{{ $organization->message_preview }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="px-4 py-8 text-center text-xs text-slate-400">No organizations are registered yet.</p>
                    @endforelse
                </div>
            </div>
            <div class="flex min-h-[28rem] flex-col">
                @if(!$selectedMessageOrg)
                    <div class="flex flex-1 items-center justify-center p-8 text-center text-sm text-slate-400">Choose an organization to start a conversation.</div>
                @else
                    <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
                        <x-ui.avatar :user="$selectedMessageOrg" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $selectedMessageOrg->name }}</p>
                            <p class="text-[11px] text-slate-400">Organization · {{ ucfirst($selectedMessageOrg->status ?: 'active') }}</p>
                        </div>
                    </div>
                    <div class="flex-1 space-y-3 overflow-y-auto px-4 py-4">
                        @forelse($platformMessages as $entry)
                            <div class="flex items-end gap-2 {{ $entry->from_admin ? 'justify-end' : 'justify-start' }}">
                                @unless($entry->from_admin)
                                    <x-ui.avatar :user="$selectedMessageOrg" size="sm" />
                                @endunless
                                <div class="max-w-[75%] rounded-2xl px-3 py-2 text-sm {{ $entry->from_admin ? 'bg-jci-blue text-white' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $entry->body }}
                                    <span class="mt-1 block text-[10px] {{ $entry->from_admin ? 'text-right text-sky-100' : 'text-slate-400' }}">{{ \App\Support\ScheduleClock::stamp($entry->created_at) }}</span>
                                </div>
                                @if($entry->from_admin)
                                    <x-ui.avatar :user="Auth::user()" size="sm" />
                                @endif
                            </div>
                        @empty
                            <p class="text-center text-sm text-slate-400">No messages yet. Send the first one below.</p>
                        @endforelse
                    </div>
                    <form action="{{ route('admin.messages.store') }}" method="POST" class="flex items-center gap-2 border-t border-slate-100 p-3">
                        @csrf
                        <input type="hidden" name="organization_id" value="{{ $selectedMessageOrg->id }}">
                        <input name="message" required maxlength="2000" placeholder="Message {{ $selectedMessageOrg->name }}..." class="flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue">
                        <button type="submit" class="flex h-10 w-10 items-center justify-center rounded-xl bg-jci-blue text-white hover:bg-jci-dark" aria-label="Send message">
                            <i class="fa-solid fa-paper-plane text-sm"></i>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @endif

    <div class="{{ $activeTab === 'broadcast' ? '' : 'hidden' }} glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-tower-broadcast text-jci-blue"></i> System Notice Console
            </h4>
            <span class="text-xs text-slate-400 font-bold flex items-center gap-1">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Web Portal
            </span>
        </div>
        
        <form action="{{ route('admin.broadcast') }}" method="POST" class="space-y-4 max-w-2xl">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Notice Type</label>
                    <select name="notice_type" required class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none bg-white">
                        <option value="technical_issue">Technical Issue</option>
                        <option value="maintenance">Scheduled Maintenance</option>
                        <option value="service_update">Service Update</option>
                        <option value="general_advisory">General Advisory</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Impact Level</label>
                    <select name="impact_level" required class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none bg-white">
                        <option value="info">Informational</option>
                        <option value="minor">Minor Impact</option>
                        <option value="major">Major Impact</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Notice Title</label>
                <input name="title" type="text" required placeholder="e.g., Login Issue Under Investigation" 
                       class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Announcement Message</label>
                <textarea name="body" required placeholder="State the issue, affected area, current action, and expected next update..." 
                          class="w-full border border-slate-200 rounded-lg p-2.5 text-xs h-24 focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
            </div>
            <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white font-bold text-xs py-3 px-6 rounded-xl transition duration-300 flex items-center gap-2">
                <i class="fa-solid fa-paper-plane"></i> Post System Announcement
            </button>
        </form>
    </div>

</x-layout.admin>
