<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use NotificationService\Sdk\Internal\ReportsApi;
use Psr\Http\Message\RequestInterface;
use TheMarketer\ApiClient\Common\Config;
use TheMarketer\ApiClient\Exception\ValidationException;

final class ReportsApiTest extends TestCase
{
    /**
     * @return array{0: ReportsApi, 1: \stdClass}
     */
    private function apiWithMockResponses(Response ...$responses): array
    {
        return $this->createApiWithMock(ReportsApi::class, ...$responses);
    }

    /**
     * @return array<string, string>
     */
    private function dateRange(): array
    {
        return [
            'start' => '2025-01-01',
            'end' => '2025-01-31',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function emailReportsQuery(): array
    {
        return array_merge($this->dateRange(), ['type' => 'sent']);
    }

    /**
     * @return array<string, string>
     */
    private function smsPushReportsQuery(): array
    {
        return array_merge($this->dateRange(), ['type' => 'sent']);
    }

    /**
     * @return array<string, string>
     */
    private function formsReportsQuery(): array
    {
        return array_merge($this->dateRange(), ['type' => 'total-impressions']);
    }

    /**
     * @return array<string, string>
     */
    private function audienceReportsQuery(): array
    {
        return array_merge($this->dateRange(), ['type' => 'total-subscribed-emails']);
    }

    /**
     * @param array<string, string> $expectedSubset
     */
    private function assertReportsGetRequest(RequestInterface $request, string $pathSuffix, array $expectedSubset): void
    {
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/reports/' . $pathSuffix, $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        foreach ($expectedSubset as $key => $value) {
            $this->assertSame($value, $query[$key] ?? null, 'Query key: ' . $key);
        }
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetEmailCampaignsSendsGetWithQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"rows":[]}'),
        );

        $q = $this->emailReportsQuery();
        $result = $api->getEmailCampaigns($q);

        $this->assertSame(['rows' => []], $result);

        $this->assertReportsGetRequest(
            $this->lastRequest($container),
            'get-email-campaigns',
            [
                'type' => 'sent',
                'start' => '2025-01-01',
                'end' => '2025-01-31',
            ],
        );
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetEmailAutomationSendsGetWithQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->getEmailAutomation($this->emailReportsQuery());

        $this->assertReportsGetRequest(
            $this->lastRequest($container),
            'get-email-automation',
            [
                'type' => 'sent',
                'start' => '2025-01-01',
                'end' => '2025-01-31',
            ],
        );
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetPushCampaignsSendsGetWithQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->getPushCampaigns($this->smsPushReportsQuery());

        $this->assertReportsGetRequest(
            $this->lastRequest($container),
            'get-push-campaigns',
            [
                'type' => 'sent',
                'start' => '2025-01-01',
                'end' => '2025-01-31',
            ],
        );
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetPushAutomationSendsGetWithQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->getPushAutomation($this->smsPushReportsQuery());

        $this->assertReportsGetRequest(
            $this->lastRequest($container),
            'get-push-automation',
            [
                'type' => 'sent',
                'start' => '2025-01-01',
                'end' => '2025-01-31',
            ],
        );
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetSmsCampaignsSendsGetWithQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->getSmsCampaigns($this->smsPushReportsQuery());

        $this->assertReportsGetRequest(
            $this->lastRequest($container),
            'get-sms-campaigns',
            [
                'type' => 'sent',
                'start' => '2025-01-01',
                'end' => '2025-01-31',
            ],
        );
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetSmsAutomationSendsGetWithQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->getSmsAutomation($this->smsPushReportsQuery());

        $this->assertReportsGetRequest(
            $this->lastRequest($container),
            'get-sms-automation',
            [
                'type' => 'sent',
                'start' => '2025-01-01',
                'end' => '2025-01-31',
            ],
        );
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetFormsPopupsSendsGetWithQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->getFormsPopups($this->formsReportsQuery());

        $this->assertReportsGetRequest(
            $this->lastRequest($container),
            'get-forms-popups',
            [
                'type' => 'total-impressions',
                'start' => '2025-01-01',
                'end' => '2025-01-31',
            ],
        );
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetFormsEmbeddedSendsGetWithQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->getFormsEmbedded($this->formsReportsQuery());

        $this->assertReportsGetRequest(
            $this->lastRequest($container),
            'get-forms-embedded',
            [
                'type' => 'total-impressions',
                'start' => '2025-01-01',
                'end' => '2025-01-31',
            ],
        );
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetAudienceSendsGetWithQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->getAudience($this->audienceReportsQuery());

        $this->assertReportsGetRequest(
            $this->lastRequest($container),
            'get-audience',
            [
                'type' => 'total-subscribed-emails',
                'start' => '2025-01-01',
                'end' => '2025-01-31',
            ],
        );
    }

    public function testGetEmailCampaignsThrowsWhenDomainKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config('', self::MOCK_API_KEY, self::MOCK_BASE_URL);
        $api = new ReportsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Customer ID not provided.');

        $api->getEmailCampaigns($this->emailReportsQuery());
    }

    public function testGetEmailCampaignsThrowsWhenApiKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config(self::MOCK_DOMAIN, '', self::MOCK_BASE_URL);
        $api = new ReportsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rest key not provided.');

        $api->getEmailCampaigns($this->emailReportsQuery());
    }

    public function testGetEmailCampaignsThrowsWhenStartMissing(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(\TheMarketer\ApiClient\Exception\ValidationException::class);

        $api->getEmailCampaigns([
            'type' => 'sent',
            'end' => '2025-01-31',
        ]);
    }
}
