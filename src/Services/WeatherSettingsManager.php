<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class WeatherSettingsManager
{
    public function getSettings(): array
    {
        $userId = Auth::id() ?? 'guest';
        $defaultSettings = [
            'show_weather' => true,
            'location_mode' => 'automatic',
            'location' => config('filament-weather-widget.default_location', 'London'),
            'unit' => config('filament-weather-widget.default_unit', 'celsius'),
            'wind_unit' => config('filament-weather-widget.default_wind_unit', 'kph'),
        ];

        $settingsPath = $this->getSettingsPath($userId);
        if (File::exists($settingsPath)) {
            $savedSettings = json_decode(File::get($settingsPath), true);
            return array_merge($defaultSettings, $savedSettings);
        }
        return $defaultSettings;
    }

    public function saveSettings(array $data): void
    {
        $userId = Auth::id() ?? 'guest';
        $settingsPath = $this->getSettingsPath($userId);
        File::put($settingsPath, json_encode($data));
        $this->clearWeatherCache();
    }

    public function resetSettings(): void
    {
        $userId = Auth::id() ?? 'guest';
        $settingsPath = $this->getSettingsPath($userId);
        if (File::exists($settingsPath)) {
            File::delete($settingsPath);
        }
        $this->clearWeatherCache();
    }

    protected function getSettingsPath(string $userId): string
    {
        return storage_path("app/filament-weather-widget-settings-{$userId}.json");
    }

    protected function clearWeatherCache(): void
    {
        $userId = Auth::id() ?? 'guest';
        $currentLocale = app()->getLocale();
        Cache::forget("weather_user_{$userId}_{$currentLocale}");
        Cache::forget("weather_data_{$userId}_{$currentLocale}");
    }
}