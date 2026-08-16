# Running Traccar on the Same VPS as app.fleetwize.io

Yes — this works fine, and for pilot scale (a handful of vehicles) it's the
more sensible choice over provisioning a second box: one less server to
patch/monitor/pay for, and the webhook call from Traccar to
`fleetwize-core` can stay on localhost instead of round-tripping over the
public internet.

**Trade-off to know going in:** the app and the tracker-ingestion pipeline
now share the same CPU/RAM/disk/network and the same blast radius — if the
VPS goes down, both stop; a spike in one can starve the other. For a pilot
this is a non-issue. If the fleet grows into the hundreds of vehicles,
revisit splitting Traccar onto its own box (the original
[traccar-production.md](traccar-production.md) plan) — nothing here paints
you into a corner, since Traccar's own state lives in its own Postgres
container and can be migrated later.

This assumes: Ubuntu/Debian VPS, the Laravel app already deployed the
traditional way (Nginx + PHP-FPM, e.g. via Forge or a manual setup — not
containerized), and you have root/sudo SSH access. Adjust package-manager
commands if you're on something else.

## 1. Check headroom before installing anything

```bash
free -h        # want at least ~1GB free after the app's normal usage
df -h /        # want a few GB free for Docker images + Postgres data
nproc          # 1 vCPU is workable at pilot scale; 2+ is more comfortable
```

Traccar itself is light (a JVM process + Postgres), but it's landing on a
box that's already running Nginx, PHP-FPM, and MySQL for the main app —
don't skip this check.

## 2. Install Docker Engine + Compose plugin

Don't use the ancient `docker.io` apt package — pull from Docker's own repo
so you get the Compose v2 plugin (`docker compose`, no hyphen):

```bash
sudo apt-get update
sudo apt-get install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# so you don't need sudo for every docker command (log out/in after this)
sudo usermod -aG docker $USER
```

Verify: `docker --version && docker compose version`

## 3. Generate a webhook secret

You'll use this same value in two places (Traccar's forwarding config and
the Laravel app's `.env`). Generate it once now:

```bash
openssl rand -hex 32
```

Keep the output handy — call it `WEBHOOK_SECRET` below.

## 4. Set up Traccar

```bash
sudo mkdir -p /opt/traccar
cd /opt/traccar
```

`/opt/traccar/docker-compose.yml`:

```yaml
services:
  traccar:
    image: traccar/traccar:latest
    restart: unless-stopped
    ports:
      - "5023:5023"       # GT06 tracker port — must be reachable from the public internet
      - "127.0.0.1:8082:8082"   # web UI/API — localhost only, not exposed publicly (see step 7)
    volumes:
      - traccar-data:/opt/traccar/data
      - traccar-logs:/opt/traccar/logs
      - ./traccar.xml:/opt/traccar/conf/traccar.xml:ro
    depends_on:
      - db

  db:
    image: postgres:16
    restart: unless-stopped
    environment:
      POSTGRES_DB: traccar
      POSTGRES_USER: traccar
      POSTGRES_PASSWORD: ${TRACCAR_DB_PASSWORD}
    volumes:
      - traccar-db:/var/lib/postgresql/data

volumes:
  traccar-data:
  traccar-logs:
  traccar-db:
```

`/opt/traccar/traccar.xml` — note `forward.url` points at the app over
**localhost**, since they're on the same box now (no public round-trip,
and no need to open anything for this call):

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE properties SYSTEM 'http://java.sun.com/dtd/properties.dtd'>
<properties>
    <entry key='database.driver'>org.postgresql.Driver</entry>
    <entry key='database.url'>jdbc:postgresql://db:5432/traccar</entry>
    <entry key='database.user'>traccar</entry>
    <entry key='database.password'>REPLACE_WITH_TRACCAR_DB_PASSWORD</entry>

    <entry key='gt06.port'>5023</entry>
    <entry key='web.port'>8082</entry>

    <entry key='forward.enable'>true</entry>
    <entry key='forward.url'>http://127.0.0.1/webhooks/traccar/position</entry>
    <entry key='forward.type'>json</entry>
    <entry key='forward.header'>X-Webhook-Secret: REPLACE_WITH_WEBHOOK_SECRET</entry>
    <entry key='forward.retry.enable'>true</entry>

    <!-- Trackers auto-register on first contact; turn this off once the
         pilot fleet is fully enrolled, so a misdialed/rogue device can't
         silently create a phantom entry. -->
    <entry key='database.registerUnknown'>true</entry>
