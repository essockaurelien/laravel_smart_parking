# sPark - Charging Service

## Autore

@essock

## Panoramica

Gestisce richieste di ricarica e coda. Pubblica eventi MQTT e crea pagamenti quando la ricarica termina.

## Endpoint principali

- GET /api/charge-requests
- POST /api/charge-requests/quote
- POST /api/charge-requests
- POST /api/charge-requests/{id}/cancel
- POST /api/charge-requests/{id}/progress

## Note di comportamento

- Le richieste passano da queued -> charging -> completed/cancelled, con ricalcolo della coda.
- Topic MQTT: `mwbot/assignment`, `mwbot/status`, `charging/progress/{id}`, `charging/complete/{id}`, opzionale `notify/user/{id}`.
- Crea un pagamento con tipo `charging` tramite il servizio payments al completamento.

## Sviluppo locale

- Richiede broker MQTT e servizio payments; vedi .env.example (`MQTT_*`, `PAYMENTS_BASE_URL`, `CHARGING_*`).
- Gira nello stack docker-compose di sPark.

