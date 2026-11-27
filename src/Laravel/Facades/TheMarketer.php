<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use TheMarketer\ApiClient\Client;

/**
 * @method static \NotificationService\Sdk\Internal\SubscribersApi subscribers()
 * @method static \NotificationService\Sdk\Internal\OrdersApi orders()
 * @method static \NotificationService\Sdk\Internal\TransactionalsApi transactionals()
 * @method static \NotificationService\Sdk\Internal\ProductsApi products()
 * @method static \NotificationService\Sdk\Internal\CampaignsApi campaigns()
 * @method static \NotificationService\Sdk\Internal\LoyaltyApi loyalty()
 * @method static \NotificationService\Sdk\Internal\CouponsApi coupons()
 * @method static \NotificationService\Sdk\Internal\ReviewsApi reviews()
 * @method static \NotificationService\Sdk\Internal\MobilePushApi mobilePush()
 * @method static \NotificationService\Sdk\Internal\EventsApi events()
 * @method static \NotificationService\Sdk\Internal\ReportsApi reports()
 * @method static \NotificationService\Sdk\Internal\AccountApi account()
 * @method static \TheMarketer\ApiClient\Common\Config config()
 *
 * @see Client
 */
class TheMarketer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
