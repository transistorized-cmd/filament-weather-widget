<?php

namespace Transistorizedcmd\FilamentWeatherWidget\Widgets;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Transistorizedcmd\FilamentWeatherWidget\Enums\LocationMode;
use Transistorizedcmd\FilamentWeatherWidget\Enums\TemperatureUnit;
use Transistorizedcmd\FilamentWeatherWidget\Enums\WindUnit;
use Transistorizedcmd\FilamentWeatherWidget\Services\LocationService;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherServiceManager;
use Transistorizedcmd\FilamentWeatherWidget\Services\WeatherSettingsManager;

class WeatherWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament-weather-widget::weather-widget';

    public ?array $weather = null;
    public string $errorMessage = '';

    protected int | string | array $columnSpan = '1';

    protected LocationService $locationService;
    protected WeatherServiceManager $weatherServiceManager;

    public function boot(LocationService $locationService, WeatherServiceManager $weatherServiceManager): void
    {
        $this->locationService = $locationService;
        $this->weatherServiceManager = $weatherServiceManager;
    }

    public function mount(): void
    {
        $this->loadWeather();
    }

    public function settingsAction(): Action
    {
        return Action::make('settings')
            ->iconButton()
            ->icon('heroicon-o-cog')
            ->modalHeading(__('filament-weather-widget::weather.settings.title'))
            ->modalSubmitActionLabel(__('filament-weather-widget::weather.settings.save'))
            ->fillForm(fn (): array => $this->getSettingsManager()->getSettings())
            ->schema([
                Toggle::make('show_weather')
                    ->label(__('filament-weather-widget::weather.settings.show_weather'))
                    ->default(true),
                Radio::make('location_mode')
                    ->label(__('filament-weather-widget::weather.settings.location_mode'))
                    ->options([
                        LocationMode::Automatic->value => __('filament-weather-widget::weather.settings.automatic'),
                        LocationMode::Manual->value => __('filament-weather-widget::weather.settings.manual'),
                    ])
                    ->default(LocationMode::Automatic->value)
                    ->live(),
                TextInput::make('location')
                    ->label(__('filament-weather-widget::weather.settings.location'))
                    ->required()
                    ->visible(fn (Get $get): bool => $get('location_mode') === LocationMode::Manual->value),
                Select::make('unit')
                    ->label(__('filament-weather-widget::weather.settings.unit'))
                    ->options([
                        TemperatureUnit::Celsius->value => __('filament-weather-widget::weather.settings.celsius'),
                        TemperatureUnit::Fahrenheit->value => __('filament-weather-widget::weather.settings.fahrenheit'),
                    ])
                    ->default(TemperatureUnit::Celsius->value)
                    ->required(),
                Select::make('wind_unit')
                    ->label(__('filament-weather-widget::weather.settings.wind_unit'))
                    ->options([
                        WindUnit::Kph->value => __('filament-weather-widget::weather.settings.kph'),
                        WindUnit::Mph->value => __('filament-weather-widget::weather.settings.mph'),
                    ])
                    ->default(WindUnit::Kph->value)
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                if ($arguments['reset'] ?? false) {
                    $this->getSettingsManager()->resetSettings();
                    $this->loadWeather();
                    Notification::make()
                        ->success()
                        ->title(__('filament-weather-widget::weather.settings.reset_success'))
                        ->send();
                    return;
                }

                try {
                    $this->getSettingsManager()->saveSettings($data);
                } catch (\Throwable $e) {
                    Log::error('Weather widget save settings failed: ' . $e->getMessage());
                    Notification::make()
                        ->danger()
                        ->title(__('filament-weather-widget::weather.errors.unable_to_load'))
                        ->send();
                    return;
                }

                $this->loadWeather();
            })
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('reset', arguments: ['reset' => true])
                    ->label(__('filament-weather-widget::weather.settings.reset'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(__('filament-weather-widget::weather.settings.reset_confirm')),
            ]);
    }

    #[On('updateGeolocation')]
    public function updateGeolocation($latitude, $longitude): void
    {
        if (! $this->locationService->setGeolocation($latitude, $longitude)) {
            return;
        }

        $this->clearWeatherCache();
        $this->loadWeather();
    }

    #[On('useIpLocation')]
    public function useIpLocation(): void
    {
        $this->locationService->clearGeolocation();
        $this->clearWeatherCache();
        $this->loadWeather();
    }

    public static function canView(): bool
    {
        return (bool) config('filament-weather-widget.enabled', true);
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
        } catch (\Throwable $e) {
            Log::error('Weather widget load data failed: ' . $e->getMessage());
            $this->weather = null;
            $this->errorMessage = $e->getMessage();
            Cache::forget($cacheKey);
        }
    }

    protected function clearWeatherCache(): void
    {
        $this->getSettingsManager()->clearWeatherCache();
    }

    protected function getSettings(): array
    {
        return $this->getSettingsManager()->getSettings();
    }

    protected function getSettingsManager(): WeatherSettingsManager
    {
        return app(WeatherSettingsManager::class);
    }

    public function render(): View
    {
        return view($this->view);
    }
}
