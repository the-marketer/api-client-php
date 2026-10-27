---
sidebar_position: 11
title: Events
---

Send standard and custom behavioral events.

```php
use TheMarketer\ApiClient\Client;

$client = new Client([
    'customerId' => 'YOUR_CUSTOMER_ID',
    'restKey' => 'YOUR_REST_KEY',
    'trackingKey' => 'YOUR_TRACKING_KEY', // required for tracking-based event methods
]);

$eventsApi = $client->events();
```

`did` is the visitor's device id: the UUID that `t.js` writes in the `__sm__c` cookie (`{"did":"…","k":"…"}`). `remote_addr` and `http_user_agent` in the body take priority over the ones the server sees, so send the visitor's, not your server's. `trackingKey` is only needed by the tracking methods in this page.

Rules shared by the tracking methods:

- Every tracking event accepts an optional `referrer` (`string`) next to `source`. Send it: the platform uses it to work out where the visit came from (without it every visit counts as direct).
- `product_id` accepts an `int` or a `string` and is sent exactly as given (`"007"` stays `"007"`). The platform matches it as text against the product id from your feed. Send the same id as the feed, not the SKU: for events there is no fallback on SKU (orders have one, see [`saveOrder`](orders.md#saveorder)).
- `addToWishlist` / `removeFromWishlist` only need `product_id`; `quantity` and `variation` are optional.

**Changed in 0.2.0:** `product_id` is no longer rejected when it is not a positive integer, and a numeric string is no longer converted to `int`. Code that relied on the client throwing for a non-numeric `product_id` must validate it itself.

---

## `sendCustomApi`

Custom event sent through the REST API (without the full tracking fields from `CustomEvent`).

**Input — `payload`**

- `email` (`string`, required, email valid)
- `event` (`string`, required)

**Response**

- `array`

```php
$result = $eventsApi->sendCustomApi([
    'email' => 'user@example.com',
    'event' => 'newsletter_click',
]);
```

---

## `sendCustom`

Custom event sent through **tracking** (`CustomEvent`).

**Input — `payload`**

- `did` (`string`, required) — device/session id
- `email` (`string`, required, email valid)
- `event` (`string`, required)
- `url` (`string`, required, URL valid)
- `http_user_agent` (`string`, required)
- `remote_addr` (`string`, required)
- `source` (`?string`, optional)

**Response**

- `array`

```php
$result = $eventsApi->sendCustom([
    'did' => 'device-abc',
    'email' => 'user@example.com',
    'event' => 'product_viewed',
    'url' => 'https://shop.example.com/p/1',
    'http_user_agent' => 'Mozilla/5.0',
    'remote_addr' => '203.0.113.10',
]);
```

---

## `viewHomepage`

Homepage or generic page view (`ViewHomepageEvent`).

**Input — `payload`**

- `did` (`string`, required)
- `event` (`string`, required) — for example, an event name agreed with the backend
- `url` (`string`, required, URL valid)
- `http_user_agent` (`string`, required)
- `remote_addr` (`string`, required)
- `source` (`?string`, optional)

**Response**

- `array`

```php
$result = $eventsApi->viewHomepage([
    'did' => 'device-abc',
    'event' => 'view_homepage',
    'url' => 'https://shop.example.com/',
    'http_user_agent' => 'Mozilla/5.0',
    'remote_addr' => '203.0.113.10',
]);
```

---

## `viewCategory`

Category page view (`ViewCategoryEvent`, event `__sm__view_category`).

**Input — `payload`**

- `did` (`string`, required)
- `event` (`string`, required) — `__sm__view_category`
- `category` (`string`, required) — the category path as in the catalog, levels joined with `|` (e.g. `Men|Shoes`)
- `url` (`string`, required, URL valid)
- `http_user_agent` (`string`, required)
- `remote_addr` (`string`, required)
- `source` (`?string`, optional)
- `referrer` (`?string`, optional)

**Response**

- `array`

```php
$result = $eventsApi->viewCategory([
    'did' => 'device-abc',
    'event' => '__sm__view_category',
    'category' => 'Men|Shoes',
    'url' => 'https://shop.example.com/c/men-shoes',
    'http_user_agent' => 'Mozilla/5.0',
    'remote_addr' => '203.0.113.10',
]);
```

---

## `genericPageView`

Any other page view (event `__generic_page_view`). Same payload as [`viewHomepage`](#viewhomepage) (`ViewHomepageEvent`), with a different `event` value.

```php
$result = $eventsApi->genericPageView([
    'did' => 'device-abc',
    'event' => '__generic_page_view',
    'url' => 'https://shop.example.com/pages/about',
    'http_user_agent' => 'Mozilla/5.0',
    'remote_addr' => '203.0.113.10',
]);
```

---

## `order`

Order placed in the visitor's session (`OrderEvent`, event `__sm__order`). Use it when the order should be tied to the visitor (`did`); use [`saveOrder`](orders.md#saveorder) for orders sent from the server without a session.

**Input — `payload`**

- every field of [`saveOrder`](orders.md#saveorder) (same rules: `number` sent as given, `email_address` or `phone` required, empty optional fields left out, `store_*` and line `sale_price` optional)
- `did` (`string`, required)
- `event` (`string`, required) — `__sm__order`
- `url` (`string`, required, URL valid)
- `http_user_agent` (`string`, required)
- `remote_addr` (`string`, required)
- `source` (`?string`, optional)
- `referrer` (`?string`, optional)

**Response**

- `array`

```php
$result = $eventsApi->order([
    'did' => 'device-abc',
    'event' => '__sm__order',
    'url' => 'https://shop.example.com/checkout/thank-you',
    'http_user_agent' => 'Mozilla/5.0',
    'remote_addr' => '203.0.113.10',
    'number' => 'EU-1001',
    'firstname' => 'John',
    'lastname' => 'Doe',
    'email_address' => 'john@doe.com',
    'discount_value' => 0.0,
    'shipping' => 0.0,
    'tax' => 0.0,
    'total_value' => 99.99,
    'products' => [
        ['product_id' => 123, 'price' => 99.99, 'quantity' => 1],
    ],
]);
```

---

## `setEmail`

Associate/set an email in the tracking flow (`SetEmailEvent`).

**Input — `payload`**

- `did` (`string`, required)
- `event` (`string`, required)
- `email_address` (`string`, required, email valid)
- `firstname` (`?string`, optional, omitted when empty)
- `lastname` (`?string`, optional, omitted when empty)
- `phone` (`?string`, optional, omitted when empty)
- `url` (`string`, required, URL valid)
- `http_user_agent` (`string`, required)
- `remote_addr` (`string`, required)
- `source` (`?string`, optional)
- `referrer` (`?string`, optional)

**Response**

- `array`

---

## `viewProduct`

Product view (`ViewProductEvent`).

**Input — `payload`**

- `did` (`string`, required)
- `event` (`string`, required)
- `product_id` (`int|string`, required, sent as given)
- `url` (`string`, required, URL valid)
- `http_user_agent` (`string`, required)
- `remote_addr` (`string`, required)
- `source` (`?string`, optional)

**Response**

- `array`

```php
$result = $eventsApi->viewProduct([
    'did' => 'device-abc',
    'event' => 'view_product',
    'product_id' => '42',
    'url' => 'https://shop.example.com/p/42',
    'http_user_agent' => 'Mozilla/5.0',
    'remote_addr' => '203.0.113.10',
]);
```

---

## `addToCart`

Adds a product line to cart.

**Input — `payload`**

- `did` (`string`, required)
- `event` (`string`, required) — e.g. `add_to_cart`, `remove_from_cart`, ...
- `product_id` (`int|string`, required, sent as given)
- `quantity` (`int`, required, positive)
- `variation` (`array`, required):
  - `id` (`string`, required)
  - `sku` (`string`, required)
- `http_user_agent` (`string`, required)
- `url` (`string`, required, URL valid)
- `remote_addr` (`string`, required)
- `source` (`?string`, optional)

**Response**

- `array`

```php
$line = [
    'did' => 'device-abc',
    'event' => 'add_to_cart',
    'product_id' => 100,
    'quantity' => 1,
    'variation' => ['id' => 'var-1', 'sku' => 'SKU-1'],
    'http_user_agent' => 'Mozilla/5.0',
    'url' => 'https://shop.example.com/p/100',
    'remote_addr' => '203.0.113.10',
];

$eventsApi->addToCart($line);
```

---

## `removeFromCart`

Removes a product line from cart.

Uses the same payload schema as `addToCart` (`ProductLineEvent`), with a different `event` value.

```php
$eventsApi->removeFromCart([
    ...$line,
    'event' => 'remove_from_cart',
]);
```

---

## `addToWishlist`

Adds a product line to wishlist.

Uses the same payload schema as `addToCart` (`ProductLineEvent`), with a different `event` value.

```php
$eventsApi->addToWishlist([
    ...$line,
    'event' => 'add_to_wishlist',
]);
```

---

## `removeFromWishlist`

Removes a product line from wishlist.

Uses the same payload schema as `addToCart` (`ProductLineEvent`), with a different `event` value.

```php
$eventsApi->removeFromWishlist([
    ...$line,
    'event' => 'remove_from_wishlist',
]);
```

---

## `initiateCheckout`

Start checkout; uses **`InitiateCheckoutEvent`**, with the same field set as **`ViewHomepageEvent`**.

**Input — `payload`**

- `did`, `event`, `url`, `http_user_agent`, `remote_addr` (required, same as `viewHomepage`)
- `source` (`?string`, optional)

**Response**

- `array`

```php
$result = $eventsApi->initiateCheckout([
    'did' => 'device-abc',
    'event' => 'initiate_checkout',
    'url' => 'https://shop.example.com/checkout',
    'http_user_agent' => 'Mozilla/5.0',
    'remote_addr' => '203.0.113.10',
]);
```

---

## `search`

Site search (`SearchEvent`).

**Input — `payload`**

- `did` (`string`, required)
- `event` (`string`, required)
- `search_term` (`string`, required)
- `url` (`string`, required, URL valid)
- `http_user_agent` (`string`, required)
- `remote_addr` (`string`, required)
- `source` (`?string`, optional)

**Response**

- `array`

```php
$result = $eventsApi->search([
    'did' => 'device-abc',
    'event' => 'search',
    'search_term' => 'running shoes',
    'url' => 'https://shop.example.com/search?q=running',
    'http_user_agent' => 'Mozilla/5.0',
    'remote_addr' => '203.0.113.10',
]);
```

---

## `serveJavascript`

Load the tracking JavaScript resource for a given key.

**Input**

- `trackingKey` (`string`, required) — 6–20 characters

**Response**

- `array` (JSON decoded by the gateway)

```php
$result = $eventsApi->serveJavascript('abcdef123456');
```

