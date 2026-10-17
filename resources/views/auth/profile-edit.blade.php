@php
    $dashboardRoute = match ($user->role) {
        'admin' => route('admin.dashboard'),
        'organization' => route('org.dashboard'),
        default => route('volunteer.dashboard'),
    };
    $initials = collect(explode(' ', (string) $user->name))->map(fn ($n) => substr($n, 0, 1))->take(2)->join('');
    $isVolunteer = $user->role === 'volunteer';
    $availabilityLabel = match ($user->availability) {
        'inactive' => 'Unavailable',
        'limited' => 'Busy / Limited',
        default => 'Available',
    };
    $org = $isVolunteer ? $user->primaryOrganization : null;
    $recentAssignments = $recentAssignments ?? collect();
    $profileStats = $profileStats ?? null;
    $membershipApplication = $membershipApplication ?? null;
@endphp

<x-dynamic-component :component="$isVolunteer ? 'layout.volunteer' : 'layout.app'" :active="'profile'" :title="'VolunteerHub - Profile'">
    @if($isVolunteer)
        <div class="mx-auto max-w-4xl space-y-5">
            {{-- Hero --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="h-20 bg-gradient-to-r from-jci-blue via-sky-500 to-jci-dark sm:h-24"></div>
                <div class="relative px-5 pb-5 pt-0 sm:px-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div class="flex items-end gap-4 -mt-10 sm:-mt-12">
                            <label for="profile-photo-input" class="group relative shrink-0 cursor-pointer" title="Change photo">
                                <span id="profile-photo-preview-wrap" class="block overflow-hidden rounded-2xl border-4 border-white shadow-md dark:border-slate-900">
                                    @if($user->profile_photo_path)
                                        <img id="profile-photo-preview" src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="{{ $user->name }}" class="h-20 w-20 object-cover sm:h-24 sm:w-24">
                                    @else
                                        <img id="profile-photo-preview" src="" alt="" class="hidden h-20 w-20 object-cover sm:h-24 sm:w-24">
                                        <span id="profile-photo-fallback" class="flex h-20 w-20 items-center justify-center bg-jci-blue text-2xl font-black text-white sm:h-24 sm:w-24">{{ strtoupper($initials) }}</span>
                                    @endif
                                </span>
                                <span class="absolute bottom-1 right-1 flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 bg-white text-jci-blue shadow-sm transition group-hover:bg-sky-50 dark:border-slate-700 dark:bg-slate-800">
                                    <i class="fa-solid fa-camera text-[11px]"></i>
                                </span>
                            </label>
                            <div class="min-w-0 pb-1">
                                <h2 class="truncate text-xl font-extrabold text-slate-900 dark:text-white">{{ $user->name }}</h2>
                                <p class="mt-0.5 truncate text-sm text-slate-500">{{ $user->email }}</p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-jci-blue dark:bg-sky-950/50 dark:text-sky-300">Volunteer</span>
                                    @if($user->isMember() && $org)
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Member · {{ $org->name }}</span>
                                    @elseif($membershipApplication)
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">{{ $membershipApplication->statusLabel() }}</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">No org yet</span>
                                    @endif
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $availabilityLabel }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 sm:pb-1">
                            <a href="{{ route('volunteer.dashboard', ['section' => 'settings']) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                <i class="fa-solid fa-gear"></i> Settings
                            </a>
                            <a href="{{ $dashboardRoute }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                <i class="fa-solid fa-arrow-left"></i> Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Stats --}}
            @if($profileStats)
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Hours</p>
                        <p class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">{{ number_format($profileStats['hours'], 1) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Completed</p>
                        <p class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">{{ $profileStats['completed'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Certificates</p>
                        <p class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">{{ $profileStats['certificates'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Skills</p>
                        <p class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">{{ $profileStats['skills'] }}</p>
                    </div>
                </div>
            @endif

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-5" id="volunteer-profile-form">
                @csrf
                <input id="profile-photo-input" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="hidden">

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">About you</h3>
                        <span class="text-[10px] font-medium text-slate-400">Photo updates when you save</span>
                    </div>
                    @error('profile_photo')
                        <p class="mt-2 text-xs font-medium text-rose-500">{{ $message }}</p>
                    @enderror

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500" for="profile-name">Full name <span class="text-rose-500">*</span></label>
                            <input id="profile-name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-jci-blue focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950 @error('name') border-rose-500 @enderror">
                            @error('name')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500" for="profile-phone">Phone</label>
                            <input id="profile-phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="e.g. 09123456789"
                                   class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-jci-blue focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">
                            @error('phone')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-semibold text-slate-500">Email</label>
                        <div class="mt-1.5 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300">
                            <span class="truncate">{{ $user->email }}</span>
                            <a href="{{ route('volunteer.dashboard', ['section' => 'settings']) }}" class="shrink-0 text-xs font-semibold text-jci-blue hover:underline">Change in Settings</a>
                        </div>
                        <input type="hidden" name="email" value="{{ $user->email }}">
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-semibold text-slate-500" for="profile-bio">Bio</label>
                        <textarea id="profile-bio" name="bio" rows="3" maxlength="1000" placeholder="A short intro about you and why you volunteer…"
                                  class="mt-1.5 w-full resize-none rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-jci-blue focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">{{ old('bio', $user->bio) }}</textarea>
                        @error('bio')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500" for="profile-interests">Interests</label>
                            <textarea id="profile-interests" name="interests" rows="3" maxlength="1000" placeholder="Causes or topics you care about…"
                                      class="mt-1.5 w-full resize-none rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-jci-blue focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">{{ old('interests', $user->interests) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500" for="profile-experience">Volunteer experience</label>
                            <textarea id="profile-experience" name="volunteer_experience" rows="3" maxlength="2000" placeholder="Past projects, roles, or training…"
                                      class="mt-1.5 w-full resize-none rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-jci-blue focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950">{{ old('volunteer_experience', $user->volunteer_experience) }}</textarea>
                        </div>
                    </div>
                </section>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Skills</h3>
                            <a href="{{ route('volunteer.dashboard', ['section' => 'skills']) }}" class="text-xs font-semibold text-jci-blue hover:underline">Manage</a>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @forelse($user->skills as $skill)
                                <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-jci-blue dark:bg-sky-950/40 dark:text-sky-300">{{ $skill->name }}</span>
                            @empty
                                <p class="text-sm text-slate-400">No skills yet. Add some so your organization can match roles.</p>
                            @endforelse
                        </div>
                        <div class="mt-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Availability</h4>
                                <a href="{{ route('volunteer.dashboard', ['section' => 'availability']) }}" class="text-xs font-semibold text-jci-blue hover:underline">Update</a>
                            </div>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $availabilityLabel }}</p>
                            @if(filled($user->availability_notes))
                                <p class="mt-1 text-xs text-slate-400">{{ $user->availability_notes }}</p>
                            @endif
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Recent participation</h3>
                            <a href="{{ route('volunteer.dashboard', ['section' => 'tasks']) }}" class="text-xs font-semibold text-jci-blue hover:underline">Tasks</a>
                        </div>
                        <ul class="mt-3 space-y-2.5">
                            @forelse($recentAssignments as $history)
                                <li class="rounded-xl border border-slate-100 px-3 py-2.5 dark:border-slate-800">
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $history->task?->title ?? 'Task' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $history->event?->title ?? 'Event' }} · {{ str_replace('_', ' ', ucfirst($history->status)) }}</p>
                                </li>
                            @empty
                                <li class="text-sm text-slate-400">No assignments yet.</li>
                            @endforelse
                        </ul>
                        @if($org)
                            <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800">
                                Organization: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $org->name }}</span>
                            </p>
                        @endif
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
                    <a href="{{ $dashboardRoute }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-jci-blue px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-sky-500/20 hover:bg-jci-dark">
                        <i class="fa-solid fa-floppy-disk"></i> Save profile
                    </button>
                </div>
            </form>
        </div>

        <script>
            (function () {
                const input = document.getElementById('profile-photo-input');
                const preview = document.getElementById('profile-photo-preview');
                const fallback = document.getElementById('profile-photo-fallback');
                if (!input || !preview) return;
                input.addEventListener('change', function () {
                    const file = input.files && input.files[0];
                    if (!file) return;
                    const url = URL.createObjectURL(file);
                    preview.src = url;
                    preview.classList.remove('hidden');
                    if (fallback) fallback.classList.add('hidden');
                });
            })();
        </script>
    @else
        {{-- Organization (and other non-admin) profile — existing compact form --}}
        <div class="flex-grow bg-slate-50 px-4 py-8 md:px-8">
            <div class="mx-auto max-w-4xl space-y-6">
                <div class="flex flex-col items-start justify-between gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center">
                    <div class="flex items-center gap-4">
                        <label for="profile-photo-input" class="group relative shrink-0 cursor-pointer" title="Change profile photo">
                            @if($user->profile_photo_path)
                                <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="{{ $user->name }}" class="h-14 w-14 rounded-2xl border border-slate-200 object-cover shadow-md">
                            @else
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-jci-blue text-xl font-black text-white shadow-md">{{ strtoupper($initials) }}</div>
                            @endif
                            <span class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full border border-slate-200 bg-white text-jci-blue shadow-sm transition group-hover:bg-blue-50">
                                <i class="fa-solid fa-camera text-[10px]"></i>
                            </span>
                        </label>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-extrabold text-slate-800">{{ $user->name }}</h2>
                                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-[10px] font-black uppercase text-jci-blue">{{ ucfirst($user->role) }} Profile</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $user->email }} • Joined {{ $user->created_at->format('M Y') }}</p>
                        </div>
                    </div>
                    <a href="{{ $dashboardRoute }}" class="flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-200">
                        <i class="fa-solid fa-arrow-left"></i> Return to Dashboard
                    </a>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        <input id="profile-photo-input" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="hidden">

                        <h3 class="flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-extrabold text-slate-800">
                            <i class="fa-solid fa-user-gear text-jci-blue"></i> General Information
                        </h3>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Organization Name <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Email Address <span class="text-rose-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Phone / Contact Number</label>
                                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue" placeholder="e.g. 09123456789">
                            </div>
                        </div>

                        @if($user->role === 'organization')
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Organization Type <span class="text-rose-500">*</span></label>
                                <select name="organization_type" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                                    <option value="" disabled {{ old('organization_type', $user->organization_type) ? '' : 'selected' }}>Select a socio-civic focus</option>
                                    @foreach(\App\Models\User::ORGANIZATION_TYPES as $value => $label)
                                        <option value="{{ $value }}" {{ old('organization_type', $user->organization_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Event completion reward <span class="text-rose-500">*</span></label>
                                    <select name="event_reward" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                                        @foreach(\App\Models\User::EVENT_REWARDS as $value => $label)
                                            <option value="{{ $value }}" {{ old('event_reward', $user->event_reward) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Task completion reward <span class="text-rose-500">*</span></label>
                                    <select name="task_reward" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue">
                                        @foreach(\App\Models\User::TASK_REWARDS as $value => $label)
                                            <option value="{{ $value }}" {{ old('task_reward', $user->task_reward) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Organization Profile & Mission Statement</label>
                            <textarea name="bio" rows="3" class="w-full resize-none rounded-xl border border-slate-200 p-3 text-xs focus:outline-none focus:ring-1 focus:ring-jci-blue" placeholder="Share your mission…">{{ old('bio', $user->bio) }}</textarea>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                            <a href="{{ $dashboardRoute }}" class="rounded-xl border border-slate-200 px-5 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Cancel</a>
                            <button type="submit" class="rounded-xl bg-jci-blue px-6 py-2.5 text-xs font-bold text-white hover:bg-jci-dark">Save Profile Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</x-dynamic-component>
