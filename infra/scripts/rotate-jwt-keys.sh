#!/usr/bin/env bash
set -euo pipefail

KEY_DIR=${KEY_DIR:-infra/envoy/keys}
JWKS_PATH=${JWKS_PATH:-infra/envoy/jwks.json}
KID=${JWT_KID:-spark-dev}

mkdir -p "$KEY_DIR"

node -e "const { generateKeyPairSync, createPublicKey } = require('crypto'); const fs = require('fs'); const keyDir = process.env.KEY_DIR; const kid = process.env.JWT_KID || 'spark-dev'; const { publicKey, privateKey } = generateKeyPairSync('rsa', { modulusLength: 2048, publicKeyEncoding: { type: 'spki', format: 'pem' }, privateKeyEncoding: { type: 'pkcs8', format: 'pem' } }); fs.writeFileSync(`${keyDir}/jwtRS256.key`, privateKey); fs.writeFileSync(`${keyDir}/jwtRS256.key.pub`, publicKey); const jwk = createPublicKey(publicKey).export({ format: 'jwk' }); jwk.alg = 'RS256'; jwk.use = 'sig'; jwk.kid = kid; fs.writeFileSync(process.env.JWKS_PATH || 'infra/envoy/jwks.json', JSON.stringify({ keys: [jwk] }, null, 2));"