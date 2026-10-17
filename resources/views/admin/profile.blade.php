<x-layout.admin activeTab="profile">
    @php
        $initials = collect(explode(' ', (string) $user->name))->map(fn ($part) => substr($part, 0, 1))->take(2)->join('');
        $joined = $user->created_at?->timezone(config('audit.display_timezone', 'Asia/Manila'));
        $joinedLabel = $joined?->format('M j, Y');
        $joinedShort = $joined?->format('M Y');
        $showPasswordForm = isset($errors) && ($errors->has('current_password') || $errors->has('password'));
        $field = 'mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-jci-blue focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100';
        $label = 'block text-xs font-semibold text-slate-500';
        $orgsDeclined = $orgsDeclined ?? 0;
        $recentActivity = $recentActivity ?? collect();
    @endphp

    <div class="mx-auto max-w-5xl space-y-5">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="h-24 bg-gradient-to-r from-jci-blue via-sky-500 to-jci-dark sm:h-28"></div>
            <div class="relative px-5 pb-5 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex items-end gap-4 -mt-12 sm:-mt-14">
                        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="admin-avatar-form" class="relative shrink-0">
                            @csrf
                            <input type="hidden" name="section" value="profile">
                            <input type="hidden" name="name" value="{{ $user->name }}">
                            <input type="hidden" name="email" value="{{ $user->email }}">
                            <input type="hidden" name="phone" value="{{ $user->phone }}">
                            <label for="admin-avatar-input" class="group relative block cursor-pointer" title="Change photo">
                                <span class="block overflow-hidden rounded-2xl border-4 border-white shadow-md dark:border-slate-900">
                                    @if($user->profile_photo_path)
                                        <img id="admin-avatar-preview" src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="{{ $user->name }}" class="h-24 w-24 object-cover sm:h-28 sm:w-28">
                                    @else
                                        <img id="admin-avatar-preview" src="" alt="" class="hidden h-24 w-24 object-cover sm:h-28 sm:w-28">
                                        <span id="admin-avatar-fallback" class="flex h-24 w-24 items-center justify-center bg-jci-blue text-3xl font-bold text-white sm:h-28 sm:w-28">{{ strtoupper($initials) }}</span>
                                    @endif
                                </span>
                                <span class="absolute bottom-1 right-1 flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-jci-blue shadow-sm transition group-hover:bg-sky-50 dark:border-slate-700 dark:bg-slate-800">
                                    <i class="fa-solid fa-camera text-xs"></i>
                                </span>
                            </label>
                            <input id="admin-avatar-input" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="previewAdminAvatar(this)">
                        </form>
                        <div class="min-w-0 pb-1">
                            <h2 class="truncate text-xl font-extrabold text-slate-900 dark:text-white sm:text-2xl">{{ $user->name }}</h2>
                            <p class="mt-0.5 truncate text-sm text-slate-500">{{ $user->email }}</p>
                            <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                                <x-ui.status-badge label="Admin" />
                                <x-ui.status-badge label="Active" />
                                @if($joinedShort)
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">Joined {{ $joinedShort }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:pb-1">
                        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            <i class="fa-solid fa-arrow-left"></i> Dashboard
                        </a>
                        <a href="{{ route('admin.audit') }}" class="inline-flex items-center gap-2 rounded-xl bg-jci-blue px-3 py-2 text-xs font-semibold text-white hover:bg-jci-dark">
                            <i class="fa-solid fa-clipboard-list"></i> Audit logs
                        </a>
                    </div>
                </div>
                @error('profile_photo')
                    <p class="mt-3 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </section>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Actions</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-jci-blue dark:bg-sky-950"><i class="fa-solid fa-bolt text-xs"></i></span>
                </div>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ number_format($actions) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400">Logged activity</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Verified</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950"><i class="fa-solid fa-building-circle-check text-xs"></i></span>
                </div>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ number_format($orgsVerified) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400">Orgs approved</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Rejected</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950"><i class="fa-solid fa-building-circle-xmark text-xs"></i></span>
                </div>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ number_format($orgsDeclined) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400">Orgs rejected</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Reports</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-950"><i class="fa-solid fa-chart-column text-xs"></i></span>
                </div>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ number_format($reports) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400">Report actions</p>
            </div>
        </div>

        <div class="grid items-start gap-5 lg:grid-cols-5">
            <div class="space-y-5 lg:col-span-3">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div class="border-b border-slate-100 pb-3 dark:border-slate-800">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Personal information</h3>
                        <p class="mt-1 text-xs text-slate-400">Name and contact details for this admin account. Role cannot be changed.</p>
                    </div>
                    <form action="{{ route('profile.update') }}" method="POST" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="section" value="profile">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="{{ $label }}">Full name <span class="text-rose-500">*</span>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="{{ $field }} @error('name') border-rose-500 @enderror">
                                @error('name')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                            </label>
                            <label class="{{ $label }}">Role
                                <input type="text" value="System Admin" readonly class="{{ $field }} bg-slate-50 text-slate-500 dark:bg-slate-900 dark:text-slate-400">
                            </label>
                            <label class="{{ $label }}">Email <span class="text-rose-500">*</span>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="{{ $field }} @error('email') border-rose-500 @enderror">
                                @error('email')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                            </label>
                            <label class="{{ $label }}">Mobile
                                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="e.g. 09123456789" class="{{ $field }} @error('phone') border-rose-500 @enderror">
                                @error('phone')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                            </label>
                        </div>
                        <div class="flex justify-end border-t border-slate-100 pt-4 dark:border-slate-800">
                            <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save changes</button>
                        </div>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Security</h3>
                            <p class="mt-1 text-xs text-slate-400">
                                @if($passwordChangedAt)
                                    Password last changed {{ $passwordChangedAt->diffForHumans() }}.
                                @else
                                    No password change is recorded yet.
                                @endif
                            </p>
                        </div>
                        <button type="button" onclick="document.getElementById('admin-password-form').classList.toggle('hidden')" class="shrink-0 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            {{ $showPasswordForm ? 'Hide form' : 'Change password' }}
                        </button>
                    </div>

                    <div class="mt-4 flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-3 dark:border-slate-800 dark:bg-slate-950">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-jci-blue dark:bg-sky-950">
                            <i class="fa-solid fa-key text-sm"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Account password</p>
                            <p class="text-xs text-slate-400">Use a strong password you do not reuse elsewhere. Two-factor authentication is not available.</p>
                        </div>
                    </div>

                    <form id="admin-password-form" action="{{ route('profile.update') }}" method="POST" class="{{ $showPasswordForm ? '' : 'hidden' }} mt-4 space-y-4">
                        @csrf
                        <input type="hidden" name="section" value="password">
                        <label class="{{ $label }}">Current password <span class="text-rose-500">*</span>
                            <input type="password" name="current_password" autocomplete="current-password" required class="{{ $field }}">
                            @error('current_password')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="{{ $label }}">New password <span class="text-rose-500">*</span>
                                <input type="password" name="password" autocomplete="new-password" required minlength="8" class="{{ $field }}">
                                @error('password')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                            </label>
                            <label class="{{ $label }}">Confirm new password <span class="text-rose-500">*</span>
                                <input type="password" name="password_confirmation" autocomplete="new-password" required minlength="8" class="{{ $field }}">
                            </label>
                        </div>
                        <div class="flex justify-end border-t border-slate-100 pt-4 dark:border-slate-800">
                            <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Update password</button>
                        </div>
                    </form>
                </section>
            </div>

            <div class="space-y-5 lg:col-span-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Account summary</h3>
                    <dl class="mt-4 space-y-3">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-3 dark:border-slate-800 dark:bg-slate-950">
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Signed in as</dt>
                            <dd class="mt-1 truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $user->email }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-3 dark:border-slate-800 dark:bg-slate-950">
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Member since</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $joinedLabel ?? '—' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-3 dark:border-slate-800 dark:bg-slate-950">
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Phone</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $user->phone ?: 'Not set' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Recent activity</h3>
                        <a href="{{ route('admin.audit') }}" class="text-[11px] font-semibold text-jci-blue hover:text-jci-dark">View all</a>
                    </div>
                    <div class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($recentActivity as $entry)
                            <div class="py-3">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $entry->action }}</p>
                                <p class="mt-0.5 line-clamp-2 text-xs text-slate-400">{{ $entry->description ?: ($entry->module ?: 'Audit entry') }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $entry->created_at?->timezone(config('audit.display_timezone', 'Asia/Manila'))->format('M j, Y g:i A') }}</p>
                            </div>
                        @empty
                            <div class="py-8 text-center">
                                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                                    <i class="fa-solid fa-clipboard-list"></i>
                                </span>
                                <p class="mt-3 text-sm text-slate-400">No activity recorded yet.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        function previewAdminAvatar(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const preview = document.getElementById('admin-avatar-preview');
            const fallback = document.getElementById('admin-avatar-fallback');
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                if (fallback) fallback.classList.add('hidden');
                document.getElementById('admin-avatar-form').submit();
            };
            reader.readAsDataURL(file);
        }
    </script>
</x-layout.admin>
