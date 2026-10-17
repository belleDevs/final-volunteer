<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Mail\PasswordResetMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditTrail;
use App\Support\OrgNotifier;
use App\Support\PortalNotifier;
use App\Support\RegisteredMailbox;
use App\Support\RegistrationOtp;
use App\Support\VolunteerNotice;

class AuthController extends Controller
{
    /**
     * Redirect root to login or appropriate dashboard if authenticated.
     */
    public function index()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return redirect()->route('login');
    }

    /**
     * Show the login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Public entry for email CTAs: always reach login (or the volunteer page after sign-in).
     */
    public function continueFromEmail(Request $request)
    {
        $section = (string) $request->query('section', 'notifications');
        $allowed = ['dashboard', 'events', 'tasks', 'notifications', 'certificates', 'skills', 'messages', 'settings', 'availability'];
        if (!in_array($section, $allowed, true)) {
            $section = 'notifications';
        }

        $target = route('volunteer.dashboard', ['section' => $section === 'dashboard' ? null : $section]);

        if (Auth::check() && Auth::user()->role === 'volunteer' && Auth::user()->status === 'approved') {
            return redirect()->to($target);
        }

        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->guest($target);
    }

    /**
     * Handle the authentication request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Check if organization is approved
            if ($user->role === 'organization' && $user->status !== 'approved') {
                AuditTrail::record([
                    'action' => 'Login denied',
                    'action_type' => 'other',
                    'module' => 'Authentication',
                    'description' => 'Sign-in was denied because the organization account is '.$user->status.'.',
                    'status' => 'failed',
                    'actor' => $user,
                    'subject' => $user,
                    'subject_label' => $user->name,
                    'previous' => ['status' => $user->status],
                ]);
                Auth::logout();
                $msg = $user->status === 'pending'
                    ? 'Your organization account is undergoing compliance review.'
                    : 'Your organization account has been declined.';
                return back()->withErrors([
                    'email' => $msg,
                ])->withInput($request->only('email', 'remember'));
            }

            if ($user->role === 'volunteer' && $user->status !== 'approved') {
                Auth::logout();
                $msg = $user->status === 'pending'
                    ? 'Your volunteer account is undergoing compliance review.'
                    : 'Your volunteer account has been declined.';
                return back()->withErrors([
                    'email' => $msg,
                ])->withInput($request->only('email', 'remember'));
            }

            $request->session()->regenerate();

            AuditTrail::record([
                'action' => 'Logged in',
                'action_type' => 'other',
                'module' => 'Authentication',
                'description' => $user->name.' signed in.',
                'actor' => $user,
                'subject' => $user,
                'subject_label' => $user->name,
            ]);

            return redirect()->intended($this->dashboardPathFor($user));
        }

        $attempted = User::where('email', $credentials['email'])->first();
        AuditTrail::record([
            'action' => 'Login failed',
            'action_type' => 'other',
            'module' => 'Authentication',
            'description' => 'Sign-in failed for '.$credentials['email'].'.',
            'status' => 'failed',
            'actor' => $attempted,
            'actor_name' => $attempted->name ?? $credentials['email'],
            'actor_role' => $attempted->role ?? 'unknown',
            'user_id' => $attempted?->id,
            'subject_label' => $credentials['email'],
        ]);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email', 'remember'));
    }

    /**
     * Forgot-password opens as a modal on the login page.
     */
    public function showForgotPassword()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return redirect()->route('login')->with('forgot_password', true);
    }

    /**
     * Email a password reset link when the account exists.
     */
    public function sendPasswordResetLink(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('login')
                ->withErrors($validator, 'forgot')
                ->withInput($request->only('email'))
                ->with('forgot_password', true);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $user = User::where('email', $email)->first();

        // Always show the same success message to avoid account enumeration.
        $statusMessage = 'If that email is registered, we sent a password reset link. Check your inbox.';

        if (! $user) {
            return redirect()->route('login')
                ->with('status', $statusMessage)
                ->with('forgot_password', true)
                ->withInput($request->only('email'));
        }

        $token = Password::broker()->createToken($user);
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        if (! $this->sendPasswordResetEmail($user, $resetUrl)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'We could not send a reset email right now. Try again shortly.'], 'forgot')
                ->withInput($request->only('email'))
                ->with('forgot_password', true);
        }

        AuditTrail::record([
            'action' => 'Password reset requested',
            'action_type' => 'other',
            'module' => 'Authentication',
            'description' => $user->name.' requested a password reset link.',
            'actor' => $user,
            'subject' => $user,
            'subject_label' => $user->name,
        ]);

        return redirect()->route('login')
            ->with('status', $statusMessage)
            ->with('forgot_password', true)
            ->withInput($request->only('email'));
    }

    /**
     * Show the reset-password form from an emailed token.
     */
    public function showResetPassword(Request $request, string $token)
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /**
     * Persist a new password from a valid reset token.
     */
    public function resetPassword(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                AuditTrail::record([
                    'action' => 'Password reset completed',
                    'action_type' => 'updated',
                    'module' => 'Authentication',
                    'description' => $user->name.' set a new password via email reset.',
                    'actor' => $user,
                    'subject' => $user,
                    'subject_label' => $user->name,
                ]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return redirect()->route('login')
            ->with('status', 'Your password was updated. Sign in with your new password.');
    }

    private function sendPasswordResetEmail(User $user, string $resetUrl): bool
    {
        $mail = new PasswordResetMail($user->name ?: 'there', $resetUrl);

        try {
            if (VolunteerNotice::mailIsReady()) {
                Mail::to($user->email)->send($mail);

                return true;
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        try {
            RegisteredMailbox::send(
                $user->email,
                'Reset your VolunteerHub password',
                $mail->render(),
                (string) config('mail.from.address', 'noreply@volunteerhub.ph'),
                'VolunteerHub'
            );

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * Show volunteer registration form.
     */
    public function showVolunteerRegister()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $skills = \App\Models\Skill::whereIn('name', \App\Support\RoleCatalog::skillNames())
            ->orderBy('name')
            ->get();
        $organizations = \App\Models\User::where('role', 'organization')
            ->where('status', 'approved')
            ->orderBy('name')
            ->get();
        $startedAt = now()->timestamp;
        session(['registration_started_at.volunteer' => $startedAt]);
        $registrationToken = $this->makeRegistrationToken('volunteer', $startedAt);

        return view('auth.register-volunteer', compact('skills', 'organizations', 'registrationToken'));
    }

    /**
     * Handle volunteer registration request.
     */
    public function registerVolunteer(Request $request)
    {
        $this->guardRegistrationSpam($request, 'volunteer');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'organization_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'organization')->where('status', 'approved');
                }),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'application_message' => ['nullable', 'string', 'max:1000'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['exists:skills,id'],
            'website' => ['nullable', 'prohibited'],
            'company_url' => ['nullable', 'prohibited'],
            'registration_token' => ['required', 'string'],
        ]);

        \App\Models\User::findOrFail($data['organization_id']);

        $staged = RegistrationOtp::stage('volunteer', [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'organization_id' => (int) $data['organization_id'],
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null,
            'application_message' => $data['application_message'] ?? null,
            'skills' => $data['skills'] ?? [],
        ]);

        if (! RegistrationOtp::send($data['email'], $data['name'], $staged['code'], 'volunteer')) {
            RegistrationOtp::forget($staged['token']);
            throw ValidationException::withMessages([
                'email' => 'We could not send a verification code to that email. Check the address and try again.',
            ]);
        }

        session()->forget('registration_started_at.volunteer');

        return redirect()->route('register.verify', ['token' => $staged['token']])
            ->with('status', 'We sent a 6-digit verification code to '.$data['email'].'. Enter it to finish signup.');
    }

    /**
     * Show organization registration form.
     */
    public function showOrgRegister()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $startedAt = now()->timestamp;
        session(['registration_started_at.organization' => $startedAt]);
        $registrationToken = $this->makeRegistrationToken('organization', $startedAt);

        return view('auth.register-org', compact('registrationToken'));
    }

    /**
     * Handle organization registration request.
     */
    public function registerOrg(Request $request)
    {
        $this->guardRegistrationSpam($request, 'organization');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:20'],
            'bio' => ['required', 'string', 'max:1000'],
            'organization_type' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Models\User::ORGANIZATION_TYPES))],
            'website' => ['nullable', 'prohibited'],
            'company_url' => ['nullable', 'prohibited'],
            'registration_token' => ['required', 'string'],
        ]);

        $staged = RegistrationOtp::stage('organization', [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'],
            'bio' => $data['bio'],
            'organization_type' => $data['organization_type'],
        ]);

        if (! RegistrationOtp::send($data['email'], $data['name'], $staged['code'], 'organization')) {
            RegistrationOtp::forget($staged['token']);
            throw ValidationException::withMessages([
                'email' => 'We could not send a verification code to that email. Check the address and try again.',
            ]);
        }

        session()->forget('registration_started_at.organization');

        return redirect()->route('register.verify', ['token' => $staged['token']])
            ->with('status', 'We sent a 6-digit verification code to '.$data['email'].'. Enter it to finish signup.');
    }

    /**
     * Show the email OTP verification step after signup forms pass spam checks.
     */
    public function showRegisterVerify(string $token)
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $pending = RegistrationOtp::get($token);
        if (! $pending) {
            return redirect()->route('register.volunteer')
                ->withErrors(['email' => 'That verification link expired. Register again to get a new code.']);
        }

        $email = (string) ($pending['email'] ?? '');
        $type = (string) ($pending['type'] ?? 'volunteer');

        return view('auth.register-verify', compact('token', 'email', 'type'));
    }

    /**
     * Confirm the signup OTP and create the account.
     */
    public function verifyRegister(Request $request, string $token)
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $pending = RegistrationOtp::verify($token, (string) $request->input('code'));
        if (! $pending) {
            throw ValidationException::withMessages([
                'code' => 'Invalid or expired verification code. Request a new code and try again.',
            ]);
        }

        $type = (string) ($pending['type'] ?? '');
        $payload = is_array($pending['payload'] ?? null) ? $pending['payload'] : [];
        $email = strtolower((string) ($payload['email'] ?? ''));

        if ($email === '' || User::where('email', $email)->exists()) {
            RegistrationOtp::forget($token);
            throw ValidationException::withMessages([
                'code' => 'This email is already registered. Sign in instead.',
            ]);
        }

        if ($type === 'volunteer') {
            $response = $this->completeVolunteerRegistration($payload);
        } elseif ($type === 'organization') {
            $response = $this->completeOrgRegistration($payload);
        } else {
            RegistrationOtp::forget($token);
            throw ValidationException::withMessages([
                'code' => 'Invalid registration session. Please start again.',
            ]);
        }

        RegistrationOtp::forget($token);

        return $response;
    }

    /**
     * Resend the signup OTP (throttled by route middleware).
     */
    public function resendRegisterOtp(Request $request, string $token)
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $refreshed = RegistrationOtp::refreshCode($token);
        if (! $refreshed) {
            return redirect()->route('register.volunteer')
                ->withErrors(['email' => 'That verification session expired. Register again to get a new code.']);
        }

        $pending = RegistrationOtp::get($token);
        $payload = is_array($pending['payload'] ?? null) ? $pending['payload'] : [];
        $email = (string) ($payload['email'] ?? '');
        $name = (string) ($payload['name'] ?? 'there');
        $type = (string) ($pending['type'] ?? 'volunteer');

        if ($email === '' || ! RegistrationOtp::send($email, $name, $refreshed['code'], $type)) {
            throw ValidationException::withMessages([
                'code' => 'We could not resend the verification code. Try again shortly.',
            ]);
        }

        return redirect()->route('register.verify', ['token' => $token])
            ->with('status', 'A new verification code was sent to '.$email.'.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function completeVolunteerRegistration(array $data)
    {
        $organization = User::findOrFail((int) $data['organization_id']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'volunteer',
            'organization_id' => null,
            'status' => 'approved',
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null,
            'availability' => 'active',
            'email_verified_at' => now(),
        ]);

        if (! empty($data['skills'])) {
            $user->skills()->sync($data['skills']);
        }

        $application = \App\Models\OrganizationApplication::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'pending',
            'message' => $data['application_message'] ?? null,
            'interview_mode' => 'personal',
        ]);

        AuditTrail::record([
            'action' => 'Volunteer registered',
            'action_type' => 'created',
            'module' => 'Accounts',
            'description' => $user->name.' created a volunteer account and applied to join '.$organization->name.'.',
            'actor' => $user,
            'subject' => $user,
            'subject_label' => $user->name,
            'new' => ['role' => 'volunteer', 'status' => 'approved', 'application' => 'pending', 'email_verified' => true],
        ]);

        OrgNotifier::send(
            (int) $organization->id,
            'volunteer',
            'New membership applicant',
            $user->name.' applied to join '.$organization->name.'. Schedule a personal interview before approving membership.',
            $user->name,
            route('org.dashboard', ['section' => 'volunteers']),
            'Review applicant',
            true,
            'membership-applicant-'.$application->id
        );

        Auth::login($user);

        return redirect()->route('volunteer.dashboard')
            ->with('success', "Welcome, {$user->name}! Your email is verified. Membership with {$organization->name} starts after a personal interview and organization approval.");
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function completeOrgRegistration(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'organization',
            'status' => 'pending',
            'phone' => $data['phone'],
            'bio' => $data['bio'],
            'organization_type' => $data['organization_type'],
            'email_verified_at' => now(),
        ]);

        AuditTrail::record([
            'action' => 'Organization registered',
            'action_type' => 'created',
            'module' => 'Organizations',
            'description' => $user->name.' applied for an organization account.',
            'actor' => $user,
            'subject' => $user,
            'subject_label' => $user->name,
            'new' => ['status' => 'pending', 'organization_type' => $user->organization_type, 'email_verified' => true],
        ]);

        PortalNotifier::admins(
            'New organization application',
            $user->name.' applied and is waiting for review.',
            $user->name.' applied as a '.($user->organizationTypeLabel() ?? 'partner organization').'. Review the registration before the organization can post events.',
            'fa-building-circle-check',
            route('admin.dashboard', ['tab' => 'audits'])
        );

        return redirect()->route('login')
            ->with('status', "Email verified for '{$user->name}'. Your organization account is undergoing compliance review by the Admin.");
    }

    /**
     * Stop common registration spam without adding friction for real users.
     */
    private function guardRegistrationSpam(Request $request, string $type): void
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $name = trim((string) $request->input('name', ''));
        $ip = (string) $request->ip();
        $ipKey = "registration-ip:{$type}:{$ip}";
        $pairKey = "registration:{$type}:{$ip}:".($email !== '' ? $email : 'unknown');

        $block = function (string $reason, string $message) use ($request, $type, $ipKey, $pairKey): void {
            RateLimiter::hit($ipKey, 3600);
            RateLimiter::hit($pairKey, 600);

            AuditTrail::record([
                'action' => 'Registration blocked',
                'action_type' => 'other',
                'module' => 'Accounts',
                'description' => 'A '.$type.' registration was blocked ('.$reason.').',
                'status' => 'failed',
                'actor_name' => (string) $request->input('email', 'Unknown'),
                'actor_role' => 'unknown',
                'user_id' => null,
            ]);

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        };

        // Honeypots — real users never see or fill these.
        if ($request->filled('website') || $request->filled('company_url')) {
            $block('honeypot', 'We could not process this registration. Please try again.');
        }

        // Signed token from the GET form (survives session quirks better than session alone).
        $tokenStartedAt = $this->registrationTokenStartedAt((string) $request->input('registration_token', ''), $type);
        if ($tokenStartedAt === null) {
            $block('bad-token', 'This registration form expired. Refresh the page and try again.');
        }

        // Form must be opened first, filled at a human pace, and not replayed forever.
        $sessionStartedAt = (int) session("registration_started_at.{$type}", 0);
        $startedAt = $sessionStartedAt > 0 ? $sessionStartedAt : $tokenStartedAt;
        $elapsed = $startedAt > 0 ? now()->timestamp - $startedAt : 0;
        if ($startedAt === 0 || $elapsed < 4) {
            $block('too-fast', 'Please review the form carefully, then submit again.');
        }
        if ($elapsed > 7200) {
            session(["registration_started_at.{$type}" => now()->timestamp]);
            $block('expired', 'This registration form expired. Refresh the page and try again.');
        }

        // IP flood control (broader than email+IP).
        if (RateLimiter::tooManyAttempts($ipKey, 8)) {
            $seconds = RateLimiter::availableIn($ipKey);
            $block('ip-limit', "Too many registration attempts from this network. Please try again in {$seconds} seconds.");
        }

        // Same email + IP bursts.
        if (RateLimiter::tooManyAttempts($pairKey, 5)) {
            $seconds = RateLimiter::availableIn($pairKey);
            $block('pair-limit', "Too many registration attempts. Please try again in {$seconds} seconds.");
        }

        if ($email !== '' && $this->isDisposableEmail($email)) {
            $block('disposable-email', 'Please use a permanent email address for registration.');
        }

        if ($name !== '' && $this->looksLikeSpamName($name)) {
            $block('spam-name', 'Please enter a valid name for registration.');
        }

        RateLimiter::hit($ipKey, 3600);
        RateLimiter::hit($pairKey, 600);
    }

    private function isDisposableEmail(string $email): bool
    {
        $domain = substr(strrchr($email, '@') ?: '', 1);
        if ($domain === '') {
            return false;
        }

        $blocked = [
            'mailinator.com', 'guerrillamail.com', 'guerrillamail.net', 'sharklasers.com',
            'grr.la', 'yopmail.com', 'yopmail.fr', 'tempmail.com', 'temp-mail.org',
            '10minutemail.com', 'trashmail.com', 'trashmail.me', 'discard.email',
            'mailnesia.com', 'maildrop.cc', 'getnada.com', 'emailondeck.com',
            'fakeinbox.com', 'throwawaymail.com', 'tmpmail.org', 'tmpmail.net',
        ];

        return in_array($domain, $blocked, true);
    }

    private function looksLikeSpamName(string $name): bool
    {
        if (preg_match('/https?:\/\//i', $name) || preg_match('/www\./i', $name)) {
            return true;
        }

        $letters = preg_replace('/[^\p{L}]/u', '', $name) ?? '';
        if (mb_strlen($letters) < 2) {
            return true;
        }

        // Mostly digits / random tokens
        if (preg_match('/^\d{6,}$/', preg_replace('/\s+/', '', $name) ?? '')) {
            return true;
        }

        return false;
    }

    private function makeRegistrationToken(string $type, int $startedAt): string
    {
        $payload = $type.'|'.$startedAt;
        $signature = hash_hmac('sha256', $payload, (string) config('app.key'));

        return rtrim(strtr(base64_encode($payload.'|'.$signature), '+/', '-_'), '=');
    }

    private function registrationTokenStartedAt(string $token, string $type): ?int
    {
        if ($token === '') {
            return null;
        }

        $decoded = base64_decode(strtr($token, '-_', '+/'), true);
        if ($decoded === false) {
            return null;
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 3) {
            return null;
        }

        [$tokenType, $startedRaw, $signature] = $parts;
        if ($tokenType !== $type || !ctype_digit($startedRaw)) {
            return null;
        }

        $payload = $tokenType.'|'.$startedRaw;
        $expected = hash_hmac('sha256', $payload, (string) config('app.key'));
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        return (int) $startedRaw;
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            AuditTrail::record([
                'action' => 'Logged out',
                'action_type' => 'other',
                'module' => 'Authentication',
                'description' => $user->name.' signed out.',
                'actor' => $user,
                'subject' => $user,
                'subject_label' => $user->name,
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Helper to redirect based on user role.
     */
    private function redirectBasedOnRole($user)
    {
        return redirect()->to($this->dashboardPathFor($user));
    }

    private function dashboardPathFor($user): string
    {
        return match ($user->role) {
            'admin' => route('admin.dashboard'),
            'organization' => route('org.dashboard'),
            'volunteer' => route('volunteer.dashboard'),
            default => route('login'),
        };
    }

    /**
     * Show profile edit view.
     */
    public function showProfile()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $adminLogs = AuditLog::where('user_id', $user->id);

            return view('admin.profile', [
                'user' => $user,
                'actions' => (clone $adminLogs)->count(),
                'orgsVerified' => (clone $adminLogs)->where('action', 'Organization approved')->count(),
                'orgsDeclined' => (clone $adminLogs)->where('action', 'Organization rejected')->count(),
                'reports' => (clone $adminLogs)->where('module', 'Reports')->count(),
                'passwordChangedAt' => AuditLog::where('user_id', $user->id)->where('action', 'Password changed')->latest('created_at')->first()?->created_at,
                'recentActivity' => AuditLog::where('user_id', $user->id)->latest('created_at')->limit(8)->get(),
            ]);
        }

        if ($user->role === 'volunteer') {
            $user->load(['skills', 'primaryOrganization']);
            $recentAssignments = $user->assignments()
                ->with(['task', 'event'])
                ->latest()
                ->limit(5)
                ->get();
            $profileStats = [
                'hours' => (float) $user->assignments()->where('status', 'completed')->sum('hours_logged'),
                'completed' => $user->assignments()->where('status', 'completed')->count(),
                'certificates' => $user->certificates()->count(),
                'skills' => $user->skills->count(),
            ];
            $membershipApplication = $user->isMember() ? null : $user->pendingOrganizationApplication();

            return view('auth.profile-edit', compact('user', 'recentAssignments', 'profileStats', 'membershipApplication'));
        }

        return view('auth.profile-edit', compact('user'));
    }

    /**
     * Update user profile information.
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($request->input('section') === 'password') {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user->password = $request->password;
            $user->save();

            AuditTrail::record([
                'action' => 'Password changed',
                'action_type' => 'updated',
                'module' => 'Authentication',
                'description' => $user->name.' changed their password.',
                'subject' => $user,
                'subject_label' => $user->name,
            ]);

            return redirect()->to($user->role === 'volunteer'
                    ? route('volunteer.dashboard', ['section' => 'settings'])
                    : route('profile.show'))
                ->with('success', 'Your password has been updated.');
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        if ($user->role === 'volunteer') {
            $rules['interests'] = ['nullable', 'string', 'max:1000'];
            $rules['volunteer_experience'] = ['nullable', 'string', 'max:2000'];
        }

        if ($user->role === 'organization') {
            $rules['organization_type'] = ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Models\User::ORGANIZATION_TYPES))];
            $rules['event_reward'] = ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Models\User::EVENT_REWARDS))];
            $rules['task_reward'] = ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Models\User::TASK_REWARDS))];
        }

        $data = $request->validate($rules);
        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'organization_type' => $user->organization_type,
            'event_reward' => $user->event_reward,
            'task_reward' => $user->task_reward,
        ];

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        if ($request->exists('bio')) {
            $user->bio = $data['bio'] ?? null;
        }
        if ($user->role === 'volunteer') {
            $user->interests = $data['interests'] ?? null;
            $user->volunteer_experience = $data['volunteer_experience'] ?? null;
        }

        if ($user->role === 'organization') {
            $user->organization_type = $data['organization_type'] ?? null;
            $user->event_reward = $data['event_reward'] ?? null;
            $user->task_reward = $data['task_reward'] ?? null;
        }

        if ($request->filled('password')) {
            $user->password = $request->password;
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $request->file('profile_photo')
                ->store('profile-photos/' . $user->id, 'public');
        }

        $user->save();

        $after = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'organization_type' => $user->organization_type,
            'event_reward' => $user->event_reward,
            'task_reward' => $user->task_reward,
        ];

        if ($before !== $after || $request->hasFile('profile_photo')) {
            if ($request->hasFile('profile_photo')) {
                $after['profile_photo'] = 'updated';
            }
            AuditTrail::record([
                'action' => 'Profile updated',
                'action_type' => 'updated',
                'module' => 'Accounts',
                'description' => $user->name.' updated their profile.',
                'subject' => $user,
                'subject_label' => $user->name,
                'previous' => $before,
                'new' => $after,
            ]);
        }

        if ($request->filled('password')) {
            AuditTrail::record([
                'action' => 'Password changed',
                'action_type' => 'updated',
                'module' => 'Authentication',
                'description' => $user->name.' changed their password.',
                'subject' => $user,
                'subject_label' => $user->name,
            ]);
        }

        return redirect()->route('profile.show')
            ->with('success', 'Profile updated.');
    }

    /**
     * Mark a notification as read.
     */
    public function markNotificationRead($id)
    {
            $notification = DB::table('notifications')
            ->where('notifiable_id', Auth::id())
            ->where('notifiable_type', User::class)
            ->where('id', $id)
            ->first();

        if ($notification) {
            DB::table('notifications')
                ->where('id', $id)
                ->where('notifiable_id', Auth::id())
                ->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }
}
