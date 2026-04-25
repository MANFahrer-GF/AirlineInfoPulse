# Changelog

All notable changes to AirlineInfoPulse are documented here.

## [1.2.4] — 2026-04-25

### 🚨 Critical Bugfix — Bid Killer

**The stale-bid cleanup added in v1.2.3 silently deleted fresh bids on
reused flight slots.** Pilots reported their just-placed bid disappearing
after a single visit to the Pulse dashboard — the cleanup matched any bid
whose `flight_id` had ever produced an ACCEPTED PIREP for that user, with
no time comparison. On airlines that reuse a master flight UUID across
different routes (private "scratchpad" slots with `route_code = PF`,
rotating schedule entries, schedule-importer reassignments) the cleanup
treated the user's brand-new bid as stale and dropped it.

This release **removes the page-load cleanup entirely** and replaces it
with an event-driven, time-aware mechanism that cannot kill a fresh bid.

### Changed

- **`Http/Controllers/AirlineInfoPulseController.php`** — Stale-bid
  cleanup removed from the dashboard render path. Page rendering is now
  side-effect-free, restoring the read-only contract documented in the
  README.
- **`Providers/AirlineInfoPulseServiceProvider.php`** — Registers the
  new `PirepObserver` in `boot()`.

### Added

- **`Observers/PirepObserver.php`** — New Eloquent observer on
  `App\Models\Pirep`. Cleanup fires exactly once when a PIREP transitions
  to `ACCEPTED`, and only deletes bids that were created *before* the
  PIREP. Bids placed *after* the PIREP (i.e. on a flight slot that has
  since been edited to a new route) survive untouched.

### Migration notes

No database migration required.

After upgrading, optionally run this one-shot SQL sweep to remove any
ghost bids that survived the previous cleanup logic:

```sql
DELETE b FROM bids b
JOIN pireps p
  ON p.flight_id = b.flight_id
 AND p.user_id   = b.user_id
WHERE p.state = 3            -- ACCEPTED
  AND p.created_at >= b.created_at;
```

(Adjust `bids` / `pireps` to your phpVMS table prefix, e.g.
`phpvms_bids` / `phpvms_pireps`.)

### Affected setups

If your airline uses any of the patterns below, v1.2.3 was actively
losing bids and v1.2.4 fixes that loss:

- **Private/scratchpad flights** — pilots editing one master flight to
  fly different routes (route_code `PF`, personal callsigns).
- **Rotating schedules** — flight UUIDs reused across the schedule
  rotation so the same `flight_id` represents different legs over time.
- **Schedule importers** — repeated `phpvms:update` runs that
  re-target existing flight UUIDs to new routes.

---

## [1.2.3] — 2026-04-05

### Fixed

- Auto-cleanup stale BIDs on page load *(reverted in 1.2.4 — caused
  fresh bids to be deleted on reused flight slots; replaced with an
  event-driven observer)*.

## [1.2.2]

- Missions: removed `ap-glass` styling.

## [1.2.1] and earlier

- See git history.
