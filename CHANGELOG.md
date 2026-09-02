# Changelog

All notable changes to AirlineInfoPulse are documented here.

## [1.4.4] — 2026-09-02

### Fixed

- **Maintenance feed reported “finished — back in service” for a grounded aircraft.**
  Reported on GSG (EI-EIE, ITA A320): the feed showed two entries for the same aircraft,
  both green and closed, while it was in fact grounded after four hard landings and
  ferrying to Naples for a structural inspection.
  The feed derived its rows itself from just two columns — `started_at` meant "started",
  `finished_at` meant "done". That was wrong in three ways: an order that was merely
  *superseded* (SkyAdventures closes the waiting order as `done` when a shop is assigned)
  produced a green all-clear at the exact moment the aircraft departed for the shop; an
  order in state `to_werft` never appeared at all, because `started_at` is only set on
  arrival; and a grounded `awaiting_werft` order looked like any other.
  The feed now asks SkyAdventures what an order means
  (`Support\MaintenanceFeed::eventsFor()`, available from SkyAdventures v0.64.0) and
  renders the returned events. **Requires SkyAdventures ≥ v0.64.0** for the new behaviour;
  with an older version the previous path stays active, without errors.
- The date filter for maintenance events now also covers `created_at`/`updated_at`, so an
  order that was created today but has not started yet is no longer missing from the feed.

## [1.4.2] — 2026-06-16

### Changed
- **Wartungs-Kachel zeigt das volle Board** (gleiche Infos wie auf der Überführungs-/Flottenseite): Flugzeug, Airline, Phase, Werft/Route, Restzeit + Pilot auf Live-Beinen — statt der kompakten Kurzliste.

## [1.4.1] — 2026-06-16

### Changed
- **Wartungs-Kachel im Glas-Design.** Die SkyAdventures-Wartungs-Kachel sitzt jetzt in einer echten `.ap-glass`-Karte mit Header (Schraubenschlüssel + Titel) und bindet das Widget im neuen `bare`-Modus ein (nur Inhalt). Vorher hatte das Widget seine eigene helle Box, die im Dark-Dashboard nicht zum Design passte; jetzt sieht die Kachel aus wie die übrigen (Aktive Buchungen, Feed …).

## [1.4.0] — 2026-06-16

### Added — SkyAdventures-Wartung im Dashboard
- **Wartungs-Kachel** (`partials/widgets.blade.php`): bindet das wiederverwendbare SkyAdventures-Wartungs-Widget (`skyadventures::widgets.maintenance`, kompakt) ein — zeigt live, welche Maschinen gerade im Wartungs-/Überführungs-Ablauf sind. Nur wenn SkyAdventures installiert ist (`view()->exists`-Guard); das Widget blendet sich selbst aus, wenn die Flotte aus ist oder nichts läuft.
- **Wartungs-Meilensteine im „Was ist passiert"-Feed** (`getFeed()`): SkyAdventures-Order-Ereignisse (Wartung gestartet · zurück im Dienst) erscheinen als `type=maintenance`-Einträge mit Flugzeug-Kennung. Read-only, aus `sa_maintenance_orders`, nur wenn die Tabelle existiert. Labels aus den SkyAdventures-Übersetzungen.

## [1.3.1] — 2026-06-15

### Fixed
- **View path registration now only registers existing directories.** `registerViews()` appended `/modules/airlineinfopulse` to every `view.paths` entry (theme-override paths) which usually don't exist. Live rendering was never affected — the view finder skips missing dirs lazily and falls back to `Resources/views` — but `php artisan view:cache` / `optimize` eager-scans every registered path via the Symfony Finder and threw `DirectoryNotFoundException`. Now wrapped in `array_filter(…, 'is_dir')`; `Resources/views` always exists so the namespace keeps at least one valid path, and existing theme overrides are still picked up.

## [1.3.0] — 2026-06-14

### Added — Awards in the Activity Feed

The activity feed ("What happened" / *Was ist passiert*) now surfaces
**awards as they are granted**. Whenever phpVMS hands a pilot an award, a
new `award` event appears in the feed — the award image (with a 🏆 trophy
fallback when none is set), the award name, the receiving pilot (name
GDPR-shortened and linked to their profile) and the grant timestamp —
interleaved chronologically with PIREP, new-pilot and maintenance events,
and bounded by the same per-time-range limit as the rest of the feed.

This is **display-only**: it reflects awards that were *actually* granted
through phpVMS. There is no leaderboard and no ranking.

- **`Http/Controllers/AirlineInfoPulseController.php`** — `getFeed()` now
  reads `user_awards ⋈ awards` for the selected date range
  (`user_awards.created_at` = grant time), schema-guarded like the
  maintenance source, with the pilot name passed through
  `PulseHelper::shortName()`.
- **`Resources/views/partials/feed.blade.php`** — New `award` event block,
  styled to match the existing "new pilot" event.
- **`Resources/lang/*/pulse.php`** — New `award_received` key in all nine
  languages (de, en, es, fr, it, ja, pt-br, pt-pt, tr).

### Migration notes

No database migration required — uses the core phpVMS `awards` /
`user_awards` tables. If no awards were granted in the selected period the
feed simply shows none.

---

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
