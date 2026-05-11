<?php

namespace Workbench\App\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherServiceManager;
use Workbench\App\Http\Middleware\SetLocaleFromQuery;
use Workbench\App\Services\StubWeatherService;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config()->set('auth.providers.users.model', \Workbench\App\Models\User::class);
    }

    public function boot(): void
    {
        $this->app->extend(WeatherServiceManager::class, function (WeatherServiceManager $manager) {
            $manager->addService('weatherapi', new StubWeatherService());
            return $manager;
        });

        $this->app->make(Kernel::class)->prependMiddleware(SetLocaleFromQuery::class);
    }
}
