<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Str;
use Transistorizedcmd\FilamentWeatherWidget\Enums\TemperatureUnit;
use Transistorizedcmd\FilamentWeatherWidget\Enums\WindUnit;

class WeatherApiService implements WeatherServiceInterface
{
    public function __construct(
        protected Client $client,
        protected string $apiKey,
        protected string $baseUrl,
    ) {
    }

    public function getCurrentWeather(string $location, array $settings): array
    {
        return $this->formatWeatherData($this->fetchWeatherData($location), $settings);
    }

    protected function fetchWeatherData(string $query): array
    {
        try {
            $response = $this->client->get(Str::finish($this->baseUrl, '/') . 'current.json', [
                'query' => [
                    'key' => $this->apiKey,
                    'q' => $query,
                    'aqi' => 'no',
                    'lang' => $this->getLanguageCode(),
                ],
            ]);
        } catch (BadResponseException $e) {
            throw new \RuntimeException($this->extractUpstreamError($e), 0, $e);
        }

        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($data) || ! isset($data['location'], $data['current'])) {
            throw new \RuntimeException(__('filament-weather-widget::weather.errors.invalid_location'));
        }

        return $data;
    }

    protected function extractUpstreamError(BadResponseException $e): string
    {
        $body = json_decode((string) $e->getResponse()->getBody(), true);

        return is_array($body) && isset($body['error']['message'])
            ? (string) $body['error']['message']
            : __('filament-weather-widget::weather.errors.unable_to_load');
    }

    protected function formatWeatherData(array $data, array $settings): array
    {
        $tempUnit = $settings['unit'] ?? TemperatureUnit::Celsius->value;
        $windUnit = $settings['wind_unit'] ?? WindUnit::Kph->value;

        return [
            'location' => $data['location']['name'],
            'temperature' => $tempUnit === TemperatureUnit::Fahrenheit->value
                ? $data['current']['temp_f']
                : $data['current']['temp_c'],
            'temperature_unit' => $tempUnit,
            'condition' => $data['current']['condition']['text'],
            'icon_url' => 'https:' . $data['current']['condition']['icon'],
            'humidity' => $data['current']['humidity'],
            'wind_speed' => $windUnit === WindUnit::Mph->value
                ? $data['current']['wind_mph']
                : $data['current']['wind_kph'],
            'wind_unit' => $windUnit,
            'wind_direction' => $data['current']['wind_dir'],
            'updated_at' => $data['current']['last_updated'],
        ];
    }

    protected function getLanguageCode(): string
    {
        $locale = strtolower(str_replace('-', '_', app()->getLocale()));

        $supported = [
            'ar', 'bn', 'bg', 'zh', 'zh_tw', 'cs', 'da', 'nl', 'fi', 'fr', 'de', 'el',
            'hi', 'hu', 'it', 'ja', 'jv', 'ko', 'zh_cmn', 'mr', 'pl', 'pt', 'pa', 'ro',
            'ru', 'sr', 'si', 'sk', 'es', 'sv', 'ta', 'te', 'tr', 'uk', 'ur', 'vi',
            'zh_wuu', 'zh_hsn', 'zh_yue', 'zu',
        ];

        if (in_array($locale, $supported, true)) {
            return $locale;
        }

        $base = explode('_', $locale)[0];
        return in_array($base, $supported, true) ? $base : 'en';
    }
}