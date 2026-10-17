@php
    $recommended = collect($recommendedSkills ?? []);
    $skillCount = $mySkills->count();
@endphp

<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Your skills</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $skillCount }}</p>
            <p class="mt-1 text-xs text-slate-400">Saved on your profile</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Recommended</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $recommended->count() }}</p>
            <p class="mt-1 text-xs text-slate-400">Useful for upcoming work</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Catalog</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $availableSkills->count() }}</p>
            <p class="mt-1 text-xs text-slate-400">Ready to add from the list</p>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Your skills</h2>
                <p class="mt-0.5 text-xs text-slate-400">Organizations use these when matching you to roles.</p>
            </div>
            <a href="{{ route('profile.show') }}" class="text-xs font-semibold text-jci-blue hover:text-jci-dark">Update profile</a>
        </div>

        <div class="space-y-5 px-5 py-5">
            <div class="flex flex-wrap gap-2">
                @forelse($mySkills as $skill)
                    <form action="{{ route('volunteer.skills.destroy', $skill->id) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1.5 text-xs font-medium text-jci-blue transition hover:bg-rose-50 hover:text-rose-600 dark:bg-sky-950 dark:text-sky-300 dark:hover:bg-rose-950 dark:hover:text-rose-300"
                                title="Remove {{ $skill->name }}">
                            {{ $skill->name }}
                            <i class="fa-solid fa-xmark text-[10px] opacity-70"></i>
                        </button>
                    </form>
                @empty
                    <p class="text-sm text-slate-400">No skills saved yet. Add skills below so organizations can match you better.</p>
                @endforelse
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                @if($availableSkills->isNotEmpty())
                    <form action="{{ route('volunteer.skills.store') }}" method="POST" class="rounded-xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        @csrf
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Add from catalog</label>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <select name="skill_id" required class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-jci-blue/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                <option value="" disabled selected>Choose a skill</option>
                                @foreach($availableSkills->sortBy('name') as $skill)
                                    <option value="{{ $skill->id }}">{{ $skill->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-semibold text-white hover:bg-jci-dark">Add</button>
                        </div>
                    </form>
                @endif

                <form action="{{ route('volunteer.skills.store') }}" method="POST" class="rounded-xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                    @csrf
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Add a custom skill</label>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input type="text" name="skill_name" maxlength="80" required placeholder="e.g. First aid, Emcee, Crowd control"
                               class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-jci-blue/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                        <button type="submit" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recommended for you</h2>
            <p class="mt-0.5 text-xs text-slate-400">Based on skills requested on upcoming tasks in your organization.</p>
        </div>

        @if($recommended->isEmpty())
            <div class="px-5 py-14 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sky-50 text-jci-blue dark:bg-sky-950">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <p class="mt-4 text-sm font-medium text-slate-700 dark:text-slate-200">No skill recommendations right now</p>
                <p class="mt-1 text-xs text-slate-400">When upcoming events list skill needs you don’t have yet, they will appear here.</p>
            </div>
        @else
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($recommended as $item)
                    @php
                        $events = collect($item['events'] ?? [$item['event_title'] ?? 'Upcoming event'])->filter()->unique()->values();
                        $demand = (int) ($item['count'] ?? 0);
                        $seenOn = 'Seen on '.$events->take(2)->join(', ');
                        if ($events->count() > 2) {
                            $seenOn .= ' and '.($events->count() - 2).' more';
                        }
                    @endphp
                    <article class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $item['skill']->name }}</h3>
                                <span class="rounded-full bg-sky-50 px-2.5 py-0.5 text-[11px] font-semibold text-jci-blue dark:bg-sky-950 dark:text-sky-300">
                                    {{ $demand }} {{ $demand === 1 ? 'upcoming task' : 'upcoming tasks' }}
                                </span>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-500">{{ $seenOn }}</p>
                        </div>
                        <form action="{{ route('volunteer.skills.store') }}" method="POST" class="shrink-0">
                            @csrf
                            <input type="hidden" name="skill_id" value="{{ $item['skill']->id }}">
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-jci-blue px-3.5 py-2 text-xs font-semibold text-white hover:bg-jci-dark">
                                <i class="fa-solid fa-plus text-[10px]"></i>
                                Add to profile
                            </button>
                        </form>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
