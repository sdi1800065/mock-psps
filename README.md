# Mock PSPs Payment Service

A small PHP service that simulates charging cards through mock payment providers (PSPs). Merchants are stored in MySQL and authenticate with Bearer tokens. Designed for local development and demos.

## What it does

- Merchants are created via an admin API and stored in MySQL
- Each merchant is tied to a PSP (`fakeStripe` or `fakePaypal`)
- Charge requests are authenticated per merchant using their API key
- Charges are persisted in MySQL
- A CLI tool sends email charge reports per merchant and date range

## Tech stack

- PHP 8.2+
- Apache
- MySQL 8
- Docker + Docker Compose
- PHPUnit

## Project structure

```
public/index.php        HTTP entrypoint and routing
src/Service/            Business logic (ChargeService, MerchantService, ChargeReportService)
src/Psp/                Fake PSP implementations
src/Repository/         Storage interfaces and MySQL implementations
src/Model/              Domain models (Merchant, Charge, PaymentProvider)
bin/create-db.php       Creates database tables (run once on setup)
bin/send-charge-report.php  CLI tool for generating and emailing reports
tests/                  PHPUnit unit tests
```

## Getting started

### 1. Start containers

```bash
docker compose up -d --build
```

### 2. Create database tables

This only needs to be run once (or after a volume wipe):

```bash
docker compose run --rm app php bin/create-db.php
```

Expected output:
```
Database tables created successfully.
```

### 3. Create your first merchant

The admin token is set via `ADMIN_TOKEN` in `docker-compose.yml`.

```bash
curl -X POST http://localhost:8080/merchant/add \
  -H 'Authorization: Bearer changeme-use-a-real-secret-in-production' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Acme Corp",
    "pspName": "fakeStripe",
    "apiKey": "my-secret-key-123",
    "email": "acme@example.com"
  }'
```

Response:
```json
{
  "id": "merchant_019d202e-...",
  "name": "Acme Corp",
  "pspName": "fakeStripe",
  "apiKey": "my-secret-key-123",
  "email": "acme@example.com"
}
```

For a PayPal merchant, use `"pspName": "fakePaypal"`.

## API

### Charge a card — `POST /merch`

Authenticated with the merchant's own API key.

Required fields for all PSPs:
- `amount` — integer, in cents
- `currency` — e.g. `EUR`

For `fakeStripe`, also include: `cardNumber`, `cvv`, `expiryMonth`, `expiryYear`  
For `fakePaypal`, also include: `email`, `password`

```bash
curl -X POST http://localhost:8080/merch \
  -H 'Authorization: Bearer my-secret-key-123' \
  -H 'Content-Type: application/json' \
  -d '{
    "amount": 1500,
    "currency": "EUR",
    "cardNumber": "4242424242424242",
    "cvv": "123",
    "expiryMonth": "12",
    "expiryYear": "2030"
  }'
```

Response:
```json
{
  "id": "charge_...",
  "status": "success",
  "transactionId": "stripe_..."
}
```

### Create a merchant — `POST /merchant/add`

Requires the admin Bearer token.

| Field | Type | Notes |
|---|---|---|
| `name` | string | Display name |
| `pspName` | string | `fakeStripe` or `fakePaypal` |
| `apiKey` | string | The key this merchant will use to authenticate |
| `email` | string | Used for charge reports |

### Remove a merchant — `POST /merchant/remove`

Requires the admin Bearer token.

```bash
curl -X POST http://localhost:8080/merchant/remove \
  -H 'Authorization: Bearer changeme-use-a-real-secret-in-production' \
  -H 'Content-Type: application/json' \
  -d '{"id": "merchant_019d202e-..."}'
```

### Error responses

- `401` — missing or invalid Bearer token
- `422` — invalid or missing fields
- `500` — PSP configuration or runtime issue

## Charge report CLI

Sends a charge summary email for a given merchant and date range. Mailpit runs locally as a mock inbox.

```bash
docker compose exec app php bin/send-charge-report.php \
  --merchant=merchant_019d202e-... \
  --from=2026-03-01 \
  --to=2026-03-24
```

View delivered emails at `http://localhost:8025`.

To switch to file-based delivery instead of SMTP, set `EMAIL_TRANSPORT=file` in the app environment.

## Tests

```bash
docker compose run --rm app vendor/bin/phpunit tests/
```

Currently covers `ChargeService` and `ChargeReportService` with in-memory fakes (no database required).

## Notes

- PSPs are fake and never call external APIs
- The admin token should be changed from the default before any real deployment — generate one with `php -r "echo bin2hex(random_bytes(32));"` and set it in your environment
- `hash_equals()` is used for token comparison to prevent timing attacks
- Charge IDs use UUID v7 for time-ordered uniqueness
