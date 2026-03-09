# Philips Hue Emulator (diyHue)

## Option A: Docker (recommended)
- Image: diyhue/core:latest
- Compose service is already defined in the root `docker-compose.yml`.
- Access UI at `http://localhost:8080` after `docker compose up -d diyhue`.

## Option B: Desktop
If you prefer a desktop emulator, tell me and I will add the exact steps for your OS.

## Integration Notes
- The IoT gateway will publish occupancy/charging events to MQTT.
- Hue emulator can be used to visualize spot occupancy using virtual lights.
- IoT API endpoints:
	- `GET /api/hue/lights` (admin view)
	- `POST /api/internal/spot/occupancy` (internal token)
- Required env vars in docker-compose: `HUE_BASE_URL`, `HUE_USERNAME`, `INTERNAL_SHARED_TOKEN`.
