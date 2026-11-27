---
sidebar_position: 7
title: Campaigns
---

List campaigns, create new campaigns, fetch email reports, and load the latest campaign snapshot. All calls use the **REST** gateway (`k` / `u` query auth). Initialize `Client` with an array config (see [Authentication](./authentication.md)).

## Access module

```php
$campaignsApi = $client->campaigns();
```

---

## `list`

Lists campaigns with optional filters. Uses **POST** to `/campaigns/list` with a JSON body built from the `ListCampaign` DTO.

**Input**

- `payload` (`array<string, mixed>`, optional; default `[]`); empty values are omitted from the body:
  - `filters` (`array|string|null`): sent as a JSON **object**; a JSON object string is decoded first (invalid JSON or a list throws `ValidationException`). Keys the platform reads, all optional (others are ignored):
    - `search` (`string`): part of the campaign name
    - `type` (`string`): e.g. `email`, `sms`, `push`
    - `start_date` (date): `sent_at` from
    - `status` (`string|list<string>`): e.g. `['sent']`; `scheduled` is a pseudo-status for future campaigns (and sorts by `send_at` ascending)
    - `send_at_from` / `send_at_to` (ISO date or date-time): `send_at` range; a bare date as `send_at_to` covers the whole day
  - `search`, `type`, `start_date` (`?string`): accepted at the top level for older callers and moved into `filters` (the platform reads them only there; a value already in `filters` wins)
  - `page` (`int|string|null`, >= 1, default 1)
  - `limit` (`int|string|null`, >= 1, default 20; the platform caps it at 100)

Deleted campaigns are never listed.

**Changed in 0.2.0:** `filters` is sent as an object (it was a string), `page`/`limit` accept ints and are sent as ints, and the top-level `search`/`type`/`start_date` (which the platform ignored) now reach it inside `filters`.

**Response**

- `array`: `{data, total, page, limit, pages}`; each campaign has `_id`, `status`, `name`, `type`, `variant`, `send_at`, `sent_at`, `num_sent`, `recipients`, `segment {id, name}`, `audience_type`, `subject`, `preview`, `sender` and more.

```php
$result = $campaignsApi->list();

$result = $campaignsApi->list([
    'filters' => [
        'status' => ['scheduled'],
        'send_at_from' => '2026-11-01',
        'send_at_to' => '2026-11-30',
    ],
    'page' => 1,
    'limit' => 20,
]);
```

---

## `create`

Creates a campaign. Payload is validated by `CreateCampaign` and nested DTOs, then sent as JSON to `/campaigns/create`.

**Input**

- `payload` (`array<string, mixed>`), top-level:
  - `type` (`string`, required): one of `sms`, `email`, `push`
  - `mode` (`string`, required): one of `ecommerce`, `regular`, `plaintext`
  - `sender` (`array`, required):
    - `name` (`string`, required)
    - `sender` (`string`, required, email)
    - `reply_to` (`string`, required, email)
  - `audience` (`array`, required):
    - `audience_type` (`string`, required): currently `all`
    - `smart_sending` (`bool`, required)
  - `subject` (`array`, required):
    - `name` (`string`, required)
    - `subject_line` (`string`, required)
    - `preview_text` (`string`, required)
  - `content` (`array`, required):
    - `html` (`string`, required): HTML body (max length enforced in DTO)
  - `scheduling` (`array`, required):
    - `send_at` (`string`, required): datetime `Y-m-d H:i`
    - `use_optimal_time` (`int`, required): `0` or `1`
    - `optimize_for` (`string`, required): `opening` or `buying`
  - `tracking` (`array`, required):
    - `utm_campaign` (`string`, required)
    - `utm_medium` (`string`, required)
    - `utm_source` (`string`, required)

**Response**

- `array`

```php
$result = $campaignsApi->create([
    'type' => 'email',
    'mode' => 'regular',
    'sender' => [
        'name' => 'Shop',
        'sender' => 'shop@example.com',
        'reply_to' => 'support@example.com',
    ],
    'audience' => [
        'audience_type' => 'all',
        'smart_sending' => false,
    ],
    'subject' => [
        'name' => 'Spring',
        'subject_line' => 'Hello',
        'preview_text' => 'Preview',
    ],
    'content' => [
        'html' => '<p>Hi</p>',
    ],
    'scheduling' => [
        'send_at' => '2026-06-01 12:00',
        'use_optimal_time' => 0,
        'optimize_for' => 'opening',
    ],
    'tracking' => [
        'utm_campaign' => 'spring-2026',
        'utm_medium' => 'email',
        'utm_source' => 'newsletter',
    ],
]);
```

---

## `getEmailReport`

Returns the email report for a campaign.

**Input**

- `id` (`string`, required): campaign identifier (embedded in the URL path; special characters should be safe for path segments—see API behavior).

**Response**

- `array`

```php
$result = $campaignsApi->getEmailReport('99');
```

Internally this calls **GET** `/campaigns/{id}/email/get-report` with standard REST auth query params.

---

## `getLatestCampaign`

Returns the latest campaign data. Uses **GET** `/get-latest-campaign`.

**Input**

- `limit` (`?int`, optional): when set, must be **positive**; sent as query param `limit`.

**Response**

- `array`: the latest sent campaign, `{id, type, status, subject_line, sent_at, audience_type, audience_segment, audience_name, stats}` (every key `null` when the account has sent none). With `limit`, a list of up to 10 such campaigns.

```php
$result = $campaignsApi->getLatestCampaign();

$result = $campaignsApi->getLatestCampaign(5);
```

---

## Validation and errors

- Invalid or incomplete payloads raise `TheMarketer\ApiClient\Exception\ValidationException` before the HTTP request.
- Failed API responses are mapped to `UnauthorizedException`, `CustomerNotFoundException`, `MethodNotAllowedException`, or `ApiException` as documented in [Errors and Troubleshooting](./errors.md).

## Source references

- API methods: `src/Api/CampaignsApi.php`
- DTOs: `src/DTO/Campaigns/*.php`
- Examples in tests: `tests/CampaignsApiTest.php`
