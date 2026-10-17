@php
    $clock = \App\Support\ScheduleClock::class;
    $filter = request('filter') === 'unread' ? 'unread' : 'all';
@endphp

<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('volunteer.dashboard', ['section' => 'notifications']) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $filter === 'all' ? 'bg-jci-blue text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-jci-blue dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700' }}">All notifications</a>
            <a href="{{ route('volunteer.dashboard', ['section' => 'notifications', 'filter' => 'unread']) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $filter === 'unread' ? 'bg-jci-blue text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-jci-blue dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700' }}">Unread</a>
        </div>
        <form action="{{ route('volunteer.notifications.read-all') }}" method="POST" class="shrink-0">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-jci-blue px-3.5 py-2 text-xs font-semibold text-white shadow-sm shadow-sky-500/20 hover:bg-jci-dark">
                <i class="fa-solid fa-check-double text-[10px]"></i>
                Mark all as read
            </button>
        </form>
    </div>

    <section class="space-y-3">
        @forelse($notices as $notice)
            @php
                $data = is_array($notice->data) ? $notice->data : [];
                $url = $data['url'] ?? null;
                $cardClass = 'block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-jci-blue dark:border-slate-800 dark:bg-slate-900 '.($notice->read_at ? '' : 'border-jci-blue/40 bg-sky-50/40 dark:bg-sky-950/20');
            @endphp
            @if($url)
                <a href="{{ $url }}"
                   data-notification-open="{{ $notice->id }}"
                   class="{{ $cardClass }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $data['title'] ?? 'Notification' }}</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $data['message'] ?? ($data['summary'] ?? '') }}</p>
                            <p class="mt-2 text-xs text-slate-400">{{ $clock::stamp($notice->created_at) }}</p>
                        </div>
                        <x-ui.status-badge :label="$notice->read_at ? 'Read' : 'Unread'" />
                    </div>
                </a>
            @else
                <article class="{{ $cardClass }} {{ $notice->read_at ? '' : 'cursor-pointer' }}"
                         @if(!$notice->read_at) data-read-notice="{{ $notice->id }}" role="button" tabindex="0" @endif>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $data['title'] ?? 'Notification' }}</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $data['message'] ?? ($data['summary'] ?? '') }}</p>
                            <p class="mt-2 text-xs text-slate-400">{{ $clock::stamp($notice->created_at) }}</p>
                        </div>
                        <x-ui.status-badge :label="$notice->read_at ? 'Read' : 'Unread'" />
                    </div>
                </article>
            @endif
        @empty
            <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-400 dark:border-slate-800 dark:bg-slate-900">No notifications in this list.</p>
        @endforelse
        @if($noticeTotal > $notices->count())
            <p class="text-xs text-slate-400">Showing the latest {{ $notices->count() }} of {{ $noticeTotal }}.</p>
        @endif
    </section>
</div>

<script>
    (function () {
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const readUrl = function (id) {
            return @json(url('/notifications')) + '/' + id + '/read';
        };

        document.querySelectorAll('[data-notification-open]').forEach(function (link) {
            link.addEventListener('click', function () {
                fetch(readUrl(link.dataset.notificationOpen), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    }
                });
            });
        });

        document.querySelectorAll('[data-read-notice]').forEach(function (card) {
            card.addEventListener('click', function () {
                fetch(readUrl(card.dataset.readNotice), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    }
                }).then(function () {
                    window.location.reload();
                });
            });
        });
    })();
</script>
