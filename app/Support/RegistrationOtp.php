<?php

namespace App\Support;

use App\Mail\RegistrationOtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegistrationOtp
{
    public const TTL_SECONDS = 900;

    public static function cacheKey(string $token): string
    {
        return 'registration-otp:'.$token;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{token: string, code: string}
     */
    public static function stage(string $type, array $payload): array
    {
        $token = (string) Str::uuid();
        $code = (string) random_int(100000, 999999);

        Cache::put(self::cacheKey($token), [
            'type' => $type,
            'payload' => $payload,
            'code_hash' => hash('sha256', $code),
            'attempts' => 0,
            'email' => strtolower((string) ($payload['email'] ?? '')),
            'created_at' => now()->timestamp,
        ], self::TTL_SECONDS);

        return ['token' => $token, 'code' => $code];
    }

    public static function get(string $token): ?array
    {
        $data = Cache::get(self::cacheKey($token));

        return is_array($data) ? $data : null;
    }

    public static function forget(string $token): void
    {
        Cache::forget(self::cacheKey($token));
    }

    public static function verify(string $token, string $code): array|false
    {
        $data = self::get($token);
        if (! $data) {
            return false;
        }

        $attempts = (int) ($data['attempts'] ?? 0);
        if ($attempts >= 8) {
            self::forget($token);

            return false;
        }

        $data['attempts'] = $attempts + 1;
        Cache::put(self::cacheKey($token), $data, self::TTL_SECONDS);

        if (! hash_equals((string) $data['code_hash'], hash('sha256', trim($code)))) {
            return false;
        }

        return $data;
    }

    /**
     * @return array{token: string, code: string}|null
     */
    public static function refreshCode(string $token): ?array
    {
        $data = self::get($token);
        if (! $data) {
            return null;
        }

        $code = (string) random_int(100000, 999999);
        $data['code_hash'] = hash('sha256', $code);
        $data['attempts'] = 0;
        Cache::put(self::cacheKey($token), $data, self::TTL_SECONDS);

        return ['token' => $token, 'code' => $code];
    }

    public static function send(string $email, string $name, string $code, string $type): bool
    {
        $mail = new RegistrationOtpMail($name, $code, $type);

        try {
            if (VolunteerNotice::mailIsReady()) {
                Mail::to($email)->send($mail);

                return true;
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        try {
            RegisteredMailbox::send(
                $email,
                'Your VolunteerHub verification code',
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
}
