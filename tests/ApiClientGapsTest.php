<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use NotificationService\Sdk\Internal\EventsApi;
use NotificationService\Sdk\Internal\OrdersApi;
use TheMarketer\ApiClient\DTO\Orders\SaveOrder;
use TheMarketer\ApiClient\Exception\ValidationException;

/**
 * Orders and tracking events, as the platform accepts them.
 */
final class ApiClientGapsTest extends TestCase
{
    /** @return array<string, mixed> */
    private function order(array $override = []): array
    {
        return array_merge([
            'number' => 1001,
            'firstname' => 'Ana',
            'lastname' => 'Ion',
            'email_address' => 'buyer@example.com',
            'discount_value' => 0,
            'shipping' => 5,
            'tax' => 0,
            'total_value' => 100,
            'products' => [['product_id' => 1, 'price' => 10, 'quantity' => 1, 'variation_sku' => 'S']],
        ], $override);
    }

    /** @return array<string, mixed> */
    private function event(array $override = []): array
    {
        return array_merge([
            'did' => 'd', 'event' => 'e', 'url' => 'https://example.com/',
            'http_user_agent' => 'UA', 'remote_addr' => '203.0.113.1',
        ], $override);
    }

    public function testSaveOrderAcceptsDraftNumberAndMinimalContact(): void
    {
        $payload = SaveOrder::validateAndCreate($this->order([
            'number' => 'D1',
            'email_address' => null,
            'phone' => '+40123456789',
            'order_status' => 'draft',
        ]))->toApiPayload();

        $this->assertSame('D1', $payload['number']);
        // the platform reads both keys unguarded, so the empty one is still sent
        $this->assertSame('', $payload['email_address']);
        $this->assertSame('draft', $payload['order_status']);
        $this->assertSame('+40123456789', $payload['phone']);
        foreach (['city', 'county', 'address', 'discount_code'] as $omitted) {
            $this->assertArrayNotHasKey($omitted, $payload);
        }
    }

    public function testWrongOrMissingTypesGiveReadableMessages(): void
    {
        try {
            SaveOrder::validateAndCreate($this->order(['firstname' => ['Ana']]));
            $this->fail('an array firstname should be rejected');
        } catch (ValidationException $e) {
            $this->assertSame('firstname: must be of type string, array given.', $e->getMessage());
        }

        $order = $this->order();
        unset($order['lastname']);
        try {
            SaveOrder::validateAndCreate($order);
            $this->fail('a missing lastname should be rejected');
        } catch (ValidationException $e) {
            $this->assertSame('lastname: This value is required.', $e->getMessage());
        }
    }

    public function testSaveOrderRequiresEmailOrPhone(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('email_address or phone is required.');

        SaveOrder::validateAndCreate($this->order(['email_address' => '']));
    }

    public function testSaveOrderNumberCanBeAnyNonEmptyText(): void
    {
        foreach (['EU-1001', '#1001', 'D1', 1001, '007', '1001'] as $number) {
            $this->assertSame($number, SaveOrder::validateAndCreate($this->order(['number' => $number]))->toApiPayload()['number']);
        }
    }

