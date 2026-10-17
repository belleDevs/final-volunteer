<x-layout.app title="VolunteerHub - Volunteer Registration">
    <div class="flex-grow flex items-center justify-center p-3 sm:p-6 md:p-12 min-h-[100dvh] bg-gradient-to-tr from-slate-900 via-jci-dark to-jci-blue py-4 sm:py-12">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden max-w-5xl w-full grid grid-cols-1 md:grid-cols-12 my-auto">
            
            <!-- Left Banner: Aesthetics & Mission -->
            <div class="hidden md:col-span-5 bg-gradient-to-b from-jci-blue to-jci-dark p-6 sm:p-8 md:p-12 text-white md:flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -top-12 -left-12 w-48 h-48 bg-white/5 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-20 -right-20 w-72 h-72 bg-jci-accent/10 rounded-full blur-3xl"></div>
                
                <div class="relative z-10">
                    <span class="bg-jci-accent text-jci-dark text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">Volunteer Sign Up</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold mt-3 sm:mt-4 tracking-tight leading-tight">Become a Community Hero.</h2>
                    <p class="text-xs sm:text-sm text-slate-200 mt-2 sm:mt-3 leading-relaxed">Register as a volunteer to showcase your skills, sign up for impactful local outreach projects, and earn verified credentials.</p>
                </div>

                <div class="mt-6 sm:mt-8 relative z-10 border-t border-white/10 pt-4 sm:pt-6">
                    <div class="flex items-center gap-3">
                        <x-ui.brand-mark class="shadow-none ring-1 ring-white/20" />
                        <div>
                            <h5 class="text-xs font-bold text-white">Interview before membership</h5>
                            <p class="text-[10px] text-slate-300">Create an account now. Joining an organization needs a personal interview and approval.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Form: Volunteer Registration -->
            <div class="md:col-span-7 p-5 sm:p-8 md:p-12 flex flex-col justify-center">
                <div class="mb-4 flex items-center gap-2.5 md:hidden">
                    <x-ui.brand-mark size="sm" />
                    <p class="text-sm font-extrabold text-slate-900">VolunteerHub</p>
                </div>
                
                <!-- Toggle Header -->
                <div class="flex items-start sm:items-center justify-between gap-3 border-b border-slate-100 pb-4 mb-6">
                    <div class="min-w-0">
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900">Volunteer Registration</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Create your volunteer profile in seconds.</p>
                    </div>
                    <a href="{{ route('login') }}" class="shrink-0 text-xs font-bold text-jci-blue hover:underline">
                        <i class="fa-solid fa-arrow-left"></i> Log In
                    </a>
                </div>

                <!-- Registration Type Selector Tabs -->
                <div class="grid grid-cols-2 gap-2 mb-6">
                    <a href="{{ route('register.volunteer') }}" class="border-2 border-jci-blue bg-blue-50/50 text-jci-blue rounded-xl p-2.5 flex items-center justify-center gap-2 font-bold text-xs shadow-sm">
                        <i class="fa-solid fa-handshake-angle text-sm"></i>
                        <span>Volunteer</span>
                    </a>
                    <a href="{{ route('register.org') }}" class="border border-slate-200 text-slate-600 rounded-xl p-2.5 flex items-center justify-center gap-2 font-bold text-xs hover:border-jci-blue hover:text-jci-blue hover:bg-blue-50/20 transition">
                        <i class="fa-solid fa-building-ngo text-sm"></i>
                        <span>Organization</span>
                    </a>
                </div>

                <form action="{{ route('register.volunteer') }}" method="POST" class="space-y-4 relative">
                    @csrf
                    <input type="hidden" name="registration_token" value="{{ $registrationToken }}">
                    <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden opacity-0" aria-hidden="true">
                        <label for="volunteer-website">Website</label>
                        <input id="volunteer-website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                        <label for="volunteer-company-url">Company URL</label>
                        <input id="volunteer-company-url" type="url" name="company_url" value="" tabindex="-1" autocomplete="off">
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Full Name <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="fa-solid fa-user text-xs"></i>
                            </span>
                            <input type="text" name="name" value="{{ old('name') }}" required 
                                   class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none @error('name') border-rose-500 @enderror" 
                                   placeholder="Juan Dela Cruz">
                        </div>
                        @error('name')
                            <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email & Phone Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Email Address <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fa-solid fa-envelope text-xs"></i>
                                </span>
                                <input type="email" name="email" value="{{ old('email') }}" required 
                                       class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none @error('email') border-rose-500 @enderror" 
                                       placeholder="juan@domain.com">
                            </div>
                            @error('email')
                                <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Phone Number</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fa-solid fa-phone text-xs"></i>
                                </span>
                                <input type="text" name="phone" value="{{ old('phone') }}" 
                                       class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none" 
                                       placeholder="09123456789">
                            </div>
                            @error('phone')
                                <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Organization Affiliation -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Preferred Organization <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="fa-solid fa-building-ngo text-xs"></i>
                            </span>
                            <select name="organization_id" required
                                    class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none bg-white @error('organization_id') border-rose-500 @enderror">
                                <option value="" disabled {{ old('organization_id') ? '' : 'selected' }}>Choose an approved organization</option>
                                @forelse($organizations as $organization)
                                    <option value="{{ $organization->id }}" {{ (string) old('organization_id') === (string) $organization->id ? 'selected' : '' }}>
                                        {{ $organization->name }}
                                    </option>
                                @empty
                                    <option value="" disabled>No approved organizations available</option>
                                @endforelse
                            </select>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">You apply to join this organization. Membership starts after a personal interview and their approval.</p>
                        @error('organization_id')
                            <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Why do you want to join? <span class="font-normal normal-case tracking-normal text-slate-400">(optional)</span></label>
                        <textarea name="application_message" rows="3" maxlength="1000" placeholder="Tell the organization a little about yourself and why you want to join." class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none @error('application_message') border-rose-500 @enderror">{{ old('application_message') }}</textarea>
                        @error('application_message')
                            <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password & Password Confirmation -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Password <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fa-solid fa-lock text-xs"></i>
                                </span>
                                <input type="password" name="password" required 
                                       class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none @error('password') border-rose-500 @enderror" 
                                       placeholder="At least 8 characters">
                            </div>
                            @error('password')
                                <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Confirm Password <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fa-solid fa-lock text-xs"></i>
                                </span>
                                <input type="password" name="password_confirmation" required 
                                       class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none" 
                                       placeholder="Re-enter password">
                            </div>
                        </div>
                    </div>

                    <!-- Bio -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Bio / Statement of Intent</label>
                        <textarea name="bio" rows="2" 
                                  class="w-full border border-slate-200 rounded-xl p-3 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none" 
                                  placeholder="Tell organizations about your interest in civic activities...">{{ old('bio') }}</textarea>
                    </div>

                    <!-- Skills Dropdown -->
                    @if(isset($skills) && $skills->count() > 0)
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Initial Skills</label>
                        <details class="relative">
                            <summary class="list-none cursor-pointer w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-600 bg-white flex items-center justify-between gap-2 hover:border-jci-blue transition">
                                <span class="font-semibold">
                                    {{ is_array(old('skills')) && count(old('skills')) > 0 ? count(old('skills')) . ' skill(s) selected' : 'Choose skills' }}
                                </span>
                                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                            </summary>
                            <div class="mt-2 bg-slate-50 p-3 rounded-xl border border-slate-200/60 max-h-44 overflow-y-auto custom-scrollbar grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($skills as $skill)
                                    <label class="flex items-center gap-2 cursor-pointer bg-white p-2 rounded-lg border border-slate-200 text-xs hover:border-jci-blue transition">
                                        <input type="checkbox" name="skills[]" value="{{ $skill->id }}"
                                               class="rounded text-jci-blue focus:ring-jci-blue"
                                               {{ is_array(old('skills')) && in_array($skill->id, old('skills')) ? 'checked' : '' }}>
                                        <span class="font-medium text-slate-700 truncate text-[11px]">{{ $skill->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    </div>
                    @endif

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" class="w-full bg-jci-blue hover:bg-jci-dark text-white font-bold text-xs py-3.5 rounded-xl transition duration-300 flex items-center justify-center gap-2 shadow-lg shadow-sky-500/20">
                            <i class="fa-solid fa-envelope-circle-check"></i> Continue — verify email
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center text-xs text-slate-400">
                    Already registered? <a href="{{ route('login') }}" class="text-jci-blue font-bold hover:underline">Log in to your account</a>
                </div>

            </div>
        </div>
    </div>
</x-layout.app>
