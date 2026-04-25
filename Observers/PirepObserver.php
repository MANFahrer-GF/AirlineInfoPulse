<?php

namespace Modules\AirlineInfoPulse\Observers;

use App\Models\Enums\PirepState;
use App\Models\Pirep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes stale bids the moment a PIREP is accepted.
 *
 * phpVMS clears the bid automatically when a PIREP is prefiled by ACARS,
 * but bids can survive when the PIREP reaches ACCEPTED via a path that
 * bypasses the normal bid lifecycle — e.g. admin accepting a manual
 * PIREP, external imports, or a third-party module filing a PIREP
 * directly without firing PirepFiled. Without cleanup the "Active
 * Bookings" widget shows ghost entries for already-flown flights.
 *
 * Critical: only bids OLDER than the accepted PIREP are removed. A
 * newer bid on the same flight_id is legitimate when pilots reuse a
 * private "scratchpad" flight slot (route_code=PF) — overwriting the
 * route to fly a new leg. Deleting a fresher bid would silently kill
 * a pilot's planned flight.
 */
class PirepObserver
{
    public function created(Pirep $pirep): void
    {
        $this->cleanupStaleBids($pirep);
    }

    public function updated(Pirep $pirep): void
    {
        if (!$pirep->wasChanged('state')) {
            return;
        }
        $this->cleanupStaleBids($pirep);
    }

    private function cleanupStaleBids(Pirep $pirep): void
    {
        if ((int) $pirep->state !== PirepState::ACCEPTED) {
            return;
        }
        if (empty($pirep->user_id) || empty($pirep->flight_id)) {
            return;
        }
        if (!Schema::hasTable(DB::getTablePrefix().'bids')) {
            return;
        }

        DB::table('bids')
            ->where('user_id', $pirep->user_id)
            ->where('flight_id', $pirep->flight_id)
            ->where('created_at', '<=', $pirep->created_at ?? now())
            ->delete();
    }
}
