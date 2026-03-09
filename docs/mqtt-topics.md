# MQTT Topics

## Occupancy
- `parking/spot/{spotId}/occupied`
	- Payload: `{ "spot_id": 12, "occupied": true }`
- `parking/spot/{spotId}/freed`
	- Payload: `{ "spot_id": 12, "occupied": false }`

## MWbot status
- `mwbot/status`
	- Payload: `{ "status": "idle|charging|moving" }`
- `mwbot/assignment`
	- Payload: `{ "request_id": 99, "spot_id": 12, "user_id": "u-123" }`

## Charging
- `charging/request`
	- Payload: `{ "request_id": 99, "spot_id": 12, "target_percent": 80 }`
- `charging/progress/{requestId}`
	- Payload: `{ "request_id": 99, "spot_id": 12, "current_percent": 55, "target_percent": 80 }`
- `charging/complete/{requestId}`
	- Payload: `{ "request_id": 99, "spot_id": 12, "user_id": "u-123" }`

## Notifications
- `notify/user/{userId}`
	- Payload: `{ "message": "Charge complete" }`
