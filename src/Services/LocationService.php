<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Services;

use Illuminate\Support\Facades\Session;

class LocationService
{
    public function getLocation(array $settings): string
    {
        if ($settings['location_mode'] === 'manual') {
            return $settings['location'];
        }

        // Check if we have a stored geolocation
        $geolocation = Session::get('weather_widget_geolocation');
        if ($geolocation) {
            return "{$geolocation['latitude']},{$geolocation['longitude']}";
        }

        // Fallback to IP-based location
        return 'auto:ip';
    }

    public function setGeolocation(float $latitude, float $longitude): void
    {
        Session::put('weather_widget_geolocation', [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function clearGeolocation(): void
    {
        Session::forget('weather_widget_geolocation');
    }
}