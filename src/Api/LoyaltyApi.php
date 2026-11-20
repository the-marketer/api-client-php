<?php

declare(strict_types=1);

namespace NotificationService\Sdk\Internal;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use TheMarketer\ApiClient\Common\AbstractApi;
use TheMarketer\ApiClient\DTO\Loyalty\LoyaltyInfo;
use TheMarketer\ApiClient\DTO\Loyalty\ManageLoyaltyPoints;
use TheMarketer\ApiClient\Exception\ApiException;
use TheMarketer\ApiClient\Exception\CustomerNotFoundException;
use TheMarketer\ApiClient\Exception\MethodNotAllowedException;
use TheMarketer\ApiClient\Exception\UnauthorizedException;
use TheMarketer\ApiClient\Exception\ValidationException;

class LoyaltyApi extends AbstractApi
{

    /**
     * Looks up the contact by `email` or, when no email is given, by `phone`.
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
    public function getInfo(?string $email = null, ?string $phone = null): array
    {
        $dto = LoyaltyInfo::validateAndCreate(['email' => $email, 'phone' => $phone]);

        return $this->context->rest->get('/loyalty_info', $dto->toApiPayload());
    }

    /**
     * Identifies the contact by `email` or, when no email is given, by `phone`.
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
    public function managePoints(?string $email, string $action, int $points, ?string $phone = null): array
    {
        $dto = ManageLoyaltyPoints::validateAndCreate([
            'email' => $email,
            'phone' => $phone,
            'action' => $action,
            'points' => $points,
        ]);

        return $this->context->rest->post('/manage_loyalty_points', $dto->toApiPayload());
    }
}
