@props(['active' => null])

@php
    $navUser = Auth::user();
    $navInitials = collect(explode(' ', (string) $navUser->name))->map(fn ($part) => substr($part, 0, 1))->take(2)->join('');
    $navActive = $active ?? (request()->routeIs('profile.show') ? 'profile' : request('section', 'dashboard'));
    $navUnread = $navUser->unreadNotifications()->count();
    $volHeader = [
        'dashboard' => ['Dashboard', 'Your events, tasks, and hours'],
        'events' => ['My Events', 'Events you are part of'],
        'tasks' => ['Assigned Tasks', 'Accept work, then submit evidence'],
        'availability' => ['Availability', 'When you can take a task'],
        'notifications' => ['Notifications', $navUnread > 0 ? $navUnread.' unread' : 'You are caught up'],
        'certificates' => ['Certificates', 'Certificates issued to you'],
        'skills' => ['Skill Recommendations', 'Build your profile for better role matches'],
        'messages' => ['Messages', 'Assistant answers and organization replies'],
        'profile' => ['Profile', $navUser->name],
        'settings' => ['Settings', 'Account and preferences'],
    ];
    $volHeading = $volHeader[$navActive] ?? ['Volunteer', 'Workspace'];
    $nav = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-table-cells', 'href' => route('volunteer.dashboard')],
        ['key' => 'events', 'label' => 'My Events', 'icon' => 'fa-calendar-days', 'href' => route('volunteer.dashboard', ['section' => 'events'])],
        ['key' => 'tasks', 'label' => 'Assigned Tasks', 'icon' => 'fa-list-check', 'href' => route('volunteer.dashboard', ['section' => 'tasks'])],
        ['key' => 'availability', 'label' => 'Availability', 'icon' => 'fa-clock', 'href' => route('volunteer.dashboard', ['section' => 'availability'])],
        ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'fa-bell', 'href' => route('volunteer.dashboard', ['section' => 'notifications'])],
        ['key' => 'certificates', 'label' => 'Certificates', 'icon' => 'fa-certificate', 'href' => route('volunteer.dashboard', ['section' => 'certificates'])],
        ['key' => 'skills', 'label' => 'Skill Recommendations', 'icon' => 'fa-wand-magic-sparkles', 'href' => route('volunteer.dashboard', ['section' => 'skills'])],
        ['key' => 'messages', 'label' => 'Messages', 'icon' => 'fa-comments', 'href' => route('volunteer.dashboard', ['section' => 'messages'])],
        ['key' => 'profile', 'label' => 'Profile', 'icon' => 'fa-user', 'href' => route('profile.show')],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'fa-gear', 'href' => route('volunteer.dashboard', ['section' => 'settings'])],
    ];
@endphp

