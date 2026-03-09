#!/usr/bin/env bash
set -euo pipefail

# Azure CLI end-to-end provisioning (no variables).
# Edit values inline before running.

az login
az account set --subscription "00000000-0000-0000-0000-000000000000"

# Resource group
az group create --name "spark-rg" --location "italynorth"

# Public IP
az network public-ip create --resource-group "spark-rg" --name "spark-vm-pip" --sku Standard --allocation-method Static --dns-name "spark-demo"

# NSG and rules
az network nsg create --resource-group "spark-rg" --name "spark-vm-nsg"
az network nsg rule create --resource-group "spark-rg" --nsg-name "spark-vm-nsg" --name "AllowSSH" --priority 1000 --access Allow --protocol Tcp --direction Inbound --source-address-prefixes "1.2.3.4/32" --destination-port-ranges 22
az network nsg rule create --resource-group "spark-rg" --nsg-name "spark-vm-nsg" --name "AllowHTTP" --priority 1010 --access Allow --protocol Tcp --direction Inbound --source-address-prefixes "*" --destination-port-ranges 80
az network nsg rule create --resource-group "spark-rg" --nsg-name "spark-vm-nsg" --name "AllowHTTPS" --priority 1020 --access Allow --protocol Tcp --direction Inbound --source-address-prefixes "*" --destination-port-ranges 443

# VNet + subnet
az network vnet create --resource-group "spark-rg" --name "spark-vm-vnet" --address-prefix 10.0.0.0/16 --subnet-name "spark-vm-subnet" --subnet-prefix 10.0.1.0/24

# NIC
az network nic create --resource-group "spark-rg" --name "spark-vm-nic" --vnet-name "spark-vm-vnet" --subnet "spark-vm-subnet" --network-security-group "spark-vm-nsg" --public-ip-address "spark-vm-pip"

# VM (Ubuntu 22.04 LTS, B2s)
az vm create \
  --resource-group "spark-rg" \
  --name "spark-vm" \
  --nics "spark-vm-nic" \
  --image Ubuntu2204 \
  --size Standard_B2s \
  --admin-username "azureuser" \
  --generate-ssh-keys

# Install Docker on VM
az vm run-command invoke --resource-group "spark-rg" --name "spark-vm" --command-id RunShellScript --scripts @infra/scripts/install-docker.sh

# Connect
ssh azureuser@spark-demo.italynorth.cloudapp.azure.com
