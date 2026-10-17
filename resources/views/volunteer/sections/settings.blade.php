@php
    $preferences = $volunteer->notification_preferences ?? [];
    $deactivationRequested = $preferences['deactivation_requested_at'] ?? null;
    $deletionRequested = $preferences['deletion_requested_at'] ?? null;
    $statusLabel = ucfirst($volunteer->status ?: 'active');
@endphp

<div class="mx-auto max-w-3xl space-y-5">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Account</h2>
        <p class="mt-1 text-sm text-slate-500">Manage sign-in details and how VolunteerHub contacts you.</p>
        <dl class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-3 dark:border-slate-800 dark:bg-slate-950">
                <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Status</dt>
                <dd class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $statusLabel }}</dd>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-3 dark:border-slate-800 dark:bg-slate-950">
                <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Signed in as</dt>
                <dd class="mt-1 truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $volunteer->email }}</dd>
            </div>
        </dl>
        <p class="mt-3 text-xs text-slate-400">
            Profile details (name, photo, bio) are under
            <a href="{{ route('profile.show') }}" class="font-semibold text-jci-blue hover:underline">Profile</a>.
        </p>
    </section>

    <form action="{{ route('profile.update') }}" method="POST" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
        @csrf
        <input type="hidden" name="section" value="password">
        <div>
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Change password</h2>
            <p class="mt-1 text-xs text-slate-400">Use a strong password you do not reuse elsewhere.</p>
        </div>
        <label class="block text-xs font-semibold text-slate-500">Current password
            <input type="password" name="current_password" required autocomplete="current-password" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-xs font-semibold text-slate-500">New password
                <input type="password" name="password" required minlength="8" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
            </label>
            <label class="block text-xs font-semibold text-slate-500">Confirm new password
                <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
            </label>
        </div>
        <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save password</button>
    </form>

    <form action="{{ route('volunteer.settings.update') }}" method="POST" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
        @csrf
        <input type="hidden" name="section" value="email">
        <div>
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Update email</h2>
            <p class="mt-1 text-xs text-slate-400">Confirm with your current password. Email verification is not turned on.</p>
        </div>
        <label class="block text-xs font-semibold text-slate-500">Email
            <input type="email" name="email" required value="{{ old('email', $volunteer->email) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
        </label>
        <label class="block text-xs font-semibold text-slate-500">Current password
            <input type="password" name="current_password" required autocomplete="current-password" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
        </label>
        <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save email</button>
    </form>

    <form action="{{ route('volunteer.settings.update') }}" method="POST" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
        @csrf
        <input type="hidden" name="section" value="preferences">
        <div>
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Notification preferences</h2>
            <p class="mt-1 text-xs text-slate-400">Choose which notices appear in your bell and notification list.</p>
        </div>
        <div class="space-y-2.5">
            @foreach(['assignments' => 'Task assignments', 'events' => 'Event notices', 'certificates' => 'Certificates', 'reminders' => 'Reminders and other notices'] as $key => $label)
                <label class="flex items-center gap-3 rounded-xl border border-slate-100 px-3 py-2.5 text-sm text-slate-700 dark:border-slate-800 dark:text-slate-200">
                    <input type="checkbox" name="{{ $key }}" value="1" @checked($preferences[$key] ?? true) class="rounded border-slate-300 text-jci-blue focus:ring-jci-blue">
                    {{ $label }}
                </label>
            @endforeach
        </div>
        <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save preferences</button>
    </form>

    <section class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm dark:border-rose-900/50 dark:bg-slate-900 sm:p-6">
        <h2 class="text-sm font-semibold text-rose-700 dark:text-rose-300">Danger zone</h2>
        <p class="mt-1 text-sm text-slate-500">These actions need a confirmation step. Nothing happens from a single click.</p>

        <div class="mt-5 space-y-4">
            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Deactivate account</h3>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">
                            Sends a request to pause your volunteer account. You stay signed in and active until an administrator reviews it.
                        </p>
                        @if($deactivationRequested)
                            <p class="mt-2 text-xs font-medium text-amber-700 dark:text-amber-300">
                                Request sent {{ \Illuminate\Support\Carbon::parse($deactivationRequested)->timezone(\App\Support\ScheduleClock::ZONE)->format('M j, Y g:i A') }}. Waiting for review.
                            </p>
                        @endif
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        @if($deactivationRequested)
                            <form action="{{ route('volunteer.settings.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="section" value="cancel_deactivate">
                                <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel request</button>
                            </form>
                        @else
                            <button type="button" onclick="document.getElementById('modal-deactivate-account').classList.remove('hidden')" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                                Request deactivation
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-rose-200 p-4 dark:border-rose-900/60">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Delete account</h3>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">
                            Permanently removes your volunteer login and profile from VolunteerHub. This cannot be undone.
                        </p>
                        @if($deletionRequested)
                            <p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-300">
                                A deletion request was noted {{ \Illuminate\Support\Carbon::parse($deletionRequested)->timezone(\App\Support\ScheduleClock::ZONE)->format('M j, Y g:i A') }}. Completing delete below removes the account now.
                            </p>
                        @endif
                    </div>
                    <button type="button" onclick="document.getElementById('modal-delete-account').classList.remove('hidden')" class="shrink-0 rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700">
                        Delete account
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>

<x-ui.modal id="modal-deactivate-account" title="Request deactivation">
    <p class="text-sm text-slate-600 dark:text-slate-300">
        This only <span class="font-semibold">requests</span> deactivation. Your account stays active until an administrator handles it. Confirm with your password to continue.
    </p>
    <form action="{{ route('volunteer.settings.update') }}" method="POST" class="mt-4 space-y-4">
        @csrf
        <input type="hidden" name="section" value="deactivate">
        <label class="block text-xs font-semibold text-slate-500">Current password
            <input type="password" name="current_password" required autocomplete="current-password" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
        </label>
        <label class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-200">
            <input type="checkbox" name="confirm" value="1" required class="mt-1 rounded border-slate-300 text-jci-blue focus:ring-jci-blue">
            <span>I understand this sends a review request and does not deactivate my account immediately.</span>
        </label>
        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
            <button type="button" onclick="document.getElementById('modal-deactivate-account').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">Cancel</button>
            <button type="submit" class="rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-amber-700">Send request</button>
        </div>
    </form>
</x-ui.modal>

<x-ui.modal id="modal-delete-account" title="Delete account">
    <div class="rounded-xl border border-rose-100 bg-rose-50 px-3.5 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200">
        Deleting your account removes your login, profile, and volunteer records tied to this user. Certificates and history linked only to you will also be removed. This cannot be undone.
    </div>
    <form action="{{ route('volunteer.settings.update') }}" method="POST" class="mt-4 space-y-4" id="delete-account-form">
        @csrf
        <input type="hidden" name="section" value="delete">
        <label class="block text-xs font-semibold text-slate-500">Current password
            <input type="password" name="current_password" required autocomplete="current-password" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
        </label>
        <label class="block text-xs font-semibold text-slate-500">
            Type <span class="font-mono text-rose-600">DELETE</span> to confirm
            <input type="text" name="confirm_text" required autocomplete="off" placeholder="DELETE" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm uppercase tracking-wide dark:border-slate-700 dark:bg-slate-950">
        </label>
        <label class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-200">
            <input type="checkbox" name="confirm" value="1" required class="mt-1 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
            <span>I understand this permanently deletes my VolunteerHub volunteer account.</span>
        </label>
        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
            <button type="button" onclick="document.getElementById('modal-delete-account').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">Cancel</button>
            <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-rose-700">Delete permanently</button>
        </div>
    </form>
</x-ui.modal>
