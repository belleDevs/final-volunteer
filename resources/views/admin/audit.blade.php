<x-layout.admin activeTab="audit">
    @php
        $filtersOpen = request()->hasAny(['from', 'to', 'role', 'status', 'module', 'action_type']);
        $activeFilterCount = collect(['from', 'to', 'role', 'status', 'module', 'action_type', 'q'])
            ->filter(fn ($key) => filled(request($key)))
            ->count();
        $formatValues = function ($values) {
            if (!is_array($values) || $values === []) {
                return 'None recorded';
            }

            return collect($values)->map(function ($value, $key) {
                $shown = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;

                return str_replace('_', ' ', (string) $key).': '.$shown;
            })->implode("\n");
        };
        $summaryCards = [
            ['key' => null, 'label' => 'All activity', 'value' => $summary['total'], 'icon' => 'fa-wave-square', 'iconBg' => 'bg-blue-50 dark:bg-sky-950', 'iconColor' => 'text-jci-blue dark:text-sky-300'],
            ['key' => 'admin', 'label' => 'Admin', 'value' => $summary['admin'], 'icon' => 'fa-shield-halved', 'iconBg' => 'bg-sky-50 dark:bg-sky-950', 'iconColor' => 'text-sky-600 dark:text-sky-300'],
            ['key' => 'organization', 'label' => 'Organization', 'value' => $summary['organization'], 'icon' => 'fa-building', 'iconBg' => 'bg-teal-50 dark:bg-teal-950', 'iconColor' => 'text-teal-600 dark:text-teal-300'],
            ['key' => 'volunteer', 'label' => 'Volunteer', 'value' => $summary['volunteer'], 'icon' => 'fa-user', 'iconBg' => 'bg-indigo-50 dark:bg-indigo-950', 'iconColor' => 'text-indigo-500 dark:text-indigo-300'],
            ['key' => 'failed', 'label' => 'Failed', 'value' => $summary['failed'], 'icon' => 'fa-triangle-exclamation', 'iconBg' => 'bg-rose-50 dark:bg-rose-950', 'iconColor' => 'text-rose-500 dark:text-rose-300'],
        ];
    @endphp

    <div class="space-y-5">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="bg-gradient-to-r from-jci-blue to-jci-dark px-5 py-5 text-white sm:px-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/70">Compliance</p>
                        <h4 class="mt-1 text-xl font-extrabold tracking-tight">{{ number_format($summary['total']) }} audit {{ \Illuminate\Support\Str::plural('entry', $summary['total']) }}</h4>
                        <p class="mt-1 text-sm text-white/75">Immutable trail of portal actions. Entries cannot be edited or deleted here.</p>
                    </div>
                    <p class="text-xs text-white/70">Kept for {{ (int) config('audit.retention_months', 24) }} months</p>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-2 gap-3 xl:grid-cols-5">
            @foreach($summaryCards as $card)
                @php
                    $active = $card['key'] === 'failed'
                        ? request('status') === 'failed' && !request('role')
                        : ($card['key'] === null ? !request('role') && !request('status') : request('role') === $card['key'] && !request('status'));
                    $params = request()->except(['page', 'role', 'status']);
                    if ($card['key'] === 'failed') {
                        $params['status'] = 'failed';
                    } elseif ($card['key']) {
                        $params['role'] = $card['key'];
                    }
                @endphp
                <a href="{{ route('admin.audit', $params) }}"
                   class="rounded-2xl border bg-white p-4 shadow-sm transition dark:bg-slate-900 {{ $active ? 'border-jci-blue ring-1 ring-jci-blue/20' : 'border-slate-200 hover:border-sky-300 dark:border-slate-800' }}">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }}">
                        <i class="fa-solid {{ $card['icon'] }} text-sm"></i>
                    </span>
                    <p class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ number_format($card['value']) }}</p>
                    <p class="mt-0.5 text-xs font-medium text-slate-400">{{ $card['label'] }}</p>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('admin.audit') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Activity trail</h2>
                    <p class="mt-0.5 text-xs text-slate-400">
                        Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ number_format($logs->total()) }}
                        @if($activeFilterCount > 0)
                            · {{ $activeFilterCount }} {{ \Illuminate\Support\Str::plural('filter', $activeFilterCount) }} on
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search actor, action, target…"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-3 text-sm text-slate-700 focus:border-jci-blue focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 sm:w-64">
                    </label>
                    <button type="button" id="audit-filter-toggle" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-300">
                        Filters{{ $activeFilterCount > 0 ? ' ('.$activeFilterCount.')' : '' }}
                    </button>
                    <a href="{{ route('admin.audit.export', ['format' => 'csv']) }}?{{ http_build_query(request()->except('page')) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-emerald-500 hover:text-emerald-600 dark:border-slate-700 dark:text-slate-300">CSV</a>
                    <a href="{{ route('admin.audit.export', ['format' => 'pdf']) }}?{{ http_build_query(request()->except('page')) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-300">PDF</a>
                </div>
            </div>

            <div id="audit-filters" class="{{ $filtersOpen ? '' : 'hidden' }} grid grid-cols-1 gap-3 border-b border-slate-100 bg-slate-50/70 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/50 md:grid-cols-3 xl:grid-cols-6">
                <label class="text-[11px] font-bold text-slate-500">
                    From
                    <input type="date" name="from" value="{{ request('from') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                </label>
                <label class="text-[11px] font-bold text-slate-500">
                    To
                    <input type="date" name="to" value="{{ request('to') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                </label>
                <label class="text-[11px] font-bold text-slate-500">
                    User role
                    <select name="role" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                        <option value="">All roles</option>
                        @foreach(\App\Support\AuditTrail::ROLES as $value => $label)
                            <option value="{{ $value }}" {{ request('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-[11px] font-bold text-slate-500">
                    Module
                    <select name="module" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                        <option value="">All modules</option>
                        @foreach(\App\Support\AuditTrail::MODULES as $module)
                            <option value="{{ $module }}" {{ request('module') === $module ? 'selected' : '' }}>{{ $module }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-[11px] font-bold text-slate-500">
                    Action type
                    <select name="action_type" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                        <option value="">All action types</option>
                        @foreach(\App\Support\AuditTrail::ACTION_TYPES as $value => $label)
                            <option value="{{ $value }}" {{ request('action_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-[11px] font-bold text-slate-500">
                    Status
                    <select name="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                        <option value="">All results</option>
                        @foreach(\App\Support\AuditTrail::STATUSES as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex justify-end gap-2 md:col-span-3 xl:col-span-6">
                    <a href="{{ route('admin.audit') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">Clear</a>
                    <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Apply filters</button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[880px] text-left">
                    <thead>
                        <tr class="text-[11px] uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3 font-semibold">Action</th>
                            <th class="px-4 py-3 font-semibold">Actor</th>
                            <th class="px-4 py-3 font-semibold">Module</th>
                            <th class="px-4 py-3 font-semibold">Target</th>
                            <th class="px-4 py-3 font-semibold">Result</th>
                            <th class="px-5 py-3 text-right font-semibold">When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                [$icon, $tone] = \App\Support\AuditTrail::icon($log);
                                $roleLabel = \App\Support\AuditTrail::roleLabel($log->actor_role);
                                $typeLabel = \App\Support\AuditTrail::ACTION_TYPES[$log->action_type] ?? 'Other';
                                $statusLabel = $log->status === 'failed' ? 'Failed' : 'Success';
                            @endphp
                            <tr data-audit-row="{{ $log->id }}" class="cursor-pointer border-t border-slate-100 transition hover:bg-slate-50/80 dark:border-slate-800 dark:hover:bg-slate-800/50">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $tone }} dark:bg-opacity-40">
                                            <i class="fa-solid {{ $icon }} text-xs"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $log->action }}</p>
                                            <p class="mt-0.5 truncate text-[11px] text-slate-400">{{ $typeLabel }} · #{{ $log->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="truncate text-sm font-medium text-slate-700 dark:text-slate-200">{{ $log->actor_name }}</p>
                                    <div class="mt-1"><x-ui.status-badge :label="$roleLabel" /></div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $log->module }}</span>
                                </td>
                                <td class="max-w-[200px] truncate px-4 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ $log->subject_label ?: '—' }}</td>
                                <td class="px-4 py-3.5">
                                    <x-ui.status-badge :label="$statusLabel" />
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right text-sm text-slate-500">
                                    {{ \App\Support\AuditTrail::trailWhen($log->created_at) }}
                                    <i data-audit-chevron="{{ $log->id }}" class="fa-solid fa-chevron-down ml-2 text-[10px] text-slate-300 transition"></i>
                                </td>
                            </tr>
                            <tr id="audit-detail-{{ $log->id }}" data-audit-detail="{{ $log->id }}" class="hidden">
                                <td colspan="6" class="bg-slate-50/70 px-5 pb-4 dark:bg-slate-950/40">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-3 dark:border-slate-800">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $log->action }}</p>
                                                <p class="mt-0.5 text-xs text-slate-400">{{ \App\Support\AuditTrail::localTime($log->created_at) }} · IP {{ $log->ip_address ?: '—' }}</p>
                                            </div>
                                            <div class="flex flex-wrap gap-1.5">
                                                <x-ui.status-badge :label="$roleLabel" />
                                                <x-ui.status-badge :label="$statusLabel" />
                                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $log->module }}</span>
                                            </div>
                                        </div>
                                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div>
                                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Performed by</p>
                                                <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $log->actor_name }} · {{ $roleLabel }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Affected record</p>
                                                <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $log->subject_label ?: '—' }}</p>
                                            </div>
                                            <div class="md:col-span-2">
                                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Description</p>
                                                <p class="mt-1 text-sm leading-relaxed text-slate-700 dark:text-slate-200">{{ $log->description ?: 'No description recorded.' }}</p>
                                            </div>
                                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950">
                                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Previous values</p>
                                                <p class="mt-1 whitespace-pre-wrap text-xs leading-relaxed text-slate-700 dark:text-slate-300">{{ $formatValues($log->previous_values) }}</p>
                                            </div>
                                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950">
                                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">New values</p>
                                                <p class="mt-1 whitespace-pre-wrap text-xs leading-relaxed text-slate-700 dark:text-slate-300">{{ $formatValues($log->new_values) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                                        <i class="fa-solid fa-clipboard-list"></i>
                                    </span>
                                    <p class="mt-3 text-sm text-slate-400">No activities match this filter.</p>
                                    @if($activeFilterCount > 0)
                                        <a href="{{ route('admin.audit') }}" class="mt-2 inline-block text-xs font-semibold text-jci-blue hover:text-jci-dark">Clear filters</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="border-t border-slate-100 px-5 py-4 dark:border-slate-800">{{ $logs->links() }}</div>
            @endif
        </form>
    </div>

    <script>
        document.getElementById('audit-filter-toggle')?.addEventListener('click', function () {
            document.getElementById('audit-filters')?.classList.toggle('hidden');
        });

        document.querySelectorAll('[data-audit-row]').forEach(function (row) {
            row.addEventListener('click', function () {
                const detail = document.getElementById('audit-detail-' + row.dataset.auditRow);
                const willOpen = detail?.classList.contains('hidden');
                document.querySelectorAll('[data-audit-detail]').forEach(function (panel) {
                    panel.classList.add('hidden');
                });
                document.querySelectorAll('[data-audit-chevron]').forEach(function (chevron) {
                    chevron.classList.remove('rotate-180');
                });
                if (willOpen && detail) {
                    detail.classList.remove('hidden');
                    row.querySelector('[data-audit-chevron]')?.classList.add('rotate-180');
                }
            });
        });
    </script>
</x-layout.admin>
