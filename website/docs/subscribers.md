---
sidebar_position: 6
title: Subscribers
---

Manage subscriber lifecycle and audience data.

## Access module

```php
$subscribersApi = $client->subscribers();
```

## `addSubscriber`

Adds or updates a subscriber via the **synchronous** endpoint (`/add_subscriber_sync`).

**Input**

- `payload` (`array<string, mixed>`):
  - `email` (`string`, required)
  - `add_tags` (`?string`): comma-separated tag names to add
  - `remove_tags` (`?string`): comma-separated tag names to remove (never creates a tag)
  - `firstname` (`?string`)
  - `lastname` (`?string`)
  - `name` (`?string`)
  - `phone` (`?string`)
  - `city` (`?string`)
  - `county` (`?string`)
  - `country` (`?string`)
  - `birthday` (`?string`)
  - `channels` (`?string`)
  - `attributes` (`?array`): custom attributes, free-form keys
  - `no_double_optin` (`0|1|bool|"0"|"1"|null`, sent as `0`/`1`): `1` = no double opt-in confirmation mail for this call
  - `pretend_double_opt_in_is` (`0|1|bool|"0"|"1"|null`, sent as `0`/`1`, `0` is sent): overrides the account's double opt-in setting for this call; `0` subscribes the contact now even when the account has double opt-in on (with `no_double_optin=1` alone the contact stays unsubscribed under double opt-in)

  - `source` (`?string`): `connector` = the change comes from the Shopify connector and is not sent back to the webhooks the connector provisioned (no echo); other account webhooks still fire

Empty values are left out of the body (except `pretend_double_opt_in_is=0`). A key not listed here throws `ValidationException` (`unknown field(s): …`).

**Response**

- `array`

```php
$result = $subscribersApi->addSubscriber([
    'email' => 'john@doe.com',
    'firstname' => 'John',
    'lastname' => 'Doe',
]);
```

## `addSubscriberAsync`

Same payload shape as `addSubscriber`, but uses the **async** path (`/add_subscriber`).

```php
$result = $subscribersApi->addSubscriberAsync([
    'email' => 'john@doe.com',
]);
```

## `addSubscriberBulk`

Adds subscribers in bulk.

**Input**

- `subscribers` (`list<array<string, mixed>>`): list of subscriber payloads.
  - each item uses the same fields as `addSubscriber` / `addSubscriberAsync` (see above).
- `source` (`?string`): `connector` = no echo to the connector's webhooks; sent once, in the query string (the body is the list of rows).

**Response**

- `array`: `{result, queued, rejected, rejected_rows}` (`rejected_rows` are positions of rows the platform refused)

```php
$result = $subscribersApi->addSubscriberBulk([
    ['email' => 'john@doe.com'],
    ['email' => 'jane@doe.com'],
]);
```

## `addSubscriberByPhone`

Adds a subscriber by phone.

**Input**

- `phone` (`string`, required)
- `firstname` (`?string`)
- `lastname` (`?string`)

**Response**

- `array`

```php
$result = $subscribersApi->addSubscriberByPhone('+40123456789', 'John', 'Doe');
```

## `anonymizeEmail`

Anonymizes a subscriber email.

**Input**

- `email` (`string`)

**Response**

- `array`

```php
$result = $subscribersApi->anonymizeEmail('john@doe.com');
```

## `deleteSubscriber`

Deletes a subscriber by `email` and/or `phone`.

**Input**

- `payload` (`array<string, mixed>`): must include at least one of:
  - `email` (`?string`, must be a valid email when present)
  - `phone` (`?string`)

**Response**

- `array`

```php
$result = $subscribersApi->deleteSubscriber([
    'email' => 'john@doe.com',
]);
```

## `listSubscribed`

Lists subscribed emails, optionally filtered by a date range.

**Input**

- `dateFrom` (`?string`) (sent as `date_from`)
- `dateTo` (`?string`) (sent as `date_to`)

**Response**

- `array`

```php
$result = $subscribersApi->listSubscribed('2026-01-01', '2026-01-31');
```

## `listUnsubscribed`

Lists unsubscribed emails, optionally filtered by a date range.

**Input**

- `dateFrom` (`?string`) (sent as `date_from`)
- `dateTo` (`?string`) (sent as `date_to`)

**Response**

- `array`

```php
$result = $subscribersApi->listUnsubscribed('2026-01-01', '2026-01-31');
```

## `removeSubscriber`

Removes a subscriber (optionally by channels).

**Input**

- `email` (`string`, required)
- `channels` (`?string`): comma-separated channels (`email`, `sms`, `push`, `loyalty`); all when omitted
- `source` (`?string`): `connector` = the unsubscribe is not echoed back to the connector's webhooks

**Response**

- `array`

```php
$result = $subscribersApi->removeSubscriber('john@doe.com', 'email');
$result = $subscribersApi->removeSubscriber('john@doe.com', 'email', 'connector');
```

## `statusSubscriber`

Gets subscriber status for an email.

**Input**

- `email` (`string`, required)

**Response**

- `array`

```php
$result = $subscribersApi->statusSubscriber('john@doe.com');
```

## `subscribersEvolution`

Returns subscribers evolution stats.

**Input**

- none

**Response**

- `array`

```php
$result = $subscribersApi->subscribersEvolution();
```

## `unsubscribedEmails`

Gets unsubscribed emails in a required date range.

**Input**

- `dateFrom` (`string`, required, format `YYYY-MM-DD`) (sent as `date_from`)
- `dateTo` (`string`, required, format `YYYY-MM-DD`) (sent as `date_to`)

**Response**

- `array`

```php
$result = $subscribersApi->unsubscribedEmails('2026-01-01', '2026-01-31');
```

## `updateTags`

Updates subscriber tags.

**Input**

- `email` (`string`, required)
- `addTags` (`list<string|int>`) (default: `[]`) — sent as `add_tags`
- `removeTags` (`list<string|int>`) (default: `[]`) — sent as `remove_tags`
- `overwriteExisting` (`?int`) (default: `null`) — sent as `overwrite_existing`

**Response**

- `array`

```php
$result = $subscribersApi->updateTags(
    'john@doe.com',
    [10, 12],
    [5],
    1,
);
```
