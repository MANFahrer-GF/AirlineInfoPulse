<?php

namespace Modules\AirlineInfoPulse\Providers;

use App\Contracts\Modules\ServiceProvider;
use App\Models\Pirep;
use Modules\AirlineInfoPulse\Observers\PirepObserver;

class AirlineInfoPulseServiceProvider extends ServiceProvider
{
    /**
     * Module name
     */
    protected $moduleName = 'AirlineInfoPulse';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerViews();
        $this->registerTranslations();
        $this->registerRoutes();
        $this->registerObservers();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'airlineinfopulse'
        );
    }

    /**
     * Register Eloquent observers.
     *
     * Stale-bid cleanup runs on the PIREP ACCEPTED transition (event-driven),
     * not on every page load — that earlier approach killed fresh bids on
     * reused flight slots. See PirepObserver for the time-aware logic.
     */
    protected function registerObservers(): void
    {
        Pirep::observe(PirepObserver::class);
    }

    /**
     * Register views
     */
    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/airlineinfopulse');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        // Nur EXISTIERENDE Verzeichnisse registrieren. Die Theme-Override-
        // Pfade (view.paths + /modules/airlineinfopulse) existieren i.d.R.
        // nicht. Live-Rendern überspringt fehlende Dirs lazy (Fallback
        // $sourcePath), aber `php artisan view:cache`/`optimize` scannt EAGER
        // jeden Pfad per Symfony-Finder → DirectoryNotFoundException.
        // $sourcePath existiert immer → Namespace behält stets einen Pfad.
        $this->loadViewsFrom(array_filter(array_merge(
            array_map(function ($path) {
                return $path . '/modules/airlineinfopulse';
            }, \Config::get('view.paths')),
            [$sourcePath]
        ), 'is_dir'), 'airlineinfopulse');
    }

    /**
     * Register translations
     */
    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/airlineinfopulse');
        $sourcePath = __DIR__ . '/../Resources/lang';

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'airlineinfopulse');
        } else {
            $this->loadTranslationsFrom($sourcePath, 'airlineinfopulse');
        }
    }

    /**
     * Register routes
     */
    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/Routes/web.php');
    }
}
