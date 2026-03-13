# sPark - IoT Gateway Service

## Autore

@essock

## Panoramica

Bridge MQTT e integrazione con emulatore Hue per la piattaforma sPark. Espone publish/status MQTT e controlla le luci dei posti.

## Endpoint principali

- POST /api/mqtt/publish
- GET /api/mqtt/status
- GET /api/hue/lights
- POST /api/hue/lights/{lightId}
- POST /api/internal/spot/occupancy (internal.token)

## Note di comportamento

- Gli aggiornamenti di occupazione pubblicano eventi MQTT su `parking/spot/{id}/occupied` o `parking/spot/{id}/freed`.
- Gli aggiornamenti di stato delle luci vengono inoltrati all'emulatore Hue (`HUE_BASE_URL`).

## Sviluppo locale

- Richiede broker MQTT e configurazione Hue; vedi .env.example (`MQTT_*`, `HUE_*`, `INTERNAL_SHARED_TOKEN`).
- Gira nello stack docker-compose di sPark.

