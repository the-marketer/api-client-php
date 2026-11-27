<?php

declare(strict_types=1);

namespace NotificationService\Sdk\Internal;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use TheMarketer\ApiClient\Common\AbstractApi;
use TheMarketer\ApiClient\DTO\Subscribers\AddSubscriberBulk;
use TheMarketer\ApiClient\DTO\Subscribers\AddSubscriberByPhone;
use TheMarketer\ApiClient\DTO\Subscribers\DeleteSubscriber;
use TheMarketer\ApiClient\DTO\Subscribers\ListSubscribersDateRange;
use TheMarketer\ApiClient\DTO\Subscribers\RemoveSubscriber;
use TheMarketer\ApiClient\DTO\Subscribers\EmailValidator;
use TheMarketer\ApiClient\DTO\Subscribers\SubscriberValidator;
use TheMarketer\ApiClient\DTO\Subscribers\UnsubscribedEmails;
use TheMarketer\ApiClient\DTO\Subscribers\UpdateTags;
use TheMarketer\ApiClient\Exception\ApiException;
use TheMarketer\ApiClient\Exception\CustomerNotFoundException;
use TheMarketer\ApiClient\Exception\MethodNotAllowedException;
use TheMarketer\ApiClient\Exception\UnauthorizedException;
use TheMarketer\ApiClient\Exception\ValidationException;

class SubscribersApi extends AbstractApi
{
    /**
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
    public function statusSubscriber(string $email): array
    {
        $dto = EmailValidator::validateAndCreate(['email' => $email]);

        return $this->context->rest->get('/status_subscriber', $dto->toApiPayload());
    }

    /**
     * @throws UnauthorizedException
     * @throws CustomerNotFoundException
     * @throws MethodNotAllowedException
     * @throws ApiException
     * @throws ValidationException
     * @throws JsonException
     * @throws GuzzleException
     */
    public function unsubscribedEmails(string $dateFrom, string $dateTo): array
    {
        $dto = UnsubscribedEmails::validateAndCreate([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        return $this->context->rest->get('/unsubscribed_emails', $dto->toApiPayload());
    }

    /**
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
    public function listUnsubscribed(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dto = ListSubscribersDateRange::validateAndCreate([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        return $this->context->rest->get('/unsubscribed_emails', $dto->toApiPayload());
    }

    /**
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
    public function listSubscribed(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dto = ListSubscribersDateRange::validateAndCreate([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        return $this->context->rest->get('/subscribed_emails', $dto->toApiPayload());
    }

    /**
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
    public function subscribersEvolution(): array
    {
        return $this->context->rest->get('/subscribers-evolution');
    }

    /**
     * `POST /add_subscriber` (queued). Same payload as {@see addSubscriber()}, including `source`.
     *
     * @param array<string, mixed> $payload
     *
     * @throws UnauthorizedException
     * @throws CustomerNotFoundException
     * @throws MethodNotAllowedException
     * @throws ApiException
     * @throws ValidationException
     * @throws JsonException
     * @throws GuzzleException
     */
    public function addSubscriberAsync(array $payload): array
    {
        $dto = SubscriberValidator::validateAndCreate($payload);

        return $this->context->rest->post('/add_subscriber', $dto->toApiPayload());
    }

    /**
     * @throws UnauthorizedException
     * @throws CustomerNotFoundException
     * @throws MethodNotAllowedException
     * @throws ApiException
     * @throws ValidationException
     * @throws GuzzleException
     * @throws JsonException
     */
    public function addSubscriberByPhone(string $phone, ?string $firstname = null, ?string $lastname = null): array
    {
        $dto = AddSubscriberByPhone::validateAndCreate([
            'phone' => $phone,
            'firstname' => $firstname,
            'lastname' => $lastname,
        ]);

        return $this->context->rest->post('/add_subscriber_by_phone', $dto->toApiPayload());
    }

    /**
     * `POST /add_subscriber_bulk`: the body is the list of rows (each validated like {@see addSubscriber()}).
     * Answers `{result, queued, rejected, rejected_rows}`.
     *
     * @param list<array<string, mixed>> $subscribers
     * @param string|null                $source      `connector`: the changes are not echoed back to the connector's
     *                                                own webhooks; sent once, in the query string
     *
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
    public function addSubscriberBulk(array $subscribers, ?string $source = null): array
    {
        $dto = AddSubscriberBulk::validateAndCreate(['subscribers' => $subscribers, 'source' => $source]);

        return $this->context->rest->post('/add_subscriber_bulk', $dto->toApiPayload(), $dto->toQuery());
    }

    /**
     * `POST /add_subscriber_sync`. Payload keys: see {@see SubscriberValidator}; `source` = `connector` means the
     * change is not echoed back to the connector's own webhooks (the platform's only recognised value).
     *
     * @param array<string, mixed> $payload
     *
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
    public function addSubscriber(array $payload): array
    {
        $dto = SubscriberValidator::validateAndCreate($payload);

        return $this->context->rest->post('/add_subscriber_sync', $dto->toApiPayload());
    }

    /**
     * @param array<string, mixed> $payload `email` și/sau `phone`
     *
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
    public function deleteSubscriber(array $payload): array
    {
        $dto = DeleteSubscriber::validateAndCreate($payload);

        return $this->context->rest->post('/delete_subscriber', $dto->toApiPayload());
    }

    /**
     * @param string|null $channels comma-separated channels to unsubscribe from (`email`, `sms`, `push`, `loyalty`); all when null
     * @param string|null $source   `connector`: the unsubscribe is not echoed back to the connector's own webhooks
     *
     * @return array<string, mixed>
     *
     * @throws UnauthorizedException
     * @throws CustomerNotFoundException
     * @throws MethodNotAllowedException
     * @throws ApiException
     * @throws ValidationException
     * @throws GuzzleException
     * @throws JsonException
     */
    public function removeSubscriber(string $email, ?string $channels = null, ?string $source = null): array
    {
        $dto = RemoveSubscriber::validateAndCreate([
            'email' => $email,
            'channels' => $channels,
            'source' => $source,
        ]);

        return $this->context->rest->post('/remove_subscriber', $dto->toApiPayload());
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function anonymizeEmail(string $email): array
    {
        $dto = EmailValidator::validateAndCreate(['email' => $email]);

        return $this->context->rest->post('/anonymize-email', $dto->toApiPayload());
    }

    /**
     * @param list<string|int> $addTags
     * @param list<string|int> $removeTags
     *
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
    public function updateTags(
        string $email,
        array $addTags = [],
        array $removeTags = [],
        ?int $overwriteExisting = null,
    ): array
    {
        $dto = UpdateTags::validateAndCreate([
            'email' => $email,
            'add_tags' => $addTags,
            'remove_tags' => $removeTags,
            'overwrite_existing' => $overwriteExisting,
        ]);

        return $this->context->rest->post('/update-tags', $dto->toApiPayload());
    }
}
