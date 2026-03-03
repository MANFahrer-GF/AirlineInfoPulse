{{-- partials/feed.blade.php — Activity Feed --}}
<div class="ap-glass w-100" style="display:flex;flex-direction:column;min-height:600px;">
  <div class="ap-glass-header">
    <div class="d-flex align-items-center gap-2">
      <i class="ph-fill ph-bolt" style="color:var(--ap-cyan);"></i>
      <span class="ap-card-title">{{ $t('feed_title') }}</span>
    </div>
    <div style="font-size:0.7rem;color:var(--ap-muted);">{{ $range['start']->format('d.m.') }} – {{ $range['end']->format('d.m.') }}</div>
  </div>
  <div class="ap-feed-scroll" style="padding:14px 16px;">
    @forelse($feed as $e)

      {{-- ── PIREP EVENT ── --}}
      @if($e['type'] === 'pirep')
        @php
          $d = $e['data'];
          $lr = $d['landing_rate'] ?? null;
        @endphp
        <div class="ap-feed-item">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div style="flex:1;min-width:0;">
              <div style="font-size:0.75rem;color:var(--ap-muted);margin-bottom:3px;">
                <i class="ph-fill ph-plane-tilt me-1" style="color:var(--ap-blue);"></i>
                <a href="{{ $mkPilotUrl($d['pilot_id'] ?? 0) }}" style="color:var(--ap-text);font-weight:600;text-decoration:none;">{{ $d['pilot_name'] ?? 'Pilot' }}</a>
                @if(!empty($d['airline_name']))
                  <span style="opacity:.4;margin:0 4px;">·</span>
                  <span style="color:var(--ap-muted);font-size:0.72rem;">{{ $d['airline_name'] }}</span>
                @endif
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="ap-feed-route">{{ $d['dpt'] ?? '—' }} → {{ $d['arr'] ?? '—' }}</span>
                @if(!empty($d['flight_number']))
                  <span class="ap-tag" style="font-size:0.62rem;">{{ $d['flight_number'] }}</span>
                @endif
              </div>
              <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                @if(!empty($d['aircraft_type']) || !empty($d['aircraft_reg']))
                  <span class="ap-tag ap-tag-cyan" style="font-size:0.62rem;">
                    <i class="ph-fill ph-airplane me-1"></i>{{ $d['aircraft_type'] }}{{ ($d['aircraft_type'] && $d['aircraft_reg']) ? ' · ' : '' }}{{ $d['aircraft_reg'] }}
                  </span>
                @endif
                @if(($d['flight_time'] ?? 0) > 0)
                  <span style="font-family:var(--ap-font-mono);font-size:0.68rem;color:var(--ap-muted);">~{{ $fmtMin($d['flight_time']) }}</span>
                @endif
                @if(!is_null($lr))
                  <span class="{{ $landingClass($lr) }}" style="font-family:var(--ap-font-mono);font-size:0.68rem;">
                    <i class="ph-fill ph-gauge me-1"></i>{{ $lr }} fpm
                  </span>
                @endif
                @if(($d['state'] ?? 0) === 2)
                  <span class="ap-tag ap-tag-green" style="font-size:0.62rem;">✓ OK</span>
                @endif
              </div>
            </div>
            <div class="d-flex flex-column align-items-end gap-1" style="flex-shrink:0;">
              <div class="ap-feed-time">{{ $e['ts']->format('H:i') }}</div>
              <a href="{{ $mkPirepUrl($d['id'] ?? 0) }}" target="_blank" style="font-family:var(--ap-font-mono);font-size:0.65rem;color:var(--ap-blue);text-decoration:none;">{{ $t('view_pirep') }}</a>
            </div>
          </div>
        </div>

      {{-- ── NEW USER EVENT ── --}}
      @elseif($e['type'] === 'user')
        @php $ud = $e['data']; @endphp
        <div class="ap-feed-item">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div style="font-size:0.68rem;color:var(--ap-green);margin-bottom:2px;"><i class="ph-fill ph-user-plus me-1"></i>{{ $t('new_pilot') }}</div>
              <div style="font-family:var(--ap-font-head);font-weight:700;font-size:0.82rem;color:var(--ap-text-head);">{{ $ud['name'] ?? 'Pilot' }}</div>
            </div>
            <div class="text-end">
              <div class="ap-feed-time">{{ $e['ts']->format('H:i') }}</div>
              <a href="{{ $mkPilotUrl($ud['id'] ?? 0) }}" style="font-size:0.65rem;color:var(--ap-blue);font-family:var(--ap-font-mono);text-decoration:none;">{{ $t('view_profile') }}</a>
            </div>
          </div>
        </div>

      {{-- ── MAINTENANCE EVENT ── --}}
      @elseif($e['type'] === 'maintenance')
        @php
          $md = $e['data'];
          $mxLabel = $md['mx_type'] ?: $t('maintenance');
          $mxLower = strtolower($mxLabel);
          if (str_contains($mxLower, 'hard')) {
              $mxColor = 'var(--ap-red)';
              $mxTagClass = 'ap-tag-red';
          } elseif (str_contains($mxLower, 'soft') || str_contains($mxLower, 'inspect')) {
              $mxColor = 'var(--ap-amber)';
              $mxTagClass = 'ap-tag-amber';
          } else {
              $mxColor = 'var(--ap-cyan)';
              $mxTagClass = 'ap-tag-cyan';
          }
        @endphp
        <div class="ap-feed-item">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div style="flex:1;min-width:0;">
              <div style="font-size:0.75rem;color:var(--ap-muted);margin-bottom:3px;">
                <i class="ph-fill ph-wrench me-1" style="color:{{ $mxColor }};"></i>
                <span style="color:{{ $mxColor }};font-weight:700;">{{ $mxLabel }}</span>
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                @if(!empty($md['ac_icao']) || !empty($md['ac_reg']))
                  <span class="ap-tag {{ $mxTagClass }}" style="font-size:0.62rem;">
                    <i class="ph-fill ph-airplane me-1"></i>{{ $md['ac_icao'] }}{{ ($md['ac_icao'] && $md['ac_reg']) ? ' · ' : '' }}{{ $md['ac_reg'] }}
                  </span>
                @endif
              </div>
            </div>
            <div class="d-flex flex-column align-items-end gap-1" style="flex-shrink:0;">
              <div class="ap-feed-time">{{ $e['ts']->format('d.m. H:i') }}</div>
            </div>
          </div>
        </div>
      @endif

    @empty
      <p style="color:var(--ap-muted);font-size:0.8rem;">{{ $t('no_events') }}</p>
    @endforelse
  </div>
</div>
