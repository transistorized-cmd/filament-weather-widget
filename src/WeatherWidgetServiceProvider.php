<?php

namespace Transistorizedcmd\FilamentWeatherWidget;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use GuzzleHttp\Client;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Transistorizedcmd\FilamentWeatherWidget\Services\LocationService;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherApiService;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherServiceManager;
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
            ->hasTranslations()
            ->hasAssets();
    }

    public function packageBooted(): void
    {
        $this->publishes([
            __DIR__ . '/../resources/js/weather-widget.js' => public_path('js/vendor/filament-weather-widget/weather-widget.js'),
        ], 'filament-weather-widget-scripts');

        FilamentAsset::register([
            Css::make('filament-weather-widget', __DIR__ . '/../resources/css/weather-widget.css'),
            Js::make('filament-weather-widget', __DIR__ . '/../resources/js/weather-widget.js'),
        ], package: 'transistorizedcmd/filament-weather-widget');

        $this->app->singleton(WeatherServiceManager::class, function ($app) {
            $manager = new WeatherServiceManager();
            $manager->addService('weatherapi', $app->make(WeatherApiService::class));
            return $manager;
        });

        $this->app->singleton(WeatherSettingsManager::class, function ($app) {
            return new WeatherSettingsManager();
        });

        $this->app->singleton(WeatherApiService::class, function ($app) {
            return new WeatherApiService(
                new Client([
                    'timeout' => 5,
                    'connect_timeout' => 2,
                ]),
                (string) config('filament-weather-widget.weatherapi.key', ''),
                (string) config('filament-weather-widget.weatherapi.base_url'),
            );
        });

        $this->app->singleton(LocationService::class, function ($app) {
            return new LocationService();
        });
    }
}
