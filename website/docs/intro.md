---
sidebar_position: 1
title: Introduction
---

# API Client Documentation

Welcome to The Marketer API Client docs.

This documentation is for teams integrating the PHP package `themarketer/api-client`.

**Official theMarketer API Reference** — HTTP endpoints, parameters, and flows for the same platform APIs this client wraps: [developers.themarketer.com/reference](https://developers.themarketer.com/reference) (the developer portal home redirects here). Use it alongside these docs for staging setup, endpoint details, and topics such as [testing on a staging environment](https://developers.themarketer.com/reference/testing-on-a-staging-environment).

Most integrations start with:

- authenticating the client
- initializing the modules you use (`orders()`, `events()`, etc.)
- handling validation and API errors

The `Client` is constructed with an **associative array** (`customerId`, `restKey`, `trackingKey`, plus optional URLs and `maxRetryAttempts`). See [Authentication](./authentication.md).

## Common first call

```php
$ok = $client->checkApiCredentials();

if (!$ok) {
    // handle invalid credentials
}
```

## Start Here

- [Overview](./overview.md)
- [Quickstart](./quickstart.md)
- [Authentication](./authentication.md)
- [Errors and Troubleshooting](./errors.md)
- [Claude Skill (PHP — AI assistant)](./claude-skill.md)
- [Framework Integrations (Laravel)](./laravel.md)

## Reading Order

1. Overview
2. Quickstart
3. Authentication
4. API Reference (Orders, Events, and more)
5. Errors and Troubleshooting
6. Claude Skill (PHP — AI assistant)
7. Framework Integrations (Laravel)
