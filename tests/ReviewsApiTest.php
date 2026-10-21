<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use NotificationService\Sdk\Internal\ReviewsApi;
use TheMarketer\ApiClient\Common\Config;
use TheMarketer\ApiClient\Exception\ValidationException;

final class ReviewsApiTest extends TestCase
{
    /**
     * @return array{0: ReviewsApi, 1: \stdClass}
     */
    private function apiWithMockResponses(Response ...$responses): array
    {
        return $this->createApiWithMock(ReviewsApi::class, ...$responses);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetProductReviewsSendsGetToProductReviewsWithAuthQuery(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"reviews":[]}'),
        );

        $result = $api->getProductReviews();

        $this->assertSame('{"reviews":[]}', $result);

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/product_reviews', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetProductReviewsMergesPaginationAndTypeQueryParams(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->getProductReviews([
            'page' => 2,
            'perPage' => 20,
            't' => 5,
        ]);

        $request = $this->lastRequest($container);
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame('2', $query['page']);
        $this->assertSame('20', $query['perPage']);
        $this->assertSame('5', $query['t']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testCreateReviewSendsPostJsonBodyToAddReview(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"ok":true}'),
        );

        $result = $api->createReview([
            'order_id' => 'ord-1',
            'review_date' => '2025-03-26',
        ]);

        $this->assertSame(['ok' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/add_review', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('ord-1', $body['order_id']);
        $this->assertSame('2025-03-26', $body['review_date']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testCreateReviewSendsNestedProductArrays(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->createReview([
            'order_id' => 'o1',
            'review_date' => '2025-01-01',
            'order_rating' => '5',
            'order_review' => 'Great',
            'product_rating' => [0 => ['3333' => '5']],
            'product_review' => [0 => ['3333' => 'Nice']],
            'media_files' => [0 => ['3333' => 'https://cdn.example/img.jpg']],
        ]);

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('5', $body['order_rating']);
        $this->assertSame('Great', $body['order_review']);
        $this->assertSame([0 => ['3333' => '5']], $body['product_rating']);
        $this->assertSame([0 => ['3333' => 'Nice']], $body['product_review']);
        $this->assertSame([0 => ['3333' => 'https://cdn.example/img.jpg']], $body['media_files']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testMerchantAddReviewSendsPostJsonWithNormalizedEmailAndProductId(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"id":1}'),
        );

        $result = $api->merchantAddReview([
            'email' => 'User@Example.COM',
            'product_id' => '42',
            'rating' => 5,
            'content' => 'Good',
        ]);

        $this->assertSame(['id' => 1], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/merchant_add_review', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('user@example.com', $body['email'], 'email is normalized to lowercase in the request body');
        $this->assertSame('42', $body['product_id']);
        $this->assertSame(5, $body['rating']);
        $this->assertSame('Good', $body['content']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testMerchantProSettingSendsPostJsonOmittingEmptyStrings(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->merchantProSetting([
            'product_feed_url' => ' https://feeds.example/p.xml ',
            'inventory_feed_url' => '',
            'api_key' => 'secret',
        ]);

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('https://feeds.example/p.xml', $body['product_feed_url']);
        $this->assertSame('secret', $body['api_key']);
        $this->assertArrayNotHasKey('inventory_feed_url', $body);
    }

    public function testGetProductReviewsThrowsWhenDomainKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config('', self::MOCK_API_KEY, self::MOCK_BASE_URL);
        $api = new ReviewsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Customer ID not provided.');

        $api->getProductReviews();
    }

    public function testGetProductReviewsThrowsWhenApiKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config(self::MOCK_DOMAIN, '', self::MOCK_BASE_URL);
        $api = new ReviewsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rest key not provided.');

        $api->getProductReviews();
    }

    public function testCreateReviewThrowsWhenDomainKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config('', self::MOCK_API_KEY, self::MOCK_BASE_URL);
        $api = new ReviewsApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Customer ID not provided.');

        $api->createReview(['order_id' => 'x', 'review_date' => '2025-01-01']);
    }

    public function testCreateReviewThrowsWhenOrderIdMissing(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(\TheMarketer\ApiClient\Exception\ValidationException::class);

        $api->createReview(['review_date' => '2025-01-01']);
    }

    public function testMerchantAddReviewThrowsWhenEmailInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->merchantAddReview([
            'email' => 'not-email',
            'product_id' => 1,
        ]);
    }
}
