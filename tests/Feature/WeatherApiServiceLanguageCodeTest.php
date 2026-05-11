<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Tests\Feature;

use GuzzleHttp\Client;
use ReflectionMethod;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherApiService;
use Transistorizedcmd\FilamentWeatherWidget\Tests\TestCase;

class WeatherApiServiceLanguageCodeTest extends TestCase
{
    private function getLangCode(string $locale): string
    {
        $this->app->setLocale($locale);

        $service = new WeatherApiService(new Client(), 'test-key', 'https://example.test/v1');
        $method = new ReflectionMethod($service, 'getLanguageCode');

        return $method->invoke($service);
    }

    public function test_directly_supported_locale_passes_through(): void
    {
        $this->assertSame('es', $this->getLangCode('es'));
        $this->assertSame('fr', $this->getLangCode('fr'));
    }

    public function test_underscored_locale_collapses_to_base_when_not_directly_supported(): void
    {
        $this->assertSame('es', $this->getLangCode('es_MX'));
        $this->assertSame('pt', $this->getLangCode('pt_BR'));
    }

    public function test_zh_tw_preserves_full_code(): void
    {
        $this->assertSame('zh_tw', $this->getLangCode('zh_TW'));
    }

    public function test_dash_separator_is_normalized_to_underscore(): void
    {
        $this->assertSame('zh_tw', $this->getLangCode('zh-TW'));
    }

    public function test_unsupported_locale_falls_back_to_en(): void
    {
        $this->assertSame('en', $this->getLangCode('xx_YY'));
    }
}
