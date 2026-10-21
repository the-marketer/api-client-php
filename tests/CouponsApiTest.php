<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use NotificationService\Sdk\Internal\CouponsApi;
use TheMarketer\ApiClient\Common\Config;
use TheMarketer\ApiClient\Exception\ValidationException;

final class CouponsApiTest extends TestCase
{
    /**
     * @return array{0: CouponsApi, 1: \stdClass}
     */
    private function apiWithMockResponses(Response ...$responses): array
    {
        return $this->createApiWithMock(CouponsApi::class, ...$responses);
    }

    /**
     * @return array<string, string>
     */
    private function validSavePayload(): array
    {
        return [
            'code' => 'WELCOME10',
            'type' => '1',
            'value' => '10',
            'expiration_date' => '2026-12-31',
        ];
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetAvailableCouponsSendsGetWithEmailAndAuthQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"coupons":[]}'),
        );

        $result = $api->getAvailableCoupons('user@example.com');

        $this->assertSame(['coupons' => []], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/get_available_coupons', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);
        $this->assertSame('user@example.com', $query['email']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSaveSendsPostJsonBodyWithCouponFields(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"saved":true}'),
        );

        $payload = $this->validSavePayload();
        $result = $api->save($payload);

        $this->assertSame(['saved' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/save_coupon', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($payload['code'], $body['code']);
        $this->assertSame($payload['type'], $body['type']);
        $this->assertSame($payload['value'], $body['value']);
        $this->assertSame($payload['expiration_date'], $body['expiration_date']);
    }

    public function testGetAvailableCouponsThrowsWhenDomainKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config('', self::MOCK_API_KEY, self::MOCK_BASE_URL);
        $api = new CouponsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Customer ID not provided.');

        $api->getAvailableCoupons('user@example.com');
    }

    public function testGetAvailableCouponsThrowsWhenApiKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config(self::MOCK_DOMAIN, '', self::MOCK_BASE_URL);
        $api = new CouponsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rest key not provided.');

        $api->getAvailableCoupons('user@example.com');
    }

    public function testSaveThrowsWhenDomainKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config('', self::MOCK_API_KEY, self::MOCK_BASE_URL);
        $api = new CouponsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Customer ID not provided.');

        $api->save($this->validSavePayload());
    }

    public function testSaveThrowsWhenApiKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config(self::MOCK_DOMAIN, '', self::MOCK_BASE_URL);
        $api = new CouponsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rest key not provided.');

        $api->save($this->validSavePayload());
    }

    public function testGetAvailableCouponsThrowsWhenEmailInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->getAvailableCoupons('not-an-email');
    }

    public function testSaveThrowsWhenCodeEmpty(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $payload = $this->validSavePayload();
        $payload['code'] = '';
        $api->save($payload);
    }

    public function testSaveThrowsWhenExpirationDateMissing(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(\TheMarketer\ApiClient\Exception\ValidationException::class);

        $payload = $this->validSavePayload();
        unset($payload['expiration_date']);
        $api->save($payload);
    }
}
