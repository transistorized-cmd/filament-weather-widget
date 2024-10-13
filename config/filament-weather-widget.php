<?php

return [
    'enabled' => env('WEATHER_WIDGET_ENABLED', true),
    'default_location' => env('WEATHER_WIDGET_LOCATION', 'London'),
    'default_unit' => env('WEATHER_DEFAULT_UNIT', 'celsius'),
    'default_wind_unit' => env('WEATHER_DEFAULT_WIND_UNIT', 'kph'),
    'service' => env('WEATHER_WIDGET_SERVICE', 'weatherapi'),
    'weatherapi' => [
        'key' => env('WEATHER_API_KEY'),
    ],
    'openstreetmap' => [
        'url' => env('OPENSTREETMAP_URL', 'https://nominatim.openstreetmap.org/reverse'),
        'user_agent' => env('OPENSTREETMAP_USER_AGENT', 'FilamentWeatherWidget/1.0'),
    ],
];