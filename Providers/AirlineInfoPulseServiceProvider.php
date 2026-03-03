<?php

namespace Modules\AirlineInfoPulse\Providers;

use App\Contracts\Modules\ServiceProvider;

class AirlineInfoPulseServiceProvider extends ServiceProvider
{
    /**
     * Modul-Name
     */
    protected $moduleName = 'AirlineInfoPulse';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerRoutes();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        //
    }

    /**
     * Konfiguration registrieren
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'airlineinfopulse'
        );
    }

    /**
     * Views registrieren
     */
    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/airlineinfopulse');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $this->loadViewsFrom(array_merge(
            array_map(function ($path) {
                return $path . '/modules/airlineinfopulse';
            }, \Config::get('view.paths')),
            [$sourcePath]
        ), 'airlineinfopulse');
    }

    /**
     * Übersetzungen registrieren (de/en)
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
     * Routes registrieren
     */
    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/Routes/web.php');
    }
}
