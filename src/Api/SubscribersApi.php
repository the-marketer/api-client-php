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
     * @param list<array<string, mixed>> $subscribers
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
    public function addSubscriberBulk(array $subscribers): array
    {
        $dto = AddSubscriberBulk::validateAndCreate(['subscribers' => $subscribers]);

        return $this->context->rest->post('/add_subscriber_bulk', $dto->toApiPayload());
    }

    /**
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
     * `email` și/sau `phone`; contactul e căutat după email, apoi după telefon.
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
    public function removeSubscriber(?string $email = null, ?string $channels = null, ?string $phone = null): array
    {
        $dto = RemoveSubscriber::validateAndCreate([
            'email' => $email,
            'channels' => $channels,
            'phone' => $phone,
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