    public function testSaveOrderRejectsBlankOrZeroNumber(): void
    {
        foreach (['', '  ', '0', 0] as $bad) {
            try {
                SaveOrder::validateAndCreate($this->order(['number' => $bad]));
                $this->fail('number ' . var_export($bad, true) . ' should be rejected');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testSaveOrderSendsPhysicalStoreWhenGiven(): void
    {
        $payload = SaveOrder::validateAndCreate($this->order([
            'store_id' => 'B-12',
            'store_name' => ' Mall Vitan ',
            'store_city' => 'Bucuresti',
            'store_country' => 'RO',
        ]))->toApiPayload();

        $this->assertSame('B-12', $payload['store_id']);
        $this->assertSame('Mall Vitan', $payload['store_name']);
        $this->assertSame('Bucuresti', $payload['store_city']);
        $this->assertSame('RO', $payload['store_country']);

        $online = SaveOrder::validateAndCreate($this->order())->toApiPayload();
        foreach (['store_id', 'store_name', 'store_city', 'store_country'] as $omitted) {
            $this->assertArrayNotHasKey($omitted, $online);
        }
    }

    public function testOrderLineAcceptsSkuAsProductIdAndSalePrice(): void
    {
        $payload = SaveOrder::validateAndCreate($this->order([
            'products' => [
                ['product_id' => 'SKU-00123', 'price' => '20', 'sale_price' => '15.5', 'quantity' => 2],
                ['product_id' => '00123', 'price' => 10, 'quantity' => 1],
            ],
        ]))->toApiPayload();

        $this->assertSame('SKU-00123', $payload['products'][0]['product_id']);
        $this->assertSame(15.5, $payload['products'][0]['sale_price']);
        // a SKU that looks numeric keeps its zeros: the platform matches it as text
        $this->assertSame('00123', $payload['products'][1]['product_id']);
        $this->assertArrayNotHasKey('sale_price', $payload['products'][1]);
    }

    public function testOrderLineRejectsBlankOrZeroProductId(): void
    {
        foreach (['', '  ', '0', 0] as $bad) {
            try {
                SaveOrder::validateAndCreate($this->order(['products' => [['product_id' => $bad, 'price' => 1, 'quantity' => 1]]]));
                $this->fail('product_id ' . var_export($bad, true) . ' should be rejected');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testOrderEventCarriesOrderAndTrackingFields(): void
    {
        [$api, $bucket] = $this->createApiWithMock(EventsApi::class, new Response(200, [], '{}'));
        $api->order($this->event(['event' => '__sm__order', 'referrer' => 'https://google.com/']) + $this->order(['number' => 'EU-1']));

        $body = json_decode((string) $this->lastRequest($bucket)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('__sm__order', $body['event']);
        $this->assertSame('EU-1', $body['number']);
        $this->assertSame('203.0.113.1', $body['remote_addr']);
        $this->assertSame('https://google.com/', $body['referrer']);
        $this->assertCount(1, $body['products']);
    }

    public function testSaveOrderWrongTypeIsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        SaveOrder::validateAndCreate($this->order(['firstname' => ['x']]));
    }

    public function testSaveOrderSendsOnlyFilledOptionalFields(): void
    {
        [$api, $bucket] = $this->createApiWithMock(OrdersApi::class, new Response(200, [], '{}'));
        $api->saveOrder($this->order(['discount_code' => '', 'city' => 'Cluj']));

        $body = json_decode((string) $this->lastRequest($bucket)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Cluj', $body['city']);
        $this->assertArrayNotHasKey('discount_code', $body);
    }

    public function testSaveOrderBlankOptionalTextIsLeftOut(): void
    {
        $payload = SaveOrder::validateAndCreate($this->order(['city' => '  ', 'discount_code' => ' ', 'address' => ' Str. 1 ']))->toApiPayload();

        $this->assertArrayNotHasKey('city', $payload);
        $this->assertArrayNotHasKey('discount_code', $payload);
        $this->assertSame('Str. 1', $payload['address']);
    }

    public function testOrderLineWithoutSkuIsAccepted(): void
    {
        $payload = SaveOrder::validateAndCreate($this->order([
            'products' => [['product_id' => 1, 'price' => 10, 'quantity' => 1, 'variation_sku' => '']],
        ]))->toApiPayload();

        $this->assertSame([['product_id' => 1, 'price' => 10.0, 'quantity' => 1]], $payload['products']);
    }

    public function testOrderLinesWithGapsInTheirKeysGoAsAList(): void
    {
        $line = ['product_id' => 1, 'price' => 10, 'quantity' => 1];
        $payload = SaveOrder::validateAndCreate($this->order(['products' => [0 => $line, 2 => $line]]))->toApiPayload();

        $this->assertTrue(array_is_list($payload['products']));
        $this->assertCount(2, $payload['products']);
    }

    public function testEventsCarryReferrer(): void
    {
        [$api, $bucket] = $this->createApiWithMock(EventsApi::class, new Response(200, [], '{}'));
        $api->viewHomepage($this->event(['referrer' => 'https://google.com/']));

        $body = json_decode((string) $this->lastRequest($bucket)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('https://google.com/', $body['referrer']);
    }

    public function testGenericPageViewUsesTrackingEndpoint(): void
    {
        [$api, $bucket] = $this->createApiWithMock(EventsApi::class, new Response(200, [], '{}'));
        $api->genericPageView($this->event(['event' => '__generic_page_view']));

        $request = $this->lastRequest($bucket);
        $this->assertStringEndsWith('/t/r', $request->getUri()->getPath());
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('__generic_page_view', $body['event']);
    }

    public function testWishlistOnlyNeedsProductId(): void
    {
        [$api, $bucket] = $this->createApiWithMock(EventsApi::class, new Response(200, [], '{}'));
        $api->addToWishlist($this->event(['event' => 'add_to_wishlist', 'product_id' => '101']));

        $body = json_decode((string) $this->lastRequest($bucket)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('101', $body['product_id']);
        $this->assertArrayNotHasKey('quantity', $body);
        $this->assertArrayNotHasKey('variation', $body);
    }

    public function testSetEmailPhoneIsOptional(): void
    {
        [$api, $bucket] = $this->createApiWithMock(EventsApi::class, new Response(200, [], '{}'));
        $api->setEmail($this->event(['event' => '__sm__set_email', 'email_address' => 'a@example.com']));

        $body = json_decode((string) $this->lastRequest($bucket)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        foreach (['phone', 'firstname', 'lastname'] as $absent) {
            $this->assertArrayNotHasKey($absent, $body);
        }
    }

    public function testProductIdAcceptsTextAndNumber(): void
    {
        [$api, $bucket] = $this->createApiWithMock(EventsApi::class, new Response(200, [], '{}'), new Response(200, [], '{}'));
        $api->viewProduct($this->event(['product_id' => 55]));
        $api->addToCart($this->event(['product_id' => 'abc-9', 'quantity' => 1, 'variation' => ['id' => 'v', 'sku' => 's']]));

        $this->assertCount(2, $bucket->requests);
    }

    public function testViewCategoryEvent(): void
    {
        [$api, $bucket] = $this->createApiWithMock(EventsApi::class, new Response(200, [], '{}'));
        $api->viewCategory($this->event(['event' => '__sm__view_category', 'category' => 'Men|Shoes']));

        $body = json_decode((string) $this->lastRequest($bucket)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Men|Shoes', $body['category']);
    }

    public function testApiContextUsesTheInjectedHttpClient(): void
    {
        $seen = [];
        $mock = new \GuzzleHttp\Handler\MockHandler([static function ($request) use (&$seen) {
            $seen[] = (string) $request->getUri();

            return new Response(200, [], '{}');
        }]);
        $context = new \TheMarketer\ApiClient\Common\ApiContext(
            new \TheMarketer\ApiClient\Common\Config('c', 'k', 'https://api.example.test'),
            0,
            new Client(['handler' => $mock]),
        );
        $context->rest->get('/ping');

        $this->assertCount(1, $seen);
        $this->assertStringContainsString('api.example.test', $seen[0]);
    }

    public function testSubscriberNameIsSentAsIs(): void
    {
        $payload = \TheMarketer\ApiClient\DTO\Subscribers\SubscriberValidator::validateAndCreate(['email' => 'a@example.com', 'name' => 'Ana Maria Ionescu'])->toApiPayload();

        $this->assertSame('Ana Maria Ionescu', $payload['name']);
        $this->assertArrayNotHasKey('firstname', $payload);
    }
}
