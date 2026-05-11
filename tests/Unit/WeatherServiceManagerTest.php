<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherServiceInterface;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherServiceManager;

class WeatherServiceManagerTest extends TestCase
{
    public function test_unknown_service_name_throws(): void
    {
        $manager = new WeatherServiceManager();

        $this->expectException(InvalidArgumentException::class);
        $manager->getService('nope');
    }

    public function test_registered_service_is_returned(): void
    {
        $stub = $this->createStub(WeatherServiceInterface::class);
        $manager = new WeatherServiceManager();
        $manager->addService('stub', $stub);

        $this->assertSame($stub, $manager->getService('stub'));
    }

    public function test_default_service_resolved_when_name_is_null(): void
    {
        $stub = $this->createStub(WeatherServiceInterface::class);
        $manager = new WeatherServiceManager('myservice');
        $manager->addService('myservice', $stub);

        $this->assertSame($stub, $manager->getService());
    }
}
