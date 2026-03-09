# API Contracts

Base URL (gateway): `http://localhost:8000`

Roles: `base`, `premium`, `admin`

## Auth Service (Public)
- `POST /auth/api/oauth/token`
- `POST /auth/api/register`
- `POST /auth/api/login`
- `POST /auth/api/refresh`
- `POST /auth/api/logout`
- `POST /auth/api/authz` (internal authz check)
- `GET /auth/api/me` (auth)

## Parking Service

Public
- `GET /parking/api/occupancy` (query: `include_spots=true`)

Premium + Admin
- `GET /parking/api/spots` (availability)
- `GET /parking/api/reservations`
- `POST /parking/api/reservations`
- `DELETE /parking/api/reservations/{reservation}`

Base + Premium + Admin
- `GET /parking/api/sessions`
- `POST /parking/api/checkin`
- `POST /parking/api/checkout`

Admin only
- `POST /parking/api/spots`
- `PATCH /parking/api/spots/{spot}`

## Charging Service (Base + Premium + Admin)
- `GET /charging/api/charge-requests`
- `POST /charging/api/charge-requests/quote`
- `POST /charging/api/charge-requests`
- `GET /charging/api/charge-requests/{id}`
- `POST /charging/api/charge-requests/{id}/cancel`
- `POST /charging/api/charge-requests/{id}/progress`

## Payments Service

Public
- `GET /payments/api/pricing`

Base + Premium + Admin
- `GET /payments/api/payments` (filters: `type`, `user_id`, `user_role`, `from`, `to`)

Admin only
- `POST /payments/api/pricing`

Internal (system-to-system)
- `POST /payments/api/payments` (header: `X-Internal-Token`, not exposed via gateway)

## IoT Gateway

Admin only
- `POST /iot/api/mqtt/publish`
- `GET /iot/api/mqtt/status`
- `GET /iot/api/hue/lights`
- `POST /iot/api/hue/lights/{lightId}`

Internal (system-to-system)
- `POST /iot/api/internal/spot/occupancy`
