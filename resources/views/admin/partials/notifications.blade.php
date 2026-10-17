@php
    $noticeFilter = $noticeFilter ?? 'all';
    $unreadCount = $adminNoticeUnreadTotal ?? $adminNotices->where('read', false)->count();
    $totalCount = $adminNoticeTotal ?? $adminNotices->count();
    $shownCount = $adminNotices->count();
@endphp

<div class="space-y-5">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="bg-gradient-to-r from-jci-blue to-jci-dark px-5 py-5 text-white sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/70">Inbox</p>
                    <h4 class="mt-1 text-xl font-extrabold tracking-tight">
                        {{ number_format($unreadCount) }} unread
                        <span class="font-semibold text-white/70">· {{ number_format($totalCount) }} total</span>
                    </h4>
                    <p class="mt-1 text-sm text-white/75">Registrations, events, documents, and system alerts for this admin account.</p>
                </div>
                @if($unreadCount > 0)
                    <form action="{{ route('admin.notifications.read-all') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-3 py-2 text-xs font-semibold text-white hover:bg-white/25">
                            <i class="fa-solid fa-check-double"></i> Mark all as read
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach([
            ['label' => 'Unread', 'value' => $unreadCount, 'icon' => 'fa-bell', 'iconBg' => 'bg-rose-50 dark:bg-rose-950', 'iconColor' => 'text-rose-500 dark:text-rose-300', 'href' => route('admin.dashboard', ['tab' => 'notifications', 'filter' => 'unread'])],
            ['label' => 'Registrations waiting', 'value' => $pendingOrgs->count(), 'icon' => 'fa-building', 'iconBg' => 'bg-amber-50 dark:bg-amber-950', 'iconColor' => 'text-amber-500 dark:text-amber-300', 'href' => route('admin.dashboard', ['tab' => 'audits'])],
            ['label' => 'Posted events', 'value' => $postedEvents->count(), 'icon' => 'fa-calendar-check', 'iconBg' => 'bg-sky-50 dark:bg-sky-950', 'iconColor' => 'text-sky-600 dark:text-sky-300', 'href' => route('admin.dashboard', ['tab' => 'events'])],
            ['label' => 'Completions in review', 'value' => $pendingCompletionReviews, 'icon' => 'fa-clipboard-check', 'iconBg' => 'bg-indigo-50 dark:bg-indigo-950', 'iconColor' => 'text-indigo-500 dark:text-indigo-300', 'href' => route('admin.dashboard', ['tab' => 'tasks'])],
        ] as $card)
            <a href="{{ $card['href'] }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-sky-300 dark:border-slate-800 dark:bg-slate-900">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }}">
                    <i class="fa-solid {{ $card['icon'] }} text-sm"></i>
                </span>
                <p class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ number_format($card['value']) }}</p>
                <p class="mt-0.5 text-xs font-medium text-slate-400">{{ $card['label'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Notice history</h4>
                        <p class="mt-0.5 text-xs text-slate-400">Showing {{ $shownCount }} of {{ number_format($totalCount) }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('admin.dashboard', ['tab' => 'notifications']) }}"
                           class="rounded-full px-3 py-1.5 text-xs font-bold {{ $noticeFilter === 'all' ? 'bg-jci-blue text-white' : 'border border-slate-200 bg-white text-slate-500 hover:border-sky-300 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300' }}">All</a>
                        <a href="{{ route('admin.dashboard', ['tab' => 'notifications', 'filter' => 'unread']) }}"
                           class="rounded-full px-3 py-1.5 text-xs font-bold {{ $noticeFilter === 'unread' ? 'bg-jci-blue text-white' : 'border border-slate-200 bg-white text-slate-500 hover:border-sky-300 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300' }}">Unread</a>
                        <label class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-300"></i>
                            <input id="admin-notice-search" type="search" placeholder="Search notices…"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-3 text-xs focus:border-jci-blue focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950 sm:w-52">
                        </label>
                    </div>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800" id="admin-notice-list">
                    @forelse($adminNotices as $notice)
                        @php
                            $isSelected = $selectedNotice && $selectedNotice['id'] === $notice['id'];
                            $searchBlob = strtolower(trim(($notice['title'] ?? '').' '.($notice['message'] ?? '').' '.($notice['summary'] ?? '')));
                        @endphp
                        <article id="notice-{{ $notice['id'] }}"
                                 data-notice-card
                                 data-search="{{ $searchBlob }}"
                                 data-read="{{ $notice['read'] ? '1' : '0' }}"
                                 class="p-4 transition {{ $isSelected ? 'bg-sky-50 dark:bg-sky-950/30' : ($notice['read'] ? 'bg-white hover:bg-slate-50/80 dark:bg-slate-900 dark:hover:bg-slate-800/40' : 'bg-sky-50/40 hover:bg-sky-50 dark:bg-sky-950/20') }}">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm {{ $notice['read'] ? 'bg-slate-100 text-slate-400 dark:bg-slate-800' : 'bg-blue-50 text-jci-blue dark:bg-sky-950 dark:text-sky-300' }}">
                                    <i class="fa-solid {{ $notice['icon'] }}"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <h5 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $notice['title'] }}</h5>
                                        <x-ui.status-badge :label="$notice['read'] ? 'Read' : 'Unread'" />
                                    </div>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $notice['message'] ?: $notice['summary'] }}</p>
                                    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2">
                                        <span class="text-[11px] text-slate-400">{{ $notice['stamp'] }} · {{ $notice['when'] }}</span>
                                        @if($notice['url'])
                                            <a href="{{ $notice['url'] }}" class="text-xs font-semibold text-jci-blue hover:text-jci-dark">
                                                {{ $notice['action'] ?: 'Open related page' }}
                                            </a>
                                        @endif
                                        @if(!$notice['read'])
                                            <button type="button"
                                                    data-mark-notice-read="{{ $notice['id'] }}"
                                                    class="text-xs font-semibold text-slate-500 hover:text-jci-blue">
                                                Mark as read
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="px-5 py-16 text-center">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                                <i class="fa-solid fa-bell-slash"></i>
                            </span>
                            <p class="mt-3 text-sm text-slate-400">
                                {{ $noticeFilter === 'unread' ? 'No unread notices right now.' : 'No notices yet. New organization applications, posted events, and compliance uploads will appear here.' }}
                            </p>
                            @if($noticeFilter === 'unread')
                                <a href="{{ route('admin.dashboard', ['tab' => 'notifications']) }}" class="mt-2 inline-block text-xs font-semibold text-jci-blue hover:text-jci-dark">Show all notices</a>
                            @endif
                        </div>
                    @endforelse
                </div>
                <p id="admin-notice-empty" class="hidden px-5 py-10 text-center text-sm text-slate-400">No notices match your search.</p>
            </section>
        </div>

        <div class="space-y-4">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">What needs attention</h4>
                <p class="mt-0.5 text-xs text-slate-400">Live counts from current records.</p>
                <div class="mt-4 space-y-2">
                    <a href="{{ route('admin.dashboard', ['tab' => 'audits']) }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 px-3.5 py-3 transition hover:border-sky-200 dark:border-slate-800 dark:hover:border-sky-800">
                        <span class="flex items-center gap-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-300"><i class="fa-solid fa-building text-xs"></i></span>
                            Org applications
                        </span>
                        <span class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $pendingOrgs->count() }}</span>
                    </a>
                    <a href="{{ route('admin.dashboard', ['tab' => 'events']) }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 px-3.5 py-3 transition hover:border-sky-200 dark:border-slate-800 dark:hover:border-sky-800">
                        <span class="flex items-center gap-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950 dark:text-sky-300"><i class="fa-solid fa-calendar-check text-xs"></i></span>
                            Posted events
                        </span>
                        <span class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $postedEvents->count() }}</span>
                    </a>
                    <a href="{{ route('admin.dashboard', ['tab' => 'tasks']) }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 px-3.5 py-3 transition hover:border-sky-200 dark:border-slate-800 dark:hover:border-sky-800">
                        <span class="flex items-center gap-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-500 dark:bg-indigo-950 dark:text-indigo-300"><i class="fa-solid fa-clipboard-check text-xs"></i></span>
                            Completions in review
                        </span>
                        <span class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $pendingCompletionReviews }}</span>
                    </a>
                    <a href="{{ route('admin.dashboard', ['tab' => 'broadcast']) }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 px-3.5 py-3 transition hover:border-sky-200 dark:border-slate-800 dark:hover:border-sky-800">
                        <span class="flex items-center gap-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-950 dark:text-violet-300"><i class="fa-solid fa-tower-broadcast text-xs"></i></span>
                            Post a system notice
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
                    </a>
                </div>
            </section>

            @if($selectedNotice)
                <section class="rounded-2xl border border-sky-200 bg-sky-50 p-5 shadow-sm dark:border-sky-900 dark:bg-sky-950/40">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-sky-700/70 dark:text-sky-300/70">Opened from the bell</p>
                    <div class="mt-2 flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-jci-blue shadow-sm dark:bg-slate-900">
                            <i class="fa-solid {{ $selectedNotice['icon'] }}"></i>
                        </span>
                        <div class="min-w-0">
                            <h5 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $selectedNotice['title'] }}</h5>
                            <p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $selectedNotice['message'] }}</p>
                            <p class="mt-2 text-[11px] text-slate-400">{{ $selectedNotice['stamp'] }}</p>
                            @if($selectedNotice['url'])
                                <a href="{{ $selectedNotice['url'] }}" class="mt-3 inline-flex text-xs font-semibold text-jci-blue hover:text-jci-dark">
                                    {{ $selectedNotice['action'] ?: 'Open related page' }}
                                </a>
                            @endif
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>

@if($activeTab === 'notifications')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const search = document.getElementById('admin-notice-search');
        const cards = Array.from(document.querySelectorAll('[data-notice-card]'));
        const empty = document.getElementById('admin-notice-empty');
        const token = document.querySelector('meta[name="csrf-token"]')?.content;

        function applySearch() {
            const query = (search?.value || '').trim().toLowerCase();
            let visible = 0;
            cards.forEach(function (card) {
                const show = query === '' || (card.dataset.search || '').includes(query);
                card.classList.toggle('hidden', !show);
                if (show) visible += 1;
            });
            if (empty) empty.classList.toggle('hidden', visible !== 0 || cards.length === 0);
        }

        search?.addEventListener('input', applySearch);

        document.querySelectorAll('[data-mark-notice-read]').forEach(function (button) {
            button.addEventListener('click', function () {
                const id = button.dataset.markNoticeRead;
                fetch(@json(url('/notifications')) + '/' + encodeURIComponent(id) + '/read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(function () {
                    window.location.reload();
                });
            });
        });

        @if($selectedNotice)
            document.getElementById('notice-{{ $selectedNotice['id'] }}')?.scrollIntoView({ block: 'center' });
        @endif
    });
</script>
@endif
