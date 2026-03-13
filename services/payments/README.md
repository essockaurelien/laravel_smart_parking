# sPark - Payments Service

## Autore

@essock

## Panoramica

Registra i pagamenti e salva le tariffe per la piattaforma sPark. Usato da parking e charging per addebitare gli utenti.

## Endpoint principali

- GET /api/payments (filtri per tipo, utente, date)
- POST /api/payments (internal.token)
- GET /api/pricing
- POST /api/pricing (solo admin)

## Note di comportamento

- I pagamenti vengono creati con stato `paid`.
- Le tariffe richiedono ruolo admin e determinano prezzi per parking e charging.

## Sviluppo locale

- Usa PostgreSQL (`payments_db` in .env.example).
- Gira nello stack docker-compose di sPark.

