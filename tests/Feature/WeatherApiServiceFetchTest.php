<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use RuntimeException;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherApiService;
use Transistorizedcmd\FilamentWeatherWidget\Tests\TestCase;

class WeatherApiServiceFetchTest extends TestCase
{
    private function makeService(MockHandler $handler): WeatherApiService
    {
        $client = new Client(['handler' => HandlerStack::create($handler)]);
        return new WeatherApiService($client, 'test-key', 'https://api.weatherapi.test/v1');
    }

    public function test_returns_formatted_payload_on_200(): void
    {
        $body = json_encode([
            'location' => ['name' => 'Madrid'],
            'current' => [
                'temp_c' => 20.5,
                'temp_f' => 68.9,
                'condition' => ['text' => 'Sunny', 'icon' => '//cdn/sunny.png'],
                'humidity' => 30,
                'wind_kph' => 10.5,
                'wind_mph' => 6.2,
                'wind_dir' => 'NE',
                'last_updated' => '2026-05-11 12:00',
            ],
        ]);

        $service = $this->makeService(new MockHandler([new Response(200, [], $body)]));

        $result = $service->getCurrentWeather('Madrid', ['unit' => 'celsius', 'wind_unit' => 'kph']);

        $this->assertSame('Madrid', $result['location']);
        $this->assertSame(20.5, $result['temperature']);
        $this->assertSame('https://cdn/sunny.png', $result['icon_url']);
    }

    public function test_surfaces_weatherapi_error_message_on_400(): void
    {
        $errorBody = json_encode([
            'error' => ['code' => 1006, 'message' => 'No matching location found.'],
        ]);

        $service = $this->makeService(new MockHandler([
            new Response(400, [], $errorBody),
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No matching location found.');

        $service->getCurrentWeather('xyzzy', []);
    }

    public function test_throws_on_malformed_json(): void
    {
        $service = $this->makeService(new MockHandler([
            new Response(200, [], '<html>maintenance</html>'),
        ]));

        $this->expectException(\JsonException::class);

        $service->getCurrentWeather('Madrid', []);
    }

    public function test_throws_on_missing_location_or_current_key(): void
    {
        $service = $this->makeService(new MockHandler([
            new Response(200, [], json_encode(['location' => ['name' => 'Madrid']])),
        ]));

        $this->expectException(RuntimeException::class);

        $service->getCurrentWeather('Madrid', []);
    }
}
