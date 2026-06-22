# The Marketer API Client (PHP)

PHP client for the **The Marketer** API. It sends HTTP requests via **Guzzle** (the internal `ApiGateway` class) and validates payloads with **Symfony Validator** (the `AbstractPayload` / `Data` DTOs from `TheMarketer\ApiClient\Common`).

## Client documentation

For a structured, easy-to-follow version:

- [API Client Docs](https://the-marketer.github.io/api-client-php/docs/intro)

### Docusaurus site (GitHub Pages)

The documentation is also available as a Docusaurus site in the `website/` folder.

Run locally:

```bash
cd website
npm install
npm run start
```

Deployment to GitHub Pages is configured through the workflow:

- `.github/workflows/deploy-docs.yml`

## Requirements

| Requirement | Version / note |
|--------|------------------|
| **PHP** | `^8.1` |
| **Composer** | for installing dependencies |
| **ext-mbstring** | required |
| **Main dependencies** | `guzzlehttp/guzzle` ^7, `symfony/validator` ^7, `symfony/expression-language` ^8 |
| **Development / tests** | `phpunit` ^10, `orchestra/testbench` ^8 (for the test suite) |

Install in your project:

```bash
composer install
```

Public package (example):

```bash
composer require themarketer/api-client
```

---

## Architecture at a glance

- **`TheMarketer\ApiClient\Client`** — single entry point: takes a configuration **array** (`customerId`, `restKey`, optionally `trackingKey`, `restUrl`, `trackingUrl`, `maxRetryAttempts`), builds **`Config`** + **`ApiContext`** and exposes the API modules (`subscribers()`, `orders()`, `checkCredentials()`, etc.).
- **`ApiContext`** — provides the **REST** gateway (`ApiGateway`) and the **tracking** gateway (`TrackingGateway`); both use Guzzle (configurable retry).
- **`ApiGateway`** — for REST: `k` (rest key) and `u` (customer id) query params; JSON on POST where the DTO requires it; maps HTTP errors to exceptions.
- **`TrackingGateway`** — for the tracking host: requires `trackingKey` in the config; a different set of authentication parameters in the query.
- **The classes in `src/Api/`** — encapsulate the endpoints; input validation happens in **`src/DTO/`**.

The base URL for REST is `Config::baseRestUrl()` = `{restUrl}/api/{apiVersion}/` (default `apiVersion` = `v1`). The default URLs live in `Client` / `Config` (`src/Common/Config.php`).

---

## Basic usage

```php
use TheMarketer\ApiClient\Client;

$client = new Client([
    'customerId' => 'THE_MARKETER_ACCOUNT_ID', // query `u` on REST
    'restKey' => 'REST_KEY',                    // query `k` on REST
    'trackingKey' => 'TRACKING_KEY',            // optional: for tracking events
    'maxRetryAttempts' => 1,                     // optional
]);

// Examples of accessing the grouped APIs
$client->subscribers()->addSubscriber([/* … */]);
$client->orders()->saveOrder([/* … */]);
$client->transactionals()->sendEmail([/* … */]);
```

---

## Credentials and utilities (directly on `Client`)

These methods do not go through `subscribers()` / `orders()`; they are delegated to the internal **`CredentialsClient`**.

### `checkCredentials(string $trackingKey): bool`

Checks the credentials (tracking key in the **JSON body** on REST, see `CredentialsClient`). On **`Client`**, it returns **`true`** if the decoded response is an empty array `[]`, otherwise **`false`**. For the raw JSON as an `array`, use `CredentialsClient::checkCredentials()` with the same context.

```php
$ok = $client->checkCredentials('TRACKING_KEY');
```

### `checkApiCredentials(): bool`

On **`Client`**, it returns a **`bool`** (same criterion: JSON body → empty array = success). For the full decoded response, see `CredentialsClient::checkApiCredentials()`.

```php
$ok = $client->checkApiCredentials();
```

### `getCosts()`, `getRealtimeVisitors()`, `getSmsCredit(): array`

Decoded JSON response.

### `getReferralLink(?string $email = null): string`

Returns the **raw content** of the response (not JSON).

### `getDeliveryLogs(array $payload): array`

`email` required; optional: `per_page`, `page`, `start`, `end`.

### `getEnteredAutomation(array $payload): array`

`date` required (`Y-m-d`); optional `page`, `perPage`.

### `config(): Config`

Access to `customerId`, `restKey`, `baseRestUrl()`, `trackingKey()`, etc.

---

## API modules (examples)

| Accessor on `Client` | Role |
|---------------------|-----|
| `subscribers()` | Subscribers: status, add/remove, bulk, tags, etc. |
| `orders()` | Orders, feed URL, retail, statistics |
| `transactionals()` | Transactional email and SMS |
| `products()` | Product CRUD / sync, categories, brands |
| `campaigns()` | List, create campaign, email report, last campaign |
| `loyalty()` | Loyalty points |
| `coupons()` | Available coupons, saving |
| `reviews()` | Product and merchant reviews, Merchant Pro settings |
| `mobilePush()` | Mobile push (iOS/Android tokens) |
| `events()` | Custom events |
| `reports()` | Email/SMS/push/forms/audience reports |

Details about parameters: the files in `src/Api/*Api.php` and `src/DTO/**`. The tests in `tests/*ApiTest.php` show examples of valid payloads.

### Campaigns — important notes

- **`list()`** uses **POST** to `/campaigns/list`, with the body from `ListCampaign`.
- **`create()`** requires a nested structure validated by `CreateCampaign`; for the **sender** it uses the keys **`name`**, **`sender`** (email), **`reply_to`** (not `sender_name` / `sender_email`).

### Reviews

- **`getProductReviews()`** returns a **string** (raw response content, not an automatically decoded `array`).

### Reports

- Queries usually include `start`, `end`, `type` (see the enums in `src/Enum/` and the DTOs in `src/DTO/Reports/`).

---

## Errors and exceptions

**Before the request (local validation)**

- **`TheMarketer\ApiClient\Exception\ValidationException`** — missing `customerId` / `restKey` in the config, or messages from Symfony validation on the DTO.
- Missing required arguments when building a DTO can lead to **`ArgumentCountError`** or **`TypeError`** before any network call.

**After the request** (`ApiGateway` maps the HTTP status)

| Status | Exception |
|--------|----------|
| **401** | `UnauthorizedException` |
| **404** | `CustomerNotFoundException` |
| **405** | `MethodNotAllowedException` |
| Other errors | `ApiException` (uses the code and message from the response; the message is extracted from the JSON `message` when present) |

On a successful response with invalid JSON, the methods that decode it can throw **`JsonException`**. For network errors: **`GuzzleHttp\Exception\GuzzleException`**.

---

## Tests

```bash
composer test
```

The suite uses `ApiGateway` with a **Guzzle MockHandler** (no real API calls). For **`Client`** in tests with an HTTP mock, `context` is `readonly`; in practice `CredentialsClient` and the `*Api` classes are tested with the same stack — see `tests/CredentialsClientTest.php` and `tests/TestCase.php`.

---

## Smoke script (`smoke.php`)

A quick check with **real credentials** (do not commit keys to the repo):

```bash
php smoke.php
```

The example in the repo calls `checkCredentials($trackingKey)` — replace the values with the ones from your The Marketer account.

---

## License

MIT (see `composer.json`).