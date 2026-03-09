# sPark - Smart Parking System

This workspace contains a Laravel 10 microservices setup for the smart parking system with MWbot charging.

## Services
- auth (OAuth2 with Laravel Passport)
- parking
- charging
- payments
- iot-gateway
- web-ui (Blade UI)

## Local Infrastructure (Docker)
- Postgres 15
- pgAdmin 4
- Eclipse Mosquitto

## Quick Start
1. Start Docker services:
   - `docker compose up -d postgres pgadmin mosquitto`
2. Start Laravel services (local PHP) or run full stack in Docker:
   - `docker compose up -d`

## API Gateway
The Envoy gateway listens on `http://localhost:8000` and routes requests by path prefix:
- `/auth/*` -> auth service
- `/parking/*` -> parking service
- `/charging/*` -> charging service
- `/payments/*` -> payments service
- `/iot/*` -> iot-gateway service
- `/` -> web-ui

Gateway enforces JWT validation (RS256) for protected services and applies role-based checks on selected endpoints.
Rate limiting is enforced per client IP via the Envoy rate limit service.

Public routes (no token required):
- `GET /parking/api/occupancy`
- `GET /payments/api/pricing`
- `/auth/*`

Role mapping (JWT claim `role`):
- `admin`: full access, plus admin-only endpoints like `POST /parking/api/spots` and `POST /payments/api/pricing`
- `premium`: all charging endpoints under `/charging/*`
- `base`: standard parking + payments usage
Admin-only endpoints are configured in [infra/envoy/envoy.yaml](infra/envoy/envoy.yaml).

Dev keys are generated locally and not committed.
To generate them, run:
- `node -e "const { generateKeyPairSync, createPublicKey } = require('crypto'); const fs = require('fs'); fs.mkdirSync('infra/envoy/keys', { recursive: true }); const { publicKey, privateKey } = generateKeyPairSync('rsa', { modulusLength: 2048, publicKeyEncoding: { type: 'spki', format: 'pem' }, privateKeyEncoding: { type: 'pkcs8', format: 'pem' } }); fs.writeFileSync('infra/envoy/keys/jwtRS256.key', privateKey); fs.writeFileSync('infra/envoy/keys/jwtRS256.key.pub', publicKey); const jwk = createPublicKey(publicKey).export({ format: 'jwk' }); jwk.alg = 'RS256'; jwk.use = 'sig'; jwk.kid = 'spark-dev'; fs.writeFileSync('infra/envoy/jwks.json', JSON.stringify({ keys: [jwk] }, null, 2));"`

Internal-only:
- `POST /payments/api/payments` requires `X-Internal-Token` and is not exposed via the gateway

## Migrations and Seeds
Run these once for each service that needs data.
- Auth: `cd services/auth && php artisan migrate && php artisan db:seed`
- Auth (Passport): `cd services/auth && php artisan passport:install`
- Parking: `cd services/parking && php artisan migrate && php artisan db:seed`
- Charging: `cd services/charging && php artisan migrate`
- Payments: `cd services/payments && php artisan migrate && php artisan db:seed`
- IoT Gateway: `cd services/iot-gateway && php artisan migrate`

## Authentication
All service APIs (except auth) require a Bearer token from the auth service.
Use `/api/register` or `/api/login` to get an `access_token`, then send `Authorization: Bearer <token>`.
Use `/api/refresh` to rotate access tokens and `/api/logout` to revoke.

## Philips Hue Emulator
Use diyHue as the Hue bridge emulator.
- Docker image: diyhue/core:latest
- See [docs/setup-hue-emulator.md](docs/setup-hue-emulator.md) for setup.

## Postman
Postman collections will be added under the `postman/` folder for API testing.

## Observability
Start the observability stack:
- `docker compose -f docker-compose.yml -f docker-compose.observability.yml up -d`

Services:
- Prometheus: `http://localhost:9090`
- Grafana: `http://localhost:3000`
- Loki: `http://localhost:3100`
- Tempo: `http://localhost:3200`

## Production Deployment (Azure + DuckDNS)
See [docs/deploy-azure-free-tier.md](docs/deploy-azure-free-tier.md).

## Operations Playbook
- Azure CLI provisioning: [docs/azure-cli-playbook.md](docs/azure-cli-playbook.md)
- Hardening (JWT rotation, fail2ban): [docs/ops-hardening.md](docs/ops-hardening.md)

## Automation
- End-to-end Azure CLI script: [infra/scripts/azure-provision.sh](infra/scripts/azure-provision.sh)
- Cloud-init: [infra/scripts/cloud-init.yaml](infra/scripts/cloud-init.yaml)
- CI/CD workflow: [.github/workflows/deploy.yml](.github/workflows/deploy.yml)

## Production Deployment (Cloud free-tier + Docker)
1. Create a domain and point DNS to your server IP.
2. Create secrets locally (not committed):
   - `infra/envoy/keys/jwtRS256.key`
   - `infra/envoy/keys/jwtRS256.key.pub`
   - `infra/envoy/jwks.json`
3. Copy `.env.prod.example` to `.env.prod` and fill values.
4. Start with the prod override:
   - `docker compose -f docker-compose.yml -f docker-compose.prod.yml --env-file .env.prod up -d --build`

Notes:
- The HTTPS entrypoint is handled by Caddy with Let's Encrypt.
- Internal ports are not exposed in the prod override.
