<?php

declare(strict_types=1);

namespace NotificationService\Sdk\Internal;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use TheMarketer\ApiClient\Common\AbstractApi;
use TheMarketer\ApiClient\DTO\Events\CustomEvent;
use TheMarketer\ApiClient\DTO\Events\InitiateCheckoutEvent;
use TheMarketer\ApiClient\DTO\Events\OrderEvent;
use TheMarketer\ApiClient\DTO\Events\ProductLineEvent;
use TheMarketer\ApiClient\DTO\Events\SearchEvent;
use TheMarketer\ApiClient\DTO\Events\SendCustomEvent;
use TheMarketer\ApiClient\DTO\Events\ServeJavascriptEvent;
use TheMarketer\ApiClient\DTO\Events\SetEmailEvent;
use TheMarketer\ApiClient\DTO\Events\ViewCategoryEvent;
use TheMarketer\ApiClient\DTO\Events\ViewHomepageEvent;
use TheMarketer\ApiClient\DTO\Events\ViewProductEvent;
use TheMarketer\ApiClient\DTO\Events\WishlistEvent;
use TheMarketer\ApiClient\Exception\ApiException;
use TheMarketer\ApiClient\Exception\CustomerNotFoundException;
use TheMarketer\ApiClient\Exception\MethodNotAllowedException;
use TheMarketer\ApiClient\Exception\UnauthorizedException;
use TheMarketer\ApiClient\Exception\ValidationException;

class EventsApi extends AbstractApi
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws UnauthorizedException
     * @throws CustomerNotFoundException
     * @throws MethodNotAllowedException
     * @throws ApiException
     * @throws ValidationException
     * @throws JsonException
     * @throws GuzzleException
     */
    public function sendCustomApi(array $payload): array
    {
        $dto = SendCustomEvent::validateAndCreate($payload);
        return $this->context->rest->post('/custom_events', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function sendCustom(array $payload): array
    {
        $dto = CustomEvent::validateAndCreate($payload);

        return $this->context->tracking->post('/t/r', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function viewHomepage(array $payload): array
    {
        $dto = ViewHomepageEvent::validateAndCreate($payload);

        return $this->context->tracking->post('/t/r', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function viewCategory(array $payload): array
    {
        $dto = ViewCategoryEvent::validateAndCreate($payload);

        return $this->context->tracking->post('/t/r', $dto->toApiPayload());
    }

    /**
     * The `__sm__order` event: order fields as in `OrdersApi::saveOrder()`, plus the tracking fields.
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    public function order(array $payload): array
    {
        $dto = OrderEvent::validateAndCreate($payload);

        return $this->context->tracking->post('/t/r', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function genericPageView(array $payload): array
    {
        $dto = ViewHomepageEvent::validateAndCreate($payload);

        return $this->context->tracking->post('/t/r', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function setEmail(array $payload): array
    {
        $dto = SetEmailEvent::validateAndCreate($payload);

        return $this->context->tracking->post('/t/r', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function viewProduct(array $payload): array
    {
        $dto = ViewProductEvent::validateAndCreate($payload);

        return $this->context->tracking->post('/t/r', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function addToCart(array $payload): array
    {
        $dto = ProductLineEvent::validateAndCreate($payload);

        return $this->context->tracking->post('/t/r', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function removeFromCart(array $payload): array
    {
        $dto = ProductLineEvent::validateAndCreate($payload);

        return $this->context->tracking->post('/t/r', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function addToWishlist(array $payload): array
    {
        $dto = WishlistEvent::validateAndCreate($payload);

        return $this->context->tracking->post('/t/r', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function removeFromWishlist(array $payload): array
    {
        $dto = WishlistEvent::validateAndCreate($payload);

        return $this->context->tracking->post('/t/r', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function initiateCheckout(array $payload): array
    {
        $dto = InitiateCheckoutEvent::validateAndCreate($payload);

        return $this->context->tracking->post('/t/r', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function search(array $payload): array
    {
        $dto = SearchEvent::validateAndCreate($payload);
        return $this->context->tracking->post('/t/r', $dto->toApiPayload());
    }

    /**
     * @param string $trackingKey maps to validated `k` (6–20 chars), same as backend `ServeJavascriptFile`
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    public function serveJavascript(string $trackingKey): array
    {
        $dto = ServeJavascriptEvent::validateAndCreate(['k' => $trackingKey]);

        return $this->context->tracking->get('/t/j/' . $trackingKey, $dto->toApiPayload());
    }
}
