<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Psr7\Response;
use NotificationService\Sdk\Internal\AccountApi;
use NotificationService\Sdk\Internal\CampaignsApi;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;
use TheMarketer\ApiClient\DTO\Account\AllowedDomain;
use TheMarketer\ApiClient\Exception\ApiException;
use TheMarketer\ApiClient\Exception\UnauthorizedException;
use TheMarketer\ApiClient\Exception\ValidationException;

final class AccountApiTest extends TestCase
{
    /**
     * @return array{0: AccountApi, 1: \stdClass}
     */
    private function apiWithMockResponses(Response ...$responses): array
    {
        return $this->createApiWithMock(AccountApi::class, ...$responses);
    }

    private static function jsonResponse(mixed $body, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, string>
     */
    private function queryOf(RequestInterface $request): array
    {
        parse_str($request->getUri()->getQuery(), $query);

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function bodyOf(RequestInterface $request): array
    {
        return json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: mixed}>
     */
    public static function getEndpoints(): iterable
    {
        yield 'account' => ['account', '/api/v1/account', [
            'id' => 'd1', 'name' => 'shop.ro', 'domain' => 'shop.ro',
            'plan' => ['name' => 'Starter', 'subscribers' => '1000'],
            'payment_status' => 'active', 'pay_yearly' => false, 'next_billing_date' => '2026-12-01',
            'pays_through_shopify' => true, 'campaigns_sent' => 3,
        ]];
        yield 'allowedDomains' => ['allowedDomains', '/api/v1/allowed_domains', ['shop.ro', 'www.shop.ro']];
        yield 'accountReputation' => ['accountReputation', '/api/v1/dashboard/account-reputation', [
            'reputation' => '100', 'reputation_color' => '#09B66D', 'blocked' => false,
        ]];
        yield 'productHealth' => ['productHealth', '/api/v1/dashboard/product-health', [
            'all' => 10, 'in_stock' => 8, 'without_image' => 1, 'broken_links' => 0, 'price_issues' => 2,
        ]];
    }

    #[DataProvider('getEndpoints')]
    public function testGetEndpointsSendGetWithAuthOnly(string $method, string $path, mixed $answer): void
    {
        [$api, $container] = $this->apiWithMockResponses(self::jsonResponse($answer));

        $this->assertSame($answer, $api->{$method}());

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame($path, $request->getUri()->getPath());
        $this->assertSame(['k' => self::MOCK_API_KEY, 'u' => self::MOCK_DOMAIN], $this->queryOf($request));
        $this->assertSame('', (string) $request->getBody());
    }

    public function testAddAllowedDomainPostsTheDomainAsGiven(): void
    {
        $answer = ['result' => true, 'domains' => ['shop.ro']];
        [$api, $container] = $this->apiWithMockResponses(self::jsonResponse($answer));

        $this->assertSame($answer, $api->addAllowedDomain('https://Shop.ro/path'));

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/allowed_domains', $request->getUri()->getPath());
        $this->assertSame(['domain' => 'https://Shop.ro/path'], $this->bodyOf($request));
    }

    public function testDeleteAllowedDomainPostsToDeletePath(): void
    {
        $answer = ['result' => true, 'domains' => []];
        [$api, $container] = $this->apiWithMockResponses(self::jsonResponse($answer));

        $this->assertSame($answer, $api->deleteAllowedDomain('shop.ro'));

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/allowed_domains/delete', $request->getUri()->getPath());
        $this->assertSame(['domain' => 'shop.ro'], $this->bodyOf($request));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidHosts(): iterable
    {
        yield 'no dot' => ['localhost'];
        yield 'blank' => ['   '];
        yield 'invalid characters' => ['shop_ro!.com'];
        yield 'too long' => [str_repeat('a', 250) . '.com'];
        yield 'scheme only' => ['https://'];
    }

    #[DataProvider('invalidHosts')]
    public function testAllowedDomainRejectsWhatThePlatformRejects(string $domain): void
    {
        [$api, $container] = $this->apiWithMockResponses();

        try {
            $api->addAllowedDomain($domain);
            $this->fail('An invalid host must throw.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('domain', $e->getMessage());
        }

        try {
            $api->deleteAllowedDomain($domain);
            $this->fail('An invalid host must throw.');
        } catch (ValidationException) {
        }

        $this->assertSame([], $container->requests);
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function normalisedHosts(): iterable
    {
        yield 'plain' => ['shop.ro', 'shop.ro'];
        yield 'www kept' => ['WWW.Shop.ro', 'www.shop.ro'];
        yield 'url with port and path' => ['https://user@shop.ro:8443/a?b=1', 'shop.ro'];
        yield 'trailing dot' => ['shop.ro.', 'shop.ro'];
        yield 'protocol-relative' => ['//shop.ro/x', 'shop.ro'];
    }

    #[DataProvider('normalisedHosts')]
    public function testNormalizeHostMatchesThePlatform(string $value, string $host): void
    {
        $this->assertSame($host, AllowedDomain::normalizeHost($value));
    }

    public function testEmailSettingsForTheMainDomainSendsNoDomain(): void
    {
        $answer = ['domain' => 'shop.ro', 'dns_routing' => 'legacy', 'dns' => [], 'validated' => true];
        [$api, $container] = $this->apiWithMockResponses(self::jsonResponse($answer));

        $this->assertSame($answer, $api->emailSettings());

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/v1/settings/email', $request->getUri()->getPath());
        $this->assertArrayNotHasKey('domain', $this->queryOf($request));
    }

    public function testEmailSettingsForAnExtraSenderDomainSendsItsIndex(): void
    {
        [$api, $container] = $this->apiWithMockResponses(self::jsonResponse(['domain' => 'mail.shop.ro']));

        $api->emailSettings(0);

        $this->assertSame('0', $this->queryOf($this->lastRequest($container))['domain']);
    }

    public function testEmailSettingsRejectsNegativeIndex(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->emailSettings(-1);
    }

    public function testEmailSettingsSurfacesTheTemporary503(): void
    {
        [$api] = $this->apiWithMockResponses(self::jsonResponse(['message' => 'DNS records are temporarily unavailable.'], 503));

        try {
            $api->emailSettings();
            $this->fail('A 503 must throw.');
        } catch (ApiException $e) {
            $this->assertSame(503, $e->getCode());
            $this->assertSame('DNS records are temporarily unavailable.', $e->getMessage());
        }
    }

    public function testValidateDomainIsTrueOnSuccess(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], 'true'));

        $this->assertTrue($api->validateDomain());

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/validate-domain', $request->getUri()->getPath());
    }

    public function testValidateDomainIsFalseWhenThePlatformAnswers422(): void
    {
        [$api] = $this->apiWithMockResponses(self::jsonResponse(['message' => 'Domain cannot be validated'], 422));

        $this->assertFalse($api->validateDomain());
    }

    public function testValidateDomainThrowsOtherErrors(): void
    {
        [$api] = $this->apiWithMockResponses(self::jsonResponse(['message' => 'Unauthorized'], 401));

        $this->expectException(UnauthorizedException::class);

        $api->validateDomain();
    }

    public function testValidateDomainThrowsServerErrors(): void
    {
        [$api] = $this->apiWithMockResponses(new Response(500, [], 'boom'));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(500);

        $api->validateDomain();
    }

    public function testLatestCampaignCarriesIdAndType(): void
    {
        $answer = ['id' => '6650f0a1b2c3d4e5f6a7b8c9', 'type' => 'email', 'status' => 'sent', 'subject_line' => 'Spring'];
        [$api, $container] = $this->createApiWithMock(CampaignsApi::class, self::jsonResponse($answer));

        $result = $api->getLatestCampaign();

        $this->assertSame('6650f0a1b2c3d4e5f6a7b8c9', $result['id']);
        $this->assertSame('email', $result['type']);
        $this->assertSame('/api/v1/get-latest-campaign', $this->lastRequest($container)->getUri()->getPath());
    }
}
