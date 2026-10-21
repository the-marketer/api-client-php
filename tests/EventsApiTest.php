<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use NotificationService\Sdk\Internal\EventsApi;
use TheMarketer\ApiClient\Common\Config;
use TheMarketer\ApiClient\Exception\ValidationException;

final class EventsApiTest extends TestCase
{
    /**
     * @return array{0: EventsApi, 1: \stdClass}
     */
    private function apiWithMockResponses(Response ...$responses): array
    {
        return $this->createApiWithMock(EventsApi::class, ...$responses);
    }

    /**
     * @return array<string, mixed>
     */
    private function validSendCustomEventPayload(): array
    {
        return [
            'did' => 'device-xyz',
            'email' => 'user@example.com',
            'event' => 'product_viewed',
            'url' => 'https://example.com/products/1',
            'http_user_agent' => 'Mozilla/5.0 (compatible)',
            'remote_addr' => '203.0.113.1',
        ];
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSendCustomEventSendsPostJsonBodyWithEmailAndEvent(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $payload = $this->validSendCustomEventPayload();
        $result = $api->sendCustom($payload);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/t/r', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_TRACKING_KEY, $query['k']);
        $this->assertSame(self::MOCK_API_KEY, $query['api_key']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($payload['did'], $body['did']);
        $this->assertSame($payload['email'], $body['email']);
        $this->assertSame($payload['event'], $body['event']);
        $this->assertSame($payload['url'], $body['url']);
        $this->assertSame($payload['http_user_agent'], $body['http_user_agent']);
        $this->assertSame($payload['remote_addr'], $body['remote_addr']);
        $this->assertArrayNotHasKey('source', $body);
    }

    /**
     * Varianta REST {@see EventsApi::sendCustomApi} (doar email + event), spre `/custom_events`.
     *
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSendCustomApiSendsPostJsonToRestCustomEvents(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $result = $api->sendCustomApi([
            'email' => 'user@example.com',
            'event' => 'product_viewed',
        ]);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/custom_events', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('user@example.com', $body['email']);
        $this->assertSame('product_viewed', $body['event']);
    }

    public function testSendCustomEventThrowsWhenDomainKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config('', self::MOCK_API_KEY, self::MOCK_BASE_URL);
        $api = new EventsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Customer ID not provided.');

        $api->sendCustomApi([
            'email' => 'user@example.com',
            'event' => 'product_viewed',
        ]);
    }

    public function testSendCustomEventThrowsWhenApiKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config(self::MOCK_DOMAIN, '', self::MOCK_BASE_URL);
        $api = new EventsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rest key not provided.');

        $api->sendCustomApi([
            'email' => 'user@example.com',
            'event' => 'product_viewed',
        ]);
    }

    public function testSendCustomEventThrowsWhenEmailInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $payload = $this->validSendCustomEventPayload();
        $payload['email'] = 'not-an-email';
        $api->sendCustom($payload);
    }

    public function testSendCustomEventThrowsWhenEventMissing(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(\TheMarketer\ApiClient\Exception\ValidationException::class);

        $payload = $this->validSendCustomEventPayload();
        unset($payload['event']);
        $api->sendCustom($payload);
    }

    public function testSendCustomEventThrowsWhenUrlInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $payload = $this->validSendCustomEventPayload();
        $payload['url'] = 'not-a-valid-url';
        $api->sendCustom($payload);
    }

    /**
     * @return array<string, string>
     */
    private function validViewHomepagePayload(): array
    {
        return [
            'did' => 'device-abc',
            'event' => 'view_homepage',
            'url' => 'https://example.com/',
            'http_user_agent' => 'Mozilla/5.0 (compatible)',
            'remote_addr' => '203.0.113.1',
        ];
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testViewHomepageSendsPostJsonBodyWithQueryAuth(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $payload = $this->validViewHomepagePayload();
        $result = $api->viewHomepage($payload);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/t/r', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_TRACKING_KEY, $query['k']);
        $this->assertSame(self::MOCK_API_KEY, $query['api_key']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($payload['did'], $body['did']);
        $this->assertSame($payload['event'], $body['event']);
        $this->assertSame($payload['url'], $body['url']);
        $this->assertSame($payload['http_user_agent'], $body['http_user_agent']);
        $this->assertSame($payload['remote_addr'], $body['remote_addr']);
        $this->assertArrayNotHasKey('source', $body);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testViewHomepageIncludesSourceWhenProvided(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $payload = $this->validViewHomepagePayload();
        $payload['source'] = 'newsletter';
        $api->viewHomepage($payload);

        $request = $this->lastRequest($container);
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('newsletter', $body['source']);
    }

    public function testViewHomepageThrowsWhenUrlInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $payload = $this->validViewHomepagePayload();
        $payload['url'] = 'not-a-valid-url';
        $api->viewHomepage($payload);
    }

    /**
     * @return array<string, string>
     */
    private function validInitiateCheckoutPayload(): array
    {
        return [
            'did' => 'device-abc',
            'event' => 'initiate_checkout',
            'url' => 'https://example.com/checkout',
            'http_user_agent' => 'Mozilla/5.0 (compatible)',
            'remote_addr' => '203.0.113.1',
        ];
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testInitiateCheckoutSendsPostJsonBodyWithQueryAuth(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $payload = $this->validInitiateCheckoutPayload();
        $result = $api->initiateCheckout($payload);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/t/r', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_TRACKING_KEY, $query['k']);
        $this->assertSame(self::MOCK_API_KEY, $query['api_key']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($payload['did'], $body['did']);
        $this->assertSame($payload['event'], $body['event']);
        $this->assertSame($payload['url'], $body['url']);
        $this->assertSame($payload['http_user_agent'], $body['http_user_agent']);
        $this->assertSame($payload['remote_addr'], $body['remote_addr']);
        $this->assertArrayNotHasKey('source', $body);
    }

    public function testInitiateCheckoutThrowsWhenUrlInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $payload = $this->validInitiateCheckoutPayload();
        $payload['url'] = 'not-a-valid-url';
        $api->initiateCheckout($payload);
    }

    /**
     * @return array<string, string>
     */
    private function validSearchPayload(): array
    {
        return [
            'did' => 'device-abc',
            'event' => 'search',
            'search_term' => 'running shoes',
            'url' => 'https://example.com/search?q=running+shoes',
            'http_user_agent' => 'Mozilla/5.0 (compatible)',
            'remote_addr' => '203.0.113.1',
        ];
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSearchSendsPostJsonBodyWithQueryAuth(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $payload = $this->validSearchPayload();
        $result = $api->search($payload);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/t/r', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_TRACKING_KEY, $query['k']);
        $this->assertSame(self::MOCK_API_KEY, $query['api_key']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($payload['did'], $body['did']);
        $this->assertSame($payload['event'], $body['event']);
        $this->assertSame($payload['search_term'], $body['search_term']);
        $this->assertSame($payload['url'], $body['url']);
        $this->assertSame($payload['http_user_agent'], $body['http_user_agent']);
        $this->assertSame($payload['remote_addr'], $body['remote_addr']);
        $this->assertArrayNotHasKey('source', $body);
    }

    public function testSearchThrowsWhenUrlInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $payload = $this->validSearchPayload();
        $payload['url'] = 'not-a-valid-url';
        $api->search($payload);
    }

    /**
     * @return array<string, string>
     */
    private function validSetEmailPayload(): array
    {
        return [
            'did' => 'device-abc',
            'event' => 'set_email',
            'email_address' => 'user@example.com',
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'phone' => '+40123456789',
            'url' => 'https://example.com/checkout',
            'http_user_agent' => 'Mozilla/5.0 (compatible)',
            'remote_addr' => '203.0.113.1',
        ];
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSetEmailSendsPostJsonBodyWithQueryAuth(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $payload = $this->validSetEmailPayload();
        $result = $api->setEmail($payload);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/t/r', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_TRACKING_KEY, $query['k']);
        $this->assertSame(self::MOCK_API_KEY, $query['api_key']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($payload['did'], $body['did']);
        $this->assertSame($payload['event'], $body['event']);
        $this->assertSame($payload['email_address'], $body['email_address']);
        $this->assertSame($payload['firstname'], $body['firstname']);
        $this->assertSame($payload['lastname'], $body['lastname']);
        $this->assertSame($payload['phone'], $body['phone']);
        $this->assertSame($payload['url'], $body['url']);
        $this->assertSame($payload['http_user_agent'], $body['http_user_agent']);
        $this->assertSame($payload['remote_addr'], $body['remote_addr']);
        $this->assertArrayNotHasKey('source', $body);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSetEmailIncludesSourceWhenProvided(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $payload = $this->validSetEmailPayload();
        $payload['source'] = 'popup';
        $api->setEmail($payload);

        $request = $this->lastRequest($container);
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('popup', $body['source']);
    }

    public function testSetEmailThrowsWhenEmailInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $payload = $this->validSetEmailPayload();
        $payload['email_address'] = 'not-an-email';
        $api->setEmail($payload);
    }

    /**
     * @return array<string, string>
     */
    private function validViewProductPayload(): array
    {
        return [
            'did' => 'device-abc',
            'event' => 'view_product',
            'product_id' => 'sku-42',
            'url' => 'https://example.com/p/sku-42',
            'http_user_agent' => 'Mozilla/5.0 (compatible)',
            'remote_addr' => '203.0.113.1',
        ];
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testViewProductSendsPostJsonBodyWithQueryAuth(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $payload = $this->validViewProductPayload();
        $result = $api->viewProduct($payload);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/t/r', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_TRACKING_KEY, $query['k']);
        $this->assertSame(self::MOCK_API_KEY, $query['api_key']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($payload['did'], $body['did']);
        $this->assertSame($payload['event'], $body['event']);
        $this->assertSame($payload['product_id'], $body['product_id']);
        $this->assertSame($payload['url'], $body['url']);
        $this->assertSame($payload['http_user_agent'], $body['http_user_agent']);
        $this->assertSame($payload['remote_addr'], $body['remote_addr']);
        $this->assertArrayNotHasKey('source', $body);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testViewProductIncludesSourceWhenProvided(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $payload = $this->validViewProductPayload();
        $payload['source'] = 'catalog';
        $api->viewProduct($payload);

        $request = $this->lastRequest($container);
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('catalog', $body['source']);
    }

    public function testViewProductThrowsWhenUrlInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $payload = $this->validViewProductPayload();
        $payload['url'] = 'not-a-valid-url';
        $api->viewProduct($payload);
    }

    /**
     * Shared payload shape for cart / wishlist line events (see {@see \TheMarketer\ApiClient\DTO\Events\ProductLineEvent}).
     *
     * @return array<string, mixed>
     */
    private function validProductLinePayload(string $event, int $quantity = 2, string $url = 'https://example.com/p/101'): array
    {
        return [
            'did' => 'device-abc',
            'event' => $event,
            'product_id' => 101,
            'quantity' => $quantity,
            'variation' => [
                'id' => 'var-red',
                'sku' => 'SKU-RED-L',
            ],
            'http_user_agent' => 'Mozilla/5.0 (compatible)',
            'url' => $url,
            'remote_addr' => '203.0.113.1',
        ];
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testAddToCartSendsPostJsonBodyWithQueryAuth(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $payload = $this->validProductLinePayload('add_to_cart');
        $result = $api->addToCart($payload);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/t/r', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_TRACKING_KEY, $query['k']);
        $this->assertSame(self::MOCK_API_KEY, $query['api_key']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($payload['did'], $body['did']);
        $this->assertSame($payload['event'], $body['event']);
        $this->assertSame($payload['product_id'], $body['product_id']);
        $this->assertSame($payload['quantity'], $body['quantity']);
        $this->assertSame($payload['variation']['id'], $body['variation']['id']);
        $this->assertSame($payload['variation']['sku'], $body['variation']['sku']);
        $this->assertSame($payload['http_user_agent'], $body['http_user_agent']);
        $this->assertSame($payload['url'], $body['url']);
        $this->assertSame($payload['remote_addr'], $body['remote_addr']);
        $this->assertArrayNotHasKey('source', $body);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testAddToCartCoercesStringProductIdAndQuantity(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $payload = $this->validProductLinePayload('add_to_cart');
        $payload['product_id'] = '55';
        $payload['quantity'] = '3';
        $api->addToCart($payload);

        $request = $this->lastRequest($container);
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(55, $body['product_id']);
        $this->assertSame(3, $body['quantity']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testAddToCartIncludesSourceWhenProvided(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $payload = $this->validProductLinePayload('add_to_cart');
        $payload['source'] = 'mini_cart';
        $api->addToCart($payload);

        $request = $this->lastRequest($container);
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('mini_cart', $body['source']);
    }

    public function testAddToCartThrowsWhenVariationMissing(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('variation must be an array.');

        $payload = $this->validProductLinePayload('add_to_cart');
        unset($payload['variation']);
        $api->addToCart($payload);
    }

    public function testAddToCartThrowsWhenUrlInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $payload = $this->validProductLinePayload('add_to_cart');
        $payload['url'] = 'not-a-valid-url';
        $api->addToCart($payload);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testRemoveFromCartSendsPostJsonBodyWithQueryAuth(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $payload = $this->validProductLinePayload('remove_from_cart', 1, 'https://example.com/cart');
        $result = $api->removeFromCart($payload);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/t/r', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_TRACKING_KEY, $query['k']);
        $this->assertSame(self::MOCK_API_KEY, $query['api_key']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($payload['did'], $body['did']);
        $this->assertSame($payload['event'], $body['event']);
        $this->assertSame($payload['product_id'], $body['product_id']);
        $this->assertSame($payload['quantity'], $body['quantity']);
        $this->assertSame($payload['variation']['id'], $body['variation']['id']);
        $this->assertSame($payload['variation']['sku'], $body['variation']['sku']);
        $this->assertSame($payload['http_user_agent'], $body['http_user_agent']);
        $this->assertSame($payload['url'], $body['url']);
        $this->assertSame($payload['remote_addr'], $body['remote_addr']);
        $this->assertArrayNotHasKey('source', $body);
    }

    public function testRemoveFromCartThrowsWhenVariationMissing(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('variation must be an array.');

        $payload = $this->validProductLinePayload('remove_from_cart', 1, 'https://example.com/cart');
        unset($payload['variation']);
        $api->removeFromCart($payload);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testAddToWishlistSendsPostJsonBodyWithQueryAuth(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $payload = $this->validProductLinePayload('add_to_wishlist');
        $result = $api->addToWishlist($payload);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/t/r', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_TRACKING_KEY, $query['k']);
        $this->assertSame(self::MOCK_API_KEY, $query['api_key']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('add_to_wishlist', $body['event']);
        $this->assertSame($payload['product_id'], $body['product_id']);
        $this->assertSame($payload['variation']['sku'], $body['variation']['sku']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testRemoveFromWishlistSendsPostJsonBodyWithQueryAuth(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $payload = $this->validProductLinePayload('remove_from_wishlist', 1, 'https://example.com/wishlist');
        $result = $api->removeFromWishlist($payload);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertStringEndsWith('/t/r', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('remove_from_wishlist', $body['event']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testServeJavascriptSendsGetWithTrackingPathAndQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->serveJavascript('abc123');

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/t/j/abc123', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame('abc123', $query['k']);
        $this->assertSame(self::MOCK_API_KEY, $query['api_key']);

        $this->assertSame('', (string) $request->getBody());
    }

    public function testServeJavascriptThrowsWhenKTooShort(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->serveJavascript('abcde');
    }

    public function testServeJavascriptThrowsWhenKTooLong(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->serveJavascript(str_repeat('a', 21));
    }
}
