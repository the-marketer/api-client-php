<?php

declare(strict_types=1);

namespace NotificationService\Sdk\Internal;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use TheMarketer\ApiClient\Common\AbstractApi;
use TheMarketer\ApiClient\DTO\Campaigns\CampaignId;
use TheMarketer\ApiClient\DTO\Campaigns\CreateCampaign;
use TheMarketer\ApiClient\DTO\Campaigns\LatestCampaign;
use TheMarketer\ApiClient\DTO\Campaigns\ListCampaign;
use TheMarketer\ApiClient\Exception\ApiException;
use TheMarketer\ApiClient\Exception\CustomerNotFoundException;
use TheMarketer\ApiClient\Exception\MethodNotAllowedException;
use TheMarketer\ApiClient\Exception\UnauthorizedException;
use TheMarketer\ApiClient\Exception\ValidationException;

class CampaignsApi extends AbstractApi
{
    private const CAMPAIGNS_ENDPOINT = '/campaigns';

    /**
     * `POST /campaigns/list`: one page of campaigns (deleted ones are never listed).
     *
     * `$payload` keys, all optional:
     * - `filters` (array, or a JSON object string, sent as an object):
     *   - `search` (string): part of the campaign name;
     *   - `type` (string): e.g. `email`, `sms`, `push`;
     *   - `start_date` (date): `sent_at` from;
     *   - `status` (string or list of strings): e.g. `['sent']`; `scheduled` is a pseudo-status for
     *     future campaigns (send_at in the future, not draft), which also sorts by `send_at` ascending;
     *   - `send_at_from` / `send_at_to` (ISO date or date-time): `send_at` range; a bare date as `send_at_to`
     *     covers the whole day.
     *   Unknown filter keys are ignored by the platform.
     * - `search`, `type`, `start_date` at the top level: moved into `filters` (the platform reads them only there).
     * - `page` (int >= 1, default 1) and `limit` (int >= 1, default 20; the platform caps it at 100): ints or numeric strings.
     *
     * Answers `{data: [...], total, page, limit, pages}`; each campaign has `_id`, `status`, `name`, `type`,
     * `variant`, `send_at`, `sent_at`, `num_sent`, `recipients`, `segment {id, name}`, `subject` and more.
     * Sorted by `sent_at` descending (by `send_at` ascending when `scheduled` is asked for).
     *
     * @param array{filters?: array<string, mixed>|string|null, search?: string|null, type?: string|null, start_date?: string|null, page?: int|string|null, limit?: int|string|null} $payload
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
    public function list(array $payload = []): array
    {
        $dto = ListCampaign::validateAndCreate($payload);

        return $this->context->rest->post(self::CAMPAIGNS_ENDPOINT . '/list', $dto->toApiPayload());
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
    public function create(array $payload): array
    {
        $dto = CreateCampaign::validateAndCreate($payload);

        return $this->context->rest->post(self::CAMPAIGNS_ENDPOINT . '/create', $dto->toApiPayload());
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
    public function getEmailReport(string $id): array
    {
        $dto = CampaignId::validateAndCreate(['id' => $id]);

        return $this->context->rest->get(self::CAMPAIGNS_ENDPOINT . '/' . $dto->id . '/email/get-report');
    }

    /**
     * `GET /get-latest-campaign`: the latest sent campaign, `{id, type, status, subject_line, sent_at, audience_type,
     * audience_segment, audience_name, stats}` (every key null when the account has sent none). With `$limit`, a list
     * of up to 10 such campaigns (the platform reads `limit` only as "give me a list").
     *
     * @return array<string, mixed>|list<array<string, mixed>>
     *
     * @throws UnauthorizedException
     * @throws CustomerNotFoundException
     * @throws MethodNotAllowedException
     * @throws ApiException
     * @throws ValidationException
     * @throws JsonException
     * @throws GuzzleException
     */
    public function getLatestCampaign(?int $limit = null): array
    {
        $dto = LatestCampaign::validateAndCreate(['limit' => $limit]);

        return $this->context->rest->get('/get-latest-campaign', $dto->toApiPayload());
    }
}
