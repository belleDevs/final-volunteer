<?php

namespace App\Mail\Transport;

use Illuminate\Http\Client\Factory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GmailApiTransport extends AbstractTransport
{
    private ?string $accessToken = null;

    private int $accessTokenExpiresAt = 0;

    public function __construct(
        private Factory $http,
        private string $clientId,
        private string $clientSecret,
        private string $refreshToken,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($dispatcher, $logger);
    }

    protected function doSend(SentMessage $message): void
    {
        $rawMessage = rtrim(strtr(base64_encode($message->getOriginalMessage()->toString()), '+/', '-_'), '=');

        $this->http
            ->withToken($this->accessToken())
            ->acceptJson()
            ->timeout(10)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $rawMessage,
            ])
            ->throw();
    }

    public function __toString(): string
    {
        return 'gmail+api';
    }

    private function accessToken(): string
    {
        if ($this->accessToken !== null && $this->accessTokenExpiresAt > time() + 30) {
            return $this->accessToken;
        }

        $response = $this->http
            ->asForm()
            ->timeout(10)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
            ])
            ->throw();

        $accessToken = $response->json('access_token');
        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Google OAuth did not return an access token.');
        }

        $expiresIn = $response->json('expires_in');
        $this->accessToken = $accessToken;
        $this->accessTokenExpiresAt = time() + (is_numeric($expiresIn) ? (int) $expiresIn : 3600);

        return $this->accessToken;
    }
}
