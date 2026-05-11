<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Transistorizedcmd\FilamentWeatherWidget\Enums\LocationMode;
use Transistorizedcmd\FilamentWeatherWidget\Enums\TemperatureUnit;
use Transistorizedcmd\FilamentWeatherWidget\Enums\WindUnit;

class WeatherSettingsManager
{
    public const CACHE_TTL_SECONDS = 1800;

    public function getSettings(): array
    {
        $defaults = $this->defaultSettings();
        $settingsPath = $this->getSettingsPath($this->userKey());

        if (! File::exists($settingsPath)) {
            return $defaults;
        }

        $saved = json_decode(File::get($settingsPath), true);
        if (! is_array($saved)) {
            return $defaults;
        }

        return array_merge($defaults, $saved);
    }

    public function saveSettings(array $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode weather widget settings: ' . json_last_error_msg());
        }

        File::put($this->getSettingsPath($this->userKey()), $json, true);
        $this->clearWeatherCache();
    }

    public function resetSettings(): void
    {
        $path = $this->getSettingsPath($this->userKey());
        if (File::exists($path)) {
            File::delete($path);
        }
        $this->clearWeatherCache();
    }

    public function userKey(): string
    {
        $id = Auth::id();
        return $id === null ? 'guest' : (string) $id;
    }

    public function cacheKey(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        return "weather_user_{$this->userKey()}_{$locale}";
    }

    /**
     * Forget the cached weather payload for the current user + current app locale.
     * Note: payloads cached under other locales (e.g. after a locale switch) are
     * not cleared and will live out their TTL.
     */
    public function clearWeatherCache(): void
    {
        Cache::forget($this->cacheKey());
    }

    protected function defaultSettings(): array
    {
        return [
            'show_weather' => true,
            'location_mode' => LocationMode::Automatic->value,
            'location' => config('filament-weather-widget.default_location', 'London'),
            'unit' => config('filament-weather-widget.default_unit', TemperatureUnit::Celsius->value),
            'wind_unit' => config('filament-weather-widget.default_wind_unit', WindUnit::Kph->value),
        ];
    }

    protected function getSettingsPath(string $userId): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $userId);
        return storage_path("app/filament-weather-widget-settings-{$safe}.json");
    }
}
