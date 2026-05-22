# Deployment Guide — neofi_mikrotik (Ubuntu + Docker + Nginx Reverse Proxy)

Repository: https://github.com/cjtech91/neofi_mikrotik.git

## 1) Requirements

- Ubuntu Server 22.04/24.04
- Docker Engine + Docker Compose plugin
- MikroTik RouterOS device(s) with API enabled (api / api-ssl)
- Open ports (minimum):
  - `80/tcp` (HTTP for Let’s Encrypt challenge + redirect to HTTPS)
  - `443/tcp` (HTTPS access to the panel through Nginx reverse proxy)
- Optional (only if you need DB access outside the server; not recommended):
  - `5432/tcp`

## 2) Install Docker (Ubuntu)

Follow the official Docker install guide for Ubuntu:
https://docs.docker.com/engine/install/ubuntu/

After install:

```bash
sudo systemctl enable --now docker
```

Optional (so you can run docker without sudo):

```bash
sudo usermod -aG docker $USER
```

Then logout/login.

## 3) Clone the Project on the Server

You can do this using MobaXterm by connecting to your Ubuntu server via SSH, then running the commands in the terminal.

```bash
sudo mkdir -p /opt/neofi_mikrotik
sudo chown -R $USER:$USER /opt/neofi_mikrotik
cd /opt/neofi_mikrotik
git clone https://github.com/cjtech91/neofi_mikrotik.git .
```

## 4) Configure Environment Values

Open `docker-compose.yml` and update these values before production:

- `APP_KEY` (required): used for encryption (device password storage). Use a long random string.
- `API_KEY` (required): required header `X-API-Key` for all `/api/*` requests.
- `ADMIN_USER` / `ADMIN_PASS` (required): Basic Auth for `/admin/*`.
- Let’s Encrypt (HTTPS):
  - `LE_DOMAIN` (required): your public domain (must point to this server).
  - `LE_EMAIL` (required): email for certificate registration/renewal notices.
  - `LE_STAGING` (optional): set to `1` for testing to avoid rate limits.
- PostgreSQL:
  - `POSTGRES_PASSWORD`
  - `DB_PASS`
  - Keep them aligned and use strong passwords.

## 5) Build and Run

From the project directory:

```bash
docker compose up -d --build
```

Check status:

```bash
docker compose ps
```

Logs:

```bash
docker compose logs -f nginx
docker compose logs -f web
docker compose logs -f db
```

## 6) Verify Endpoints

Health:

- `GET https://YOUR_DOMAIN/health`
- Expected response:
  - `{"ok":true}`

Admin:

- `GET https://YOUR_DOMAIN/admin`
- Browser will prompt Basic Auth (use `ADMIN_USER` / `ADMIN_PASS`).

## 7) MikroTik Router Setup (Required)

### Enable RouterOS API

On MikroTik:

```routeros
/ip service enable api
```

Optional SSL API:

```routeros
/ip service enable api-ssl
```

### Secure Access (Recommended)

- Restrict access to the API service to your server’s IP address (firewall rules / allowed address list).
- Create a dedicated RouterOS user for API access with required permissions (hotspot/ppp/dns depending on features you use).

## 8) Register Devices in neofi_mikrotik (Required)

All API calls require:

- Header: `X-API-Key: <API_KEY>`

### Create a device

`POST https://YOUR_DOMAIN/api/devices`

Body example:

```json
{
  "name": "Main Router",
  "host": "192.168.88.1",
  "api_port": 8728,
  "use_ssl": false,
  "username": "apiuser",
  "password": "apipass"
}
```

### Test connection

`POST https://YOUR_DOMAIN/api/devices/{id}/test-connection`

## 9) Use Admin Configuration Pages

Open each page via:

- `/admin/interfaces`
- `/admin/pppoe`
- `/admin/adblocker`
- `/admin/qos`
- etc.

### Save Buttons + CSRF

- Each config page has a Save button that stores config in PostgreSQL (`module_configs` table).
- Admin POST actions are CSRF-protected; do not submit these forms from other origins.

### Interfaces (Live Sync)

- Page: `/admin/interfaces`
- Save configuration (Default Router, Sync interval)
- Use “Sync Now” to fetch live interfaces from the selected router.

### Adblocker (Apply to Router)

- Page: `/admin/adblocker`
- Configure:
  - Router
  - Mode: enabled/disabled
  - Redirect IP
  - Allowlist / Denylist domains
- Click “Save & Apply to Router”
  - It updates `/ip/dns/static` entries with comment `neofi-adblock`.

### PPPoE (Apply to Router)

- Page: `/admin/pppoe`
- Configure Router + Default Profile
- Click “Save & Apply to Router”
  - Ensures `/ppp/profile` exists (creates it if missing).

## 10) Firewall / Security Checklist

- Change all default `change-me-*` values.
- Keep DB port `5432` closed to the internet (recommended).
- Restrict MikroTik API access to server IP only.
- Use strong Basic Auth credentials for `/admin`.
- Make sure ports `80` and `443` are open to the internet for HTTPS and renewals.

## 11) Updating / Redeploy

```bash
cd /opt/neofi_mikrotik
git pull
docker compose up -d --build
```

## 12) Backup / Restore Database (Basic)

Backup:

```bash
docker compose exec -T db pg_dump -U neofi neofi > backup.sql
```

Restore:

```bash
cat backup.sql | docker compose exec -T db psql -U neofi -d neofi
```

## 13) Troubleshooting

- If `/admin` loads but saving fails:
  - Check `docker compose logs -f web`
  - Verify DB is reachable
  - Verify system time and that sessions are working (cookies enabled)
- If MikroTik actions fail:
  - Ensure API is enabled and reachable from the server
  - Verify port 8728/8729 and firewall rules on MikroTik
  - Re-test: `POST /api/devices/{id}/test-connection`
