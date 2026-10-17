@php
    $organization = $volunteer->primaryOrganization;
    $orgName = $organization->name ?? 'your organization';
    $timeline = $threads->sortBy('created_at')->values();
    $selfPhoto = $volunteer->profile_photo_path ? asset('storage/'.$volunteer->profile_photo_path) : null;
    $orgPhoto = $organization?->profile_photo_path ? asset('storage/'.$organization->profile_photo_path) : null;
    $selfInitials = collect(preg_split('/\s+/', trim((string) $volunteer->name)) ?: [])
        ->filter()->take(2)->map(fn ($p) => strtoupper(substr($p, 0, 1)))->join('') ?: '?';
    $orgInitials = collect(preg_split('/\s+/', trim((string) $orgName)) ?: [])
        ->filter()->take(2)->map(fn ($p) => strtoupper(substr($p, 0, 1)))->join('') ?: '?';
@endphp

<div class="mx-auto flex max-w-3xl flex-col gap-4">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
                @if($organization)
                    <x-ui.avatar :user="$organization" size="lg" />
                @else
                    <x-ui.brand-mark size="lg" />
                @endif
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Messages</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Ask the assistant about your account, or talk with {{ $orgName }} in the same conversation.
                    </p>
                </div>
            </div>
            <button type="button" onclick="toggleVolunteerChatbot()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-jci-blue hover:bg-sky-50 dark:border-slate-700 dark:hover:bg-slate-800">
                <i class="fa-solid fa-robot"></i>
                Open floating chat
            </button>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            @foreach([
                'Who is the president now?',
                'What are my tasks?',
                'When is the next event?',
                'How do I submit evidence?',
                'What is on my account?',
            ] as $prompt)
                <button type="button" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:text-slate-300" data-chat-prompt="{{ $prompt }}">{{ $prompt }}</button>
            @endforeach
        </div>

        <div id="volunteer-messages-log" class="custom-scrollbar mt-5 max-h-[28rem] space-y-4 overflow-y-auto rounded-xl bg-slate-50/80 p-4 dark:bg-slate-950/50">
            @forelse($timeline as $thread)
                <article class="space-y-2" data-thread-id="{{ $thread->id }}">
                    <div class="flex items-end justify-end gap-2">
                        <div class="max-w-[85%] rounded-2xl rounded-tr-md bg-jci-blue px-3.5 py-2.5 text-sm text-white">
                            <p class="whitespace-pre-wrap">{{ $thread->message }}</p>
                            <p class="mt-1 text-[10px] font-semibold text-sky-100">You · {{ \App\Support\ScheduleClock::stamp($thread->created_at) }}</p>
                        </div>
                        <x-ui.avatar :user="$volunteer" size="sm" />
                    </div>

                    @if(filled($thread->response))
                        <div class="flex items-end gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-jci-blue text-white shadow-sm">
                                <i class="fa-solid fa-robot text-[10px]"></i>
                            </span>
                            <div class="max-w-[85%] rounded-2xl rounded-tl-md bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Assistant</p>
                                <p class="mt-1 whitespace-pre-wrap leading-relaxed">{{ $thread->response }}</p>
                                <p class="mt-1 text-[10px] font-semibold text-slate-400">{{ \App\Support\ScheduleClock::stamp($thread->created_at) }}</p>
                            </div>
                        </div>
                    @elseif($thread->needs_takeover && !filled($thread->admin_reply))
                        <div class="flex items-end gap-2">
                            @if($organization)
                                <x-ui.avatar :user="$organization" size="sm" />
                            @else
                                <x-ui.brand-mark size="sm" :round="true" class="shadow-sm" />
                            @endif
                            <div class="max-w-[85%] rounded-2xl rounded-tl-md bg-amber-50 px-3.5 py-2.5 text-sm text-amber-800 ring-1 ring-amber-100 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-900">
                                <p>Waiting for {{ $orgName }} to reply.</p>
                            </div>
                        </div>
                    @endif

                    @if(filled($thread->admin_reply))
                        <div class="flex items-end gap-2">
                            @if($organization)
                                <x-ui.avatar :user="$organization" size="sm" />
                            @else
                                <x-ui.brand-mark size="sm" :round="true" class="shadow-sm" />
                            @endif
                            <div class="max-w-[85%] rounded-2xl rounded-tl-md bg-sky-50 px-3.5 py-2.5 text-sm text-slate-800 ring-1 ring-sky-100 dark:bg-sky-950/40 dark:text-slate-100 dark:ring-sky-900">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-jci-blue">{{ $orgName }}</p>
                                <p class="mt-1 whitespace-pre-wrap leading-relaxed">{{ $thread->admin_reply }}</p>
                                <p class="mt-1 text-[10px] font-semibold text-slate-400">{{ \App\Support\ScheduleClock::stamp($thread->admin_replied_at) }}</p>
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                <p id="volunteer-messages-empty" class="py-8 text-center text-sm text-slate-400">No messages yet. Ask a question below — the assistant answers first, and {{ $orgName }} can reply here too.</p>
            @endforelse
        </div>

        <form id="volunteer-messages-form" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="sr-only" for="volunteer-message-input">Message</label>
                <textarea id="volunteer-message-input" maxlength="500" required rows="2" placeholder="Ask about tasks, events, officers… or write to {{ $orgName }}" class="w-full resize-none rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
            </div>
            <button type="submit" id="volunteer-message-send" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Send</button>
        </form>
        <p class="mt-2 text-[11px] text-slate-400">The assistant uses your saved records. If it can’t answer, {{ $orgName }} is notified to reply in this thread.</p>
    </section>
