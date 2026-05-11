<?php

namespace Workbench\App\Services;

use Transistorizedcmd\FilamentWeatherWidget\Enums\TemperatureUnit;
use Transistorizedcmd\FilamentWeatherWidget\Enums\WindUnit;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherServiceInterface;

class StubWeatherService implements WeatherServiceInterface
{
    public function getCurrentWeather(string $location, array $settings): array
    {
        $unit = $settings['unit'] ?? TemperatureUnit::Celsius->value;
        $windUnit = $settings['wind_unit'] ?? WindUnit::Kph->value;

        $displayLocation = match (true) {
            $location === 'auto:ip' => 'San Francisco',
            str_contains($location, ',') => 'San Francisco',
            default => $location,
        };

        $localizedCondition = match (app()->getLocale()) {
            'es', 'es_MX' => 'Parcialmente nublado',
            default => 'Partly cloudy',
        };

        return [
            'location' => $displayLocation,
            'temperature' => $unit === TemperatureUnit::Fahrenheit->value ? 64.4 : 18.0,
            'temperature_unit' => $unit,
            'condition' => $localizedCondition,
            'icon_url' => 'https://cdn.weatherapi.com/weather/64x64/day/116.png',
            'humidity' => 67,
            'wind_speed' => $windUnit === WindUnit::Mph->value ? 8.3 : 13.4,
            'wind_unit' => $windUnit,
            'wind_direction' => 'WSW',
            'updated_at' => now()->format('Y-m-d H:i'),
        ];
    }
}
