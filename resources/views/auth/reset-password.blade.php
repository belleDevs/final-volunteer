<x-layout.app title="VolunteerHub - Reset Password">
    <div class="flex-grow flex items-center justify-center p-3 sm:p-6 md:p-12 min-h-[100dvh] bg-gradient-to-tr from-slate-900 via-jci-dark to-jci-blue py-4 sm:py-12">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden max-w-5xl w-full grid grid-cols-1 md:grid-cols-12 my-auto">

            <div class="hidden md:col-span-5 bg-gradient-to-b from-jci-blue to-jci-dark p-6 sm:p-8 md:p-12 text-white md:flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -top-12 -left-12 w-48 h-48 bg-white/5 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-20 -right-20 w-72 h-72 bg-jci-accent/10 rounded-full blur-3xl"></div>

                <div class="relative z-10">
                    <span class="bg-jci-accent text-jci-dark text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">Account recovery</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold mt-3 sm:mt-4 tracking-tight leading-tight">Choose a new password.</h2>
                    <p class="text-xs sm:text-sm text-slate-200 mt-2 sm:mt-3 leading-relaxed">Pick something strong and unique. You will use it the next time you sign in to VolunteerHub.</p>
                </div>

                <div class="mt-6 sm:mt-8 relative z-10 border-t border-white/10 pt-4 sm:pt-6">
                    <div class="flex items-center gap-3">
                        <x-ui.brand-mark class="shadow-none ring-1 ring-white/20" />
                        <div>
                            <h5 class="text-xs font-bold text-white">VolunteerHub</h5>
                            <p class="text-[10px] text-slate-300">Minimum 8 characters</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-7 p-5 sm:p-8 md:p-12 flex flex-col justify-center">
                <div class="mb-4 flex items-center gap-2.5 md:hidden">
                    <x-ui.brand-mark size="sm" />
                    <p class="text-sm font-extrabold text-slate-900">VolunteerHub</p>
                </div>
                <div class="mb-6">
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900">Set new password</h3>
                    <p class="text-sm text-slate-500 mt-1">Confirm your email and enter a new password below.</p>
                </div>

                <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Email Address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="fa-solid fa-envelope text-xs"></i>
                            </span>
                            <input type="email" name="email" value="{{ old('email', $email) }}" required
                                   class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none @error('email') border-rose-500 @enderror"
                                   placeholder="Enter your account email">
                        </div>
                        @error('email')
                            <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">New Password</label>
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
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Confirm Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="fa-solid fa-lock text-xs"></i>
                            </span>
                            <input type="password" name="password_confirmation" required
                                   class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none"
                                   placeholder="Repeat new password">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-jci-blue hover:bg-jci-dark text-white font-bold text-xs py-3 rounded-xl transition duration-300 flex items-center justify-center gap-2 shadow-lg shadow-sky-500/20">
                        <i class="fa-solid fa-key"></i> Update password
                    </button>
                </form>

                <p class="mt-6 text-center text-[11px] text-slate-400">
                    <a href="{{ route('login') }}" class="font-bold text-jci-blue hover:underline">Back to log in</a>
                </p>
            </div>
        </div>
    </div>
</x-layout.app>
