{{-- partials/widgets.blade.php — Active Flights + Bookings Widgets --}}
<div class="mb-3">
  @widget('DBasic::FlightBoard')
</div>
<div class="mb-3">
  @widget('DBasic::ActiveBookings')
</div>
{{-- SkyAdventures: Wartungs-Kachel im AirlinePulse-Glas-Design. Der Host (AirlinePulse)
     liefert die .ap-glass-Karte + Header; das Widget rendert „bare" nur den Inhalt, damit
     es exakt wie die übrigen Kacheln aussieht. Nur wenn das Modul installiert ist. --}}
@if (view()->exists('skyadventures::widgets.maintenance'))
<div class="ap-glass w-100 mb-3">
  <div class="ap-glass-header">
    <div class="d-flex align-items-center gap-2">
      <i class="ph-fill ph-wrench" style="color:var(--ap-amber);"></i>
      <span class="ap-card-title">{{ __('skyadventures::ferry.maint_section_title') }}</span>
    </div>
  </div>
  <div style="padding:14px 16px;">
    {{-- Volles Board (gleiche Infos wie auf der Überführungs-/Flottenseite): Flugzeug,
         Airline, Phase, Werft/Route, Restzeit + Pilot auf Live-Beinen. --}}
    @include('skyadventures::widgets.maintenance', ['bare' => true])
  </div>
</div>
@endif
