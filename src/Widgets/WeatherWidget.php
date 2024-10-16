<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Transistorizedcmd\FilamentWeatherWidget\Services\LocationService;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherServiceManager;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherSettingsManager;

class WeatherWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament-weather-widget::weather-widget';

    public ?array $data = [];
    public $weather;
    public string $errorMessage = '';
    
    protected int | string | array $columnSpan = '1';

    protected $weatherData = null;
    protected LocationService $locationService;
    protected WeatherServiceManager $weatherServiceManager;

    protected $listeners = [
        'updateGeolocation' => 'updateGeolocation',
        'useIpLocation' => 'useIpLocation'
    ];
    
    public function boot(LocationService $locationService, WeatherServiceManager $weatherServiceManager)
    {
        $this->locationService = $locationService;
        $this->weatherServiceManager = $weatherServiceManager;
    }
    
    public function mount(): void
    {
        $this->form->fill($this->getSettingsManager()->getSettings());
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
            $settings = $this->getSettings();
            // If no cached data, detect location and fetch weather
            $weatherData = $this->fetchWeatherData($settings);

            if ($weatherData) {
                $this->weatherData = $weatherData;
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

    protected function fetchWeatherData(array $settings)
    {
        $location = $this->locationService->getLocation($settings);
        $weatherService = $this->weatherServiceManager->getService($settings['service'] ?? 'weatherapi');
        $weatherData = $weatherService->getCurrentWeather($location, $settings);

        if (!isset($weatherData['location'])) {
            throw new \Exception(__('filament-weather-widget::weather.errors.invalid_location'));
        }

        return $weatherData;
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
            $this->weather = Cache::remember($cacheKey, 1800, function () use ($settings) {
                $location = $this->locationService->getLocation($settings);
                $weatherService = $this->weatherServiceManager->getService($settings['service'] ?? 'weatherapi');
                $weatherData = $weatherService->getCurrentWeather($location, $settings);
                
                if (!isset($weatherData['location'])) {
                    throw new \Exception(__('filament-weather-widget::weather.errors.invalid_location'));
                }
 
                return $weatherData;
            });

            $this->errorMessage = '';
        } catch (\Exception $e) {
            Log::error('Weather widget load data failed: ' . $e->getMessage());
            $this->weather = null;
            $this->errorMessage = $e->getMessage();
            Cache::forget($cacheKey);
        }
    }

    protected function getSettingsManager() :WeatherSettingsManager
    {
        return app(WeatherSettingsManager::class);
    }

    public function getWeatherService(): string
    {
        return Config::get('filament-weather-widget.service', 'weatherapi');
    }

    protected function clearWeatherCache(): void
    {
        $userId = Auth::id() ?? 'guest';
        $currentLocale = app()->getLocale();
        Cache::forget("weather_user_{$userId}_{$currentLocale}");
    }

    protected function getLocationFallback()
    {
        return Config::get('filament-weather-widget.default_location', 'London');
    }

    protected function getSettings(): array
    {
        return $this->getSettingsManager()->getSettings();
    }

    public function saveSettings($data = null): void
    {
        if ($data === null) {
            $data = $this->form->getState();
        }
        
        $this->getSettingsManager()->saveSettings($data);

        $this->clearWeatherCache();
        $this->loadWeather();

        $this->dispatch('close-modal', id: 'weather-settings');
    }

    public function resetConfiguration(): void
    {
        $this->getSettingsManager()->resetSettings();

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

    public function updateGeolocation($latitude, $longitude)
    {
        $this->locationService->setGeolocation($latitude, $longitude);
        $this->clearWeatherCache();
        $this->loadWeather();
    }

    public function useIpLocation()
    {
        $this->locationService->clearGeolocation();
        $this->clearWeatherCache();
        $this->loadWeather();
    }

    protected function getScripts(): array
    {
        return [
            'weather-widget' => asset('js/weather-widget.js'),
        ];
    }

    public function render() :View
    {
        // Log the scripts that should be loaded
        Log::info('Scripts to be loaded: ' . json_encode($this->getScripts()));

        return view(static::$view, $this->data);
    }
}