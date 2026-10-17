@props(['color' => 'default'])

@php
    $isOrganization = Auth::user()?->role === 'organization';
    if ($isOrganization) {
        $notifications = \App\Support\OrgNotifier::recent((int) Auth::id());
        $unreadCount = \App\Support\OrgNotifier::unreadCount((int) Auth::id());
    } else {
        $notifications = Auth::user() ? Auth::user()->notifications()->orderBy('created_at', 'desc')->take(6)->get() : collect();
        $unreadCount = Auth::user() ? Auth::user()->unreadNotifications()->count() : 0;
    }

    $btnClasses = $color === 'white'
        ? 'relative flex h-9 w-9 items-center justify-center rounded-full text-slate-200 transition hover:bg-white/10 hover:text-white focus:outline-none'
        : 'relative flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-jci-blue focus:outline-none dark:text-slate-300 dark:hover:bg-slate-800';
    $noticeIndex = Auth::user()?->role === 'admin'
        ? route('admin.dashboard', ['tab' => 'notifications'])
        : ($isOrganization
            ? route('org.dashboard', ['section' => 'notifications'])
            : (Auth::user()?->role === 'volunteer' ? route('volunteer.dashboard', ['section' => 'notifications']) : null));
@endphp

<div class="relative inline-block text-left" id="notifications-bell-dropdown-wrapper">
    <button type="button" onclick="toggleNotificationsDropdown(event)" aria-label="Notifications"
            class="{{ $btnClasses }}">
        <i class="fa-solid fa-bell text-lg"></i>
        @if ($unreadCount > 0)
            <span class="absolute top-0.5 right-0.5 inline-flex items-center justify-center px-1.5 py-0.5 text-[8px] font-black leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-rose-500 rounded-full shadow-md" id="notifications-bell-badge">
                {{ $unreadCount }}
            </span>
        @endif
    </button>

    <div id="notifications-dropdown-menu"
         class="absolute right-0 z-[999] mt-3 hidden w-[min(20rem,calc(100vw-1.5rem))] max-w-[calc(100vw-1.5rem)] origin-top-right overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-950">
            <h4 class="flex items-center gap-1.5 text-xs font-extrabold uppercase tracking-wider text-slate-800 dark:text-slate-100">
                <i class="fa-solid fa-bell text-xs text-jci-blue"></i> Notifications
            </h4>
            @if ($unreadCount > 0)
                <span class="rounded-lg bg-blue-50 px-2 py-0.5 text-[9px] font-bold text-jci-blue dark:bg-sky-950" id="notifications-unread-label">
                    {{ $unreadCount }} New
                </span>
            @endif
        </div>

        <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 custom-scrollbar" id="notifications-list-container">
            @if ($notifications->count() > 0)
                @foreach ($notifications as $notif)
                    @php
                        $notifData = is_array($notif->data) ? $notif->data : json_decode($notif->data, true);
                        $title = $notifData['title'] ?? 'Alert';
                        $message = $notifData['message'] ?? '';
                        $summary = $notifData['summary'] ?? \Illuminate\Support\Str::limit($message, 88);
                        $icon = $notifData['icon'] ?? 'fa-circle-info';
                        $isUnread = is_null($notif->read_at);
                        $noticeUrl = Auth::user()?->role === 'admin'
                            ? route('admin.dashboard', ['tab' => 'notifications', 'notice' => $notif->id])
                            : ($isOrganization
                                ? route('org.notifications.open', $notif->id)
                                : ($notifData['url'] ?? null));
                        $itemClass = 'p-4 flex gap-3 hover:bg-slate-50/60 transition group relative cursor-pointer '.($isUnread ? 'bg-blue-50/10' : '');
                    @endphp
                    @if($noticeUrl)
                        <a href="{{ $noticeUrl }}"
                           class="{{ $itemClass }}"
                           id="notification-item-{{ $notif->id }}"
                           data-notification-open="{{ $notif->id }}"
                           @if(!$isOrganization && Auth::user()?->role !== 'admin') data-mark-read-on-open="1" @endif>
                    @else
                        <button type="button"
                                class="{{ $itemClass }} w-full text-left"
                                id="notification-item-{{ $notif->id }}"
                                onclick="dismissNotification('{{ $notif->id }}', event)">
                    @endif
                        <div class="h-8 w-8 rounded-full shrink-0 flex items-center justify-center text-xs
                                    {{ $isUnread ? 'bg-blue-50 text-jci-blue border border-blue-100' : 'bg-slate-100 text-slate-400' }}">
                            <i class="fa-solid {{ $icon }}"></i>
                        </div>
                        <div class="flex-grow space-y-0.5 pr-4 min-w-0">
                            <h5 class="text-xs font-bold text-slate-800 leading-tight truncate dark:text-slate-100">{{ $title }}</h5>
                            <p class="text-[10px] text-slate-500 leading-normal">{{ $summary }}</p>
                            <span class="text-[8px] text-slate-400 font-medium block pt-1">
                                {{ \App\Support\ScheduleClock::stamp($notif->created_at) }}
                            </span>
                        </div>
                        @if ($isUnread)
                            <span class="absolute top-4 right-4 h-2 w-2 rounded-full bg-jci-blue" id="unread-dot-{{ $notif->id }}"></span>
                        @endif
                    @if($noticeUrl)
                        </a>
                    @else
                        </button>
                    @endif
                @endforeach
            @else
                <div class="p-6 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-1.5">
                    <i class="fa-solid fa-envelope-open-text text-2xl text-slate-300"></i>
                    <span>All caught up! No notifications.</span>
                </div>
            @endif
        </div>
        @if($noticeIndex)
            <a href="{{ $noticeIndex }}" class="block border-t border-slate-100 py-3 text-center text-xs font-bold text-jci-blue hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800">
                View all notifications
            </a>
        @endif
    </div>
</div>

<script>
    (function () {
        document.querySelectorAll('[data-mark-read-on-open]').forEach(function (link) {
            link.addEventListener('click', function () {
                const id = link.dataset.notificationOpen;
                if (!id) {
                    return;
                }
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                if (!token) {
                    return;
                }
                fetch('/notifications/' + id + '/read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    }
                });
            });
        });
    })();
</script>
