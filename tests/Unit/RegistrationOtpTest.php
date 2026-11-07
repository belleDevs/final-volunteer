<?php

namespace Tests\Unit;

use App\Mail\RegistrationOtpMail;
use App\Support\RegistrationOtp;
use App\Support\VolunteerNotice;
use Illuminate\Mail\Transport\ResendTransport;
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
}
