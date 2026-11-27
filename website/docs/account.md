---
sidebar_position: 16
title: Account
---

The account's own data and settings, read with the REST key (no user session): plan, allowed tracking domains, dashboard blocks, the email DNS setup and the webhooks the Shopify connector provisions. For the latest campaign (with its `id` and `type`) see [`campaigns()->getLatestCampaign()`](./campaigns.md#getlatestcampaign).

## Access module

```php
$accountApi = $client->account();
```

## `account`

**GET** `/account`: the account's plan and name.

**Response**

- `array`: `{id, name, domain, plan: {name, subscribers}, payment_status, pay_yearly, next_billing_date, pays_through_shopify, campaigns_sent}`. `name` is the account's domain name; `plan.subscribers` is a string or `null`; `next_billing_date` is `Y-m-d` or `null`. No user data and no keys.

```php
$account = $accountApi->account();
$planName = $account['plan']['name'];
```

## `allowedDomains`

**GET** `/allowed_domains`: the hosts the tracker accepts besides the account's own domain.

**Response**

- `list<string>`

## `addAllowedDomain`

**POST** `/allowed_domains`: adds a host (idempotent).

**Input**

- `domain` (`string`, required, at most 253 characters): a host name or a URL whose host is read. It must read as a valid host (with a dot, valid characters), otherwise `ValidationException` before the request, as the platform would answer 422. Sent as given; the platform stores it lowercased, without scheme, port or path (`www.` is kept).

**Response**

- `array`: `{result: true, domains: list<string>}`, the list after the call.

```php
$result = $accountApi->addAllowedDomain('https://www.shop.example.com/');
// $result['domains'] contains 'www.shop.example.com'
```

## `deleteAllowedDomain`

**POST** `/allowed_domains/delete`: removes a host, matched as given and normalised; a host that is not there is a no-op.

**Input**

- `domain` (`string`, required): same rule as `addAllowedDomain`.

**Response**

- `array`: `{result: true, domains: list<string>}`, the list after the call.

## `accountReputation`

**GET** `/dashboard/account-reputation`: the dashboard's reputation block.

**Response**

- `array`: `reputation`, `reputation_color`; for each of `soft_bounce`, `hard_bounce`, `complaint`, `unsubscribe` the rate (formatted string), `<key>_text` (`Excellent` … `Blocked`) and `<key>_color`; `blocked` (`bool`).

## `productHealth`

**GET** `/dashboard/product-health`: product feed counters.

**Response**

- `array<string, int>`: `{all, in_stock, without_image, broken_links, price_issues}`

## `emailSettings`

**GET** `/settings/email`: a sender domain's DNS records (read-only).

**Input**

- `domain` (`?int`, `>= 0`): index of an extra sender domain; `null` (default) = the account's main domain.

**Response**

- `array`: `{domain, dns_routing, dns, validated}`; `dns_routing` is `legacy`, `migrating` or `herald`.

**Errors**

- 503 when the DNS provider gives no answer: `ApiException` with code `503`; retry later.

## `validateDomain`

**POST** `/validate-domain`: re-checks the account's sender domain DNS now.

**Response**

- `bool`: `true` when valid (the platform records the validation), `false` when the platform answers 422 ("Domain cannot be validated"). Other errors are thrown.

```php
if (!$accountApi->validateDomain()) {
    // show the DNS records from emailSettings() again
}
```

## `upsertShopifyWebhooks`

**POST** `/shopify_webhooks` with `action=upsert`: creates or updates the account's webhooks that call the Shopify connector. Each item is matched on `event_type` + `destination_url` (exact); other webhooks are not touched. The webhooks are POST, Bearer `token`, enabled, visible in Settings → Webhooks. Idempotent; a new token replaces the old one.

**Input**

- `token` (`string`, required, at most 255 characters): the Bearer token the platform sends.
- `webhooks` (`list<array>`, 1 to 20 items):
  - `event_type` (`string`): a webhook event, e.g. `contact_subscribed`, `contact_unsubscribed`, `sms_subscribed`, `sms_unsubscribed`, `contact_updated`
  - `destination_url` (`string`, at most 2048 characters): `https://` only (`http://` is accepted only when the client talks to a plain-http, i.e. local, platform)
  - `json` (`string`, required): the body template, a JSON object; `{{client_email}}`-style variables are allowed as values
- `shop` (`?string`): the shop's `*.myshopify.com` domain, remembered by the platform.

**Response**

- `array`: `{result: true, webhooks: {<event_type>: "created"|"updated"}}`

**Errors**

- Invalid input is rejected before the request (`ValidationException`), with the platform's rules; a platform-side refusal (e.g. a non-public URL) answers 422, thrown as `ApiException` with code `422` and the platform's message.

```php
$result = $accountApi->upsertShopifyWebhooks($token, [
    [
        'event_type' => 'contact_subscribed',
        'destination_url' => 'https://connector.example/shopify/shop.myshopify.com/tm-webhooks/subscribed',
        'json' => '{"email":"{{client_email}}","phone":"{{client_phone}}"}',
    ],
], 'shop.myshopify.com');
```

## `deleteShopifyWebhooks`

**POST** `/shopify_webhooks` with `action=delete`: deletes the webhooks with each item's `event_type` + `destination_url`. A missing webhook is not an error. Allowed on a closed or suspended account.

**Input**

- `webhooks` (`list<array>`, 1 to 20 items): `event_type`, `destination_url` (same rules as above); `json` may be present and is not sent.
- `shop` (`?string`): optional `*.myshopify.com` domain.

**Response**

- `array`: `{result: true, webhooks: {<event_type>: "deleted"|"missing"}}`

## Source references

- API methods: `src/Api/AccountApi.php`
- DTOs: `src/DTO/Account/*.php`
- Examples in tests: `tests/AccountApiTest.php`
