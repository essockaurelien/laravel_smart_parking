# sPark

## Autore

@essock

## Panoramica

sPark e una piattaforma modulare per parcheggi, ricarica e pagamenti, con una web UI e un gateway IoT. I servizi comunicano via HTTP e MQTT e condividono l'autenticazione tramite JWT.

## Servizi

- auth: registrazione/login, JWT, refresh, revoca
- parking: posti, prenotazioni, sessioni, occupazione
- charging: coda ricarica, eventi MQTT, pagamenti ricarica
- payments: tariffe e registri pagamenti
- iot-gateway: integrazione MQTT + emulatore Hue
- web-ui: dashboard server-rendered e monitor

## Flussi end-to-end

### Login e dashboard

1) L'utente fa login dalla Web UI -> auth /api/login.
2) La Web UI salva il token di accesso in sessione.
3) La dashboard aggrega dati da parking, charging, payments e (solo admin) IoT.

### Prenotazione -> check-in -> pagamento

1) L'utente crea una prenotazione dalla Web UI -> parking /api/reservations.
2) Check-in -> parking /api/checkin e aggiornamento occupazione -> endpoint interno IoT.
3) Check-out -> parking /api/checkout crea un pagamento -> payments /api/payments.

### Richiesta ricarica -> completamento -> pagamento

1) L'utente chiede un preventivo -> charging /api/charge-requests/quote.
2) L'utente crea una richiesta di ricarica -> charging /api/charge-requests.
3) Aggiornamenti di avanzamento -> /api/charge-requests/{id}/progress pubblica eventi MQTT.
4) Completamento -> payments /api/payments.

## Sviluppo locale

- Tutti i servizi girano nello stack docker-compose della root.
- Ogni servizio ha un .env.example con URL base e dipendenze.
- Il gateway IoT richiede un broker MQTT e la configurazione dell'emulatore Hue.

