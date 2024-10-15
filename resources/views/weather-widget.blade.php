<x-filament::widget>
    <x-filament::card>
        @if($this->getSettings()['show_weather'])
            @if($weather && empty($errorMessage))
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">{{ __('filament-weather-widget::weather.widget.title', ['location' => $weather['location']]) }}</h2>
                    <span class="text-sm text-gray-500">{{ __('filament-weather-widget::weather.widget.updated') }}: {{ $weather['updated_at'] }}</span>
                </div>
                <div class="mt-4 flex items-center">
                    <img src="{{ $weather['icon_url'] }}" alt="{{ $weather['condition'] }}" class="w-16 h-16 mr-4">
                    <div>
                        <p class="text-3xl font-bold">{{ $weather['temperature'] }}°{{ strtoupper($weather['temperature_unit'][0]) }}</p>
                        <p class="text-xl">{{ $weather['condition'] }}</p>
                    </div>
                </div>
                <div class="mt-2 text-sm text-gray-600">
                    <p>{{ __('filament-weather-widget::weather.widget.humidity') }}: {{ $weather['humidity'] }}%</p>
                    <p>{{ __('filament-weather-widget::weather.widget.wind') }}: {{ $weather['wind_speed'] }} {{ $weather['wind_unit'] }} {{ $weather['wind_direction'] }}</p>
                </div>
            @else
                <p class="text-lg text-red-500">{{ $errorMessage ?: __('filament-weather-widget::weather.errors.unable_to_load') }}</p>
            @endif
        @else
            <p class="text-lg">{{ __('filament-weather-widget::weather.widget.hidden') }}</p>
        @endif
        
        <x-filament::button
            icon="heroicon-o-cog"
            x-on:click="$dispatch('open-modal', { id: 'weather-settings' })"
            size="sm"
            class="mt-4"
        >
            {{ __('filament-weather-widget::weather.widget.settings') }}
        </x-filament::button>
    </x-filament::card>

    <x-filament::modal id="weather-settings">
        <x-slot name="heading">
            {{ __('filament-weather-widget::weather.settings.title') }}
        </x-slot>

        <form wire:submit="saveSettings">
            {{ $this->form }}
        
            <div class="mt-4 flex justify-between">
                <x-filament::button type="submit" size="sm">
                    {{ __('filament-weather-widget::weather.settings.save') }}
                </x-filament::button>
                <x-filament::button
                    type="button"
                    color="danger"
                    size="sm"
                    wire:click="resetConfiguration"
                    wire:confirm="{{ __('filament-weather-widget::weather.settings.reset_confirm') }}"
                >
                    {{ __('filament-weather-widget::weather.settings.reset') }}
                </x-filament::button>
            </div>
        </form>
    </x-filament::modal>
</x-filament::widget>