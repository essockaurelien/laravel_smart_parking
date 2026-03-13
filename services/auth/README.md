# sPark - Auth Service

## Autore

@essock

## Panoramica

Autenticazione basata su JWT per la piattaforma sPark. Gestisce registrazione/login, refresh, logout e accesso per ruoli (base/premium/admin).

## Endpoint principali

- POST /api/register
- POST /api/login
- POST /api/refresh
- POST /api/logout
- POST /api/authz
- GET /api/me (auth.jwt)

## Note di comportamento

- I token di accesso sono JWT con jti.
- I refresh token sono salvati hashati in `jwt_refresh_tokens`.
- I token revocati sono tracciati in `jwt_revoked_tokens` e verificati da /api/authz.

## Sviluppo locale

- Usa PostgreSQL (`auth_db` in .env.example).
- Gira nello stack docker-compose di sPark.

