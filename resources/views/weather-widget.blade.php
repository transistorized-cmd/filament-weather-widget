<x-filament-widgets::widget>
    <x-filament::section>
        <div
            x-data="WeatherWidget.init({{ Js::from($this->getSettings()['location_mode']) }})"
            x-init="init"
        >
            <div id="weather-widget-container">
                @if($this->getSettings()['show_weather'])
                    @if($weather && empty($errorMessage))
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-bold">
                                {{ __('filament-weather-widget::weather.widget.title', ['location' => $weather['location']]) }}
                            </h2>
                            {{ $this->settingsAction }}
                        </div>
                        <div class="mt-4 flex items-center">
                            <img src="{{ $weather['icon_url'] }}" alt="{{ $weather['condition'] }}" class="w-16 h-16 mr-4">
                            <div>
                                <p class="text-2xl font-bold">{{ $weather['temperature'] }}°{{ strtoupper($weather['temperature_unit'][0]) }}</p>
                                <p class="text-l">{{ $weather['condition'] }}</p>
                            </div>
                        </div>
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            <p>
                                {{ __('filament-weather-widget::weather.widget.humidity') }}: {{ $weather['humidity'] }}% |
                                {{ __('filament-weather-widget::weather.widget.wind') }}: {{ $weather['wind_speed'] }} {{ $weather['wind_unit'] }} {{ $weather['wind_direction'] }} |
                                {{ __('filament-weather-widget::weather.widget.updated') }}: {{ $weather['updated_at'] }}
                            </p>
                        </div>
                    @else
                        <div class="flex items-center justify-between">
                            <p class="text-lg text-red-500">{{ $errorMessage ?: __('filament-weather-widget::weather.errors.unable_to_load') }}</p>
                            {{ $this->settingsAction }}
                        </div>
                    @endif
                    <p x-show="locationFallbackMessage" x-text="locationFallbackMessage" class="text-yellow-500 mt-2"></p>
                @else
                    <div class="flex items-center justify-between">
                        <p class="text-lg">{{ __('filament-weather-widget::weather.widget.hidden') }}</p>
                        {{ $this->settingsAction }}
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
