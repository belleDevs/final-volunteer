@php
    $clock = \App\Support\ScheduleClock::class;
    $duty = \App\Support\VolunteerDuty::class;
    $taskStatus = (string) request('task_status', 'all');
    $filters = [
        'all' => 'All',
        'pending' => 'Needs response',
        'active' => 'Active',
        'submitted' => 'Submitted',
        'completed' => 'Completed',
    ];
    $activeBoard = $taskBoard->filter(function ($assign) use ($taskStatus, $duty) {
        $label = $duty::label($assign->status, $assign->schedule_phase, $assign->feedback);

        return match ($taskStatus) {
            'pending' => $label === 'Pending Response',
            'active' => in_array($label, ['Accepted', 'In Progress', 'Revision requested'], true),
            'submitted' => $label === 'Submitted for Review',
            'completed' => false,
            default => ! in_array($assign->status, ['cancelled'], true),
        };
    });
    $rows = $activeBoard
        ->filter(fn ($assign) => $assign->task && $assign->event)
        ->sortBy(fn ($assign) => ($assign->schedule_phase === 'past' ? '1' : '0').($assign->status === 'pending' ? '0' : '1').($assign->event->start_time?->format('Y-m-d H:i:s') ?? ''))
        ->values();
    $completedRows = $taskStatus === 'all' || $taskStatus === 'completed'
        ? $completedAssignments->filter(fn ($assign) => $assign->task && $assign->event)->values()
        : collect();
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full max-w-sm">
            <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-300"></i>
            <input id="volunteer-task-search" type="search" placeholder="Search tasks" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-jci-blue/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
        </div>
        <div class="flex flex-wrap gap-1.5">
            @foreach($filters as $value => $label)
                <a href="{{ route('volunteer.dashboard', ['section' => 'tasks', 'task_status' => $value === 'all' ? null : $value]) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $taskStatus === $value ? 'bg-jci-blue text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-jci-blue dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        @if($taskStatus !== 'completed' && $rows->isNotEmpty())
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($rows as $assign)
                    @php
                        $event = $assign->event;
                        $phase = $assign->schedule_phase;
                        $label = $duty::label($assign->status, $phase, $assign->feedback);
                        $canRespond = $duty::canRespond($assign->status, $phase);
                        $canSubmit = in_array($assign->status, ['approved', 'rejected'], true);
                        $search = strtolower($assign->task->title.' '.$event->title.' '.$label.' '.($assign->task->description ?? ''));
                    @endphp
                    <article id="duty-{{ $assign->id }}" data-task-card data-search="{{ $search }}" class="px-5 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $assign->task->title }}</h2>
                                    <x-ui.status-badge :label="$label" />
                                </div>
                                <p class="mt-1 text-xs text-slate-400">{{ $event->title }} · {{ $clock::range($event->start_time, $event->end_time) }}</p>
                                @if(filled($assign->task->description))
                                    <p class="mt-2 text-sm text-slate-500">{{ $assign->task->description }}</p>
                                @endif
                            </div>
                            @if($canRespond || $canSubmit)
                                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                                    @if($canRespond)
                                        <form action="{{ route('volunteer.assignments.respond', $assign->id) }}" method="POST" onsubmit="return confirm('Accept this task?');">
                                            @csrf
                                            <input type="hidden" name="decision" value="accept">
                                            <button type="submit" class="rounded-lg bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark">Accept</button>
                                        </form>
                                        <form action="{{ route('volunteer.assignments.respond', $assign->id) }}" method="POST" onsubmit="return confirm('Decline this task?');">
                                            @csrf
                                            <input type="hidden" name="decision" value="decline">
                                            <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-rose-300 hover:text-rose-600 dark:border-slate-700 dark:text-slate-300">Decline</button>
                                        </form>
                                    @endif
                                    @if($canSubmit)
                                        <button type="button"
                                                class="rounded-lg bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark"
                                                data-evidence-open
                                                data-action="{{ route('volunteer.tasks.complete', $assign->id) }}"
                                                data-task-title="{{ $assign->task->title }}"
                                                data-event-title="{{ $event->title }}"
                                                data-feedback="{{ $assign->status === 'rejected' ? ($assign->feedback ?? '') : '' }}">
                                            Submit evidence
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if($assign->status === 'submitted')
                            <p class="mt-3 text-xs text-slate-500">Evidence is with the organization for review.</p>
                        @elseif($canSubmit && $assign->status === 'rejected' && $assign->feedback)
                            <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700">{{ $assign->feedback }}</p>
                        @elseif($duty::declined($assign->feedback) && $assign->feedback)
                            <p class="mt-3 text-xs text-slate-500">{{ $assign->feedback }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @elseif($taskStatus !== 'completed')
            <div class="px-5 py-14 text-center">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No assigned tasks here</p>
                <p class="mt-1 text-xs text-slate-400">Roles assigned by your organization will appear in this list.</p>
            </div>
        @endif

        @if($completedRows->isNotEmpty())
            <div class="{{ $taskStatus !== 'completed' && $rows->isNotEmpty() ? 'border-t border-slate-100 dark:border-slate-800' : '' }}">
                @if($taskStatus === 'all')
                    <div class="bg-slate-50 px-5 py-2.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:bg-slate-950">Completed</div>
                @endif
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($completedRows as $assign)
                        @php $search = strtolower($assign->task->title.' '.$assign->event->title.' completed'); @endphp
                        <div data-task-card data-search="{{ $search }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $assign->task->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $assign->event->title }} · {{ number_format((float) $assign->hours_logged, 1) }} hours approved</p>
                            </div>
                            <x-ui.status-badge label="Completed" />
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif($taskStatus === 'completed')
            <div class="px-5 py-14 text-center">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No completed tasks yet</p>
                <p class="mt-1 text-xs text-slate-400">Hours appear here after the organization approves your evidence.</p>
            </div>
        @endif
    </section>

    <p id="volunteer-task-search-empty" class="hidden rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-400 dark:border-slate-700 dark:bg-slate-900">No tasks match that search.</p>
</div>

<x-ui.modal id="modal-submit-evidence" title="Submit evidence">
    <p id="evidence-modal-context" class="mb-4 text-sm text-slate-500"></p>
    <p id="evidence-modal-feedback" class="mb-4 hidden rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700"></p>
    <form id="evidence-modal-form" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">What you completed</label>
            <textarea name="completion_note" id="evidence-modal-note" required rows="4" maxlength="1000" placeholder="Describe the work, where it happened, and the result." class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">{{ old('completion_note') }}</textarea>
        </div>
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500">Proof of work <span class="font-normal normal-case tracking-normal text-slate-400">(optional)</span></p>
            <p class="mb-2 text-[11px] leading-relaxed text-slate-400">Attach a photo or document that shows what you finished — for example a site photo, attendance sheet, or short report.</p>
            <label for="evidence-modal-file" class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-center transition hover:border-jci-blue hover:bg-sky-50/50 dark:border-slate-700 dark:bg-slate-950 dark:hover:border-jci-blue">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white text-jci-blue shadow-sm dark:bg-slate-900">
                    <i class="fa-solid fa-paperclip text-sm"></i>
                </span>
                <span id="evidence-modal-file-label" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Attach a photo or document</span>
                <span class="text-[11px] text-slate-400">JPG, PNG, PDF, or DOCX · max 5 MB</span>
                <input type="file" name="completion_proof" id="evidence-modal-file" accept=".jpg,.jpeg,.png,.pdf,.docx,image/jpeg,image/png,application/pdf" class="sr-only">
            </label>
            <button type="button" id="evidence-modal-file-clear" class="mt-2 hidden text-xs font-semibold text-slate-500 hover:text-rose-600">Remove attachment</button>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('modal-submit-evidence').classList.add('hidden')" class="rounded-xl bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700">Cancel</button>
            <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-xs font-bold text-white hover:bg-jci-dark">Submit for review</button>
        </div>
    </form>
</x-ui.modal>

<script>
    (function () {
        const search = document.getElementById('volunteer-task-search');
        if (search) {
            search.addEventListener('input', function () {
                const query = search.value.trim().toLowerCase();
                let shown = 0;
                document.querySelectorAll('[data-task-card]').forEach(function (card) {
                    const hit = query === '' || (card.dataset.search || '').includes(query);
                    card.classList.toggle('hidden', !hit);
                    if (hit) shown += 1;
                });
                const empty = document.getElementById('volunteer-task-search-empty');
                if (empty) {
                    empty.classList.toggle('hidden', shown > 0 || query === '');
                }
            });
        }

        const modal = document.getElementById('modal-submit-evidence');
        const form = document.getElementById('evidence-modal-form');
        const context = document.getElementById('evidence-modal-context');
        const feedback = document.getElementById('evidence-modal-feedback');
        const note = document.getElementById('evidence-modal-note');
        const file = document.getElementById('evidence-modal-file');
        const fileLabel = document.getElementById('evidence-modal-file-label');
        const fileClear = document.getElementById('evidence-modal-file-clear');
        const defaultFileLabel = 'Attach a photo or document';

        function resetEvidenceFile() {
            if (file) {
                file.value = '';
            }
            if (fileLabel) {
                fileLabel.textContent = defaultFileLabel;
            }
            if (fileClear) {
                fileClear.classList.add('hidden');
            }
        }

        if (modal && form) {
            document.body.appendChild(modal);
            document.querySelectorAll('[data-evidence-open]').forEach(function (button) {
                button.addEventListener('click', function () {
                    form.action = button.dataset.action || '';
                    context.textContent = (button.dataset.taskTitle || 'Task') + ' · ' + (button.dataset.eventTitle || 'Event');
                    const message = (button.dataset.feedback || '').trim();
                    if (message) {
                        feedback.textContent = message;
                        feedback.classList.remove('hidden');
                    } else {
                        feedback.textContent = '';
                        feedback.classList.add('hidden');
                    }
                    if (note) {
                        note.value = '';
                    }
                    resetEvidenceFile();
                    modal.classList.remove('hidden');
                });
            });
            if (file && fileLabel) {
                file.addEventListener('change', function () {
                    const selected = file.files && file.files[0];
                    if (selected) {
                        fileLabel.textContent = selected.name;
                        if (fileClear) {
                            fileClear.classList.remove('hidden');
                        }
                    } else {
                        resetEvidenceFile();
                    }
                });
            }
            if (fileClear) {
                fileClear.addEventListener('click', function () {
                    resetEvidenceFile();
                });
            }
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        }
    })();
</script>