<x-layout.app title="VolunteerHub - Volunteer Portal">
    <style>
        @media (min-width: 768px) {
            html.vol-collapsed #vol-sidebar { display: none; }
            html.vol-collapsed #vol-sidebar-show { display: inline-flex; }
            html.vol-collapsed #vol-page { max-width: none; }
        }
    </style>
    <script>
        try {
            if (localStorage.getItem('volSidebarCollapsed') === '1' && window.matchMedia('(min-width: 768px)').matches) {
                document.documentElement.classList.add('vol-collapsed');
            }
        } catch (e) {}
    </script>
    <div class="flex h-screen overflow-hidden bg-[#f5f7fb] text-slate-800 dark:bg-[#0b1120] dark:text-slate-100">
        <div id="vol-sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-slate-900/40 md:hidden"></div>

        <aside id="vol-sidebar" class="flex h-full w-60 shrink-0 flex-col border-r border-slate-200 bg-white dark:border-slate-800 dark:bg-[#0c1222] max-md:fixed max-md:inset-y-0 max-md:left-0 max-md:z-50 max-md:-translate-x-full max-md:transition-transform max-md:duration-200">
            <div class="flex items-center gap-3 px-5 py-5">
                <x-ui.brand-mark />
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold tracking-tight text-slate-900 dark:text-white">VolunteerHub</p>
                    <p class="text-[11px] font-medium text-slate-400">Volunteer</p>
                </div>
            </div>

            <div class="px-4 pb-2">
                <p class="px-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Workspace</p>
            </div>

            <nav class="custom-scrollbar flex-1 space-y-0.5 overflow-y-auto px-3 pb-4">
                @foreach($nav as $item)
                    @php $isActive = $navActive === $item['key']; @endphp
                    <a href="{{ $item['href'] }}"
                       class="flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-[13px] font-semibold transition-all {{ $isActive ? 'bg-jci-blue text-white shadow-md shadow-sky-500/25' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                        <i class="fa-solid {{ $item['icon'] }} w-4 text-center text-sm"></i>
                        <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                        @if($item['key'] === 'notifications' && $navUnread > 0)
                            <span class="ml-auto rounded-full px-2 py-0.5 text-[10px] font-black {{ $isActive ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-600' }}">{{ $navUnread > 99 ? '99+' : $navUnread }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div id="vol-sidebar-foot" class="space-y-0.5 border-t border-slate-200 p-3 dark:border-slate-800">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-slate-500 transition hover:bg-rose-50 hover:text-rose-600">
                        <i class="fa-solid fa-arrow-right-from-bracket w-4 text-center text-sm"></i>
                        Logout
                    </button>
                </form>
                <button type="button" id="vol-collapse-toggle" class="hidden w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-white md:flex">
                    <i class="fa-solid fa-angles-left w-4 text-center text-sm"></i>
                    Collapse
                </button>
            </div>
        </aside>

        <div class="flex min-h-0 min-w-0 flex-1 flex-col bg-gradient-to-br from-[#f7f9fc] via-[#f3f7ff] to-[#e7f2fb] dark:bg-none dark:bg-[#0b1120]">
            <header class="z-30 flex shrink-0 items-center justify-between gap-4 border-b border-slate-200/80 bg-white/80 px-4 py-3.5 backdrop-blur dark:border-slate-800 dark:bg-[#0c1222]/90 sm:px-6 md:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" id="vol-sidebar-show" class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300" title="Show sidebar">
                        <i class="fa-solid fa-bars text-sm"></i>
                    </button>
                    <button type="button" id="vol-sidebar-toggle" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-jci-blue hover:text-jci-blue dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 md:hidden" title="Show sidebar">
                        <i class="fa-solid fa-bars text-sm"></i>
                    </button>
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-extrabold text-slate-900 dark:text-white sm:text-lg">{{ $volHeading[0] }}</h3>
                        <p class="truncate text-[11px] font-medium text-slate-400 sm:text-xs">{{ $volHeading[1] }}</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <button type="button" id="vol-theme-toggle" class="flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-jci-blue dark:text-slate-300 dark:hover:bg-slate-800" title="Switch to dark mode" aria-label="Switch color theme">
                        <i id="vol-theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    <x-ui.notifications-bell />
                    <div class="hidden h-8 w-px bg-slate-200 dark:bg-slate-700 sm:block"></div>
                    <a href="{{ route('profile.show') }}" class="group flex items-center gap-2.5 rounded-xl px-1.5 py-1 transition hover:bg-slate-50 dark:hover:bg-slate-800">
                        <div class="hidden text-right sm:block">
                            <p class="text-xs font-bold leading-tight text-slate-800 transition group-hover:text-jci-blue dark:text-slate-100">{{ $navUser->name }}</p>
                            <p class="text-[10px] font-medium text-slate-400">Volunteer</p>
                        </div>
                        @if($navUser->profile_photo_path)
                            <img src="{{ asset('storage/'.$navUser->profile_photo_path) }}" alt="{{ $navUser->name }}" class="h-9 w-9 shrink-0 rounded-full border border-slate-200 object-cover">
                        @else
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-jci-blue text-xs font-bold text-white">{{ strtoupper($navInitials) }}</span>
                        @endif
                    </a>
                </div>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto">
            <main class="flex-1">
                <div id="vol-page" class="mx-auto w-full max-w-[1200px] px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>
            </div>
        </div>
    </div>

    <x-ui.volunteer-chatbot />

    <script>
        (function () {
            const sidebar = document.getElementById('vol-sidebar');
            const backdrop = document.getElementById('vol-sidebar-backdrop');
            const toggle = document.getElementById('vol-sidebar-toggle');
            const collapseButton = document.getElementById('vol-collapse-toggle');
            const showButton = document.getElementById('vol-sidebar-show');
            const themeButton = document.getElementById('vol-theme-toggle');
            const themeIcon = document.getElementById('vol-theme-icon');
            const root = document.documentElement;

            function paintTheme() {
                const dark = root.classList.contains('dark');
                if (themeIcon) {
                    themeIcon.className = dark ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
                }
                if (themeButton) {
                    const label = dark ? 'Switch to light mode' : 'Switch to dark mode';
                    themeButton.title = label;
                    themeButton.setAttribute('aria-label', label);
                }
            }

            function closeMobile() {
                sidebar.classList.add('max-md:-translate-x-full');
                backdrop.classList.add('hidden');
            }

            function openMobile() {
                sidebar.classList.remove('max-md:-translate-x-full');
                backdrop.classList.remove('hidden');
            }

            paintTheme();

            themeButton.addEventListener('click', function () {
                const dark = root.classList.toggle('dark');
                localStorage.setItem('theme', dark ? 'dark' : 'light');
                paintTheme();
            });

            toggle.addEventListener('click', function () {
                sidebar.classList.add('max-md:transition-transform', 'max-md:duration-200');
                if (sidebar.classList.contains('max-md:-translate-x-full')) {
                    openMobile();
                } else {
                    closeMobile();
                }
            });

            collapseButton.addEventListener('click', function () {
                root.classList.add('vol-collapsed');
                localStorage.setItem('volSidebarCollapsed', '1');
            });
            showButton.addEventListener('click', function () {
                root.classList.remove('vol-collapsed');
                localStorage.setItem('volSidebarCollapsed', '0');
            });

            backdrop.addEventListener('click', closeMobile);
            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', closeMobile);
            });
        })();
    </script>
</x-layout.app>
