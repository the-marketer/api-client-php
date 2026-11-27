---
sidebar_position: 6
title: Errors and Troubleshooting
---

## Exception mapping

| HTTP status | Exception |
| --- | --- |
| `401` | `UnauthorizedException` |
| `404` | `CustomerNotFoundException` |
| `405` | `MethodNotAllowedException` |
| Other API errors | `ApiException` |

Local payload/config validation failures are raised as `TheMarketer\ApiClient\Exception\ValidationException`.

**Before any HTTP request**, gateways also validate auth context:

- **REST** (`ApiGateway`): `customerId` and `restKey` must be non-empty.
- **Tracking** (`TrackingGateway`): `trackingKey` must be non-empty for tracking endpoints.

## Most common exceptions

## `ValidationException`

Appears when payload input is invalid before request execution, or when required config (customer id, rest key, tracking key) is missing for the gateway in use.

Typical causes:

- missing required fields in payload
- a payload key the DTO does not know (`unknown field(s): foo, bar`; since 0.2.0 unknown keys are no longer dropped silently)
- invalid email format
- invalid enum/choice values
- invalid date format
- empty `trackingKey` while calling tracking-only flows (configure `trackingKey` in `Client` array)

## `UnauthorizedException`

Appears when credentials are invalid (`401`).

Typical causes:

- wrong `customerId` or `restKey`
- expired/revoked credentials

## `CustomerNotFoundException`

Appears when customer is not found (`404`).

Typical causes:

- invalid customer/account context

## `MethodNotAllowedException`

Appears when endpoint does not allow that HTTP method (`405`).

Typical causes:

- endpoint/method mismatch

## `ApiException`

Generic API exception for non-mapped errors.

Typical causes:

- business validation errors returned by API
- temporary upstream API issues

## `JsonException`

Raised when response decoding fails.

Typical causes:

- invalid JSON response body

## `GuzzleHttp\\Exception\\GuzzleException`

Transport-level failure (network/request layer).

Typical causes:

- timeouts
- connection failures
- DNS/TLS issues

## Fast troubleshooting checklist

- Run `checkApiCredentials()` before business calls (returns `bool` on `Client`; see [Credentials and Utilities](./credentials-utilities.md)).
- For tracking features, ensure `trackingKey` is set in the client config and use `checkCredentials($key)` as needed.
- Log request/response metadata.
- Validate payload shape before sending.
- Reproduce with a minimal payload.
