<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use NotificationService\Sdk\Internal\OrdersApi;
use TheMarketer\ApiClient\Common\Config;
use TheMarketer\ApiClient\DTO\Orders\SaveOrderProductLine;
use TheMarketer\ApiClient\Exception\CustomerNotFoundException;
use TheMarketer\ApiClient\Exception\ValidationException;

final class OrdersApiTest extends TestCase
{
    /**
     * @return array{0: OrdersApi, 1: \stdClass}
     */
    private function apiWithMockResponses(Response ...$responses): array
    {
        return $this->createApiWithMock(OrdersApi::class, ...$responses);
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalValidSaveOrderProducts(): array
    {
        return [
            [
                'product_id' => 100,
                'price' => 19.99,
                'quantity' => 1,
                'variation_sku' => 'VAR-1',
            ],
        ];
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testUpdateOrderStatusSendsPostWithJsonBody(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '1'),
        );

        $result = $api->updateOrderStatus('ORD-42', 'shipped');

        $this->assertSame([], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/api/v1/update_order_status', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(['k' => self::MOCK_API_KEY, 'u' => self::MOCK_DOMAIN], $query);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['order_number' => 'ORD-42', 'order_status' => 'shipped'], $body);
    }

    public function testUpdateOrderStatusSendsAnIntegerNumberAsGiven(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], '1'));

        $api->updateOrderStatus(1001, 'shipped');

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(1001, $body['order_number']);
    }

    public function testUpdateOrderStatusThrowsCustomerNotFoundForAnUnknownOrder(): void
    {
        [$api] = $this->apiWithMockResponses(
            new Response(404, ['Content-Type' => 'application/json'], '{"message":"Order not found."}'),
        );

        try {
            $api->updateOrderStatus('NOPE-1', 'shipped');
            $this->fail('A 404 must throw.');
        } catch (CustomerNotFoundException $e) {
            $this->assertSame('Order not found.', $e->getMessage());
            $this->assertSame(404, $e->getCode());
        }
    }

    public function testUpdateOrderStatusThrowsWhenNumberBlank(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->updateOrderStatus('  ', 'shipped');
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSaveOrderSendsPostJsonBody(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"saved":1}'),
        );

        $products = $this->minimalValidSaveOrderProducts();

        $result = $api->saveOrder([
            'number' => 1001,
            'email_address' => 'buyer@example.com',
            'phone' => '+40123456789',
            'firstname' => 'Ana',
            'lastname' => 'Ion',
            'city' => 'Bucharest',
            'county' => 'RO',
            'address' => 'Str. X 1',
            'discount_value' => '0',
            'discount_code' => 'NONE',
            'shipping' => '5',
            'tax' => '2',
            'total_value' => '100',
            'products' => $products,
        ]);

        $this->assertSame(['saved' => 1], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/save_order', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(1001, $body['number']);
        $this->assertSame('buyer@example.com', $body['email_address']);
        $this->assertSame($products, $body['products']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSaveDelegatesToSaveOrder(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"saved":1}'),
        );

        $products = $this->minimalValidSaveOrderProducts();

        $api->saveOrder([
            'number' => 1001,
            'email_address' => 'buyer@example.com',
            'phone' => '+40123456789',
            'firstname' => 'Ana',
            'lastname' => 'Ion',
            'city' => 'Bucharest',
            'county' => 'RO',
            'address' => 'Str. X 1',
            'discount_value' => '0',
            'discount_code' => 'NONE',
            'shipping' => '5',
            'tax' => '2',
            'total_value' => '100',
            'products' => $products,
        ]);

        $request = $this->lastRequest($container);
        $this->assertStringEndsWith('/save_order', $request->getUri()->getPath());
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSaveOrderRetailSendsPostToRetailPath(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $products = $this->minimalValidSaveOrderProducts();

        $api->saveOrderRetail([
            'number' => 9,
            'email_address' => 'r@example.com',
            'phone' => '+40',
            'firstname' => 'A',
            'lastname' => 'B',
            'city' => 'C',
            'county' => 'D',
            'address' => 'E',
            'discount_value' => 0,
            'discount_code' => 'NONE',
            'shipping' => '1',
            'tax' => '0',
            'total_value' => '10',
            'products' => $products,
            'store_id' => 1234,
            'store_name' => 'Store A',
            'store_city' => 'Bucharest',
            'store_country' => 'Romania',
        ]);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/save_order_retail', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(9, $body['number']);
        $this->assertSame(1234, $body['store_id']);
        $this->assertSame('Store A', $body['store_name']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testUpdateFeedUrlSendsPostJsonUrlOnly(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"updated":true}'),
        );

        $result = $api->updateFeedUrl('https://example.com/feed.xml');

        $this->assertSame(['updated' => true], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/update_feed_url', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['url' => 'https://example.com/feed.xml'], $body);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testUpdateFeedUrlSendsPostJsonWithType(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->updateFeedUrl('https://cdn.example.com/p.xml', 'product');

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([
            'url' => 'https://cdn.example.com/p.xml',
            'type' => 'product',
        ], $body);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testUpdateOrderFeedUrlSendsPostToOrderFeedPath(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{}'),
        );

        $api->updateOrderFeedUrl('https://orders.example.com/o.xml', 'category');

        $request = $this->lastRequest($container);
        $this->assertStringEndsWith('/update_order_feed_url', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([
            'url' => 'https://orders.example.com/o.xml',
            'type' => 'category',
        ], $body);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testGetEcommerceStatsSendsGet(): void
    {
        [$api, $container] = $this->apiWithMockResponses(
            new Response(200, [], '{"orders":0}'),
        );

        $result = $api->getEcommerceStats();

        $this->assertSame(['orders' => 0], $result);

        $request = $this->lastRequest($container);
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringEndsWith('/get-ecommerce-stats', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(self::MOCK_API_KEY, $query['k']);
        $this->assertSame(self::MOCK_DOMAIN, $query['u']);
    }

    public function testThrowsWhenDomainKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config('', self::MOCK_API_KEY, self::MOCK_BASE_URL);
        $api = new OrdersApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Customer ID not provided.');

        $api->getEcommerceStats();
    }

    public function testThrowsWhenApiKeyMissing(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200)]))]);
        $config = new Config(self::MOCK_DOMAIN, '', self::MOCK_BASE_URL);
        $api = new OrdersApi($this->makeApiContextWithMockClient($config, $client));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rest key not provided.');

        $api->getEcommerceStats();
    }

    public function testUpdateFeedUrlThrowsWhenUrlInvalid(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);

        $api->updateFeedUrl('not-a-valid-url');
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return array<string, mixed>
     */
    private function minimalOrder(array $products, array $extra = []): array
    {
        return [
            'number' => 'EU-1',
            'email_address' => 'buyer@example.com',
            'firstname' => 'Ana',
            'lastname' => 'Ion',
            'discount_value' => 0,
            'shipping' => 0,
            'tax' => 0,
            'total_value' => 10,
            'products' => $products,
        ] + $extra;
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSaveOrderKeepsLineVariation(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], 'true'));

        $api->saveOrder($this->minimalOrder([
            ['product_id' => 7, 'price' => 10, 'quantity' => 1, 'variation' => ['id' => 'gid-55', 'sku' => 'SKU-55']],
            ['product_id' => 8, 'price' => 5, 'quantity' => 2, 'variation' => ['id' => 56]],
            ['product_id' => 9, 'price' => 5, 'quantity' => 1],
        ]));

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['id' => 'gid-55', 'sku' => 'SKU-55'], $body['products'][0]['variation']);
        $this->assertSame(['id' => 56], $body['products'][1]['variation']);
        $this->assertArrayNotHasKey('variation', $body['products'][2]);
    }

    public function testSaveOrderProductLineVariationSurvivesValidateAndCreate(): void
    {
        $line = SaveOrderProductLine::validateAndCreate([
            'product_id' => 7, 'price' => 10, 'quantity' => 1, 'variation' => ['id' => 55, 'sku' => 'S'],
        ]);

        $this->assertSame(['id' => 55, 'sku' => 'S'], $line->toApiPayload()['variation']);
    }

    public function testSaveOrderRejectsVariationWithoutId(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('id');

        $api->saveOrder($this->minimalOrder([
            ['product_id' => 7, 'price' => 10, 'quantity' => 1, 'variation' => ['sku' => 'S']],
        ]));
    }

    public function testSaveOrderRejectsVariationThatIsNotAnArray(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('variation must be an array.');

        $api->saveOrder($this->minimalOrder([
            ['product_id' => 7, 'price' => 10, 'quantity' => 1, 'variation' => 'S'],
        ]));
    }

    public function testSaveOrderRejectsUnknownKeysOnOrderAndLine(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], 'true'));

        try {
            $api->saveOrder($this->minimalOrder([['product_id' => 7, 'price' => 10, 'quantity' => 1]], ['foo' => 1, 'bar' => 2]));
            $this->fail('An unknown order key must throw.');
        } catch (ValidationException $e) {
            $this->assertSame('unknown field(s): foo, bar', $e->getMessage());
        }

        try {
            $api->saveOrder($this->minimalOrder([['product_id' => 7, 'price' => 10, 'quantity' => 1, 'colour' => 'red']]));
            $this->fail('An unknown line key must throw.');
        } catch (ValidationException $e) {
            $this->assertSame('unknown field(s): colour', $e->getMessage());
        }

        $this->assertSame([], $container->requests);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSaveOrderSendsCurrencyAndRefundValue(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], 'true'));

        $api->saveOrder($this->minimalOrder(
            [['product_id' => 7, 'price' => 10, 'quantity' => 1]],
            ['currency' => 'RON', 'refund_value' => '12.5'],
        ));

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('RON', $body['currency']);
        $this->assertSame(12.5, $body['refund_value']);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function testSaveOrderSendsZeroRefundValueAndOmitsEmptyCurrency(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], 'true'));

        $api->saveOrder($this->minimalOrder(
            [['product_id' => 7, 'price' => 10, 'quantity' => 1]],
            ['currency' => '', 'refund_value' => 0],
        ));

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('currency', $body);
        $this->assertEquals(0, $body['refund_value']);
    }

    public function testSaveOrderOmitsCurrencyAndRefundValueWhenNotGiven(): void
    {
        [$api, $container] = $this->apiWithMockResponses(new Response(200, [], 'true'));

        $api->saveOrder($this->minimalOrder([['product_id' => 7, 'price' => 10, 'quantity' => 1]]));

        $body = json_decode((string) $this->lastRequest($container)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('currency', $body);
        $this->assertArrayNotHasKey('refund_value', $body);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidCurrencies(): iterable
    {
        yield 'lowercase' => ['ron'];
        yield 'two letters' => ['RO'];
        yield 'four letters' => ['EURO'];
        yield 'symbol' => ['€'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidCurrencies')]
    public function testSaveOrderRejectsInvalidCurrency(string $currency): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('currency must be a 3-letter uppercase ISO 4217 code.');

        $api->saveOrder($this->minimalOrder([['product_id' => 7, 'price' => 10, 'quantity' => 1]], ['currency' => $currency]));
    }

    public function testSaveOrderRejectsNegativeRefundValue(): void
    {
        [$api] = $this->apiWithMockResponses();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('refund_value');

        $api->saveOrder($this->minimalOrder([['product_id' => 7, 'price' => 10, 'quantity' => 1]], ['refund_value' => -1]));
    }
}
