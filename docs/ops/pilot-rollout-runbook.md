# Pilot Rollout Runbook — Repointing Real Trackers to Traccar

**This requires a human with a phone and physical/SIM access to the pilot
vehicles' trackers.** Nothing in this runbook can be executed by an AI
agent — it's here so whoever runs the pilot has an exact, low-risk sequence
to follow, rather than improvising against live customer hardware.

Prerequisite: [traccar-production.md](traccar-production.md) is deployed and
validated with a synthetic packet (step 3 there) before you touch any real
tracker.

## 1. Pick 2–3 pilot vehicles

Choose vehicles that are easy to physically re-check (e.g. parked at your own
office/yard) in case something needs correcting. Get each one's
`tracker_phone_number` from the `vehicles` table.

## 2. Confirm dual-server support first — do not skip this

Before sending any config-changing SMS, send the WanWay G18's status/query
command (not a config change) to confirm the device responds at all and to
see its current firmware version. **The exact SMS syntax for the G18
specifically was not confirmed during the task #5 spike** — the generic
GT06-family format is `SERVER,0,<ip>,<port>,0#`, and a secondary/backup slot
is often available via `SERVER,1,<ip>,<port>,0#`, but this needs verifying
against one physical unit (or WanWay/your reseller support) before it's
trusted on the full pilot batch. Test the exact command on **one** device
first.

If dual-server works: add Traccar as the secondary server and leave `18gps.net`
as primary. This is non-destructive — `18gps.net` keeps working exactly as
today, Traccar just starts receiving a copy of the same stream. This is the
safe path and the one to prefer.

If dual-server isn't supported: switching primary server stops `18gps.net`
from receiving that device's data. Only do this after step 2's single-device
test has fully passed steps 3–4 below.

## 3. Send the config SMS

```
SERVER,0,<traccar-public-ip>,5023,0#
```

(or `SERVER,1,...` for the secondary slot, if supported — see step 2)

Send from a phone to the vehicle's `tracker_phone_number`. Expect an SMS
reply from the device confirming the new setting — if nothing comes back
within a few minutes, the device may not have signal or the command syntax
may be wrong; don't proceed to the next vehicle until this one is confirmed.

## 4. Validate

For each pilot vehicle, over the following hour of normal driving:
1. Check `vehicle_tracker_states` for that vehicle — is `reported_at`
   advancing roughly every time the vehicle moves?
2. Cross-check the lat/lng/speed/ignition against what `18gps.net`'s own
   dashboard shows for the same vehicle at the same moment — they should
   agree closely (small drift is normal, large disagreement is not).
3. Open the vehicle in the Fleetwize mobile app (task #8) — confirm the map
   marker, ignition tile, and speed tile update live.

Only move to the next pilot vehicle once all three pass.

## 5. Rollback

If a device stops reporting anywhere (neither `18gps.net` nor Traccar) after
the SMS: resend the original `18gps.net` server address as the primary slot.
Keep a note of the exact pre-change `SERVER,0,...` value for every device you
touch, captured *before* step 3, specifically so this rollback is a known
command, not a guess.

## 6. After the pilot passes

Only then does it make sense to plan the full-fleet SMS rollout (bulk-sending
this same command to every `tracker_phone_number`) — that's future work, not
part of this pilot.
