<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class WeatherAPIService implements WeatherServiceInterface
{
    protected Client $client;
    protected string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->client = new Client();
        $this->apiKey = $apiKey;
    }

    public function getCurrentWeather(string $location): array
    {
        try {
            $response = $this->client->get('http://api.weatherapi.com/v1/current.json', [
                'query' => [
                    'key' => $this->apiKey,
                    'q' => $location,
                    'aqi' => 'no',
                    'lang' => $this->getLanguageCode()
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'location' => $data['location']['name'],
                'temp_c' => $data['current']['temp_c'],
                'temp_f' => $data['current']['temp_f'],
                'condition' => $data['current']['condition']['text'],
                'icon' => $data['current']['condition']['icon'],
                'icon_url' => 'https:' . $data['current']['condition']['icon'],
                'humidity' => $data['current']['humidity'],
                'wind_speed' => $data['current']['wind_kph'],
                'wind_direction' => $data['current']['wind_dir'],
                'updated_at' => $data['current']['last_updated'],
            ];
        } catch (GuzzleException $e) {
            // Handle the exception (log it, return an error state, etc.)
            return [
                'error' => 'Failed to fetch weather data: ' . $e->getMessage(),
            ];
        }
    }
    
    protected function getLanguageCode(): string
    {
        $locale = app()->getLocale();
        
        // WeatherAPI supported languages
        $supportedLanguages = [
            'ar', 'bn', 'bg', 'zh', 'zh_tw', 'cs', 'da', 'nl', 'fi', 'fr', 'de', 'el', 
            'hi', 'hu', 'it', 'ja', 'jv', 'ko', 'zh_cmn', 'mr', 'pl', 'pt', 'pa', 'ro', 
            'ru', 'sr', 'si', 'sk', 'es', 'sv', 'ta', 'te', 'tr', 'uk', 'ur', 'vi', 'zh_wuu', 
            'zh_hsn', 'zh_yue', 'zu'
        ];

        // Check if the full locale is supported
        if (in_array($locale, $supportedLanguages)) {
            return $locale;
        }

        // If not, check if it's in the form xx_XX and try to match the first part
        $parts = explode('_', $locale);
        if (count($parts) > 1) {
            $languageCode = $parts[0];
            
            if (in_array($languageCode, $supportedLanguages)) {
                return $languageCode;
            }
        }
        
        // If no match found, default to English
        return 'en';
    }
}