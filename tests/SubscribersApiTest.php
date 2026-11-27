<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use JsonException;
use NotificationService\Sdk\Internal\SubscribersApi;
use TheMarketer\ApiClient\Common\Config;
use TheMarketer\ApiClient\DTO\Subscribers\SubscriberValidator;
use TheMarketer\ApiClient\Exception\ValidationException;

final class SubscribersApiTest extends TestCase
{
    /**
     * @return array{0: SubscribersApi, 1: \stdClass}
     */
    private function apiWithMockResponses(Response ...$responses): array
    {
        return $this->createApiWithMock(SubscribersApi::class, ...$responses);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testStatusSubscriberSendsGetWithEmailAndAuthQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"status":"active"}'),
        );

        $result = $api->statusSubscriber('user@example.com');

        $this->assertSame(['status' => 'active'], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/status_subscriber', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);
        $this->assertSame('user@example.com', $query['email']);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testUnsubscribedEmailsSendsGetWithDateRangeAndAuthQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '["a@x.com"]'),
        );

        $result = $api->unsubscribedEmails('2024-01-01', '2024-01-31');

        $this->assertSame(['a@x.com'], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/unsubscribed_emails', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);
        $this->assertSame('2024-01-01', $query['date_from']);
        $this->assertSame('2024-01-31', $query['date_to']);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testAddSubscriberSendsPostJsonWithEmailAndOptionalFields(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $result = $api->addSubscriber([
            'email' => 'user@example.com',
            'add_tags' => 'tag1,tag2',
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'phone' => '+40123456789',
            'city' => 'Bucharest',
            'country' => 'RO',
            'birthday' => '1990-05-01',
            'channels' => 'email,sms',
            'attributes' => ['foo' => 'bar'],
        ]);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/add_subscriber_sync', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([
            'email' => 'user@example.com',
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'add_tags' => 'tag1,tag2',
            'phone' => '+40123456789',
            'city' => 'Bucharest',
            'country' => 'RO',
            'birthday' => '1990-05-01',
            'channels' => 'email,sms',
            'attributes' => ['foo' => 'bar'],
        ], $body);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testAddSubscriberSendsPostJsonWithEmailOnlyWhenOptionalsOmitted(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $api->addSubscriber(['email' => 'only@example.com']);

        $request = $this->lastRequest($container);
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['email' => 'only@example.com'], $body);
    }

    public function testAddSubscriberByPhoneSendsPostJson(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->addSubscriberByPhone('+40700000000', 'Ion', 'Pop');

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/add_subscriber_by_phone', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([
            'phone' => '+40700000000',
            'firstname' => 'Ion',
            'lastname' => 'Pop',
        ], $body);
    }

    public function testAddSubscriberBulkSendsJsonArrayBody(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"imported":2}'),
        );

        $result = $api->addSubscriberBulk([
            ['email' => 'a@x.com', 'firstname' => 'A'],
            ['email' => 'b@x.com', 'lastname' => 'B'],
        ]);

        $this->assertSame(['imported' => 2], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/add_subscriber_bulk', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([
            ['email' => 'a@x.com', 'firstname' => 'A'],
            ['email' => 'b@x.com', 'lastname' => 'B'],
        ], $body);
    }

    public function testAddSubscriberBulkThrowsWhenEmpty(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->addSubscriberBulk([]);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testAddSubscriberBulkThrowsWhenRowIsNotArray(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(\TheMarketer\ApiClient\Exception\ValidationException::class);

        /** @noinspection PhpParamsInspection */
        /** @phpstan-ignore-next-line */
        $api->addSubscriberBulk(['not-an-array']);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testAddSubscriberBulkThrowsWhenAttributesIsNotArray(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(\TheMarketer\ApiClient\Exception\ValidationException::class);

        $api->addSubscriberBulk([
            ['email' => 'a@x.com', 'attributes' => 'not-array'],
        ]);
    }

    public function testRemoveSubscriberSendsPostJsonBodyWithEmailAndOptionalChannels(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"removed":true}'),
        );

        $result = $api->removeSubscriber('user@example.com', 'email');

        $this->assertSame(['removed' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/remove_subscriber', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['email' => 'user@example.com', 'channels' => 'email'], $body);
    }

    public function testAnonymizeEmailSendsPostJsonBody(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":1}'),
        );

        $api->anonymizeEmail('old@example.com');

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/anonymize-email', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['email' => 'old@example.com'], $body);
    }

    public function testUpdateTagsSendsPostJsonBody(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"tags":[]}'),
        );

        $api->updateTags('u@x.com', [1, 'news'], ['old'], 1);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/update-tags', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([
            'email' => 'u@x.com',
            'overwrite_existing' => 1,
            'add_tags' => [1, 'news'],
            'remove_tags' => ['old'],
        ], $body);
    }

    public function testThrowsWhenDomainKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config('', self::MOCK_API_KEY, self::MOCK_BASE_URL);
        $api = new SubscribersApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Customer ID not provided.');

        $api->statusSubscriber('a@b.com');
    }

    public function testThrowsWhenEmailEmptyForStatusSubscriber(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->statusSubscriber('   ');
    }

    public function testThrowsWhenApiKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config(self::MOCK_DOMAIN, '', self::MOCK_BASE_URL);
        $api = new SubscribersApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rest key not provided.');

        $api->statusSubscriber('a@b.com');
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testListUnsubscribedSendsGetWithoutDateParamsWhenBothNull(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '[]'),
        );

        $result = $api->listUnsubscribed();

        $this->assertSame([], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/unsubscribed_emails', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);
        $this->assertArrayNotHasKey('date_from', $query);
        $this->assertArrayNotHasKey('date_to', $query);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testListUnsubscribedSendsGetWithDateRange(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '[]'),
        );

        $api->listUnsubscribed('2025-01-01', '2025-01-31');

        $request = $this->lastRequest($container);
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame('2025-01-01', $query['date_from']);
        $this->assertSame('2025-01-31', $query['date_to']);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testListSubscribedSendsGetWithOptionalQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"items":[]}'),
        );

        $result = $api->listSubscribed('2024-06-01', null);

        $this->assertSame(['items' => []], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/subscribed_emails', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame('2024-06-01', $query['date_from']);
        $this->assertArrayNotHasKey('date_to', $query);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testSubscribersEvolutionSendsGet(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"series":[]}'),
        );

        $result = $api->subscribersEvolution();

        $this->assertSame(['series' => []], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/subscribers-evolution', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testAddSubscriberSyncSendsPostToAddSubscriberSyncPath(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"synced":true}'),
        );

        $result = $api->addSubscriber([
            'email' => 'sync@example.com',
            'firstname' => 'S',
            'lastname' => 'Y',
        ]);

        $this->assertSame(['synced' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/add_subscriber_sync', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([
            'email' => 'sync@example.com',
            'firstname' => 'S',
            'lastname' => 'Y',
        ], $body);
    }

    public function testDeleteSubscriberSendsPostJsonWithEmailOnly(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"deleted":1}'),
        );

        $result = $api->deleteSubscriber(['email' => 'gone@example.com']);

        $this->assertSame(['deleted' => 1], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/delete_subscriber', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['email' => 'gone@example.com'], $body);
    }

    public function testDeleteSubscriberSendsPostJsonWithPhoneOnly(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->deleteSubscriber(['phone' => ' +40 700 000 000 ']);

        $request = $this->lastRequest($container);
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['phone' => ' +40 700 000 000 '], $body);
    }

    public function testDeleteSubscriberSendsPostJsonWithEmailAndPhone(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->deleteSubscriber([
            'email' => 'x@y.com',
            'phone' => '+40111222333',
        ]);

        $request = $this->lastRequest($container);
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([
            'email' => 'x@y.com',
            'phone' => '+40111222333',
        ], $body);
    }

    public function testDeleteSubscriberThrowsWhenNeitherEmailNorPhoneProvided(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->deleteSubscriber([]);
    }

    public function testAddSubscriberByPhoneSendsPostJsonWithPhoneOnly(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->addSubscriberByPhone('+40700000000');

        $request = $this->lastRequest($container);
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['phone' => '+40700000000'], $body);
    }

    public function testRemoveSubscriberOmitsChannelsFromBodyWhenNull(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->removeSubscriber('only@example.com');

        $request = $this->lastRequest($container);
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['email' => 'only@example.com'], $body);
    }

    public function testUpdateTagsSendsPostWithEmailOnlyWhenTagListsEmpty(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->updateTags('plain@example.com');

        $request = $this->lastRequest($container);
        $this->assertStringEndsWith('/update-tags', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['email' => 'plain@example.com'], $body);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testAddDelegatesToAddSubscriber(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $result = $api->addSubscriberAsync(['email' => 'alias@example.com']);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/add_subscriber', $request->getUri()->getPath());
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['email' => 'alias@example.com'], $body);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: array<string, mixed>}>
     */
    public static function newSubscriberFields(): iterable
    {
        yield 'county' => [['county' => 'Cluj'], ['county' => 'Cluj']];
        yield 'remove_tags' => [['remove_tags' => 'lead,trial'], ['remove_tags' => 'lead,trial']];
        yield 'no_double_optin as "1"' => [['no_double_optin' => '1'], ['no_double_optin' => 1]];
        yield 'no_double_optin as true' => [['no_double_optin' => true], ['no_double_optin' => 1]];
        yield 'pretend_double_opt_in_is as "0" is kept' => [['pretend_double_opt_in_is' => '0'], ['pretend_double_opt_in_is' => 0]];
        yield 'pretend_double_opt_in_is as false is kept' => [['pretend_double_opt_in_is' => false], ['pretend_double_opt_in_is' => 0]];
        yield 'pretend_double_opt_in_is as 1' => [['pretend_double_opt_in_is' => 1], ['pretend_double_opt_in_is' => 1]];
    }

    /**
     * @param array<string, mixed> $field
     * @param array<string, mixed> $sent
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('newSubscriberFields')]
    public function testNewSubscriberFieldsReachEveryAddSubscriberPath(array $field, array $sent): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"result":true}'),
            new Response(200, [], '{"result":true}'),
            new Response(200, [], '{"result":true}'),
        );

        $api->addSubscriber(['email' => 'a@x.com'] + $field);
        $api->addSubscriberAsync(['email' => 'a@x.com'] + $field);
        $api->addSubscriberBulk([['email' => 'a@x.com'] + $field, ['email' => 'b@x.com']]);

        [$sync, $async, $bulk] = array_map(
            static fn($request): array => json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR),
            $container->requests,
        );

        $this->assertSame(['email' => 'a@x.com'] + $sent, $sync);
        $this->assertSame(['email' => 'a@x.com'] + $sent, $async);
        $this->assertSame([['email' => 'a@x.com'] + $sent, ['email' => 'b@x.com']], $bulk);
    }

    public function testSubscriberValidatorPayloadHasTheNewFields(): void
    {
        $payload = SubscriberValidator::validateAndCreate([
            'email' => 'a@x.com',
            'city' => 'Cluj-Napoca',
            'county' => 'Cluj',
            'add_tags' => 'vip',
            'remove_tags' => 'lead',
            'no_double_optin' => 1,
            'pretend_double_opt_in_is' => 0,
        ])->toApiPayload();

        $this->assertSame('Cluj', $payload['county']);
        $this->assertSame('lead', $payload['remove_tags']);
        $this->assertSame(1, $payload['no_double_optin']);
        $this->assertSame(0, $payload['pretend_double_opt_in_is']);
    }

    public function testAddSubscriberOmitsEmptyNewFields(): void
    {
        $payload = SubscriberValidator::validateAndCreate([
            'email' => 'a@x.com',
            'county' => '',
            'remove_tags' => null,
            'no_double_optin' => '',
        ])->toApiPayload();

        $this->assertSame(['email' => 'a@x.com'], $payload);
    }

    public function testAddSubscriberRejectsDoubleOptInFlagOutsideZeroOrOne(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('no_double_optin must be 0 or 1.');

        $api->addSubscriber(['email' => 'a@x.com', 'no_double_optin' => 2]);
    }

    public function testAddSubscriberBulkRejectsUnknownRowKey(): void
    {
        [$api, $container] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('unknown field(s): province');

        try {
            $api->addSubscriberBulk([['email' => 'a@x.com', 'province' => 'Cluj']]);
        } finally {
            $this->assertSame([], $container->requests);
        }
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testSourceConnectorIsSentInTheBodyOfAddSubscriberAndAddSubscriberAsync(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"result":true}'),
            new Response(200, [], '{"result":true}'),
        );

        $api->addSubscriber(['email' => 'a@x.com', 'source' => 'connector']);
        $api->addSubscriberAsync(['email' => 'a@x.com', 'source' => 'connector']);

        foreach ($container->requests as $request) {
            $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame(['email' => 'a@x.com', 'source' => 'connector'], $body);
        }
        $this->assertStringEndsWith('/add_subscriber_sync', $container->requests[0]->getUri()->getPath());
        $this->assertStringEndsWith('/add_subscriber', $container->requests[1]->getUri()->getPath());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testAddSubscriberBulkSendsSourceOnceInTheQueryString(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '{"result":true,"queued":2}'));

        $api->addSubscriberBulk([['email' => 'a@x.com'], ['email' => 'b@x.com']], 'connector');

        $request = $this->lastRequest($container);
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame('connector', $query['source']);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([['email' => 'a@x.com'], ['email' => 'b@x.com']], $body);
    }

    public function testAddSubscriberBulkWithoutSourceSendsNoSourceQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '{"result":true}'));

        $api->addSubscriberBulk([['email' => 'a@x.com']]);

        parse_str($this->lastRequest($container)->getUri()->getQuery(), $query);
        $this->assertArrayNotHasKey('source', $query);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testRemoveSubscriberSendsSourceInTheBody(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '{"result":true}'));

        $api->removeSubscriber('a@x.com', 'email', 'connector');

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['email' => 'a@x.com', 'channels' => 'email', 'source' => 'connector'], $body);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testRemoveSubscriberSourceWithoutChannels(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '{"result":true}'));

        $api->removeSubscriber('a@x.com', source: 'connector');

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['email' => 'a@x.com', 'source' => 'connector'], $body);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testContactsSendsGetWithPageAndSearch(): void
    {
        $answer = ['data' => [['email' => 'a@x.com']], 'total' => 1, 'current_page' => 2, 'per_page' => 15];
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], json_encode($answer)));

        $this->assertSame($answer, $api->contacts(2, '  ana  '));

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/api/v1/contacts', $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(['k' => self::MOCK_API_KEY, 'u' => self::MOCK_DOMAIN, 'page' => '2', 'search' => 'ana'], $query);
        $this->assertSame('', (string) $request->getBody());
    }

    public function testContactsDefaultsToPageOneWithoutSearch(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '{"data":[]}'));

        $api->contacts(search: '   ');

        parse_str($this->lastRequest($container)->getUri()->getQuery(), $query);
        $this->assertSame('1', $query['page']);
        $this->assertArrayNotHasKey('search', $query);
    }

    public function testContactsRejectsPageBelowOne(): void
    {
        [$api, $container] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('page must be 1 or more.');

        $api->contacts(0);
    }

    public function testContactsRejectsSearchLongerThan200Characters(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('search must be at most 200 characters.');

        $api->contacts(1, str_repeat('ă', 201));
    }

    public function testContactsAcceptsSearchOf200MultibyteCharacters(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '{"data":[]}'));

        $api->contacts(1, str_repeat('ă', 200));

        parse_str($this->lastRequest($container)->getUri()->getQuery(), $query);
        $this->assertSame(str_repeat('ă', 200), $query['search']);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testAudienceStatisticsSendsGet(): void
    {
        $answer = ['subscribed' => 10, 'active' => 7, 'inactive' => 3, 'bounced' => 1, 'cleaned' => 0, 'manually_unsubscribed' => 2];
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], json_encode($answer)));

        $this->assertSame($answer, $api->audienceStatistics());

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/api/v1/audience-statistics', $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(['k' => self::MOCK_API_KEY, 'u' => self::MOCK_DOMAIN], $query);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testSmsConsentPostsTheFullContract(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '{"result":true,"outcome":"subscribed"}'));

        $result = $api->smsConsent(
            '+40700000001',
            'subscribed',
            'ana@example.com',
            'Ana',
            'Pop',
            '2026-11-27T10:15:00Z',
            'connector',
        );

        $this->assertSame(['result' => true, 'outcome' => 'subscribed'], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/api/v1/sms_consent', $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(['k' => self::MOCK_API_KEY, 'u' => self::MOCK_DOMAIN], $query);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([
            'phone' => '+40700000001',
            'state' => 'subscribed',
            'email' => 'ana@example.com',
            'firstname' => 'Ana',
            'lastname' => 'Pop',
            'consent_updated_at' => '2026-11-27T10:15:00Z',
            'source' => 'connector',
        ], $body);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function testSmsConsentSendsOnlyPhoneAndStateWhenNothingElseIsKnown(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '{"result":true,"outcome":"missing"}'));

        $this->assertSame('missing', $api->smsConsent(' +40700000001 ', 'unsubscribed', '', '  ')['outcome']);

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['phone' => '+40700000001', 'state' => 'unsubscribed'], $body);
    }

    public function testSmsConsentFormatsADateTime(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '{"result":true,"outcome":"stale"}'));

        $api->smsConsent('+40700000001', 'subscribed', consentUpdatedAt: new \DateTimeImmutable('2026-11-27 12:15:00', new \DateTimeZone('Europe/Bucharest')));

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('2026-11-27T12:15:00.000+02:00', $body['consent_updated_at']);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidSmsConsents(): iterable
    {
        yield 'blank phone' => [['phone' => '  ', 'state' => 'subscribed'], 'phone'];
        yield 'phone too long' => [['phone' => str_repeat('1', 33), 'state' => 'subscribed'], 'phone'];
        yield 'unknown state' => [['phone' => '+40700000001', 'state' => 'pending'], 'state must be subscribed or unsubscribed.'];
        yield 'invalid email' => [['phone' => '+40700000001', 'state' => 'subscribed', 'email' => 'not-an-email'], 'email'];
        yield 'firstname too long' => [['phone' => '+40700000001', 'state' => 'subscribed', 'firstname' => str_repeat('a', 256)], 'firstname'];
        yield 'lastname too long' => [['phone' => '+40700000001', 'state' => 'subscribed', 'lastname' => str_repeat('a', 256)], 'lastname'];
        yield 'date without time' => [['phone' => '+40700000001', 'state' => 'subscribed', 'consentUpdatedAt' => '2026-11-27'], 'consent_updated_at'];
        yield 'date without offset' => [['phone' => '+40700000001', 'state' => 'subscribed', 'consentUpdatedAt' => '2026-11-27T10:15:00'], 'consent_updated_at'];
        yield 'impossible date' => [['phone' => '+40700000001', 'state' => 'subscribed', 'consentUpdatedAt' => '2026-02-30T10:15:00Z'], 'consent_updated_at'];
        yield 'not a date' => [['phone' => '+40700000001', 'state' => 'subscribed', 'consentUpdatedAt' => 'yesterday'], 'consent_updated_at'];
    }

    /**
     * @param array<string, mixed> $args
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidSmsConsents')]
    public function testSmsConsentRejectsInvalidInput(array $args, string $message): void
    {
        [$api, $container] = $this->apiWithMockResponses();

        try {
            $api->smsConsent(...$args);
            $this->fail('Invalid input must throw.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString($message, $e->getMessage());
        }

        $this->assertSame([], $container->requests);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function validConsentDates(): iterable
    {
        yield 'Z' => ['2026-11-27T10:15:00Z'];
        yield 'offset' => ['2026-11-27T12:15:00+02:00'];
        yield 'fraction' => ['2026-11-27T10:15:00.123456Z'];
        yield 'no seconds' => ['2026-11-27T10:15Z'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validConsentDates')]
    public function testSmsConsentAcceptsIso8601DateTimes(string $date): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '{"result":true,"outcome":"unchanged"}'));

        $api->smsConsent('+40700000001', 'subscribed', consentUpdatedAt: $date);

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($date, $body['consent_updated_at']);
    }
}
