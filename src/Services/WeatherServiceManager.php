<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Services;

use InvalidArgumentException;

class WeatherServiceManager
{
    protected array $services = [];
    protected string $defaultService;

    public function __construct(string $defaultService = 'weatherapi')
    {
        $this->defaultService = $defaultService;
    }

    public function addService(string $name, WeatherServiceInterface $service): void
    {
        $this->services[$name] = $service;
    }

    public function getService(?string $name = null): WeatherServiceInterface
    {
        $serviceName = $name ?? $this->defaultService;

        if (!isset($this->services[$serviceName])) {
            throw new InvalidArgumentException("Weather service '{$serviceName}' is not registered.");
        }

        return $this->services[$serviceName];
    }

    public function setDefaultService(string $name): void
    {
        $this->defaultService = $name;
    }
}