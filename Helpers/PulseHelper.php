<?php

namespace Modules\AirlineInfoPulse\Helpers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PulseHelper
{
    /**
     * Zeitraum-Definitionen basierend auf dem Filter
     */
    public static function getDateRange(string $filter, ?string $customStart = null, ?string $customEnd = null): array
    {
        $now = Carbon::now();

        switch ($filter) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                break;

            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end   = $now->copy()->subDay()->endOfDay();
                break;

            case 'week':
                $start = $now->copy()->startOfWeek(Carbon::MONDAY);
                $end   = $now->copy()->endOfWeek(Carbon::SUNDAY);
                break;

            case 'month':
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfMonth();
                break;

            case 'quarter':
                $start = $now->copy()->firstOfQuarter();
                $end   = $now->copy()->lastOfQuarter()->endOfDay();
                break;

            case 'year':
                $start = $now->copy()->startOfYear();
                $end   = $now->copy()->endOfYear();
                break;

            case 'custom':
                try {
                    $start = $customStart ? Carbon::parse($customStart)->startOfDay() : $now->copy()->startOfMonth();
                    $end   = $customEnd ? Carbon::parse($customEnd)->endOfDay() : $now->copy()->endOfDay();
                    // Max 366 Tage Range erlauben (Schutz gegen Mega-Queries)
                    if ($start->diffInDays($end) > 366) {
                        $start = $end->copy()->subDays(366)->startOfDay();
                    }
                    // End darf nicht in ferner Zukunft liegen
                    if ($end->greaterThan($now->copy()->endOfYear())) {
                        $end = $now->copy()->endOfDay();
                    }
                } catch (\Throwable $e) {
                    $start = $now->copy()->startOfWeek(Carbon::MONDAY);
                    $end   = $now->copy()->endOfWeek(Carbon::SUNDAY);
                }
                break;

            default:
                $start = $now->copy()->startOfWeek(Carbon::MONDAY);
                $end   = $now->copy()->endOfWeek(Carbon::SUNDAY);
                break;
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Vorperiode berechnen (gleich lange Zeitspanne davor)
     */
    public static function getPreviousPeriod(Carbon $start, Carbon $end): array
    {
        $diffDays = $start->diffInDays($end) + 1;

        return [
            'start' => $start->copy()->subDays($diffDays),
            'end'   => $start->copy()->subDay()->endOfDay(),
        ];
    }

    /**
     * Delta-Badge berechnen (Veränderung in Prozent)
     */
    public static function calculateDelta($current, $previous): array
    {
        if ($previous == 0 && $current == 0) {
            return ['value' => 0, 'direction' => 'neutral', 'label' => '—'];
        }

        if ($previous == 0) {
            return ['value' => 100, 'direction' => 'up', 'label' => '↑ neu'];
        }

        $delta = (($current - $previous) / abs($previous)) * 100;

        return [
            'value'     => round(abs($delta), 1),
            'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral'),
            'label'     => ($delta > 0 ? '↑' : '↓') . ' ' . round(abs($delta), 1) . '%',
        ];
    }

    /**
     * Landing Rate Farbe bestimmen
     */
    public static function landingRateColor(float $rate): string
    {
        $abs = abs($rate);

        if ($abs <= 299) {
            return 'success';  // grün
        }
        if ($abs <= 499) {
            return 'warning';  // orange
        }

        return 'danger';  // rot
    }

    /**
     * Minuten in h:mm Format
     */
    public static function minutesToHours(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Flug-Streak berechnen (aufeinanderfolgende Tage mit Flügen)
     */
    public static function calculateStreak(array $flightDates): int
    {
        if (empty($flightDates)) {
            return 0;
        }

        $dates = collect($flightDates)
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->unique()
            ->sort()
            ->values();

        $streak  = 0;
        $current = Carbon::today();

        // Rückwärts ab heute zählen
        while ($dates->contains($current->format('Y-m-d'))) {
            $streak++;
            $current->subDay();
        }

        return $streak;
    }

    /**
     * Filter-Label für die Anzeige
     */
    public static function filterLabel(string $filter): string
    {
        return match ($filter) {
            'today'     => __('airlineinfopulse::pulse.today'),
            'yesterday' => __('airlineinfopulse::pulse.yesterday'),
            'week'      => __('airlineinfopulse::pulse.this_week'),
            'month'     => __('airlineinfopulse::pulse.this_month'),
            'quarter'   => __('airlineinfopulse::pulse.this_quarter'),
            'year'      => __('airlineinfopulse::pulse.this_year'),
            'custom'    => __('airlineinfopulse::pulse.custom'),
            default     => __('airlineinfopulse::pulse.this_week'),
        };
    }
}
