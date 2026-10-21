<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use NotificationService\Sdk\Internal\ProductsApi;
use TheMarketer\ApiClient\Common\Config;
use TheMarketer\ApiClient\Exception\ValidationException;

final class ProductsApiTest extends TestCase
{
    /**
     * @return array{0: ProductsApi, 1: \stdClass}
     */
    private function apiWithMockResponses(Response ...$responses): array
    {
        return $this->createApiWithMock(ProductsApi::class, ...$responses);
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalCreateProductPayload(): array
    {
        return [
            'id' => '1',
            'sku' => 'SKU-1',
            'name' => 'Product',
            'description' => 'Description',
            'url' => 'https://shop.example/p/1',
            'main_image' => 'https://cdn.example/main.jpg',
            'category' => 'Electronics',
            'brand' => 'Acme',
            'acquisition_price' => 10.0,
            'price' => 20.0,
            'sale_price' => '19.99',
            'availability' => 1,
            'stock' => 5,
            'media_gallery' => [
                'https://cdn.example/a.jpg',
                'https://cdn.example/b.jpg',
            ],
            'created_at' => '2025-01-01T00:00:00Z',
        ];
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testCreateProductSendsPostToProductCreateWithJsonBody(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"created":1}'),
        );

        $payload = $this->minimalCreateProductPayload();
        $result = $api->createProduct($payload);

        $this->assertSame(['created' => 1], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/product/create', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($payload['id'], $body['id']);
        $this->assertSame($payload['sku'], $body['sku']);
        $this->assertSame($payload['name'], $body['name']);
        $this->assertSame($payload['media_gallery'], $body['media_gallery']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testUpdateProductSendsPostToProductUpdateWithMinimalBody(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"updated":true}'),
        );

        $payload = [
            'id' => '42',
            'sku' => 'SKU-42',
            'name' => 'Renamed',
        ];

        $result = $api->updateProduct($payload);

        $this->assertSame(['updated' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/product/update', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('42', $body['id']);
        $this->assertSame('SKU-42', $body['sku']);
        $this->assertSame('Renamed', $body['name']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSyncCategoriesSendsPostToCategoryUpsert(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"synced":1}'),
        );

        $payload = [
            'id' => 'cat-1',
            'name' => 'Laptops',
            'hierarchy' => '1/2/3',
            'url' => 'https://shop.example/c/laptops',
            'image_url' => 'https://cdn.example/cat.jpg',
        ];

        $result = $api->syncCategories($payload);

        $this->assertSame(['synced' => 1], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/category/upsert', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($payload['id'], $body['id']);
        $this->assertSame($payload['name'], $body['name']);
        $this->assertSame($payload['hierarchy'], $body['hierarchy']);
        $this->assertSame($payload['url'], $body['url']);
        $this->assertSame($payload['image_url'], $body['image_url']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSyncBrandsSendsPostToBrandUpsert(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"synced":1}'),
        );

        $payload = [
            'id' => 'brand-1',
            'name' => 'Acme',
            'url' => 'https://shop.example/b/acme',
            'image_url' => 'https://cdn.example/brand.jpg',
        ];

        $result = $api->syncBrands($payload);

        $this->assertSame(['synced' => 1], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/brand/upsert', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($payload['id'], $body['id']);
        $this->assertSame($payload['name'], $body['name']);
        $this->assertSame($payload['url'], $body['url']);
        $this->assertSame($payload['image_url'], $body['image_url']);
    }

    public function testCreateProductThrowsWhenDomainKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config('', self::MOCK_API_KEY, self::MOCK_BASE_URL);
        $api = new ProductsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Customer ID not provided.');

        $api->createProduct($this->minimalCreateProductPayload());
    }

    public function testCreateProductThrowsWhenApiKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config(self::MOCK_DOMAIN, '', self::MOCK_BASE_URL);
        $api = new ProductsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rest key not provided.');

        $api->createProduct($this->minimalCreateProductPayload());
    }

    public function testCreateProductThrowsWhenSkuMissing(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(\TheMarketer\ApiClient\Exception\ValidationException::class);

        $payload = $this->minimalCreateProductPayload();
        unset($payload['sku']);
        $api->createProduct($payload);
    }

    public function testSyncCategoriesThrowsWhenNameIsBlank(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->syncCategories([
            'id' => 'c1',
            'name' => '',
            'hierarchy' => '1',
            'url' => 'https://example.com/c',
            'image_url' => 'https://example.com/i.jpg',
        ]);
    }
}
