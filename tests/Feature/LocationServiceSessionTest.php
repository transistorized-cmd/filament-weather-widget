<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Tests\Feature;

use Illuminate\Support\Facades\Session;
use Transistorizedcmd\FilamentWeatherWidget\Services\LocationService;
use Transistorizedcmd\FilamentWeatherWidget\Tests\TestCase;

class LocationServiceSessionTest extends TestCase
{
    public function test_set_geolocation_stores_and_validates(): void
    {
        $service = new LocationService();

        $this->assertTrue($service->setGeolocation(51.5074, -0.1278));
        $this->assertSame([
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ], Session::get('weather_widget_geolocation'));
    }

    public function test_set_geolocation_rejects_out_of_range(): void
    {
        $service = new LocationService();

        $this->assertFalse($service->setGeolocation(91.0, 0.0));
        $this->assertNull(Session::get('weather_widget_geolocation'));
    }

    public function test_set_geolocation_rejects_non_numeric(): void
    {
        $service = new LocationService();

        $this->assertFalse($service->setGeolocation('not-a-number', 0.0));
        $this->assertFalse($service->setGeolocation(0.0, 'x'));
        $this->assertNull(Session::get('weather_widget_geolocation'));
    }

    public function test_get_location_manual_returns_setting_value(): void
    {
        $service = new LocationService();

        $this->assertSame('Paris', $service->getLocation([
            'location_mode' => 'manual',
            'location' => 'Paris',
        ]));
    }

    public function test_get_location_automatic_uses_session_lat_lng(): void
    {
        $service = new LocationService();
        $service->setGeolocation(40.7128, -74.0060);

        $this->assertSame('40.7128,-74.006', $service->getLocation([
            'location_mode' => 'automatic',
        ]));
    }

    public function test_get_location_automatic_falls_back_to_auto_ip(): void
    {
        $service = new LocationService();

        $this->assertSame('auto:ip', $service->getLocation([
            'location_mode' => 'automatic',
        ]));
    }

    public function test_clear_geolocation_removes_session_entry(): void
    {
        $service = new LocationService();
        $service->setGeolocation(40.0, -74.0);
        $service->clearGeolocation();

        $this->assertNull(Session::get('weather_widget_geolocation'));
    }
}
