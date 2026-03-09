# Azure Free Tier Deployment (DuckDNS)

This guide targets a single Azure VM (B2s) with Docker, DuckDNS, and Caddy TLS.

## 1) Provision Azure VM
- Region: Italy North
- Size: B2s
- OS: Ubuntu 22.04 LTS
- Open ports: 22, 80, 443
- Attach a static public IP

## 2) DuckDNS
- Create a DuckDNS subdomain (e.g., `spark-demo.duckdns.org`)
- Point it to your VM public IP

## 3) Install Docker
- Install Docker Engine and Docker Compose on the VM

## 4) Copy project
- Clone the repository onto the VM
- Create `.env.prod` from `.env.prod.example`

## 5) Generate JWT keys on the VM
Run:
```
node -e "const { generateKeyPairSync, createPublicKey } = require('crypto'); const fs = require('fs'); fs.mkdirSync('infra/envoy/keys', { recursive: true }); const { publicKey, privateKey } = generateKeyPairSync('rsa', { modulusLength: 2048, publicKeyEncoding: { type: 'spki', format: 'pem' }, privateKeyEncoding: { type: 'pkcs8', format: 'pem' } }); fs.writeFileSync('infra/envoy/keys/jwtRS256.key', privateKey); fs.writeFileSync('infra/envoy/keys/jwtRS256.key.pub', publicKey); const jwk = createPublicKey(publicKey).export({ format: 'jwk' }); jwk.alg = 'RS256'; jwk.use = 'sig'; jwk.kid = 'spark-dev'; fs.writeFileSync('infra/envoy/jwks.json', JSON.stringify({ keys: [jwk] }, null, 2));"`
```

## 6) Start production stack
```
docker compose -f docker-compose.yml -f docker-compose.prod.yml --env-file .env.prod up -d --build
```

## 7) Optional observability
```
docker compose -f docker-compose.yml -f docker-compose.observability.yml up -d
```

## 8) Security hardening checklist
- Restrict SSH by IP in Azure NSG
- Enable automatic OS updates
- Store secrets only in `.env.prod` on the VM
- Rotate JWT keys periodically
- Backup Postgres (already scheduled)
