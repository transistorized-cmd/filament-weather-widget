<x-filament::widget>
    <div
    x-data="{
    isLoading: true,
    weatherData: null,
    locationError: null,
    async initializeWidget() {
        this.isLoading = true;
        try {
            const result = await $wire.initializeWeatherData();
            if (result.success) {
                this.weatherData = result.data;
                this.locationError = null;
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Failed to initialize weather data:', error);
            this.locationError = 'Failed to load weather data. Please try again.';
        } finally {
            this.isLoading = false;
        }
    }
}"
x-init="initializeWidget"
    >
        @if($this->getSettings()['show_weather'])
            @if($weather && empty($errorMessage))
                <x-filament::card>
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold">{{ __('filament-weather-widget::weather.widget.title', ['location' => $weather['location']]) }}</h2>
                        <div class="flex items-center">
                            <span class="text-sm text-gray-500 mr-2">{{ __('filament-weather-widget::weather.widget.updated') }}: {{ $weather['updated_at'] }}&nbsp;</span>
                            <x-filament::icon-button
                                icon="heroicon-o-cog"
                                x-on:click="$dispatch('open-modal', { id: 'weather-settings' })"
                                size="sm"
                                outlined
                            >
                            </x-filament::button>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center">
                        <img src="{{ $weather['icon_url'] }}" alt="{{ $weather['condition'] }}" class="w-16 h-16 mr-4">
                        <div>
                            <p class="text-3xl font-bold">{{ $weather['temperature'] }}°{{ $this->getSettings()['unit'] === 'celsius' ? 'C' : 'F' }}</p>
                            <p class="text-xl">{{ $weather['condition'] }}</p>
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        <p>{{ __('filament-weather-widget::weather.widget.humidity') }}: {{ $weather['humidity'] }}%</p>
                        <p>{{ __('filament-weather-widget::weather.widget.wind') }}: {{ $weather['wind_speed'] }} {{ __('filament-weather-widget::weather.settings.' . $weather['wind_unit']) }} {{ $weather['wind_direction'] }}</p>
                    </div>
                </x-filament::card>
            @else
                <x-filament::card>
                    <div class="flex items-center justify-between">
                        <p class="text-lg text-red-500">{{ $errorMessage ?: __('filament-weather-widget::weather.errors.unable_to_load') }}</p>
                        <x-filament::button
                            icon="heroicon-o-cog"
                            x-on:click="$dispatch('open-modal', { id: 'weather-settings' })"
                            size="sm"
                        >
                            {{ __('filament-weather-widget::weather.widget.settings') }}
                        </x-filament::button>
                    </div>
                </x-filament::card>
            @endif
        @else
            <x-filament::card>
                <div class="flex items-center justify-between">
                    <p class="text-lg">{{ __('filament-weather-widget::weather.widget.hidden') }}</p>
                    <x-filament::button
                        icon="heroicon-o-cog"
                        x-on:click="$dispatch('open-modal', { id: 'weather-settings' })"
                        size="sm"
                    >
                        {{ __('filament-weather-widget::weather.widget.settings') }}
                    </x-filament::button>
                </div>
            </x-filament::card>
        @endif

        <x-filament::modal id="weather-settings">
            <x-slot name="heading">
                {{ __('filament-weather-widget::weather.settings.title') }}
            </x-slot>

            <form wire:submit="saveSettings">
                {{ $this->form }}
            
                <p><br></p>
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
    </div>
</x-filament::widget>