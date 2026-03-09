# Ops Hardening

## JWT Key Rotation (cron)
On the VM:
```
chmod +x infra/scripts/rotate-jwt-keys.sh
(crontab -l 2>/dev/null; echo "0 3 * * 0 /home/azureuser/sPark/infra/scripts/rotate-jwt-keys.sh") | crontab -
```
After rotation, restart auth and gateway:
```
docker compose -f docker-compose.yml -f docker-compose.prod.yml --env-file .env.prod restart auth api-gateway
```

## Fail2ban
Run:
```
sudo bash infra/scripts/setup-fail2ban.sh
```

## NSG Notes
- Allow inbound: 22 (restricted to your IP), 80, 443
- Deny inbound: all other ports
