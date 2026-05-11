@php
    $settings = $this->getSettings();
    $showWeather = $settings['show_weather'];
    $heading = $showWeather && $weather && empty($errorMessage)
        ? __('filament-weather-widget::weather.widget.title', ['location' => $weather['location']])
        : __('filament-weather-widget::weather.settings.title');
@endphp

<x-filament-widgets::widget>
    <x-filament::section :heading="$heading">
        <x-slot name="afterHeader">
            {{ $this->settingsAction }}
        </x-slot>

        <div
            x-data="WeatherWidget.init({{ Js::from($settings['location_mode']) }})"
            x-init="init"
        >
            @if ($showWeather)
                @if ($weather && empty($errorMessage))
                    <div class="fi-wi-weather-body">
                        <img
                            src="{{ $weather['icon_url'] }}"
                            alt="{{ $weather['condition'] }}"
                            class="fi-wi-weather-icon"
                        />
                        <div class="fi-wi-weather-readout">
                            <p class="fi-wi-weather-temp">
                                {{ $weather['temperature'] }}°{{ strtoupper($weather['temperature_unit'][0]) }}
                            </p>
                            <p class="fi-wi-weather-condition">{{ $weather['condition'] }}</p>
                        </div>
                    </div>
                    <p class="fi-wi-weather-meta">
                        {{ __('filament-weather-widget::weather.widget.humidity') }}: {{ $weather['humidity'] }}%
                        &middot;
                        {{ __('filament-weather-widget::weather.widget.wind') }}: {{ $weather['wind_speed'] }} {{ $weather['wind_unit'] }} {{ $weather['wind_direction'] }}
                        &middot;
                        {{ __('filament-weather-widget::weather.widget.updated') }}: {{ $weather['updated_at'] }}
                    </p>
                @else
                    <p class="fi-wi-weather-error">
                        {{ $errorMessage ?: __('filament-weather-widget::weather.errors.unable_to_load') }}
                    </p>
                @endif
                <p
                    x-show="locationFallbackMessage"
                    x-text="locationFallbackMessage"
                    class="fi-wi-weather-fallback"
                ></p>
            @else
                <p class="fi-wi-weather-hidden">{{ __('filament-weather-widget::weather.widget.hidden') }}</p>
            @endif
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
