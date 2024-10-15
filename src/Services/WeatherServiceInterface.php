<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Services;

interface WeatherServiceInterface
{
    public function getCurrentWeather(string $location, array $settings): array;
}