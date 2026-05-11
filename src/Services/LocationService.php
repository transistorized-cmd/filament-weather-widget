<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Services;

use Illuminate\Support\Facades\Session;
use Transistorizedcmd\FilamentWeatherWidget\Enums\LocationMode;

class LocationService
{
    public function getLocation(array $settings): string
    {
        if (($settings['location_mode'] ?? null) === LocationMode::Manual->value) {
            return (string) ($settings['location'] ?? '');
        }

        $geolocation = Session::get('weather_widget_geolocation');
        if (is_array($geolocation) && isset($geolocation['latitude'], $geolocation['longitude'])) {
            return "{$geolocation['latitude']},{$geolocation['longitude']}";
        }

        return 'auto:ip';
    }

    public function setGeolocation(mixed $latitude, mixed $longitude): bool
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return false;
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;

        if (! self::isValidCoordinate($lat, $lng)) {
            return false;
        }

        Session::put('weather_widget_geolocation', [
            'latitude' => $lat,
            'longitude' => $lng,
        ]);

        return true;
    }

    public function clearGeolocation(): void
    {
        Session::forget('weather_widget_geolocation');
    }

    public static function isValidCoordinate(float $latitude, float $longitude): bool
    {
        return is_finite($latitude)
            && is_finite($longitude)
            && $latitude >= -90.0 && $latitude <= 90.0
            && $longitude >= -180.0 && $longitude <= 180.0;
    }
}