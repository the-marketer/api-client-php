---
sidebar_position: 13
title: Loyalty
---

Read loyalty info and add/remove points.

## Access module

```php
$loyaltyApi = $client->loyalty();
```

## `getInfo`

Returns loyalty info for a subscriber, identified by `email` or `phone`.

The contact is looked up by `email`; when no `email` is given, `phone` is used.

**Input**

- `email` (`string`, optional, valid email)
- `phone` (`string`, optional)

At least one of `email` or `phone` is required.

**Response**

- `array`

```php
$result = $loyaltyApi->getInfo('john@doe.com');

// by phone only
$byPhone = $loyaltyApi->getInfo(phone: '+40700000000');
```

## `managePoints`

Increases or decreases loyalty points for a subscriber, identified by `email` or `phone`.

The contact is looked up by `email`; when no `email` is given, `phone` is used.

**Input**

- `email` (`string`, optional, valid email)
- `action` (`string`, required): `increase` or `decrease`
- `points` (`int`, required, positive)
- `phone` (`string`, optional)

At least one of `email` or `phone` is required.

**Response**

- `array`

```php
$result = $loyaltyApi->managePoints('john@doe.com', 'increase', 100);

// by phone only
$byPhone = $loyaltyApi->managePoints(null, 'increase', 100, '+40700000000');
```

