<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Tests\Feature;

use Illuminate\Support\Facades\File;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherSettingsManager;
use Transistorizedcmd\FilamentWeatherWidget\Tests\TestCase;

class WeatherSettingsManagerTest extends TestCase
{
    private function settingsPath(WeatherSettingsManager $manager): string
    {
        return storage_path('app/filament-weather-widget-settings-' . $manager->userKey() . '.json');
    }

    protected function tearDown(): void
    {
        $manager = new WeatherSettingsManager();
        $path = $this->settingsPath($manager);
        if (File::exists($path)) {
            File::delete($path);
        }
        parent::tearDown();
    }

    public function test_returns_defaults_when_no_saved_file(): void
    {
        $manager = new WeatherSettingsManager();
        $defaults = $manager->getSettings();

        $this->assertTrue($defaults['show_weather']);
        $this->assertSame('automatic', $defaults['location_mode']);
        $this->assertSame('celsius', $defaults['unit']);
        $this->assertSame('kph', $defaults['wind_unit']);
    }

    public function test_save_then_get_round_trip(): void
    {
        $manager = new WeatherSettingsManager();

        $manager->saveSettings([
            'show_weather' => false,
            'location_mode' => 'manual',
            'location' => 'Tokyo',
            'unit' => 'fahrenheit',
            'wind_unit' => 'mph',
        ]);

        $settings = $manager->getSettings();
        $this->assertFalse($settings['show_weather']);
        $this->assertSame('manual', $settings['location_mode']);
        $this->assertSame('Tokyo', $settings['location']);
        $this->assertSame('fahrenheit', $settings['unit']);
        $this->assertSame('mph', $settings['wind_unit']);
    }

    public function test_corrupt_json_falls_back_to_defaults(): void
    {
        $manager = new WeatherSettingsManager();
        File::put($this->settingsPath($manager), '{this is not json');

        $settings = $manager->getSettings();

        $this->assertSame($manager->getSettings(), $settings);
        $this->assertSame('automatic', $settings['location_mode']);
    }

    public function test_non_array_json_falls_back_to_defaults(): void
    {
        $manager = new WeatherSettingsManager();
        File::put($this->settingsPath($manager), '"just a string"');

        $settings = $manager->getSettings();

        $this->assertSame('automatic', $settings['location_mode']);
    }

    public function test_reset_settings_removes_file(): void
    {
        $manager = new WeatherSettingsManager();
        $manager->saveSettings(['show_weather' => false]);
        $this->assertTrue(File::exists($this->settingsPath($manager)));

        $manager->resetSettings();

        $this->assertFalse(File::exists($this->settingsPath($manager)));
        $this->assertTrue($manager->getSettings()['show_weather']);
    }

    public function test_cache_key_shape(): void
    {
        $manager = new WeatherSettingsManager();

        $this->assertSame('weather_user_guest_en', $manager->cacheKey('en'));
        $this->assertStringStartsWith('weather_user_', $manager->cacheKey());
    }

    public function test_user_key_returns_guest_when_no_auth(): void
    {
        $manager = new WeatherSettingsManager();
        $this->assertSame('guest', $manager->userKey());
    }
}
