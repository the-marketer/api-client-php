<?php

declare(strict_types=1);

namespace Tests\Unit;

use NotificationService\Sdk\Internal\AccountApi;
use NotificationService\Sdk\Internal\CampaignsApi;
use NotificationService\Sdk\Internal\CouponsApi;
use NotificationService\Sdk\Internal\EventsApi;
use NotificationService\Sdk\Internal\LoyaltyApi;
use NotificationService\Sdk\Internal\MobilePushApi;
use NotificationService\Sdk\Internal\OrdersApi;
use NotificationService\Sdk\Internal\ProductsApi;
use NotificationService\Sdk\Internal\ReportsApi;
use NotificationService\Sdk\Internal\ReviewsApi;
use NotificationService\Sdk\Internal\SubscribersApi;
use NotificationService\Sdk\Internal\TransactionalsApi;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use TheMarketer\ApiClient\Client;

final class ClientAccessorsTest extends PHPUnitTestCase
{
    public function testAccessorsReturnApiInstances(): void
    {
        $client = new Client([
            'customerId' => 'c',
            'restKey' => 'k',
        ]);

        $this->assertInstanceOf(SubscribersApi::class, $client->subscribers());
        $this->assertInstanceOf(OrdersApi::class, $client->orders());
        $this->assertInstanceOf(TransactionalsApi::class, $client->transactionals());
        $this->assertInstanceOf(ProductsApi::class, $client->products());
        $this->assertInstanceOf(CampaignsApi::class, $client->campaigns());
        $this->assertInstanceOf(LoyaltyApi::class, $client->loyalty());
        $this->assertInstanceOf(CouponsApi::class, $client->coupons());
        $this->assertInstanceOf(ReviewsApi::class, $client->reviews());
        $this->assertInstanceOf(MobilePushApi::class, $client->mobilePush());
        $this->assertInstanceOf(EventsApi::class, $client->events());
        $this->assertInstanceOf(ReportsApi::class, $client->reports());
        $this->assertInstanceOf(AccountApi::class, $client->account());

        $this->assertSame($client->subscribers(), $client->subscribers());
    }
}
