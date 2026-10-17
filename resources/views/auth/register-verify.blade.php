<x-layout.app title="VolunteerHub - Verify Email">
    <div class="flex-grow flex items-center justify-center p-3 sm:p-6 md:p-12 min-h-[100dvh] bg-gradient-to-tr from-slate-900 via-jci-dark to-jci-blue py-4 sm:py-12">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden max-w-5xl w-full grid grid-cols-1 md:grid-cols-12 my-auto">

            <div class="hidden md:col-span-5 bg-gradient-to-b from-jci-blue to-jci-dark p-6 sm:p-8 md:p-12 text-white md:flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -top-12 -left-12 w-48 h-48 bg-white/5 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-20 -right-20 w-72 h-72 bg-jci-accent/10 rounded-full blur-3xl"></div>

                <div class="relative z-10">
                    <span class="bg-jci-accent text-jci-dark text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">Email verification</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold mt-3 sm:mt-4 tracking-tight leading-tight">Confirm you own this inbox.</h2>
                    <p class="text-xs sm:text-sm text-slate-200 mt-2 sm:mt-3 leading-relaxed">A one-time code stops fake signups. Your {{ $type === 'organization' ? 'organization' : 'volunteer' }} account is created only after this step.</p>
                </div>

                <div class="mt-6 sm:mt-8 relative z-10 border-t border-white/10 pt-4 sm:pt-6">
                    <div class="flex items-center gap-3">
                        <x-ui.brand-mark class="shadow-none ring-1 ring-white/20" />
                        <div>
                            <h5 class="text-xs font-bold text-white">VolunteerHub</h5>
                            <p class="text-[10px] text-slate-300">Codes expire in 15 minutes</p>
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
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900">Enter verification code</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        We sent a 6-digit code to
                        <span class="font-bold text-slate-700">{{ $email }}</span>.
                    </p>
                </div>

                @if (session('status'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <form action="{{ route('register.verify.submit', $token) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Verification code</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="fa-solid fa-shield-halved text-xs"></i>
                            </span>
                            <input
                                type="text"
                                name="code"
                                value="{{ old('code') }}"
                                required
                                maxlength="6"
                                inputmode="numeric"
                                pattern="[0-9]{6}"
                                autocomplete="one-time-code"
                                autofocus
                                class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm font-bold tracking-[0.35em] focus:ring-1 focus:ring-jci-blue focus:outline-none @error('code') border-rose-500 @enderror"
                                placeholder="••••••"
                            >
                        </div>
                        @error('code')
                            <p class="text-[10px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full bg-jci-blue hover:bg-jci-dark text-white font-bold text-sm rounded-xl py-3 transition shadow-lg shadow-jci-blue/20">
                        Verify &amp; finish signup
                    </button>
                </form>

                <form action="{{ route('register.verify.resend', $token) }}" method="POST" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full text-xs font-bold text-jci-blue hover:underline">
                        Resend code
                    </button>
                </form>

                <p class="mt-6 text-center text-[11px] text-slate-400">
                    Wrong email?
                    <a href="{{ $type === 'organization' ? route('register.org') : route('register.volunteer') }}" class="font-bold text-jci-blue hover:underline">Start over</a>
                </p>
            </div>
        </div>
    </div>
</x-layout.app>
