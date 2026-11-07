<?php

namespace Tests\Unit;

use App\Mail\RegistrationOtpMail;
use App\Support\RegistrationOtp;
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
}