</properties>
```

> **Careful with `forward.url` = `http://127.0.0.1/...`:** this hits
> whatever Nginx is listening on `127.0.0.1:80` — that's only correct if
> Nginx's default/catch-all server block for port 80 is your
> `app.fleetwize.io` vhost. If Nginx has multiple sites on this box, either
> add an explicit `Host: app.fleetwize.io` header to the forward config,
> or just use the real public URL instead
> (`https://app.fleetwize.io/webhooks/traccar/position`) — simpler and
> unambiguous, at the cost of one extra network hop through your own
> reverse proxy. For a single-site VPS, `127.0.0.1` is fine and faster.

Replace `REPLACE_WITH_TRACCAR_DB_PASSWORD` (pick a new random password) and
`REPLACE_WITH_WEBHOOK_SECRET` (the value from step 3).

Create a `.env` file for the Postgres password so it's not hardcoded in the compose file:

```bash
echo "TRACCAR_DB_PASSWORD=$(openssl rand -hex 24)" | sudo tee /opt/traccar/.env
```

(If you generate the password this way, copy the same value into
`traccar.xml`'s `database.password` field above — they must match.)

## 5. Firewall — only open the tracker port

```bash
sudo ufw allow 5023/tcp comment 'GT06 trackers'
sudo ufw status
```

Do **not** open 8082 — it's bound to `127.0.0.1` in the compose file above,
so `ufw` allowing/denying it is moot, but double-check nothing else (a
cloud provider security group, for instance) exposes it. Port 80/443
should already be open for the main app; nothing changes there.

## 6. Start it

```bash
cd /opt/traccar
sudo docker compose up -d
sudo docker compose logs -f traccar   # watch for "Server started" then Ctrl+C
```

## 7. Create the Traccar API user (for the app's REST pulls)

The Laravel app's `TraccarService` needs its own API credentials
(`TRACCAR_USERNAME`/`TRACCAR_PASSWORD`) for on-demand refresh calls — this
is separate from the webhook secret. Reach the UI via an SSH tunnel rather
than exposing it publicly:

```bash
# from your own machine, not the VPS:
ssh -L 8082:localhost:8082 your-user@your-vps-ip
```

Leave that running, then open `http://localhost:8082` in your own browser.
Log in with Traccar's default `admin`/`admin` (**change this password
immediately** — Settings → Users), then create a dedicated non-admin user
for the app to authenticate as.

## 8. Point the Laravel app at it

On the VPS, edit the **app's** production `.env` (not the Traccar one):

```
TRACCAR_BASE_URL=http://127.0.0.1:8082
TRACCAR_USERNAME=<the user you created in step 7>
TRACCAR_PASSWORD=<its password>
TRACCAR_WEBHOOK_SECRET=<the same value you put in traccar.xml's forward.header>
```

Then, from the app's deploy directory:

```bash
php artisan config:cache
```

(Skip `config:cache` if your deploy process already runs it — just make
sure it re-runs after this `.env` change, or Laravel will keep serving the
old cached config.)

## 9. Validate end-to-end before touching any real tracker

This mirrors the validation step in
[traccar-production.md](traccar-production.md#3-validate-the-middleware-before-touching-any-real-tracker):
send a synthetic GT06 packet at `127.0.0.1:5023` (or the VPS's public IP if
testing from elsewhere), and confirm:

1. Traccar's logs show the device connecting and decoding a position.
2. The webhook actually lands — check `storage/logs/laravel.log` on the
   app for the incoming request, or add temporary logging to
   `TrackerWebhookController` if needed.
3. A `vehicle_tracker_states` row appears for a **test** vehicle (not a
   real customer's).

Only proceed to real hardware once all three pass — same rule as the
original runbook.

## 10. (Optional) Give the Traccar dashboard a real URL

If SSH-tunneling every time you want to glance at the map gets old, add a
subdomain instead of exposing 8082 directly:

1. DNS: point `traccar.fleetwize.io` (A record) at the VPS's IP.
2. New Nginx server block (e.g. `/etc/nginx/sites-available/traccar`):

    ```nginx
    server {
        listen 80;
        server_name traccar.fleetwize.io;

        location / {
            proxy_pass http://127.0.0.1:8082;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
        }
    }
    ```

    ```bash
    sudo ln -s /etc/nginx/sites-available/traccar /etc/nginx/sites-enabled/
    sudo nginx -t && sudo systemctl reload nginx
    ```

3. TLS: `sudo certbot --nginx -d traccar.fleetwize.io` (assumes certbot is
   already installed, per how the main app's cert was likely issued).
4. Since this makes the Traccar login page internet-reachable, make sure
   the admin password was actually changed in step 7, and consider adding
   Nginx basic auth or an IP allowlist in front of it as a second layer —
   Traccar's own auth is the only thing standing between the internet and
   your fleet's live locations otherwise.

## Ongoing: keep an eye on resource sharing

```bash
docker stats            # Traccar + Postgres memory/CPU, live
free -h                 # overall box headroom
```

If the app starts feeling sluggish after this, that's your signal to
revisit the separate-box plan rather than upsizing the VPS reactively.
