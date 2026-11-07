<?php

namespace Tests\Unit;

use App\Mail\RegistrationOtpMail;
use App\Support\RegistrationOtp;
use App\Support\VolunteerNotice;
use Illuminate\Http\Client\Request;
use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationOtpTest extends TestCase
{
    public function test_it_fails_fast_when_outgoing_mail_is_not_configured(): void
    {
        config(['mail.default' => 'array']);

        $this->assertFalse(RegistrationOtp::send('person@example.com', 'Person', '123456', 'volunteer'));
    }

    public function test_it_sends_the_code_through_the_configured_mailer(): void
    {
        Mail::fake();
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.com',
            'mail.mailers.smtp.username' => 'mailer',
            'mail.mailers.smtp.password' => 'secret',
        ]);

        $sent = RegistrationOtp::send('person@example.com', 'Person', '123456', 'volunteer');

        $this->assertTrue($sent);
        Mail::assertSent(RegistrationOtpMail::class, function (RegistrationOtpMail $mail): bool {
            return $mail->hasTo('person@example.com')
                && $mail->code === '123456';
        });
    }

    public function test_resend_is_ready_only_when_its_api_key_is_configured(): void
    {
        config([
            'mail.default' => 'resend',
            'services.resend.key' => null,
        ]);
        $this->assertFalse(VolunteerNotice::mailIsReady());

        config(['services.resend.key' => 'test-api-key']);
        $this->assertTrue(VolunteerNotice::mailIsReady());
    }

    public function test_registration_otp_uses_resend_without_user_mail_credentials(): void
    {
        Mail::fake();
        config([
            'mail.default' => 'resend',
            'services.resend.key' => 'test-api-key',
        ]);

        $sent = RegistrationOtp::send('person@example.com', 'Person', '123456', 'organization');

        $this->assertTrue($sent);
        Mail::assertSent(RegistrationOtpMail::class, function (RegistrationOtpMail $mail): bool {
            return $mail->hasTo('person@example.com')
                && $mail->accountType === 'organization';
        });
    }

    public function test_resend_transport_can_be_created_with_the_site_api_key(): void
    {
        config([
            'mail.default' => 'resend',
            'services.resend.key' => 'test-api-key',
        ]);

        $transport = Mail::mailer('resend')->getSymfonyTransport();

        $this->assertInstanceOf(ResendTransport::class, $transport);
    }

    public function test_gmail_api_is_ready_only_when_oauth_credentials_are_configured(): void
    {
        config([
            'mail.default' => 'gmail_api',
            'services.google.gmail_client_id' => null,
            'services.google.gmail_client_secret' => null,
            'services.google.gmail_refresh_token' => null,
        ]);
        $this->assertFalse(VolunteerNotice::mailIsReady());

        config([
            'services.google.gmail_client_id' => 'client-id',
            'services.google.gmail_client_secret' => 'client-secret',
            'services.google.gmail_refresh_token' => 'refresh-token',
        ]);
        $this->assertTrue(VolunteerNotice::mailIsReady());
    }

    public function test_registration_otp_is_sent_through_the_gmail_api(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response([
                'id' => 'test-message-id',
            ]),
        ]);
        config([
            'mail.default' => 'gmail_api',
            'services.google.gmail_client_id' => 'client-id',
            'services.google.gmail_client_secret' => 'client-secret',
            'services.google.gmail_refresh_token' => 'refresh-token',
            'mail.from.address' => 'volunteerhub.support1@gmail.com',
            'mail.from.name' => 'VolunteerHub',
        ]);

        $sent = RegistrationOtp::send('person@example.com', 'Person', '123456', 'volunteer');

        $this->assertTrue($sent);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://oauth2.googleapis.com/token'
                && $request->data()['client_id'] === 'client-id'
                && $request->data()['refresh_token'] === 'refresh-token';
        });
        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send') {
                return false;
            }

            $raw = $request->data()['raw'] ?? '';
            $padding = str_repeat('=', (4 - strlen($raw) % 4) % 4);
            $message = base64_decode(strtr($raw, '-_', '+/').$padding);

            return $request->hasHeader('Authorization', 'Bearer test-access-token')
                && str_contains($message, 'person@example.com')
                && str_contains($message, '123456');
        });
    }
}
