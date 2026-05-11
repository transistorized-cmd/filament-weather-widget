<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Tests;

use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Transistorizedcmd\FilamentWeatherWidget\WeatherWidgetServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        $providers = [
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            WeatherWidgetServiceProvider::class,
        ];

        if (class_exists(SchemasServiceProvider::class)) {
            array_splice($providers, 2, 0, [SchemasServiceProvider::class]);
        }

        return $providers;
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('filament-weather-widget.weatherapi.key', 'test-key');
        $app['config']->set('filament-weather-widget.weatherapi.base_url', 'https://api.weatherapi.test/v1');
    }
}
