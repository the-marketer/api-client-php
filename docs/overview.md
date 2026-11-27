# Overview

`themarketer/api-client` este clientul oficial PHP pentru integrarea cu The Marketer API.

## What this package does

- Trimite request-uri HTTP către endpoint-urile The Marketer.
- Validează payload-urile local prin DTO-uri.
- Returnează răspunsuri JSON decodate pentru majoritatea metodelor.
- Mapează erorile HTTP în excepții clare.

## Requirements

- PHP `^8.1`
- Composer
- `ext-mbstring`

## Core usage model

1. Creezi un `Client` cu un **array**: minim `customerId` și `restKey`; opțional `trackingKey`, `restUrl`, `trackingUrl`, `maxRetryAttempts`.
2. `Client` construiește `Config` și `ApiContext` (REST prin `ApiGateway`, tracking prin `TrackingGateway` când e cazul).
3. Apelezi un modul (de exemplu `orders()`).
4. Trimite payload valid către metoda dorită.
5. Procesezi răspunsul sau tratezi excepțiile.

URL-ul de bază REST este `{restUrl}/api/{apiVersion}/` — vezi `Config::baseRestUrl()`.

## Available API modules

- `subscribers()`
- `orders()`
- `transactionals()`
- `products()`
- `campaigns()`
- `loyalty()`
- `coupons()`
- `reviews()`
- `mobilePush()` (push mobil)
- `events()`
- `reports()`
- `account()` (plan, allowed domains, reputație, sănătatea produselor, DNS email)

## Next step

Continuă cu [Quickstart](./quickstart.md) pentru primul request funcțional.
