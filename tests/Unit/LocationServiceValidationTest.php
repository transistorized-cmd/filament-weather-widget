<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Transistorizedcmd\FilamentWeatherWidget\Services\LocationService;

class LocationServiceValidationTest extends TestCase
{
    #[DataProvider('validCoordinates')]
    public function test_accepts_valid_coordinates(float $lat, float $lng): void
    {
        $this->assertTrue(LocationService::isValidCoordinate($lat, $lng));
    }

    public static function validCoordinates(): array
    {
        return [
            'origin' => [0.0, 0.0],
            'london' => [51.5074, -0.1278],
            'south pole' => [-90.0, 0.0],
            'north pole' => [90.0, 0.0],
            'date line west' => [0.0, -180.0],
            'date line east' => [0.0, 180.0],
        ];
    }

    #[DataProvider('invalidCoordinates')]
    public function test_rejects_invalid_coordinates(float $lat, float $lng): void
    {
        $this->assertFalse(LocationService::isValidCoordinate($lat, $lng));
    }

    public static function invalidCoordinates(): array
    {
        return [
            'lat too high' => [90.0001, 0.0],
            'lat too low' => [-90.0001, 0.0],
            'lng too high' => [0.0, 180.0001],
            'lng too low' => [0.0, -180.0001],
            'lat NaN' => [NAN, 0.0],
            'lng NaN' => [0.0, NAN],
            'lat Inf' => [INF, 0.0],
            'lng -Inf' => [0.0, -INF],
        ];
    }
}
