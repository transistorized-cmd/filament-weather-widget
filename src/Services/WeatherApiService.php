<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class WeatherAPIService implements WeatherServiceInterface
{
    protected Client $client;
    protected string $apiKey;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->apiKey = Config::get('filament-weather-widget.weatherapi.key');
    }

    public function getCurrentWeather(string $location, array $settings): array
    {
        try {
            $data = $this->fetchWeatherData($location);
            return $this->formatWeatherData($data, $settings);
        } catch (\Exception $e) {
            Log::error('WeatherAPI data fetch failed: ' . $e->getMessage());
            return ['error' => 'Failed to fetch weather data: ' . $e->getMessage()];
        }
    }

    protected function fetchWeatherData(string $query): array
    {
        $response = $this->client->get("http://api.weatherapi.com/v1/current.json", [
            'query' => [
                'key' => $this->apiKey,
                'q' => $query,
                'aqi' => 'no',
                'lang' => $this->getLanguageCode()
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    protected function formatWeatherData(array $data, array $settings) :array
    {
        $currentLocale = app()->getLocale();

        return [
            'location' => $data['location']['name'],
            'temperature' => $settings['unit'] === 'fahrenheit' ? $data['current']['temp_f'] : $data['current']['temp_c'],
            'temperature_unit' => $settings['unit'],
            'condition' => $data['current']['condition']['text'],
            'icon_url' => 'https:' . $data['current']['condition']['icon'],
            'humidity' => $data['current']['humidity'],
            'wind_speed' => $settings['wind_unit'] === 'mph' ? $data['current']['wind_mph'] : $data['current']['wind_kph'],
            'wind_unit' => $settings['wind_unit'],
            'wind_direction' => $data['current']['wind_dir'],
            'updated_at' => $data['current']['last_updated'],
            'cached_locale' => $currentLocale,
        ];
    }

    protected function getLanguageCode(): string
    {
        $locale = app()->getLocale();
        
        $supportedLanguages = [
            'ar', 'bn', 'bg', 'zh', 'zh_tw', 'cs', 'da', 'nl', 'fi', 'fr', 'de', 'el', 
            'hi', 'hu', 'it', 'ja', 'jv', 'ko', 'zh_cmn', 'mr', 'pl', 'pt', 'pa', 'ro', 
            'ru', 'sr', 'si', 'sk', 'es', 'sv', 'ta', 'te', 'tr', 'uk', 'ur', 'vi', 'zh_wuu', 
            'zh_hsn', 'zh_yue', 'zu'
        ];

        if (in_array($locale, $supportedLanguages)) {
            return $locale;
        }

        $parts = explode('_', $locale);
        if (count($parts) > 1 && in_array($parts[0], $supportedLanguages)) {
            return $parts[0];
        }
        
        return 'en';
    }
}