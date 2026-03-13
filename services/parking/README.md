# sPark - Parking Service

## Autore

@essock

## Panoramica

Disponibilita parcheggi, prenotazioni e sessioni per la piattaforma sPark. Integra pagamenti e occupazione IoT.

## Endpoint principali

- GET /api/occupancy
- GET/POST /api/spots, PATCH /api/spots/{id}
- GET/POST /api/reservations, DELETE /api/reservations/{id}
- GET /api/sessions
- POST /api/checkin
- POST /api/checkout

## Note di comportamento

- Le prenotazioni richiedono utenti premium (admin sempre autorizzato).
- Le no-show scadono dopo `RESERVATION_TOLERANCE_MINUTES` e addebitano `RESERVATION_NO_SHOW_PENALTY`.
- Check-in/out crea pagamenti con tipo `parking` e notifica l'occupazione IoT via token interno.

## Sviluppo locale

- Richiede auth, payments e IoT; vedi .env.example (`AUTH_BASE_URL`, `PAYMENTS_BASE_URL`, `IOT_BASE_URL`, `IOT_INTERNAL_TOKEN`).
- Gira nello stack docker-compose di sPark.

