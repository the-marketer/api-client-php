<?php

declare(strict_types=1);

namespace NotificationService\Sdk\Internal;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use TheMarketer\ApiClient\Common\AbstractApi;
use TheMarketer\ApiClient\DTO\Orders\SaveOrder;
use TheMarketer\ApiClient\DTO\Orders\SaveOrderRetail;
use TheMarketer\ApiClient\DTO\Orders\UpdateFeedUrl;
use TheMarketer\ApiClient\DTO\Orders\UpdateOrderStatus;
use TheMarketer\ApiClient\Exception\ApiException;
use TheMarketer\ApiClient\Exception\CustomerNotFoundException;
use TheMarketer\ApiClient\Exception\MethodNotAllowedException;
use TheMarketer\ApiClient\Exception\UnauthorizedException;
use TheMarketer\ApiClient\Exception\ValidationException;

class OrdersApi extends AbstractApi
{
    /**
     * `POST /update_order_status` with a JSON body `{order_number, order_status}`. The platform queues the change and
     * answers `true` (decoded here as `[]`).
     *
     * Since 0.2.0 this is a POST (it was a GET, which the platform still serves but answers `true` even for an
     * unknown order). On POST the platform checks the order first: an unknown `order_number` answers 404, which the
     * REST layer throws as {@see CustomerNotFoundException} with the message `Order not found.` (the 404 mapping is
     * shared by every endpoint). A field error answers 422 ({@see ApiException}, code 422).
     *
     * @param int|string $order_number the order's `number`, as sent to {@see saveOrder()} (compared as text)
     *
     * @return array<string, mixed>
     *
     * @throws CustomerNotFoundException the order does not exist
     * @throws UnauthorizedException
     * @throws MethodNotAllowedException
     * @throws ApiException
     * @throws ValidationException
     * @throws GuzzleException
     * @throws JsonException
     */
    public function updateOrderStatus(int|string $order_number, string $order_status): array
    {
        $dto = UpdateOrderStatus::validateAndCreate([
            'order_number' => $order_number,
            'order_status' => $order_status,
        ]);

        return $this->context->rest->post('/update_order_status', $dto->toApiPayload());
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     * @throws JsonException
     * @throws GuzzleException
     */
    public function saveOrder(array $payload): array
    {
        $dto = SaveOrder::validateAndCreate($payload);

        return $this->context->rest->post('/save_order', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function saveOrderRetail(array $payload): array
    {
        $dto = SaveOrderRetail::validateAndCreate($payload);

        return $this->context->rest->post('/save_order_retail', $dto->toApiPayload());
    }

    /**
     * @return array<string, mixed>
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    public function updateFeedUrl(string $url, ?string $type = null): array
    {
        $dto = UpdateFeedUrl::validateAndCreate([
            'url' =>  $url,
            'type' => $type
        ]);

        return $this->context->rest->post('/update_feed_url', $dto->toApiPayload());
    }

    /**
     * @return array<string, mixed>
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    public function updateOrderFeedUrl(string $url, ?string $type = null): array
    {
        $dto = UpdateFeedUrl::validateAndCreate([
            'url' =>  $url,
            'type' => $type
        ]);

        return $this->context->rest->post('/update_order_feed_url', $dto->toApiPayload());
    }

    /**
     * @return array<string, mixed>
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getEcommerceStats(): array
    {
        return $this->context->rest->get('/get-ecommerce-stats');
    }
}
