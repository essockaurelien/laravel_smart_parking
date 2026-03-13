# sPark - Web UI Service

## Autore

@essock

## Panoramica

Web UI server-rendered per la piattaforma sPark. Si autentica tramite il servizio auth e aggrega dati da parking, charging, payments e IoT.

## Pagine e azioni principali

- GET /login, POST /login
- GET /register, POST /register
- POST /logout
- GET /monitor (vista pubblica occupazione)
- GET / (dashboard per ruolo)
- POST /reservations, /reservations/{id}/cancel
- POST /charge-quotes, /charge-requests
- POST /checkin, /checkout
- POST /pricing (admin)

## Note di comportamento

- Salva token di accesso e utente in sessione.
- La dashboard legge dati dagli altri servizi tramite i loro base URL.
- La vista admin include lo stato luci Hue dal gateway IoT.

## Sviluppo locale

- Richiede base URL dei servizi; vedi .env.example (`AUTH_BASE_URL`, `PARKING_BASE_URL`, `CHARGING_BASE_URL`, `PAYMENTS_BASE_URL`, `IOT_BASE_URL`).
- Gira nello stack docker-compose di sPark.

