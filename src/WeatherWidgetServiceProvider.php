<?php

namespace Transistorizedcmd\FilamentWeatherWidget;

use Filament\Facades\Filament;
use GuzzleHttp\Client;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Transistorizedcmd\FilamentWeatherWidget\Widgets\WeatherWidget;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherServiceManager;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherAPIService;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherSettingsManager;

class WeatherWidgetServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-weather-widget';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        Filament::registerWidgets([
            WeatherWidget::class,
        ]);

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-weather-widget');

        $this->app->singleton(WeatherServiceManager::class, function ($app) {
            $manager = new WeatherServiceManager();
            $manager->addService('weatherapi', $app->make(WeatherAPIService::class));
            return $manager;
        });

        $this->app->singleton(WeatherSettingsManager::class, function ($app) {
            return new WeatherSettingsManager();
        });

        $this->app->bind(WeatherAPIService::class, function ($app) {
            return new WeatherAPIService(new Client());
        });
    }

    public function packageRegistered(): void
    {
        $this->mergeConfigFrom($this->package->basePath('/../config/filament-weather-widget.php'), 'filament-weather-widget');

        config([
            'filament-weather-widget.enabled' => env('WEATHER_WIDGET_ENABLED', config('filament-weather-widget.enabled')),
            'filament-weather-widget.location' => env('WEATHER_WIDGET_LOCATION', config('filament-weather-widget.location')),
            'filament-weather-widget.service' => env('WEATHER_WIDGET_SERVICE', config('filament-weather-widget.service', 'weatherapi')),
            'filament-weather-widget.weatherapi.key' => env('WEATHER_API_KEY', config('filament-weather-widget.weatherapi.key')),
        ]);
    }
}