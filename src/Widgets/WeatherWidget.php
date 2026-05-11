<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Transistorizedcmd\FilamentWeatherWidget\Enums\LocationMode;
use Transistorizedcmd\FilamentWeatherWidget\Enums\TemperatureUnit;
use Transistorizedcmd\FilamentWeatherWidget\Enums\WindUnit;
use Transistorizedcmd\FilamentWeatherWidget\Services\LocationService;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherServiceManager;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherSettingsManager;

class WeatherWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament-weather-widget::weather-widget';

    public ?array $data = [];
    public ?array $weather = null;
    public string $errorMessage = '';

    protected int | string | array $columnSpan = '1';

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
                        LocationMode::Automatic->value => __('filament-weather-widget::weather.settings.automatic'),
                        LocationMode::Manual->value => __('filament-weather-widget::weather.settings.manual'),
                    ])
                    ->default(LocationMode::Automatic->value)
                    ->reactive(),
                Forms\Components\TextInput::make('location')
                    ->label(__('filament-weather-widget::weather.settings.location'))
                    ->required()
                    ->visible(fn (Forms\Get $get) => $get('location_mode') === LocationMode::Manual->value)
                    ->afterStateUpdated(fn () => $this->loadWeather()),
                Forms\Components\Select::make('unit')
                    ->label(__('filament-weather-widget::weather.settings.unit'))
                    ->options([
                        TemperatureUnit::Celsius->value => __('filament-weather-widget::weather.settings.celsius'),
                        TemperatureUnit::Fahrenheit->value => __('filament-weather-widget::weather.settings.fahrenheit'),
                    ])
                    ->default(TemperatureUnit::Celsius->value)
                    ->required(),
                Forms\Components\Select::make('wind_unit')
                    ->label(__('filament-weather-widget::weather.settings.wind_unit'))
                    ->options([
                        WindUnit::Kph->value => __('filament-weather-widget::weather.settings.kph'),
                        WindUnit::Mph->value => __('filament-weather-widget::weather.settings.mph'),
                    ])
                    ->default(WindUnit::Kph->value)
                    ->required(),
            ])
            ->statePath('data');
    }

    protected function loadWeather(): void
    {
        $settings = $this->getSettings();

        if (! $settings['show_weather']) {
            $this->weather = null;
            $this->errorMessage = '';
            return;
        }

        $cacheKey = $this->getSettingsManager()->cacheKey();

        try {
            $this->weather = Cache::remember($cacheKey, WeatherSettingsManager::CACHE_TTL_SECONDS, function () use ($settings) {
                $location = $this->locationService->getLocation($settings);
                $weatherService = $this->weatherServiceManager->getService($settings['service'] ?? 'weatherapi');

                return $weatherService->getCurrentWeather($location, $settings);
            });

            $this->errorMessage = '';
        } catch (\Exception $e) {
            Log::error('Weather widget load data failed: ' . $e->getMessage());
            $this->weather = null;
            $this->errorMessage = $e->getMessage();
            Cache::forget($cacheKey);
        }
    }

    protected function getSettingsManager(): WeatherSettingsManager
    {
        return app(WeatherSettingsManager::class);
    }

    protected function clearWeatherCache(): void
    {
        $this->getSettingsManager()->clearWeatherCache();
    }

    protected function getSettings(): array
    {
        return $this->getSettingsManager()->getSettings();
    }

    public function saveSettings(): void
    {
        $data = $this->form->getState();

        try {
            $this->getSettingsManager()->saveSettings($data);
        } catch (\Throwable $e) {
            Log::error('Weather widget save settings failed: ' . $e->getMessage());
            $this->dispatch('notify', [
                'type' => 'danger',
                'message' => __('filament-weather-widget::weather.errors.unable_to_load'),
            ]);
            return;
        }

        $this->loadWeather();

        $this->dispatch('close-modal', id: 'weather-settings');
    }

    public function resetConfiguration(): void
    {
        $this->getSettingsManager()->resetSettings();

        $this->form->fill($this->getSettings());

        $this->loadWeather();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('filament-weather-widget::weather.settings.reset_success'),
        ]);
    }

    public static function canView(): bool
    {
        return (bool) config('filament-weather-widget.enabled', true);
    }

    public function updateGeolocation($latitude, $longitude): void
    {
        if (! $this->locationService->setGeolocation($latitude, $longitude)) {
            return;
        }

        $this->clearWeatherCache();
        $this->loadWeather();
    }

    public function useIpLocation()
    {
        $this->locationService->clearGeolocation();
        $this->clearWeatherCache();
        $this->loadWeather();
    }

    public function render(): View
    {
        return view(static::$view);
    }
}