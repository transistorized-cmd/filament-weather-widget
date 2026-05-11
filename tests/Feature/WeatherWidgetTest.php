<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use ReflectionMethod;
use Transistorizedcmd\FilamentWeatherWidget\Services\LocationService;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherApiService;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherServiceManager;
use Transistorizedcmd\FilamentWeatherWidget\Tests\TestCase;
use Transistorizedcmd\FilamentWeatherWidget\Widgets\WeatherWidget;

class WeatherWidgetTest extends TestCase
{
    private function bindWeatherApiResponse(Response $response): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([$response]))]);

        $service = new WeatherApiService($client, 'test-key', 'https://api.weatherapi.test/v1');

        $this->app->instance(WeatherApiService::class, $service);

        $this->app->forgetInstance(WeatherServiceManager::class);
        $this->app->singleton(WeatherServiceManager::class, function () use ($service) {
            $manager = new WeatherServiceManager();
            $manager->addService('weatherapi', $service);
            return $manager;
        });
    }

    private function makeWidget(): WeatherWidget
    {
        $widget = new WeatherWidget();
        $widget->boot(
            $this->app->make(LocationService::class),
            $this->app->make(WeatherServiceManager::class),
        );
        return $widget;
    }

    private function sampleApiBody(): string
    {
        return json_encode([
            'location' => ['name' => 'Madrid'],
            'current' => [
                'temp_c' => 22.5,
                'temp_f' => 72.5,
                'condition' => ['text' => 'Sunny', 'icon' => '//cdn/sunny.png'],
                'humidity' => 30,
                'wind_kph' => 10.5,
                'wind_mph' => 6.5,
                'wind_dir' => 'NE',
                'last_updated' => '2026-05-12 12:00',
            ],
        ]);
    }

    private function invokeProtected(WeatherWidget $widget, string $method): mixed
    {
        $ref = new ReflectionMethod($widget, $method);
        return $ref->invoke($widget);
    }

    public function test_load_weather_populates_payload_on_success(): void
    {
        $this->bindWeatherApiResponse(new Response(200, [], $this->sampleApiBody()));

        $widget = $this->makeWidget();
        $this->invokeProtected($widget, 'loadWeather');

        $this->assertSame('Madrid', $widget->weather['location']);
        $this->assertSame(22.5, $widget->weather['temperature']);
        $this->assertSame('', $widget->errorMessage);
    }

    public function test_load_weather_surfaces_upstream_error_message(): void
    {
        $errorBody = json_encode(['error' => ['code' => 1006, 'message' => 'No matching location found.']]);
        $this->bindWeatherApiResponse(new Response(400, [], $errorBody));

        $widget = $this->makeWidget();
        $this->invokeProtected($widget, 'loadWeather');

        $this->assertNull($widget->weather);
        $this->assertSame('No matching location found.', $widget->errorMessage);
    }

    public function test_update_geolocation_with_valid_coords_reloads_weather(): void
    {
        $this->bindWeatherApiResponse(new Response(200, [], $this->sampleApiBody()));

        $widget = $this->makeWidget();
        $widget->updateGeolocation(40.7128, -74.0060);

        $this->assertSame('Madrid', $widget->weather['location']);
    }

    public function test_update_geolocation_with_invalid_coords_is_no_op(): void
    {
        $this->bindWeatherApiResponse(new Response(200, [], $this->sampleApiBody()));

        $widget = $this->makeWidget();
        $widget->updateGeolocation('not-a-number', 0);

        $this->assertNull($widget->weather);
        $this->assertSame('', $widget->errorMessage);
    }

    public function test_can_view_respects_config_flag(): void
    {
        config()->set('filament-weather-widget.enabled', false);
        $this->assertFalse(WeatherWidget::canView());

        config()->set('filament-weather-widget.enabled', true);
        $this->assertTrue(WeatherWidget::canView());
    }

    public function test_settings_action_returns_filament_action_instance(): void
    {
        $widget = $this->makeWidget();
        $action = $widget->settingsAction();

        $this->assertInstanceOf(\Filament\Actions\Action::class, $action);
        $this->assertSame('settings', $action->getName());
    }
}
