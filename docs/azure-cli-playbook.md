# Azure CLI Playbook (Italy North, B2s)

This playbook provisions a VM, NSG rules, and public IP using Azure CLI.

## Prerequisites
- Azure CLI installed and logged in: `az login`
- Subscription selected: `az account set --subscription <SUBSCRIPTION_ID>`

## Variables
Set these first:
```
RG=spark-rg
LOC=italynorth
VM=spark-vm
USER=azureuser
DNSLABEL=spark-demo
SIZE=Standard_B2s
```

## Create resource group
```
az group create --name $RG --location $LOC
```

## Create public IP
```
az network public-ip create --resource-group $RG --name ${VM}-pip --sku Standard --allocation-method Static --dns-name $DNSLABEL
```

## Create NSG and rules (22/80/443 only)
```
az network nsg create --resource-group $RG --name ${VM}-nsg
az network nsg rule create --resource-group $RG --nsg-name ${VM}-nsg --name AllowSSH --priority 1000 --access Allow --protocol Tcp --direction Inbound --source-address-prefixes <YOUR_IP>/32 --destination-port-ranges 22
az network nsg rule create --resource-group $RG --nsg-name ${VM}-nsg --name AllowHTTP --priority 1010 --access Allow --protocol Tcp --direction Inbound --source-address-prefixes * --destination-port-ranges 80
az network nsg rule create --resource-group $RG --nsg-name ${VM}-nsg --name AllowHTTPS --priority 1020 --access Allow --protocol Tcp --direction Inbound --source-address-prefixes * --destination-port-ranges 443
```

## Create VNet and subnet
```
az network vnet create --resource-group $RG --name ${VM}-vnet --address-prefix 10.0.0.0/16 --subnet-name ${VM}-subnet --subnet-prefix 10.0.1.0/24
```

## Create NIC
```
az network nic create --resource-group $RG --name ${VM}-nic --vnet-name ${VM}-vnet --subnet ${VM}-subnet --network-security-group ${VM}-nsg --public-ip-address ${VM}-pip
```

## Create VM
```
az vm create \
  --resource-group $RG \
  --name $VM \
  --nics ${VM}-nic \
  --image Ubuntu2204 \
  --size $SIZE \
  --admin-username $USER \
  --generate-ssh-keys
```

## Install Docker on VM
```
az vm run-command invoke --resource-group $RG --name $VM --command-id RunShellScript --scripts @infra/scripts/install-docker.sh
```

## Connect
```
ssh $USER@${DNSLABEL}.${LOC}.cloudapp.azure.com
```

## DuckDNS
- Create a DuckDNS subdomain and point it to the VM public IP.
- Use that domain value in `.env.prod`.
