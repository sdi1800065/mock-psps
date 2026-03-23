# Mock PSPs Payment Service

Small PHP service for charging cards through mock payment service providers (PSPs), authenticated per merchant.

## What This Project Does

- Exposes an HTTP API to create a charge.
- Authenticates every request using a merchant API key.
- Routes each merchant to a configured PSP (`fakeStripe` or `fakePaypal`).
- Persists charge records in MySQL.

## Tech Stack

- PHP 8.2+
- Apache (PHP image)
- MySQL 8
- Docker + Docker Compose
- Composer

## Project Structure

- `public/index.php`: HTTP entrypoint and routing.
- `src/Service/ChargeService.php`: charge orchestration.
- `src/Psp/*`: fake PSP implementations.
- `src/Repository/*`: storage abstraction and MySQL implementation.
- `config/merchants.php`: in-memory merchant configuration.

## Prerequisites

- Docker Desktop (or Docker Engine + Compose plugin)

## Run The Application

1. Build and start containers:

```bash
docker compose up -d --build
```

2. Check services:

```bash
docker compose ps
```

3. API base URL:

```text
http://localhost:8080
```

## Merchant Authentication

Use Bearer token authentication.

Configured demo merchants:

- `merchant-1` (PSP: `fakeStripe`) -> API key: `test-key-stripe-123`
- `merchant-2` (PSP: `fakePaypal`) -> API key: `test-key-paypal-456`

## API

### POST /charge

Creates a charge for the authenticated merchant.

Common required fields:

- `amount` (integer, cents)
- `currency` (string, example `EUR`)

PSP-specific required fields:

- For `fakeStripe`: `cardNumber`, `cvv`, `expiryMonth`, `expiryYear`
- For `fakePaypal`: `email`, `password`

### Example: fakeStripe merchant

```bash
curl -X POST http://localhost:8080/charge \
	-H 'Authorization: Bearer test-key-stripe-123' \
	-H 'Content-Type: application/json' \
	-d '{
		"amount": 1000,
		"currency": "EUR",
		"cardNumber": "4242424242424242",
		"cvv": "123",
		"expiryMonth": "12",
		"expiryYear": "2030"
	}'
```

### Example: fakePaypal merchant

```bash
curl -X POST http://localhost:8080/charge \
	-H 'Authorization: Bearer test-key-paypal-456' \
	-H 'Content-Type: application/json' \
	-d '{
		"amount": 2500,
		"currency": "EUR",
		"email": "buyer@example.com",
		"password": "secret"
	}'
```

### Response (success)

```json
{
	"id": "charge_...",
	"status": "success",
	"transactionId": "stripe_..."
}
```

### Error responses

- `401 Unauthorized`: missing or invalid Bearer token
- `422 Unprocessable Entity`: invalid or missing request fields
- `500 Internal Server Error`: PSP configuration/runtime issue

## Charge Report Command

The specification asks for a CLI command that collects charges by merchant and date range and sends them by email.

Current status: not implemented yet.

Planned command shape:

```bash
php bin/send-charge-report.php --merchant=merchant-1 --from=2026-03-01 --to=2026-03-23
```

## Tests

PHPUnit is configured in `composer.json`, but test cases are still pending implementation.

Once tests are added, run them with:

```bash
docker compose exec app vendor/bin/phpunit
```

## Assumptions And Trade-offs

- Merchant configuration is static and loaded from `config/merchants.php`.
- PSPs are fake by design and do not call external APIs.
- Charge IDs use `uniqid`, which is acceptable for this exercise but not ideal for high-scale production systems.
- Single endpoint, minimal routing and validation to keep the implementation focused.
- MySQL schema is created at runtime in repository constructor for simplicity.
