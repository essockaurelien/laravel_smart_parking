#!/usr/bin/env bash
set -euo pipefail

sudo apt-get update
sudo apt-get install -y fail2ban

sudo tee /etc/fail2ban/jail.d/sshd.local > /dev/null <<'EOF'
[sshd]
enabled = true
port = ssh
maxretry = 5
findtime = 10m
bantime = 1h
EOF

sudo systemctl enable fail2ban
sudo systemctl restart fail2ban
