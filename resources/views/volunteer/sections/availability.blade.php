@php
    $schedule = old('days', $volunteer->availability_schedule ?? []);
    $current = old('availability', $volunteer->availability);
    if (! in_array($current, ['active', 'limited', 'inactive'], true)) {
        $current = 'active';
    }
    $days = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];
    $statusMeta = [
        'active' => ['Available', 'Open to new task assignments', 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900'],
        'limited' => ['Busy / Limited', 'Fewer new roles — share when you can help', 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:border-amber-900'],
        'inactive' => ['Unavailable', 'Not open to new assignments right now', 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'],
    ];
    $activeDays = collect($schedule)->filter(fn ($day) => ! empty($day['on']))->count();
    [$statusLabel, $statusHint, $statusChip] = $statusMeta[$current];
@endphp

<div class="mx-auto max-w-3xl space-y-5">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:col-span-2">
            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Current status</p>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $statusChip }}">{{ $statusLabel }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-500">{{ $statusHint }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Days listed</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900 dark:text-white">{{ $activeDays }}</p>
            <p class="mt-1 text-xs text-slate-400">In your weekly schedule</p>
        </div>
    </div>

    <form action="{{ route('volunteer.availability.toggle') }}" method="POST" id="availability-form" class="space-y-5">
        @csrf

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">How available are you?</h2>
            <p class="mt-1 text-xs text-slate-400">Organizations use this when offering new roles. Tasks you already accepted stay assigned.</p>

            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach([
                    'active' => ['Available', 'Ready for new assignments', 'fa-circle-check'],
                    'limited' => ['Busy / Limited', 'Only some days or hours', 'fa-clock'],
                    'inactive' => ['Unavailable', 'Pause new assignments', 'fa-ban'],
                ] as $value => [$title, $desc, $icon])
                    <label class="relative cursor-pointer">
                        <input type="radio" name="availability" value="{{ $value }}" class="peer sr-only" @checked($current === $value) required>
                        <span class="flex h-full flex-col rounded-2xl border border-slate-200 bg-slate-50/80 p-4 transition peer-checked:border-jci-blue peer-checked:bg-sky-50 peer-checked:ring-2 peer-checked:ring-jci-blue/20 dark:border-slate-700 dark:bg-slate-950 dark:peer-checked:border-jci-blue dark:peer-checked:bg-sky-950/30">
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-800 dark:text-slate-100">
                                <i class="fa-solid {{ $icon }} text-jci-blue text-xs"></i>
                                {{ $title }}
                            </span>
                            <span class="mt-1.5 text-xs leading-relaxed text-slate-500">{{ $desc }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Weekly schedule</h2>
                    <p class="mt-1 text-xs text-slate-400">Optional. Turn on a day, then set the hours you can volunteer.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" id="avail-weekdays" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-300">Weekdays</button>
                    <button type="button" id="avail-clear" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-300">Clear</button>
                </div>
            </div>

            <div class="mt-4 space-y-2">
                @foreach($days as $key => $label)
                    @php
                        $day = $schedule[$key] ?? [];
                        $on = ! empty($day['on']);
                    @endphp
                    <div class="avail-day grid grid-cols-1 items-center gap-2 rounded-xl border border-slate-100 px-3 py-2.5 dark:border-slate-800 sm:grid-cols-[9.5rem_1fr_auto_1fr]" data-day="{{ $key }}">
                        <label class="flex items-center gap-2.5 text-sm font-medium text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="days[{{ $key }}][on]" value="1" class="avail-day-toggle rounded border-slate-300 text-jci-blue focus:ring-jci-blue" @checked($on)>
                            {{ $label }}
                        </label>
                        <div>
                            <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:hidden">From</span>
                            <input type="time" name="days[{{ $key }}][from]" value="{{ $day['from'] ?? '' }}" aria-label="{{ $label }} from" class="avail-day-from w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950" @disabled(! $on)>
                        </div>
                        <span class="hidden text-xs font-medium text-slate-400 sm:block">to</span>
                        <div>
                            <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:hidden">To</span>
                            <input type="time" name="days[{{ $key }}][to]" value="{{ $day['to'] ?? '' }}" aria-label="{{ $label }} to" class="avail-day-to w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950" @disabled(! $on)>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <label class="block text-sm font-semibold text-slate-900 dark:text-white" for="availability_notes">Notes</label>
            <p class="mt-1 text-xs text-slate-400">Optional context for your organization (exam week, travel, preferred roles, etc.).</p>
            <textarea id="availability_notes" name="availability_notes" rows="3" maxlength="1000" placeholder="e.g. Free after 5 PM on weekdays; weekends preferred for outdoor events"
                      class="mt-3 w-full resize-none rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">{{ old('availability_notes', $volunteer->availability_notes) }}</textarea>
        </section>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-slate-400">Saving updates what organizations see for new assignments.</p>
            <button type="submit" class="rounded-xl bg-jci-blue px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-sky-500/20 hover:bg-jci-dark">Save availability</button>
        </div>
    </form>
</div>

<script>
(function () {
    const form = document.getElementById('availability-form');
    if (!form) return;

    function syncDay(row) {
        const on = row.querySelector('.avail-day-toggle')?.checked;
        row.querySelectorAll('.avail-day-from, .avail-day-to').forEach(function (input) {
            input.disabled = !on;
            if (!on) {
                input.classList.add('opacity-50');
            } else {
                input.classList.remove('opacity-50');
            }
        });
    }

    form.querySelectorAll('.avail-day').forEach(function (row) {
        syncDay(row);
        row.querySelector('.avail-day-toggle')?.addEventListener('change', function () {
            syncDay(row);
        });
    });

    document.getElementById('avail-weekdays')?.addEventListener('click', function () {
        ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].forEach(function (day) {
            const row = form.querySelector('.avail-day[data-day="' + day + '"]');
            const toggle = row?.querySelector('.avail-day-toggle');
            if (toggle) {
                toggle.checked = true;
                syncDay(row);
            }
        });
    });

    document.getElementById('avail-clear')?.addEventListener('click', function () {
        form.querySelectorAll('.avail-day').forEach(function (row) {
            const toggle = row.querySelector('.avail-day-toggle');
            if (toggle) toggle.checked = false;
            row.querySelectorAll('.avail-day-from, .avail-day-to').forEach(function (input) {
                input.value = '';
            });
            syncDay(row);
        });
    });
})();
</script>
