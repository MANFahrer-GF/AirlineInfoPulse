{{-- partials/widgets.blade.php — Active Flights + Bookings Widgets --}}
<div class="mb-3">
  @widget('DBasic::FlightBoard')
</div>
<div class="mb-3">
  @widget('DBasic::ActiveBookings')
</div>
{{-- SkyAdventures: Wartungs-Kachel (nur wenn das Modul installiert ist; das Widget
     blendet sich selbst aus, wenn die Flotte aus ist oder nichts in Wartung läuft). --}}
@if (view()->exists('skyadventures::widgets.maintenance'))
<div class="mb-3">
  @include('skyadventures::widgets.maintenance', ['compact' => true])
</div>
@endif
