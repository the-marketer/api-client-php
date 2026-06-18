---
sidebar_position: 2
title: Overview
---

`themarketer/api-client` is the official PHP client for The Marketer API.

## What this package does

- Consumes The Marketer API from PHP applications through a clean, module-based client.
- Helps you integrate faster by keeping request payloads consistent and validated before sending.
- Provides a predictable integration flow: authenticate, call a module, send data, receive a response.
- Surfaces API failures in a clear way, so you can handle errors reliably in your code.

## Requirements

- PHP `^8.1`
- Composer
- `ext-mbstring`

## Core usage model

1. Create a `Client` with an **array** config: at minimum `customerId` and `restKey`; optionally `trackingKey`, `restUrl`, `trackingUrl`, `maxRetryAttempts` (see [Authentication](./authentication.md)).
2. Internally, `Client` builds `Config` and `ApiContext`. The context exposes:
   - **REST** traffic via `ApiGateway` (query `k` / `u` for API calls under `Config::baseRestUrl()`).
   - **Tracking** traffic via `TrackingGateway` when you use modules that send behavioral data to the tracking host.
3. Access a module (for example `orders()`).
4. Send a validated payload.
5. Handle response data or exceptions.

Base URL for REST is built as `{restUrl}/api/{apiVersion}/` (default `apiVersion` `v1`) — see `Config::baseRestUrl()` in `src/Common/Config.php`.

## Available API modules

- [`orders()`](./orders.md)
- [`subscribers()`](./subscribers.md)
- [`campaigns()`](./campaigns.md)
- [`products()`](./products.md)
- [`transactionals()`](./transactionals.md)
- [`reports()`](./reports.md)
- [`events()`](./events.md)
- [`coupons()`](./coupons.md)
- [`loyalty()`](./loyalty.md)
- [`reviews()`](./reviews.md)
- [`mobilePush()` (mobile push)](./app-push.md)

Utilities:

- [`Client` utilities](./credentials-utilities.md)
- [Errors & troubleshooting](./errors.md)
- [Claude Skill](./claude-skill.md) — official Claude skill (PHP) for AI-assisted integration with this package; a separate skill exists for Node.js projects
