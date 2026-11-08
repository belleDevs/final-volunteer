<x-dynamic-component :component="$audience === 'organization' ? 'layout.org' : 'layout.volunteer'" active="audit">
    @php
        $exportRoute = $audience === 'organization' ? 'org.audit.export' : 'volunteer.audit.export';
        $pageRoute = $audience === 'organization' ? 'org.audit' : 'volunteer.audit';
        $query = request()->except('page');
    @endphp

    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Account activity</p>
                    <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">My audit logs</h2>
                    <p class="mt-1 text-sm text-slate-500">Only actions recorded for your account are shown here.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach(['pdf' => 'PDF', 'xls' => 'Excel', 'csv' => 'CSV'] as $format => $label)
                        <a href="{{ route($exportRoute, ['format' => $format]).($query ? '?'.http_build_query($query) : '') }}"
                           class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <form method="GET" action="{{ route($pageRoute) }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <label class="text-[11px] font-bold text-slate-500">
                    Search
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Action or description"
                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                </label>
                <label class="text-[11px] font-bold text-slate-500">
                    From
                    <input type="date" name="from" value="{{ request('from') }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                </label>
                <label class="text-[11px] font-bold text-slate-500">
                    To
                    <input type="date" name="to" value="{{ request('to') }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                </label>
                <label class="text-[11px] font-bold text-slate-500">
                    Result
                    <select name="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <option value="">All results</option>
                        <option value="success" @selected(request('status') === 'success')>Success</option>
                        <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                    </select>
                </label>
                <div class="flex items-end gap-2">
                    <button class="rounded-xl bg-jci-blue px-4 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Apply</button>
                    <a href="{{ route($pageRoute) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:text-slate-300">Clear</a>
                </div>
            </div>
        </form>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-400 dark:bg-slate-950">
                        <tr>
                            <th class="px-5 py-3">Action</th>
                            <th class="px-4 py-3">Module</th>
                            <th class="px-4 py-3">Details</th>
                            <th class="px-4 py-3">Result</th>
                            <th class="px-5 py-3 text-right">Date and time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($logs as $log)
                            <tr>
                                <td class="px-5 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $log->action }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $log->module }}</td>
                                <td class="max-w-xl px-4 py-3 text-slate-500 dark:text-slate-400">{{ $log->description }}</td>
                                <td class="px-4 py-3">
                                    <x-ui.status-badge :label="$log->status === 'failed' ? 'Failed' : 'Success'" />
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-xs text-slate-500">{{ \App\Support\AuditTrail::localTime($log->created_at) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center text-sm text-slate-400">No account activity matches these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
                <div class="border-t border-slate-100 px-5 py-4 dark:border-slate-800">{{ $logs->links() }}</div>
            @endif
        </section>
    </div>
</x-dynamic-component>
