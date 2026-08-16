# Traccar Production Deployment

This stands up the real, always-on Traccar instance that replaces `18gps.net`
for live location/ignition data. It's a separate box from `fleetwize-core` —
Traccar just needs a public IP the tracker SIMs can reach over GPRS, and
network access to POST to `fleetwize-core`'s webhook.

Do not confuse this with the throwaway local instance used for the task #5
protocol spike (H2 database, `localhost` only, deleted after that spike).

## 1. Provision

Any small VPS works (1 vCPU / 1GB RAM is plenty at pilot scale). You need:
- A public IPv4 address (the trackers dial this directly over TCP, no DNS
  required for the device connection itself, but use a domain if you have one).
- Port `5023` open (GT06 protocol — matches the port used in the task #5 spike
  and referenced in `TraccarService`'s expectations).
- Port `8082` open if you want the Traccar web UI reachable (optional —
  useful for cross-checking a pilot vehicle's raw decoded position, but not
  required for the ingestion pipeline itself).

## 2. Run it (docker compose)

```yaml
# docker-compose.yml
services:
  traccar:
    image: traccar/traccar:latest
    restart: unless-stopped
    ports:
      - "5023:5023"   # gt06.port — must match what devices are configured to dial
      - "8082:8082"   # web UI (optional, put behind your own auth/reverse proxy if exposed)
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

```xml
<!-- traccar.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE properties SYSTEM 'http://java.sun.com/dtd/properties.dtd'>
<properties>
    <entry key='database.driver'>org.postgresql.Driver</entry>
    <entry key='database.url'>jdbc:postgresql://db:5432/traccar</entry>
    <entry key='database.user'>traccar</entry>
    <entry key='database.password'>REPLACE_WITH_TRACCAR_DB_PASSWORD</entry>

    <entry key='gt06.port'>5023</entry>
    <entry key='web.port'>8082</entry>

    <!-- Must match fleetwize-core's TRACCAR_WEBHOOK_SECRET (config/fleetwize.php) -->
    <entry key='forward.enable'>true</entry>
    <entry key='forward.url'>https://YOUR_FLEETWIZE_CORE_DOMAIN/webhooks/traccar/position</entry>
    <entry key='forward.type'>json</entry>
    <entry key='forward.header'>X-Webhook-Secret: REPLACE_WITH_TRACCAR_WEBHOOK_SECRET</entry>
    <entry key='forward.retry.enable'>true</entry>

    <!-- Trackers auto-register on first contact; turn this off once the pilot
         fleet is fully enrolled, so a misdialed/rogue device can't silently
         create a phantom entry. -->
    <entry key='database.registerUnknown'>true</entry>
</properties>
```

Then on `fleetwize-core`, set the matching production env vars (`config/fleetwize.php`
already reads these — see task #6/#7):

```
TRACCAR_BASE_URL=https://your-traccar-host:8082
TRACCAR_USERNAME=<create an API user in Traccar's web UI first>
TRACCAR_PASSWORD=<...>
TRACCAR_WEBHOOK_SECRET=<same long random string as forward.header above>
```

## 3. Validate the middleware before touching any real tracker

Repeat the task #5 spike's synthetic GT06 packet test (see git history /
session notes for `send_gt06.py`) against this **production** Traccar
instance, confirm:
- the position gets forwarded to `fleetwize-core`'s webhook
- a `vehicle_tracker_states` row appears for a **test** vehicle you create
  for this purpose (not a real customer vehicle)

Only proceed to real hardware once this passes end-to-end.
