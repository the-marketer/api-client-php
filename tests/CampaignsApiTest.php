<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use NotificationService\Sdk\Internal\CampaignsApi;
use TheMarketer\ApiClient\Common\Config;
use TheMarketer\ApiClient\Exception\ValidationException;

final class CampaignsApiTest extends TestCase
{
    /**
     * @return array{0: CampaignsApi, 1: \stdClass}
     */
    private function apiWithMockResponses(Response ...$responses): array
    {
        return $this->createApiWithMock(CampaignsApi::class, ...$responses);
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalCreateCampaignPayload(): array
    {
        return [
            'type' => 'email',
            'mode' => 'regular',
            'sender' => [
                'name' => 'Shop',
                'sender' => 'shop@example.com',
                'reply_to' => 'support@example.com',
            ],
            'audience' => [
                'audience_type' => 'all',
                'smart_sending' => false,
            ],
            'subject' => [
                'name' => 'Spring',
                'subject_line' => 'Hello',
                'preview_text' => 'Preview',
            ],
            'content' => [
                'html' => '<p>Hi</p>',
            ],
            'scheduling' => [
                'send_at' => '2025-06-01 12:00',
                'use_optimal_time' => 0,
                'optimize_for' => 'opening',
            ],
            'tracking' => [
                'utm_campaign' => 'c1',
                'utm_medium' => 'email',
                'utm_source' => 'nl',
            ],
        ];
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testListSendsPostWithAuthQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"items":[]}'),
        );

        $result = $api->list();

        $this->assertSame(['items' => []], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/campaigns/list', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testCreateSendsPostJsonBody(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"id":"new-1"}'),
        );

        $payload = $this->minimalCreateCampaignPayload();
        $result = $api->create($payload);

        $this->assertSame(['id' => 'new-1'], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/campaigns/create', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('email', $body['type']);
        $this->assertSame('regular', $body['mode']);
        $this->assertSame('Shop', $body['sender']['name']);
        $this->assertSame('all', $body['audience']['audience_type']);
        $this->assertSame('<p>Hi</p>', $body['content']['html']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetEmailReportSendsGetWithEncodedCampaignId(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"opens":1}'),
        );

        $result = $api->getEmailReport('foo/bar');

        $this->assertSame(['opens' => 1], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $path = $request->getUri()->getPath();
        $this->assertStringEndsWith('/campaigns/foo/bar/email/get-report', $path);

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetEmailReportAcceptsNumericStringId(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->getEmailReport('99');

        $request = $this->lastRequest($container);
        $this->assertStringContainsString('/campaigns/99/email/get-report', $request->getUri()->getPath());
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetLatestCampaignWithoutLimitSendsGetWithAuthOnly(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '[]'),
        );

        $result = $api->getLatestCampaign();

        $this->assertSame([], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/get-latest-campaign', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);
        $this->assertArrayNotHasKey('limit', $query);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetLatestCampaignWithLimitAddsQueryParam(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->getLatestCampaign(5);

        $request = $this->lastRequest($container);
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame('5', $query['limit']);
    }

    public function testListThrowsWhenDomainKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config('', self::MOCK_API_KEY, self::MOCK_BASE_URL);
        $api = new CampaignsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Customer ID not provided.');

        $api->list();
    }

    public function testListThrowsWhenApiKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config(self::MOCK_DOMAIN, '', self::MOCK_BASE_URL);
        $api = new CampaignsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rest key not provided.');

        $api->list();
    }

    public function testCreateThrowsWhenPayloadInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $payload = $this->minimalCreateCampaignPayload();
        $payload['type'] = 'invalid-type';
        $api->create($payload);
    }

    public function testGetLatestCampaignThrowsWhenLimitBelowMinimum(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->getLatestCampaign(0);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testListSendsFiltersAsObjectAndPageLimitAsInts(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '{"data":[]}'));

        $api->list([
            'filters' => ['status' => ['scheduled'], 'send_at_from' => '2026-11-01', 'send_at_to' => '2026-11-30'],
            'page' => '2',
            'limit' => 50,
        ]);

        $request = $this->lastRequest($container);
        $this->assertStringContainsString('"filters":{', (string) $request->getBody());
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([
            'page' => 2,
            'limit' => 50,
            'filters' => ['status' => ['scheduled'], 'send_at_from' => '2026-11-01', 'send_at_to' => '2026-11-30'],
        ], $body);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testListDecodesJsonStringFilters(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '{"data":[]}'));

        $api->list(['filters' => '{"type":"email","search":"Spring"}']);

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['filters' => ['type' => 'email', 'search' => 'Spring']], $body);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testListMovesTopLevelSearchIntoFilters(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '{"data":[]}'));

        $api->list(['search' => 'Spring', 'type' => 'sms', 'filters' => ['type' => 'email']]);

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['filters' => ['type' => 'email', 'search' => 'Spring']], $body);
    }

    public function testListRejectsInvalidJsonFilters(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('filters');

        $api->list(['filters' => '{not json']);
    }

    public function testListRejectsListFilters(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->list(['filters' => ['scheduled']]);
    }

    public function testListRejectsNonNumericPage(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('page: must be an integer.');

        $api->list(['page' => 'two']);
    }

    public function testListRejectsZeroLimit(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->list(['limit' => 0]);
    }
}
