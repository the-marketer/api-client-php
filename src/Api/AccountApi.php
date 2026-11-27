<?php

declare(strict_types=1);

namespace NotificationService\Sdk\Internal;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use TheMarketer\ApiClient\Common\AbstractApi;
use TheMarketer\ApiClient\DTO\Account\AllowedDomain;
use TheMarketer\ApiClient\DTO\Account\EmailSettings;
use TheMarketer\ApiClient\Exception\ApiException;
use TheMarketer\ApiClient\Exception\CustomerNotFoundException;
use TheMarketer\ApiClient\Exception\MethodNotAllowedException;
use TheMarketer\ApiClient\Exception\UnauthorizedException;
use TheMarketer\ApiClient\Exception\ValidationException;

/**
 * The account's own data and settings, read with the REST key: plan, allowed (tracking) domains, dashboard blocks,
 * email DNS setup. For the latest campaign see {@see CampaignsApi::getLatestCampaign()}.
 */
class AccountApi extends AbstractApi
{
    private const VALIDATION_FAILED = 422;

    /**
     * `GET /account`: `{id, name, domain, plan: {name, subscribers}, payment_status, pay_yearly, next_billing_date,
     * pays_through_shopify, campaigns_sent}`. `name` is the account's domain name; `plan.subscribers` is a string
     * or null; `next_billing_date` is `Y-m-d` or null. No user data and no keys.
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
    public function account(): array
    {
        return $this->context->rest->get('/account');
    }

    /**
     * `GET /allowed_domains`: the hosts the tracker accepts besides the account's own domain.
     *
     * @return list<string>
     *
     * @throws UnauthorizedException
     * @throws CustomerNotFoundException
     * @throws MethodNotAllowedException
     * @throws ApiException
     * @throws ValidationException
     * @throws JsonException
     * @throws GuzzleException
     */
    public function allowedDomains(): array
    {
        return $this->context->rest->get('/allowed_domains');
    }

    /**
     * `POST /allowed_domains`: adds a host (idempotent). The platform stores it normalised (lowercased, without
     * scheme, port or path; `www.` kept). Answers `{result: true, domains: list<string>}`, the list after the call.
     *
     * @param string $domain a host name or a URL whose host is read (`https://Shop.example.com/x` -> `shop.example.com`)
     *
     * @return array{result: bool, domains: list<string>}
     *
     * @throws ValidationException the value is not a valid host (checked before the request, as the platform does)
     * @throws UnauthorizedException
     * @throws CustomerNotFoundException
     * @throws MethodNotAllowedException
     * @throws ApiException
     * @throws JsonException
     * @throws GuzzleException
     */
    public function addAllowedDomain(string $domain): array
    {
        $dto = AllowedDomain::validateAndCreate(['domain' => $domain]);

        return $this->context->rest->post('/allowed_domains', $dto->toApiPayload());
    }

    /**
     * `POST /allowed_domains/delete`: removes a host, matched as given and normalised; a missing host is a no-op.
     * Answers `{result: true, domains: list<string>}`, the list after the call.
     *
     * @return array{result: bool, domains: list<string>}
     *
     * @throws ValidationException the value is not a valid host
     * @throws UnauthorizedException
     * @throws CustomerNotFoundException
     * @throws MethodNotAllowedException
     * @throws ApiException
     * @throws JsonException
     * @throws GuzzleException
     */
    public function deleteAllowedDomain(string $domain): array
    {
        $dto = AllowedDomain::validateAndCreate(['domain' => $domain]);

        return $this->context->rest->post('/allowed_domains/delete', $dto->toApiPayload());
    }

    /**
     * `GET /dashboard/account-reputation`: the dashboard's reputation block, `reputation`, `reputation_color`,
     * and for each of `soft_bounce`, `hard_bounce`, `complaint`, `unsubscribe` the rate (formatted string) plus
     * `<key>_text` (`Excellent` … `Blocked`) and `<key>_color`; `blocked` (bool).
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
    public function accountReputation(): array
    {
        return $this->context->rest->get('/dashboard/account-reputation');
    }

    /**
     * `GET /dashboard/product-health`: product feed counters, `{all, in_stock, without_image, broken_links, price_issues}`.
     *
     * @return array<string, int>
     *
     * @throws UnauthorizedException
     * @throws CustomerNotFoundException
     * @throws MethodNotAllowedException
     * @throws ApiException
     * @throws ValidationException
     * @throws JsonException
     * @throws GuzzleException
     */
    public function productHealth(): array
    {
        return $this->context->rest->get('/dashboard/product-health');
    }

    /**
     * `GET /settings/email`: a sender domain's DNS records, read-only: `{domain, dns_routing, dns, validated}`.
     * `dns_routing` is `legacy`, `migrating` or `herald`. When the DNS provider gives no answer the platform answers
     * 503, thrown as {@see ApiException} with code 503 (retry later).
     *
     * @param int|null $domain index of an extra sender domain; null = the account's main domain
     *
     * @return array<string, mixed>
     *
     * @throws ApiException 503 when the records are temporarily unavailable
     * @throws UnauthorizedException
     * @throws CustomerNotFoundException
     * @throws MethodNotAllowedException
     * @throws ValidationException
     * @throws JsonException
     * @throws GuzzleException
     */
    public function emailSettings(?int $domain = null): array
    {
        $dto = EmailSettings::validateAndCreate(['domain' => $domain]);

        return $this->context->rest->get('/settings/email', $dto->toApiPayload());
    }

    /**
     * `POST /validate-domain`: re-checks the account's sender domain DNS now. True when it is valid (the platform
     * then records the validation); false when the platform answers 422 ("Domain cannot be validated").
     * Any other error is thrown.
     *
     * @throws UnauthorizedException
     * @throws CustomerNotFoundException
     * @throws MethodNotAllowedException
     * @throws ApiException
     * @throws ValidationException
     * @throws JsonException
     * @throws GuzzleException
     */
    public function validateDomain(): bool
    {
        try {
            $this->context->rest->post('/validate-domain');
        } catch (ApiException $e) {
            if ($e->getCode() === self::VALIDATION_FAILED) {
                return false;
            }

            throw $e;
        }

        return true;
    }
}
