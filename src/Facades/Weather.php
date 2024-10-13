<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Facades;

use Illuminate\Support\Facades\Facade;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherServiceManager;

/**
 * @method static array getCurrentWeather(string $location)
 */
class Weather extends Facade
{
    protected static function getFacadeAccessor()
    {
        return WeatherServiceManager::class;
    }

    public static function getCurrentWeather(string $location): array
    {
        $defaultService = config('filament-weather-widget.service', 'weatherapi');
        return static::getService($defaultService)->getCurrentWeather($location);
    }
}