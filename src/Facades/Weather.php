<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Facades;

use Illuminate\Support\Facades\Facade;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherServiceInterface;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherServiceManager;

/**
 * @method static void addService(string $name, WeatherServiceInterface $service)
 * @method static WeatherServiceInterface getService(?string $name = null)
 * @method static void setDefaultService(string $name)
 */
class Weather extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WeatherServiceManager::class;
    }
}
