---
sidebar_position: 16
title: Account
---

The account's own data and settings, read with the REST key (no user session): plan, allowed tracking domains, dashboard blocks and the email DNS setup. For the latest campaign (with its `id` and `type`) see [`campaigns()->getLatestCampaign()`](./campaigns.md#getlatestcampaign).

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

## Source references

- API methods: `src/Api/AccountApi.php`
- DTOs: `src/DTO/Account/*.php`
- Examples in tests: `tests/AccountApiTest.php`
