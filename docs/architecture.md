# Architecture Overview

- Microservices: auth, parking, charging, payments, iot-gateway, web-ui
- REST for service APIs
- MQTT for IoT events and notifications
- Database per service (separate Postgres databases)

## Core Flows
- Premium booking: web-ui -> parking -> payments
- Charging request: web-ui -> charging -> iot-gateway (MQTT)
- Notifications: charging -> iot-gateway -> MQTT topic -> client
