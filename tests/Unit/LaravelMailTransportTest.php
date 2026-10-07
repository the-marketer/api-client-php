<?php

declare(strict_types=1);

namespace Tests\Unit;

use NotificationService\Sdk\Internal\TransactionalsApi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;
use TheMarketer\ApiClient\Client;
use TheMarketer\ApiClient\Laravel\Mail\TheMarketerTransport;

final class LaravelMailTransportTest extends TestCase
{
    public function testSendsSingleEmailThroughTransactionalsApi(): void
    {
        $api = $this->createMock(TransactionalsApi::class);
        $api
            ->expects($this->once())
            ->method('sendEmail')
            ->with($this->callback(static function (array $payload): bool {
                return $payload['to'] === 'user@example.com'
                    && $payload['subject'] === 'Welcome'
                    && $payload['body'] === '<p>Hello</p>'
                    && $payload['from'] === 'from@example.com'
                    && $payload['reply_to'] === 'reply@example.com';
            }));

        $client = $this->createStub(Client::class);
        $client->method('transactionals')->willReturn($api);

        $transport = new TheMarketerTransport($client);
        $email = (new Email())
            ->to('user@example.com')
            ->from('from@example.com')
            ->replyTo('reply@example.com')
            ->subject('Welcome')
            ->html('<p>Hello</p>');

        $transport->send($email);
    }

    public function testSendsBulkWhenMessageHasMultipleRecipients(): void
    {
        $api = $this->createMock(TransactionalsApi::class);
        $api
            ->expects($this->once())
            ->method('sendEmailsBulk')
            ->with($this->callback(static function (array $payload): bool {
                if (!isset($payload['emails']) || !is_array($payload['emails']) || count($payload['emails']) !== 2) {
                    return false;
                }

                $first = $payload['emails'][0];
                $second = $payload['emails'][1];

                return $first['to'] === 'one@example.com'
                    && $second['to'] === 'two@example.com'
                    && $first['subject'] === 'Notice'
                    && $second['subject'] === 'Notice';
            }));

        $client = $this->createStub(Client::class);
        $client->method('transactionals')->willReturn($api);

        $transport = new TheMarketerTransport($client);
        $email = (new Email())
            ->from('sender@example.com')
            ->to('one@example.com')
            ->cc('two@example.com')
            ->subject('Notice')
            ->text('Body text');

        $transport->send($email);
    }

    public function testTransportStringIdentifierIsThemarketer(): void
    {
        $client = $this->createStub(Client::class);
        $transport = new TheMarketerTransport($client);
        $this->assertSame('themarketer', (string) $transport);
    }
}
