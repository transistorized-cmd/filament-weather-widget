<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Tests\Unit;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherApiService;

class WeatherApiServiceFormatTest extends TestCase
{
    private function format(array $apiPayload, array $settings): array
    {
        $service = new WeatherApiService(new Client(), 'test-key', 'https://example.test/v1');
        $method = new ReflectionMethod($service, 'formatWeatherData');

        return $method->invoke($service, $apiPayload, $settings);
    }

    private function samplePayload(): array
    {
        return [
            'location' => ['name' => 'London'],
            'current' => [
                'temp_c' => 12.5,
                'temp_f' => 54.5,
                'condition' => ['text' => 'Cloudy', 'icon' => '//cdn.test/cloudy.png'],
                'humidity' => 80,
                'wind_kph' => 15.0,
                'wind_mph' => 9.3,
                'wind_dir' => 'WSW',
                'last_updated' => '2026-05-11 12:00',
            ],
        ];
    }

    public function test_celsius_kph_settings(): void
    {
        $result = $this->format($this->samplePayload(), ['unit' => 'celsius', 'wind_unit' => 'kph']);

        $this->assertSame('London', $result['location']);
        $this->assertSame(12.5, $result['temperature']);
        $this->assertSame('celsius', $result['temperature_unit']);
        $this->assertSame(15.0, $result['wind_speed']);
        $this->assertSame('kph', $result['wind_unit']);
        $this->assertSame('https://cdn.test/cloudy.png', $result['icon_url']);
    }

    public function test_fahrenheit_mph_settings(): void
    {
        $result = $this->format($this->samplePayload(), ['unit' => 'fahrenheit', 'wind_unit' => 'mph']);

        $this->assertSame(54.5, $result['temperature']);
        $this->assertSame('fahrenheit', $result['temperature_unit']);
        $this->assertSame(9.3, $result['wind_speed']);
        $this->assertSame('mph', $result['wind_unit']);
    }

    public function test_missing_unit_settings_default_to_celsius_kph(): void
    {
        $result = $this->format($this->samplePayload(), []);

        $this->assertSame(12.5, $result['temperature']);
        $this->assertSame('celsius', $result['temperature_unit']);
        $this->assertSame(15.0, $result['wind_speed']);
        $this->assertSame('kph', $result['wind_unit']);
    }
}
