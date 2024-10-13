<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class WeatherWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament-weather-widget::weather-widget';

    public ?array $data = [];
    public $weather;
    public string $errorMessage = '';
    public ?array $detectedLocation = null;
    
    protected int | string | array $columnSpan = 'full';

    protected $weatherData = null;

    public function mount(): void
    {
        $this->form->fill($this->getSettings());
        $this->loadWeather();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('show_weather')
                    ->label(__('filament-weather-widget::weather.settings.show_weather'))
                    ->default(true),
                Forms\Components\Radio::make('location_mode')
                    ->label(__('filament-weather-widget::weather.settings.location_mode'))
                    ->options([
                        'automatic' => __('filament-weather-widget::weather.settings.automatic'),
                        'manual' => __('filament-weather-widget::weather.settings.manual'),
                    ])
                    ->default('automatic')
                    ->reactive(),
                Forms\Components\TextInput::make('location')
                    ->label(__('filament-weather-widget::weather.settings.location'))
                    ->required()
                    ->visible(fn (Forms\Get $get) => $get('location_mode') === 'manual')
                    ->afterStateUpdated(fn () => $this->loadWeather()),
                Forms\Components\Select::make('unit')
                    ->label(__('filament-weather-widget::weather.settings.unit'))
                    ->options([
                        'celsius' => __('filament-weather-widget::weather.settings.celsius'),
                        'fahrenheit' => __('filament-weather-widget::weather.settings.fahrenheit'),
                    ])
                    ->default('celsius')
                    ->required(),
                Forms\Components\Select::make('wind_unit')
                    ->label(__('filament-weather-widget::weather.settings.wind_unit'))
                    ->options([
                        'kph' => __('filament-weather-widget::weather.settings.kph'),
                        'mph' => __('filament-weather-widget::weather.settings.mph'),
                    ])
                    ->default('kph')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function initializeWeatherData()
    {
        try {
            $userId = Auth::id() ?? 'guest';
            $cacheKey = "weather_data_{$userId}";

            // Try to get data from cache first
            $cachedData = Cache::get($cacheKey);
            if ($cachedData) {
                $this->weatherData = $cachedData;
                return ['success' => true, 'data' => $this->weatherData];
            }

            // If no cached data, detect location and fetch weather
            $weatherData = $this->fetchWeatherData();

            if ($weatherData) {
                $this->weatherData = $this->processWeatherData($weatherData);
                // Cache the new data
                Cache::put($cacheKey, $this->weatherData, now()->addMinutes(30));
                return ['success' => true, 'data' => $this->weatherData];
            } else {
                throw new \Exception('Failed to fetch weather data');
            }
        } catch (\Exception $e) {
            Log::error('Weather widget initialization failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function fetchWeatherData()
    {
        $weatherService = $this->getWeatherService();
        $settings = $this->getSettings();

        if ($weatherService === 'weatherapi') {
            if ($settings['location_mode'] === 'automatic') {
                // Use WeatherAPI with IP-based location
                return $this->getWeatherAPIData('auto:ip');
            } else {
                // Use the manually set location
                return $this->getWeatherAPIData($settings['location']);
            }
        } else {
            // Implement other weather services here
            // For now, we'll use a fallback method
            $location = $this->getLocationFallback();
            return $this->getWeatherAPIData($location);
        }
    }

    protected function getLocationFallback()
    {
        // Implement geolocation logic here
        // For simplicity, we'll just use the default location
        return Config::get('filament-weather-widget.default_location', 'London');
    }

    public function getWeatherAPIData($query)
    {
        $apiKey = Config::get('filament-weather-widget.weatherapi.key');
        $client = new Client();

        try {
            $response = $client->get("http://api.weatherapi.com/v1/current.json", [
                'query' => [
                    'key' => $apiKey,
                    'q' => $query,
                    'aqi' => 'no',
                    'lang' => $this->getLanguageCode()
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('WeatherAPI data fetch failed: ' . $e->getMessage());
            return null;
        }
    }

    protected function processWeatherData($data)
    {
        $settings = $this->getSettings();
        $unit = $settings['unit'];
        $windUnit = $settings['wind_unit'];

        return [
            'location' => $data['location']['name'],
            'temperature' => $unit === 'fahrenheit' ? $data['current']['temp_f'] : $data['current']['temp_c'],
            'condition' => $data['current']['condition']['text'],
            'icon_url' => 'https:' . $data['current']['condition']['icon'],
            'humidity' => $data['current']['humidity'],
            'wind_speed' => $windUnit === 'mph' ? $data['current']['wind_mph'] : $data['current']['wind_kph'],
            'wind_direction' => $data['current']['wind_dir'],
            'updated_at' => $data['current']['last_updated'],
            'wind_unit' => $windUnit,
            'cached_locale' => $data['cached_locale'] ?? app()->getLocale(),
        ];
    }

    protected function loadWeather(): void
    {
        $settings = $this->getSettings();
        
        if (!$settings['show_weather']) {
            $this->weather = null;
            $this->errorMessage = '';
            return;
        }

        $userId = Auth::id() ?? 'guest';
        $currentLocale = app()->getLocale();
        $cacheKey = "weather_user_{$userId}_{$currentLocale}";

        try {
            $this->weather = Cache::remember($cacheKey, 1800, function () use ($currentLocale) {
                $weatherData = $this->fetchWeatherData();
                
                if (!isset($weatherData['location'])) {
                    throw new \Exception(__('filament-weather-widget::weather.errors.invalid_location'));
                }
 
                // Store the locale used for this cached data
                $weatherData['cached_locale'] = $currentLocale;

                return $this->processWeatherData($weatherData);
            });

            // Check if the cached data's locale matches the current locale
            if ($this->weather['cached_locale'] !== $currentLocale) {
                // If not, clear the cache and fetch new data
                Cache::forget($cacheKey);
                $this->loadWeather(); // Recursive call to fetch fresh data
                return;
            }

            $this->errorMessage = '';
        } catch (\Exception $e) {
            $this->weather = null;
            $this->errorMessage = $e->getMessage();
            Cache::forget($cacheKey);
        }
    }

    protected function getSettings(): array
    {
        $userId = Auth::id() ?? 'guest';
        $defaultSettings = [
            'show_weather' => true,
            'location_mode' => 'automatic',
            'location' => config('filament-weather-widget.default_location', 'London'),
            'unit' => config('filament-weather-widget.default_unit', 'celsius'),
            'wind_unit' => config('filament-weather-widget.default_wind_unit', 'kph'),
        ];

        $settingsPath = storage_path("app/filament-weather-widget-settings-{$userId}.json");
        if (File::exists($settingsPath)) {
            $savedSettings = json_decode(File::get($settingsPath), true);
            return array_merge($defaultSettings, $savedSettings);
        }
        return $defaultSettings;
    }

    public function saveSettings($data = null): void
    {
        if ($data === null) {
            $data = $this->form->getState();
        }
        
        $userId = Auth::id() ?? 'guest';
        $settingsPath = storage_path("app/filament-weather-widget-settings-{$userId}.json");
        File::put($settingsPath, json_encode($data));

        $this->clearWeatherCache();
        $this->loadWeather();

        $this->dispatch('close-modal', id: 'weather-settings');
    }

    protected function clearWeatherCache(): void
    {
        $userId = Auth::id() ?? 'guest';
        $currentLocale = app()->getLocale();
        Cache::forget("weather_user_{$userId}_{$currentLocale}");
        Cache::forget("weather_data_{$userId}_{$currentLocale}");
    }

    public function resetConfiguration(): void
    {
        $userId = Auth::id() ?? 'guest';
        $settingsPath = storage_path("app/filament-weather-widget-settings-{$userId}.json");
        if (File::exists($settingsPath)) {
            File::delete($settingsPath);
        }

        $this->clearWeatherCache();

        $this->form->fill($this->getSettings());

        $this->loadWeather();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('filament-weather-widget::weather.settings.reset_success'),
        ]);
    }

    public static function canView(): bool
    {
        return (new static)->getSettings()['show_weather'];
    }

    public function getWeatherService(): string
    {
        return Config::get('filament-weather-widget.service', 'weatherapi');
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