</div>

<script>
(function () {
    const form = document.getElementById('volunteer-messages-form');
    const input = document.getElementById('volunteer-message-input');
    const sendBtn = document.getElementById('volunteer-message-send');
    const log = document.getElementById('volunteer-messages-log');
    const orgName = @json($orgName);
    const selfPhoto = @json($selfPhoto);
    const orgPhoto = @json($orgPhoto);
    const selfInitials = @json($selfInitials);
    const orgInitials = @json($orgInitials);

    function scrollLog() {
        if (log) log.scrollTop = log.scrollHeight;
    }

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function formatBody(str) {
        return escapeHtml(str).replace(/\n/g, '<br>');
    }

    function avatarHtml(photo, initials) {
        if (photo) {
            return '<img src="' + escapeHtml(photo) + '" alt="" class="h-7 w-7 shrink-0 rounded-full object-cover">';
        }
        return '<span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-300">' + escapeHtml(initials) + '</span>';
    }

    function removeEmpty() {
        document.getElementById('volunteer-messages-empty')?.remove();
    }

    function appendYou(text, time) {
        removeEmpty();
        const article = document.createElement('article');
        article.className = 'space-y-2';
        article.innerHTML = '<div class="flex items-end justify-end gap-2">'
            + '<div class="max-w-[85%] rounded-2xl rounded-tr-md bg-jci-blue px-3.5 py-2.5 text-sm text-white">'
            + '<p class="whitespace-pre-wrap">' + formatBody(text) + '</p>'
            + '<p class="mt-1 text-[10px] font-semibold text-sky-100">You · ' + escapeHtml(time) + '</p>'
            + '</div>'
            + avatarHtml(selfPhoto, selfInitials)
            + '</div>';
        log.appendChild(article);
        return article;
    }

    function appendAssistant(article, text, time) {
        const div = document.createElement('div');
        div.className = 'flex items-end gap-2';
        div.innerHTML = '<span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-jci-blue text-white shadow-sm"><i class="fa-solid fa-robot text-[10px]"></i></span>'
            + '<div class="max-w-[85%] rounded-2xl rounded-tl-md bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700">'
            + '<p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Assistant</p>'
            + '<p class="mt-1 whitespace-pre-wrap leading-relaxed">' + formatBody(text) + '</p>'
            + '<p class="mt-1 text-[10px] font-semibold text-slate-400">' + escapeHtml(time) + '</p>'
            + '</div>';
        article.appendChild(div);
    }

    function appendWaiting(article) {
        const div = document.createElement('div');
        div.className = 'flex items-end gap-2';
        div.innerHTML = avatarHtml(orgPhoto, orgInitials)
            + '<div class="max-w-[85%] rounded-2xl rounded-tl-md bg-amber-50 px-3.5 py-2.5 text-sm text-amber-800 ring-1 ring-amber-100 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-900">'
            + '<p>Waiting for ' + escapeHtml(orgName) + ' to reply.</p></div>';
        article.appendChild(div);
    }

    document.querySelectorAll('[data-chat-prompt]').forEach(function (button) {
        button.addEventListener('click', function () {
            input.value = button.dataset.chatPrompt;
            input.focus();
        });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const message = input.value.trim();
        if (!message || sendBtn.disabled) return;

        const now = new Date().toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
        input.value = '';
        sendBtn.disabled = true;
        const article = appendYou(message, now);
        scrollLog();

        fetch(@json(route('volunteer.chatbot.ask')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ message: message })
        }).then(function (response) { return response.json(); }).then(function (data) {
            sendBtn.disabled = false;
            if (data.show_assistant && data.response) {
                appendAssistant(article, data.response, data.time || now);
            } else if (data.with_agent || !data.response) {
                appendWaiting(article);
            }
            scrollLog();
            if (typeof volunteerChatFingerprint !== 'undefined') {
                volunteerChatFingerprint = '';
            }
        }).catch(function () {
            sendBtn.disabled = false;
            appendAssistant(article, 'Connection issue. Please try again.', now);
            scrollLog();
        });
    });

    scrollLog();
})();
</script>
