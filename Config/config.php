<?php

return [
    /**
     * Tagesziel in Minuten (Standard: 4 Stunden = 240 Minuten)
     */
    'daily_goal_minutes' => 240,

    /**
     * Meilenstein-Stufen für die "Nächster Meilenstein" Mission
     */
    'milestones' => [10, 25, 50, 100, 150, 200, 300, 500, 750, 1000, 1500, 2000, 5000],

    /**
     * Tages-Challenge: Anzahl Flüge für die tägliche Challenge
     */
    'daily_challenge_flights' => 3,

    /**
     * Schnellstart: Maximale Anzahl zufälliger Routen
     */
    'quickstart_max_routes' => 200,

    /**
     * CO2-Umrechnungsfaktor (kg Fuel → kg CO2)
     */
    'co2_factor' => 3.16,

    /**
     * Landing Rate Schwellenwerte (fpm)
     */
    'landing_rate_thresholds' => [
        'green'  => -299,   // 0 bis -299 = grün (butterweich)
        'orange' => -499,   // -300 bis -499 = orange (ok)
        // alles darunter = rot (hart)
    ],

    /**
     * Anzahl der Top-Einträge (Piloten / Aircraft)
     */
    'top_limit' => 10,

    /**
     * Anzahl Feed-Einträge
     */
    'feed_limit' => 25,

    /**
     * Anzahl Airline Snapshot Top-Karten
     */
    'snapshot_top_count' => 4,
];
