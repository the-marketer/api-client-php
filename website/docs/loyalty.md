---
sidebar_position: 13
title: Loyalty
---

Read loyalty info and add/remove points.

## Access module

```php
$loyaltyApi = $client->loyalty();
```

## Finding a contact by phone

`getInfo` and `managePoints` look the contact up by `email`. If no `email` is given, they use `phone`.

- The phone is normalized with the country prefix of your account, so local (`0735…`) and international (`+40735…`, `0040735…`) formats match the same contact. If the account has no country set, a lookup by phone never finds a contact.
- If several contacts share the phone number, the most recently created one is used. Send the `email` to target a specific contact.
- An unknown or invalid phone number gets the same "Subscriber not found" (404) response as an unknown email.
- If neither `email` nor `phone` is given, the SDK throws a validation error before sending the request. (The raw API responds `401` with "Email address or phone number is required.")

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

