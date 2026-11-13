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
  - `add_tags` (`?string`)
  - `firstname` (`?string`)
  - `lastname` (`?string`)
  - `phone` (`?string`)
  - `city` (`?string`)
  - `country` (`?string`)
  - `birthday` (`?string`)
  - `channels` (`?string`)
  - `attributes` (`?array`)

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

**Response**

- `array`

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

Removes a subscriber (optionally by channels), identified by `email` and/or `phone`.

The contact is looked up by `email` first; if `email` is missing or not found, `phone` is used as a fallback.

**Input**

- `email` (`?string`)
- `channels` (`?string`)
- `phone` (`?string`)

At least one of `email` or `phone` is required.

**Response**

- `array`

```php
$result = $subscribersApi->removeSubscriber('john@doe.com', 'email');

// by phone only
$result = $subscribersApi->removeSubscriber(phone: '+40700000000', channels: 'sms');
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
