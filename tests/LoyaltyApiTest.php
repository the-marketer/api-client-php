<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use NotificationService\Sdk\Internal\LoyaltyApi;
use TheMarketer\ApiClient\Common\Config;
use TheMarketer\ApiClient\Exception\ValidationException;

final class LoyaltyApiTest extends TestCase
{
    /**
     * @return array{0: LoyaltyApi, 1: \stdClass}
     */
    private function apiWithMockResponses(Response ...$responses): array
    {
        return $this->createApiWithMock(LoyaltyApi::class, ...$responses);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetInfoSendsGetWithEmailAndAuthQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"points":100}'),
        );

        $result = $api->getInfo('user@example.com');

        $this->assertSame(['points' => 100], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/loyalty_info', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);
        $this->assertSame('user@example.com', $query['email']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testManagePointsSendsPostJsonBodyWithEmailActionPoints(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"success":true}'),
        );

        $result = $api->managePoints('user@example.com', 'increase', 50);

        $this->assertSame(['success' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/manage_loyalty_points', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('user@example.com', $body['email']);
        $this->assertSame('increase', $body['action']);
        $this->assertSame(50, $body['points']);
    }

    public function testManagePointsSendsDecreaseAction(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->managePoints('a@b.com', 'decrease', 1);

        $request = $this->lastRequest($container);
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('decrease', $body['action']);
        $this->assertSame(1, $body['points']);
    }

    public function testGetInfoThrowsWhenDomainKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config('', self::MOCK_API_KEY, self::MOCK_BASE_URL);
        $api = new LoyaltyApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Customer ID not provided.');

        $api->getInfo('user@example.com');
    }

    public function testGetInfoThrowsWhenApiKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config(self::MOCK_DOMAIN, '', self::MOCK_BASE_URL);
        $api = new LoyaltyApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rest key not provided.');

        $api->getInfo('user@example.com');
    }

    public function testManagePointsThrowsWhenDomainKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config('', self::MOCK_API_KEY, self::MOCK_BASE_URL);
        $api = new LoyaltyApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Customer ID not provided.');

        $api->managePoints('user@example.com', 'increase', 10);
    }

    public function testManagePointsThrowsWhenApiKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config(self::MOCK_DOMAIN, '', self::MOCK_BASE_URL);
        $api = new LoyaltyApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rest key not provided.');

        $api->managePoints('user@example.com', 'increase', 10);
    }

    public function testGetInfoThrowsWhenEmailInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->getInfo('not-an-email');
    }

    public function testManagePointsThrowsWhenEmailInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->managePoints('invalid', 'increase', 10);
    }

    public function testManagePointsThrowsWhenActionInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->managePoints('user@example.com', 'freeze', 10);
    }

    public function testManagePointsThrowsWhenPointsBelowOne(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->managePoints('user@example.com', 'increase', 0);
    }

    public function testGetInfoSendsPhoneWhenNoEmail(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->getInfo(phone: '0735111411');

        parse_str($this->lastRequest($container)->getUri()->getQuery(), $query);
        $this->assertSame('0735111411', $query['phone']);
        $this->assertArrayNotHasKey('email', $query);
    }

    public function testGetInfoThrowsWhenEmailAndPhoneMissing(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Either email or phone is required.');

        $api->getInfo();
    }

    public function testManagePointsSendsPhoneWhenNoEmail(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->managePoints(null, 'increase', 5, '0735111411');

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('0735111411', $body['phone']);
        $this->assertArrayNotHasKey('email', $body);
        $this->assertSame('increase', $body['action']);
        $this->assertSame(5, $body['points']);
    }

    public function testManagePointsThrowsWhenEmailAndPhoneMissing(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Either email or phone is required.');

        $api->managePoints(null, 'increase', 5);
    }
}
