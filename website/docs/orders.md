---
sidebar_position: 5
title: Orders
---

The Orders module manages order syncing, order status updates, feed URL setup, and ecommerce stats.

Obtain `$client` via `new Client([...])` as described in [Authentication](./authentication.md).

## Access module

```php
$ordersApi = $client->orders();
```

## `getEcommerceStats`

Returns ecommerce statistics.

**Input**

- none

**Response**

- `array`

```php
$stats = $ordersApi->getEcommerceStats();
```

## `saveOrder`

Creates or syncs an order using the standard payload format.

**Input**

- `payload` (`array<string, mixed>`):
  - `number` (`int|string`, required; a draft number such as `D1` is accepted; sent as given, so `"007"` stays `"007"`)
  - `email_address` (`?string`; `email_address` or `phone` is required)
  - `phone` (`?string`, omitted when empty)
  - `firstname` (`string`, required)
  - `lastname` (`string`, required)
  - `city` (`?string`, omitted when empty)
  - `county` (`?string`, omitted when empty)
  - `address` (`?string`, omitted when empty)
  - `discount_value` (`float`, required)
  - `discount_code` (`?string`, omitted when empty)
  - `order_status` (`?string`, omitted when empty)
  - `store_id` (`int|string|null`, omitted when empty) — physical store the order was placed in
  - `store_name`, `store_city`, `store_country` (`?string`, omitted when empty)
  - `shipping` (`float`, required)
  - `tax` (`float`, required)
  - `total_value` (`float`, required)
  - `products` (`list<array<string, mixed>>`, required, min 1)
    - `product_id` (`int|string`, required, sent as given) — your product id or the product SKU: the platform looks up the id first, then the SKU
    - `price` (`float`, required)
    - `sale_price` (`?float`, omitted when empty) — the price paid per unit; the platform uses it instead of `price` when present
    - `quantity` (`int`, required)
    - `variation_sku` (`?string`, omitted when empty; not read by the platform)

Leave `discount_code` out when there is no code: the platform uses it as a pattern on coupons, so a placeholder such as `-` matches real coupons.

**Changed in 0.2.0:** `number` and line `product_id` are sent as given (a numeric string is no longer converted to `int`); line `product_id` accepts text (a SKU); `store_*` and line `sale_price` are new optional fields.

**Response**

- `array`

```php
$result = $ordersApi->saveOrder([
    'number' => 1001,
    'email_address' => 'john@doe.com',
    'phone' => '+40123456789',
    'firstname' => 'John',
    'lastname' => 'Doe',
    'city' => 'Bucharest',
    'county' => 'Bucharest',
    'address' => 'Street 1, no 2',
    'discount_value' => 0.0,
    'shipping' => 0.0,
    'tax' => 0.0,
    'total_value' => 99.99,
    'products' => [
        [
            'product_id' => 123,
            'price' => 99.99,
            'quantity' => 1,
            'variation_sku' => 'SKU-123',
        ],
    ],
]);
```

## `saveOrderRetail`

Creates or syncs an order using the retail payload format.

**Input**

- `payload` (`array<string, mixed>`):
  - all fields from `saveOrder` payload, plus:
  - `store_id` (`int`, required)
  - `store_name` (`string`, required)
  - `store_city` (`string`, required)
  - `store_country` (`string`, required)

**Response**

- `array`

```php
$result = $ordersApi->saveOrderRetail([
    'number' => 1001,
    'email_address' => 'john@doe.com',
    'phone' => '+40123456789',
    'firstname' => 'John',
    'lastname' => 'Doe',
    'city' => 'Bucharest',
    'county' => 'Bucharest',
    'address' => 'Street 1, no 2',
    'discount_value' => 0.0,
    'discount_code' => '-',
    'shipping' => 0.0,
    'tax' => 0.0,
    'total_value' => 99.99,
    'products' => [
        [
            'product_id' => 123,
            'price' => 99.99,
            'quantity' => 1,
            'variation_sku' => 'SKU-123',
        ],
    ],
    'store_id' => 10,
    'store_name' => 'Store name',
    'store_city' => 'Bucharest',
    'store_country' => 'RO',
]);
```

## `updateFeedUrl`

Sets or updates the feed URL.

**Input**

- `url` (`string`, required): feed URL to import from.
- `type` (`?string`): optional. Allowed values: `product`, `category`, `brand`.

**Response**

- `array`

```php
$result = $ordersApi->updateFeedUrl(
    'https://shop.example.com/feed.xml',
    'product'
);
```

## `updateOrderFeedUrl`

Sets or updates the order feed URL.

**Input**

- `url` (`string`): order feed URL.
- `type` (`?string`): optional feed type.

**Response**

- `array`

```php
$result = $ordersApi->updateOrderFeedUrl(
    'https://shop.example.com/orders-feed.xml',
    null
);
```

## `updateOrderStatus`

Updates the status of an existing order.

**Input**

- `order_number` (`string`): the order identifier.
- `order_status` (`string`): the new status to set.

**Response**

- `array`

```php
$result = $ordersApi->updateOrderStatus('1001', 'shipped');
```

