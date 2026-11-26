<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use TheMarketer\ApiClient\Common\AbstractPayload;

/**
 * Smoke: fiecare DTO acceptă un payload minimal valid prin {@see AbstractPayload::validateAndCreate()}.
 * {@see \TheMarketer\ApiClient\DTO\Subscribers\DeleteSubscriber} are teste dedicate în {@see \Tests\DeleteSubscriberTest}.
 */
final class DtoMinimalValidationTest extends PHPUnitTestCase
{
    /**
     * @return iterable<string, array{0: class-string<AbstractPayload>, 1: array<string, mixed>}>
     */
    public static function dtoPayloadProvider(): iterable
    {
        $orderLine = ['product_id' => 1, 'price' => 1.0, 'quantity' => 1, 'variation_sku' => 'v'];
        $saveOrder = [
            'number' => 1,
            'email_address' => 'buyer@example.com',
            'phone' => '+40123456789',
            'firstname' => 'A',
            'lastname' => 'B',
            'city' => 'C',
            'county' => 'RO',
            'address' => 'Str 1',
            'discount_value' => 0.0,
            'discount_code' => '-',
            'shipping' => 0.0,
            'tax' => 0.0,
            'total_value' => 10.0,
            'products' => [$orderLine],
        ];
        $saveOrderRetail = array_merge($saveOrder, [
            'store_id' => 1,
            'store_name' => 'S',
            'store_city' => 'City',
            'store_country' => 'RO',
        ]);
        $createProduct = [
            'id' => '1',
            'sku' => 'SKU-1',
            'name' => 'Product',
            'description' => 'D',
            'url' => 'https://shop.example/p/1',
            'main_image' => 'https://cdn.example/m.jpg',
            'category' => 'Cat',
            'brand' => 'B',
            'acquisition_price' => 10.0,
            'price' => 20.0,
            'sale_price' => '19.99',
            'availability' => 1,
            'stock' => 5,
            'media_gallery' => ['https://a.example/1.jpg', 'https://a.example/2.jpg'],
            'created_at' => '2025-01-01T00:00:00Z',
        ];
        $viewHome = [
            'did' => 'dev-1',
            'event' => 'view_homepage',
            'url' => 'https://example.com/',
            'http_user_agent' => 'Mozilla/5.0',
            'remote_addr' => '127.0.0.1',
        ];
        $campaignNested = [
            'type' => 'email',
            'mode' => 'regular',
            'sender' => ['name' => 'N', 'sender' => 'a@b.com', 'reply_to' => 'r@b.com'],
            'audience' => ['audience_type' => 'all', 'smart_sending' => false],
            'subject' => ['name' => 'S', 'subject_line' => 'Subj', 'preview_text' => 'Prev'],
            'content' => ['html' => '<p>x</p>'],
            'scheduling' => ['send_at' => '2025-06-01 12:00', 'use_optimal_time' => 0, 'optimize_for' => 'opening'],
            'tracking' => ['utm_campaign' => 'c', 'utm_medium' => 'email', 'utm_source' => 's'],
        ];

        yield 'Credentials\CheckCredentials' => [\TheMarketer\ApiClient\DTO\Credentials\CheckCredentials::class, ['k' => 'tk', 'r' => 'rk', 'u' => 'uid']];
        yield 'Credentials\DeliveryLogs' => [\TheMarketer\ApiClient\DTO\Credentials\DeliveryLogs::class, ['email' => 'u@gmail.com']];
        yield 'Credentials\EnteredAutomation' => [\TheMarketer\ApiClient\DTO\Credentials\EnteredAutomation::class, ['date' => '2025-03-15']];
        yield 'Credentials\ReferralLink' => [\TheMarketer\ApiClient\DTO\Credentials\ReferralLink::class, []];

        yield 'Campaigns\CampaignId' => [\TheMarketer\ApiClient\DTO\Campaigns\CampaignId::class, ['id' => '99']];
        yield 'Campaigns\LatestCampaign' => [\TheMarketer\ApiClient\DTO\Campaigns\LatestCampaign::class, []];
        yield 'Campaigns\ListCampaign' => [\TheMarketer\ApiClient\DTO\Campaigns\ListCampaign::class, []];
        yield 'Campaigns\CreateCampaignSender' => [\TheMarketer\ApiClient\DTO\Campaigns\CreateCampaignSender::class, ['name' => 'N', 'sender' => 'a@b.com', 'reply_to' => 'r@b.com']];
        yield 'Campaigns\CreateCampaignAudience' => [\TheMarketer\ApiClient\DTO\Campaigns\CreateCampaignAudience::class, ['audience_type' => 'all', 'smart_sending' => false]];
        yield 'Campaigns\CreateCampaignSubject' => [\TheMarketer\ApiClient\DTO\Campaigns\CreateCampaignSubject::class, ['name' => 'S', 'subject_line' => 'L', 'preview_text' => 'P']];
        yield 'Campaigns\CreateCampaignContent' => [\TheMarketer\ApiClient\DTO\Campaigns\CreateCampaignContent::class, ['html' => '<p>h</p>']];
        yield 'Campaigns\CreateCampaignScheduling' => [\TheMarketer\ApiClient\DTO\Campaigns\CreateCampaignScheduling::class, ['send_at' => '2025-06-01 12:00', 'use_optimal_time' => 0, 'optimize_for' => 'opening']];
        yield 'Campaigns\CreateCampaignTracking' => [\TheMarketer\ApiClient\DTO\Campaigns\CreateCampaignTracking::class, ['utm_campaign' => 'c', 'utm_medium' => 'm', 'utm_source' => 's']];
        yield 'Campaigns\CreateCampaign' => [\TheMarketer\ApiClient\DTO\Campaigns\CreateCampaign::class, $campaignNested];

        yield 'Coupons\SaveCoupon' => [\TheMarketer\ApiClient\DTO\Coupons\SaveCoupon::class, ['code' => 'X', 'type' => 'percent', 'value' => '10', 'expiration_date' => '2025-12-31']];

        yield 'Products\CreateProduct' => [\TheMarketer\ApiClient\DTO\Products\CreateProduct::class, $createProduct];
        yield 'Products\UpdateProduct' => [\TheMarketer\ApiClient\DTO\Products\UpdateProduct::class, ['id' => '1', 'sku' => 'S']];
        yield 'Products\SyncCategory' => [\TheMarketer\ApiClient\DTO\Products\SyncCategory::class, ['id' => '1', 'name' => 'N', 'hierarchy' => 'root', 'url' => 'https://x.com/c', 'image_url' => 'https://x.com/i.jpg']];
        yield 'Products\SyncBrand' => [\TheMarketer\ApiClient\DTO\Products\SyncBrand::class, ['id' => '1', 'name' => 'B', 'url' => 'https://b.com', 'image_url' => 'https://b.com/i.jpg']];

        yield 'Subscribers\EmailValidator' => [\TheMarketer\ApiClient\DTO\Subscribers\EmailValidator::class, ['email' => 'a@b.com']];
        yield 'Subscribers\SubscriberValidator' => [\TheMarketer\ApiClient\DTO\Subscribers\SubscriberValidator::class, ['email' => 'a@b.com']];
        yield 'Subscribers\AddSubscriberBulk' => [\TheMarketer\ApiClient\DTO\Subscribers\AddSubscriberBulk::class, ['subscribers' => [['email' => 'a@b.com']]]];
        yield 'Subscribers\AddSubscriberByPhone' => [\TheMarketer\ApiClient\DTO\Subscribers\AddSubscriberByPhone::class, ['phone' => '+40700000000']];
        yield 'Subscribers\UnsubscribedEmails' => [\TheMarketer\ApiClient\DTO\Subscribers\UnsubscribedEmails::class, ['date_from' => '2025-01-01', 'date_to' => '2025-01-31']];
        yield 'Subscribers\ListSubscribersDateRange' => [\TheMarketer\ApiClient\DTO\Subscribers\ListSubscribersDateRange::class, []];
        yield 'Subscribers\UpdateTags' => [\TheMarketer\ApiClient\DTO\Subscribers\UpdateTags::class, ['email' => 'a@b.com']];
        yield 'Subscribers\RemoveSubscriber' => [\TheMarketer\ApiClient\DTO\Subscribers\RemoveSubscriber::class, ['email' => 'a@b.com']];
        yield 'Subscribers\RemoveSubscriber (phone)' => [\TheMarketer\ApiClient\DTO\Subscribers\RemoveSubscriber::class, ['phone' => '+40700000000']];

        yield 'Events\SendCustomEvent' => [\TheMarketer\ApiClient\DTO\Events\SendCustomEvent::class, ['email' => 'a@b.com', 'event' => 'e']];
        yield 'Events\CustomEvent' => [\TheMarketer\ApiClient\DTO\Events\CustomEvent::class, [
            'did' => 'd', 'email' => 'a@b.com', 'event' => 'ev', 'url' => 'https://example.com/',
            'http_user_agent' => 'Mozilla/5.0', 'remote_addr' => '127.0.0.1',
        ]];
        yield 'Events\ViewHomepageEvent' => [\TheMarketer\ApiClient\DTO\Events\ViewHomepageEvent::class, $viewHome];
        yield 'Events\InitiateCheckoutEvent' => [\TheMarketer\ApiClient\DTO\Events\InitiateCheckoutEvent::class, array_merge($viewHome, ['event' => 'initiate_checkout'])];

        yield 'Events\SetEmailEvent' => [\TheMarketer\ApiClient\DTO\Events\SetEmailEvent::class, [
            'did' => 'd', 'event' => 'set_email', 'email_address' => 'a@b.com', 'firstname' => 'A', 'lastname' => 'B',
            'phone' => '+40', 'url' => 'https://example.com/', 'http_user_agent' => 'Mozilla/5.0', 'remote_addr' => '127.0.0.1',
        ]];
        yield 'Events\ViewProductEvent' => [\TheMarketer\ApiClient\DTO\Events\ViewProductEvent::class, [
            'did' => 'd', 'event' => 'view_product', 'product_id' => '1', 'url' => 'https://example.com/p',
            'http_user_agent' => 'Mozilla/5.0', 'remote_addr' => '127.0.0.1',
        ]];
        yield 'Events\ProductLineVariation' => [\TheMarketer\ApiClient\DTO\Events\ProductLineVariation::class, ['id' => '1', 'sku' => 'sku']];
        yield 'Events\ProductLineEvent' => [\TheMarketer\ApiClient\DTO\Events\ProductLineEvent::class, [
            'did' => 'd', 'event' => 'add_to_cart', 'product_id' => 1, 'quantity' => 1,
            'variation' => ['id' => '1', 'sku' => 'v'],
            'http_user_agent' => 'Mozilla/5.0', 'url' => 'https://example.com/', 'remote_addr' => '127.0.0.1',
        ]];
        yield 'Events\SearchEvent' => [\TheMarketer\ApiClient\DTO\Events\SearchEvent::class, [
            'did' => 'd', 'event' => 'search', 'search_term' => 'q', 'url' => 'https://example.com/s',
            'http_user_agent' => 'Mozilla/5.0', 'remote_addr' => '127.0.0.1',
        ]];
        yield 'Events\ServeJavascriptEvent' => [\TheMarketer\ApiClient\DTO\Events\ServeJavascriptEvent::class, ['k' => 'abcdef']];

        yield 'Transactionals\SendEmail' => [\TheMarketer\ApiClient\DTO\Transactionals\SendEmail::class, ['to' => 'a@b.com', 'subject' => 'S', 'body' => 'B']];
        yield 'Transactionals\SendSms' => [\TheMarketer\ApiClient\DTO\Transactionals\SendSms::class, ['to' => '+40700111222', 'content' => 'Hi']];
        yield 'Transactionals\SendEmailsBulk' => [\TheMarketer\ApiClient\DTO\Transactionals\SendEmailsBulk::class, [
            'emails' => [['to' => 'a@b.com', 'subject' => 'S', 'body' => 'B']],
        ]];

        yield 'Orders\UpdateOrderStatus' => [\TheMarketer\ApiClient\DTO\Orders\UpdateOrderStatus::class, ['order_number' => '1', 'order_status' => 'shipped']];
        yield 'Orders\UpdateFeedUrl' => [\TheMarketer\ApiClient\DTO\Orders\UpdateFeedUrl::class, ['url' => 'https://feed.example.com/x.xml']];
        yield 'Orders\SaveOrderProductLine' => [\TheMarketer\ApiClient\DTO\Orders\SaveOrderProductLine::class, $orderLine];
        yield 'Orders\SaveOrder' => [\TheMarketer\ApiClient\DTO\Orders\SaveOrder::class, $saveOrder];
        yield 'Orders\SaveOrderRetail' => [\TheMarketer\ApiClient\DTO\Orders\SaveOrderRetail::class, $saveOrderRetail];

        yield 'Reviews\AddReview' => [\TheMarketer\ApiClient\DTO\Reviews\AddReview::class, ['order_id' => '1', 'review_date' => '2025-01-01']];
        yield 'Reviews\ProductReviews' => [\TheMarketer\ApiClient\DTO\Reviews\ProductReviews::class, []];
        yield 'Reviews\MerchantAddReview' => [\TheMarketer\ApiClient\DTO\Reviews\MerchantAddReview::class, ['email' => 'a@b.com', 'product_id' => 1]];

        yield 'MerchantPro\MerchantProSettings' => [\TheMarketer\ApiClient\DTO\MerchantPro\MerchantProSettings::class, []];

        yield 'AppPush\SetMobilePushToken' => [\TheMarketer\ApiClient\DTO\AppPush\SetMobilePushToken::class, ['email' => 'a@b.com', 'token' => 'tok', 'type' => 'ios']];
        yield 'AppPush\RemoveMobilePushToken' => [\TheMarketer\ApiClient\DTO\AppPush\RemoveMobilePushToken::class, ['email' => 'a@b.com', 'type' => 'android']];

        yield 'Loyalty\ManageLoyaltyPoints' => [\TheMarketer\ApiClient\DTO\Loyalty\ManageLoyaltyPoints::class, ['email' => 'a@b.com', 'action' => 'increase', 'points' => 5]];

        yield 'Reports\EmailReports' => [\TheMarketer\ApiClient\DTO\Reports\EmailReports::class, ['type' => 'sent', 'start' => '2025-01-01', 'end' => '2025-01-31']];
        yield 'Reports\PushReports' => [\TheMarketer\ApiClient\DTO\Reports\PushReports::class, ['type' => 'sent', 'start' => '2025-01-01', 'end' => '2025-01-31']];
        yield 'Reports\SmsReports' => [\TheMarketer\ApiClient\DTO\Reports\SmsReports::class, ['type' => 'sent', 'start' => '2025-01-01', 'end' => '2025-01-31']];
        yield 'Reports\FormsReports' => [\TheMarketer\ApiClient\DTO\Reports\FormsReports::class, ['type' => 'total-impressions', 'start' => '2025-01-01', 'end' => '2025-01-31']];
        yield 'Reports\Audience' => [\TheMarketer\ApiClient\DTO\Reports\Audience::class, ['type' => 'total-subscribed-emails', 'start' => '2025-01-01', 'end' => '2025-01-31']];
    }

    #[DataProvider('dtoPayloadProvider')]
    public function testValidateAndCreateSucceeds(string $class, array $payload): void
    {
        $dto = $class::validateAndCreate($payload);

        $this->assertInstanceOf($class, $dto);
        $this->assertInstanceOf(AbstractPayload::class, $dto);
    }
}
