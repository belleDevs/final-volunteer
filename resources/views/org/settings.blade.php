@php
    $panes = [
        'profile' => ['Organization Profile', 'fa-building', 'Public details volunteers see'],
        'security' => ['Account & Security', 'fa-shield-halved', 'Login email, password, sessions'],
        'notifications' => ['Notifications', 'fa-bell', 'Email preferences'],
        'preferences' => ['Preferences', 'fa-sliders', 'Time zone and date format'],
        'documents' => ['Verification', 'fa-file-shield', 'Compliance documents'],
    ];
    $field = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100';
    $label = 'block text-xs font-semibold text-slate-500';
    $accountStatus = ucfirst($organization->status ?: 'pending');
    $orgType = $organization->organizationTypeLabel() ?? 'Organization';
    $enabledMailCount = collect($notificationPreferences ?? [])->filter()->count();
@endphp

<div class="space-y-5">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="bg-gradient-to-br from-jci-blue to-jci-dark px-5 py-6 text-white sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    @if($organization->profile_photo_path)
                        <img src="{{ asset('storage/'.$organization->profile_photo_path) }}" alt="{{ $organization->name }}" class="h-16 w-16 rounded-2xl object-cover ring-2 ring-white/30">
                    @else
                        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 text-2xl font-bold ring-2 ring-white/20">{{ strtoupper(substr($organization->name, 0, 1)) }}</span>
                    @endif
                    <div class="min-w-0">
                        <h1 class="truncate text-xl font-semibold tracking-tight">{{ $organization->name }}</h1>
                        <p class="mt-0.5 truncate text-sm text-white/75">{{ $orgType }} · {{ $organization->email }}</p>
                        <div class="mt-2.5 flex flex-wrap items-center gap-2">
                            <x-ui.status-badge :label="$organization->verificationLabel()" />
                            <x-ui.status-badge :label="$accountStatus" />
                        </div>
                    </div>
                </div>
                <div class="grid shrink-0 grid-cols-2 gap-2 sm:max-w-xs">
                    <div class="rounded-xl bg-white/10 px-3 py-2.5 backdrop-blur-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-white/60">Mail alerts</p>
                        <p class="mt-0.5 text-sm font-semibold">{{ $enabledMailCount }} of {{ count(\App\Support\OrgPreferences::NOTIFICATION_KEYS) }} on</p>
                    </div>
                    <div class="rounded-xl bg-white/10 px-3 py-2.5 backdrop-blur-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-white/60">Documents</p>
                        <p class="mt-0.5 text-sm font-semibold">{{ ($complianceDocuments ?? collect())->count() }} filed</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid items-start gap-5 lg:grid-cols-[220px_minmax(0,1fr)]">
        <nav class="space-y-1 rounded-2xl border border-slate-200 bg-white p-2 dark:border-slate-800 dark:bg-slate-900 lg:sticky lg:top-4">
            @foreach($panes as $key => [$labelText, $icon, $hint])
                <a href="{{ route('org.dashboard', ['section' => 'settings', 'pane' => $key]) }}"
                   class="flex items-start gap-3 rounded-xl px-3 py-2.5 transition {{ $settingsPane === $key ? 'bg-jci-blue text-white shadow-sm shadow-sky-500/20' : 'text-slate-600 hover:bg-slate-50 hover:text-jci-blue dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    <i class="fa-solid {{ $icon }} mt-0.5 w-4 text-center text-sm {{ $settingsPane === $key ? 'text-white/90' : 'text-slate-400' }}"></i>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold leading-tight">{{ $labelText }}</span>
                        <span class="mt-0.5 block text-[11px] leading-snug {{ $settingsPane === $key ? 'text-white/70' : 'text-slate-400' }}">{{ $hint }}</span>
                    </span>
                </a>
            @endforeach
        </nav>

        <div class="space-y-5">
            @if($settingsPane === 'profile')
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Organization profile</h2>
                    <p class="mt-1 text-sm text-slate-500">These details appear to volunteers and on your public-facing org info. Change the login email under Account & Security.</p>

                    <form action="{{ route('org.settings.profile') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-5">
                        @csrf
                        <div class="flex flex-col gap-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950 sm:flex-row sm:items-center">
                            @if($organization->profile_photo_path)
                                <img src="{{ asset('storage/'.$organization->profile_photo_path) }}" alt="{{ $organization->name }}" class="h-20 w-20 rounded-2xl object-cover">
                            @else
                                <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-jci-blue text-2xl font-semibold text-white">{{ strtoupper(substr($organization->name, 0, 1)) }}</span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Organization logo</p>
                                <p class="mt-0.5 text-xs text-slate-400">JPG, PNG, or WebP · max 2 MB</p>
                                <input name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-jci-blue file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-jci-dark">
                                @error('profile_photo')
                                    <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="{{ $label }} sm:col-span-2">Organization name <span class="text-rose-500">*</span>
                                <input name="name" value="{{ old('name', $organization->name) }}" required maxlength="255" class="{{ $field }} mt-1.5">
                                @error('name')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                            </label>
                            <label class="{{ $label }}">Organization type <span class="text-rose-500">*</span>
                                <select name="organization_type" required class="{{ $field }} mt-1.5">
                                    @foreach(\App\Models\User::ORGANIZATION_TYPES as $value => $typeLabel)
                                        <option value="{{ $value }}" @selected(old('organization_type', $organization->organization_type) === $value)>{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                                @error('organization_type')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                            </label>
                            <label class="{{ $label }}">Phone number
                                <input name="phone" value="{{ old('phone', $organization->phone) }}" maxlength="30" class="{{ $field }} mt-1.5">
                                @error('phone')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                            </label>
                            <label class="{{ $label }} sm:col-span-2">Description and mission
                                <textarea name="bio" rows="4" maxlength="2000" class="{{ $field }} mt-1.5" placeholder="What your organization does and who you serve">{{ old('bio', $organization->bio) }}</textarea>
                                @error('bio')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                            </label>
                        </div>

                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-400">Contact</h3>
                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                <label class="{{ $label }}">Authorized representative
                                    <input name="representative_name" value="{{ old('representative_name', $organization->representative_name) }}" maxlength="255" class="{{ $field }} mt-1.5">
                                    @error('representative_name')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                                </label>
                                <label class="{{ $label }}">Public contact email
                                    <input name="contact_email" type="email" value="{{ old('contact_email', $organization->contact_email) }}" maxlength="255" placeholder="{{ $organization->email }}" class="{{ $field }} mt-1.5">
                                    @error('contact_email')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                                </label>
                                <label class="{{ $label }} sm:col-span-2">Office address
                                    <input name="address" value="{{ old('address', $organization->address) }}" maxlength="500" class="{{ $field }} mt-1.5">
                                    @error('address')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                                </label>
                                <label class="{{ $label }} sm:col-span-2">Website
                                    <input name="website" type="url" value="{{ old('website', $organization->website) }}" maxlength="255" placeholder="https://" class="{{ $field }} mt-1.5">
                                    @error('website')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                                </label>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-400">Social</h3>
                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                <label class="{{ $label }}">Facebook
                                    <input name="facebook_url" type="url" value="{{ old('facebook_url', $organization->facebook_url) }}" maxlength="255" placeholder="https://" class="{{ $field }} mt-1.5">
                                    @error('facebook_url')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                                </label>
                                <label class="{{ $label }}">Instagram
                                    <input name="instagram_url" type="url" value="{{ old('instagram_url', $organization->instagram_url) }}" maxlength="255" placeholder="https://" class="{{ $field }} mt-1.5">
                                    @error('instagram_url')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end border-t border-slate-100 pt-4 dark:border-slate-800">
                            <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save profile</button>
                        </div>
                    </form>
                </section>

            @elseif($settingsPane === 'security')
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Login & password</h2>
                    <p class="mt-1 text-sm text-slate-500">This account stays an organization login. It cannot be promoted to administrator.</p>

                    <form action="{{ route('org.settings.security') }}" method="POST" class="mt-5 space-y-4">
                        @csrf
                        <label class="{{ $label }}">Login email <span class="text-rose-500">*</span>
                            <input name="email" type="email" required value="{{ old('email', $organization->email) }}" class="{{ $field }} mt-1.5">
                            @error('email')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <label class="{{ $label }}">Current password <span class="text-rose-500">*</span>
                            <input name="current_password" type="password" required autocomplete="current-password" class="{{ $field }} mt-1.5">
                            @error('current_password')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="{{ $label }}">New password
                                <input name="password" type="password" autocomplete="new-password" minlength="8" class="{{ $field }} mt-1.5" placeholder="Leave blank to keep">
                                @error('password')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                            </label>
                            <label class="{{ $label }}">Confirm new password
                                <input name="password_confirmation" type="password" autocomplete="new-password" class="{{ $field }} mt-1.5">
                            </label>
                        </div>
                        <p class="text-xs text-slate-400">Verification: <span class="font-semibold text-slate-600 dark:text-slate-200">{{ $organization->verificationLabel() }}</span>. Two-factor authentication is not available.</p>
                        <div class="flex justify-end border-t border-slate-100 pt-4 dark:border-slate-800">
                            <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save security</button>
                        </div>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Active sessions</h2>
                    <p class="mt-1 text-sm text-slate-500">Sign out devices you do not recognize. Your current browser stays signed in.</p>
                    <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($accountSessions as $accountSession)
                            <div class="flex items-center justify-between gap-3 py-3.5">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                        <i class="fa-solid fa-desktop text-sm"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ \Illuminate\Support\Str::limit($accountSession->user_agent ?: 'Unknown device', 72) }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">{{ $accountSession->ip_address ?: 'Unknown address' }} · {{ \App\Support\OrgPreferences::format(\Carbon\Carbon::createFromTimestamp($accountSession->last_activity), $organization, true) }}</p>
                                    </div>
                                </div>
                                @if($accountSession->id === session()->getId())
                                    <span class="shrink-0 rounded-full bg-jci-blue px-2.5 py-1 text-[11px] font-semibold text-white">This device</span>
                                @else
                                    <form action="{{ route('org.settings.sessions.destroy', $accountSession->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-xl border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:border-rose-900 dark:hover:bg-rose-950/40">Sign out</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-slate-400">No sessions are recorded yet.</p>
                        @endforelse
                    </div>
                </section>

            @elseif($settingsPane === 'notifications')
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Email preferences</h2>
                    <p class="mt-1 text-sm text-slate-500">Choose which updates are emailed to <span class="font-medium text-slate-700 dark:text-slate-200">{{ $organization->email }}</span>. The in-app inbox still records every notice.</p>

                    <form action="{{ route('org.settings.notifications') }}" method="POST" class="mt-5 space-y-2.5">
                        @csrf
                        @foreach(\App\Support\OrgPreferences::NOTIFICATION_KEYS as $key => $notifLabel)
                            <input type="hidden" name="{{ $key }}" value="0">
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-100 px-3.5 py-3 transition hover:border-slate-200 dark:border-slate-800 dark:hover:border-slate-700">
                                <input type="checkbox" name="{{ $key }}" value="1" @checked(filter_var(old($key, $notificationPreferences[$key]), FILTER_VALIDATE_BOOLEAN)) class="rounded border-slate-300 text-jci-blue focus:ring-jci-blue">
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-slate-800 dark:text-slate-100">{{ $notifLabel }}</span>
                                    @if($key === 'email')
                                        <span class="mt-0.5 block text-xs text-slate-400">Master switch — turn off to pause all email alerts</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                        <p class="pt-1 text-xs text-slate-400">SMS notifications are not available.</p>
                        <div class="flex justify-end border-t border-slate-100 pt-4 dark:border-slate-800">
                            <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save preferences</button>
                        </div>
                    </form>
                </section>

            @elseif($settingsPane === 'preferences')
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Display preferences</h2>
                    <p class="mt-1 text-sm text-slate-500">These choices change how dates and times appear across this organization workspace. VolunteerHub is available in English.</p>

                    <form action="{{ route('org.settings.preferences') }}" method="POST" class="mt-5 space-y-4">
                        @csrf
                        <label class="{{ $label }}">Language
                            <select name="locale" class="{{ $field }} mt-1.5">
                                <option value="en" selected>English</option>
                            </select>
                        </label>
                        <label class="{{ $label }}">Time zone <span class="text-rose-500">*</span>
                            <select name="timezone" required class="{{ $field }} mt-1.5">
                                @foreach(\App\Support\OrgPreferences::TIMEZONES as $value => $tzLabel)
                                    <option value="{{ $value }}" @selected(old('timezone', $organization->timezone ?: 'Asia/Manila') === $value)>{{ $tzLabel }}</option>
                                @endforeach
                            </select>
                            @error('timezone')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <label class="{{ $label }}">Date format <span class="text-rose-500">*</span>
                            <select name="date_format" required class="{{ $field }} mt-1.5">
                                @foreach(\App\Support\OrgPreferences::DATE_FORMATS as $value => $fmtLabel)
                                    <option value="{{ $value }}" @selected(old('date_format', $organization->date_format ?: 'M j, Y') === $value)>{{ $fmtLabel }}</option>
                                @endforeach
                            </select>
                            @error('date_format')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-3 dark:border-slate-800 dark:bg-slate-950">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Preview</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ \App\Support\OrgPreferences::format(now(), $organization, true) }}</p>
                        </div>
                        <div class="flex justify-end border-t border-slate-100 pt-4 dark:border-slate-800">
                            <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save preferences</button>
                        </div>
                    </form>
                </section>

            @else
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Verification status</h2>
                            <p class="mt-1 text-sm text-slate-500">An administrator decides verification. You can submit documents here, but you cannot approve your own organization.</p>
                        </div>
                        <div class="shrink-0 text-left sm:text-right">
                            <x-ui.status-badge :label="$organization->verificationLabel()" />
                            @if($organization->verification_updated_at)
                                <p class="mt-1.5 text-xs text-slate-400">Updated {{ \App\Support\OrgPreferences::format($organization->verification_updated_at, $organization, true) }}</p>
                            @endif
                        </div>
                    </div>

                    @if($organization->documents_requested)
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
                            <p class="font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1.5 text-xs"></i>Documents requested</p>
                            <p class="mt-1 leading-relaxed">{{ $organization->documents_requested }}</p>
                        </div>
                    @endif
                    @if($organization->verification_feedback)
                        <div class="mt-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200">
                            <p class="font-semibold">Administrator feedback</p>
                            <p class="mt-1 leading-relaxed">{{ $organization->verification_feedback }}</p>
                        </div>
                    @endif
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Upload document</h2>
                    <p class="mt-1 text-sm text-slate-500">PDF, DOCX, or XLSX. Use this to submit or resubmit compliance files.</p>
                    <form action="{{ route('org.documents.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950 sm:flex-row sm:items-end">
                        @csrf
                        <input type="hidden" name="return" value="settings">
                        <label class="block min-w-0 flex-1 text-xs font-semibold text-slate-500">Choose file
                            <input name="document_file" type="file" required accept=".pdf,.docx,.xlsx" class="mt-1.5 block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 file:shadow-sm dark:file:bg-slate-800 dark:file:text-slate-200">
                            @error('document_file')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Upload</button>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Submitted documents</h2>
                    <div class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($complianceDocuments as $document)
                            @php
                                $file = $document->original_data ?? [];
                                $rawSubmitted = $file['uploaded_at'] ?? null;
                                $submittedAt = $rawSubmitted ? \Carbon\Carbon::parse($rawSubmitted) : $document->created_at;
                                $submittedHasTime = is_string($rawSubmitted) && preg_match('/\d{1,2}:\d{2}/', $rawSubmitted);
                            @endphp
                            <div class="flex items-center justify-between gap-3 py-3.5">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-jci-blue dark:bg-sky-950">
                                        <i class="fa-solid fa-file-lines text-sm"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $file['filename'] ?? 'Document' }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">Submitted {{ \App\Support\OrgPreferences::format($submittedAt, $organization, $submittedHasTime || !$rawSubmitted) }}@if(!empty($file['size'])) · {{ $file['size'] }}@endif</p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    @if(!empty($file['file_path']))
                                        <a href="{{ route('org.documents.download', $document) }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-jci-blue hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Download</a>
                                    @endif
                                    <form action="{{ route('org.documents.destroy', $document) }}" method="POST" onsubmit="return confirm('Delete this document? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-xl border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:border-rose-900 dark:hover:bg-rose-950/40">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="py-10 text-center">
                                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                                    <i class="fa-solid fa-folder-open"></i>
                                </span>
                                <p class="mt-3 text-sm text-slate-400">No documents have been submitted yet.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
