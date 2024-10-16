function initWeatherWidget(locationMode) {
    function detectBrowser() {
        const userAgent = navigator.userAgent;
        if (userAgent.match(/chrome|chromium|crios/i)) return "Chrome";
        if (userAgent.match(/firefox|fxios/i)) return "Firefox";
        if (userAgent.match(/safari/i)) return "Safari";
        if (userAgent.match(/opr\//i)) return "Opera";
        if (userAgent.match(/edg/i)) return "Edge";
        return "Unknown";
    }
    
    return {
        locationMode,
        browser: detectBrowser(),
        locationFallbackMessage: '',

        init() {
            if (this.locationMode === 'automatic') {
                this.detectLocation();
            }
        },

        detectLocation() {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    this.handleGeolocationSuccess.bind(this),
                    this.handleGeolocationError.bind(this),
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                this.handleGeolocationNotSupported();
            }
        },

        handleGeolocationSuccess(position) {
            const { latitude, longitude } = position.coords;
            this.$dispatch('updateGeolocation', { latitude, longitude });
        },

        handleGeolocationError(error) {
            const errorMessages = {
                1: "User denied the request for geolocation.",
                2: "Location information is unavailable.",
                3: "The request to get user location timed out."
            };

            this.locationFallbackMessage = this.browser === "Safari"
                ? "Geolocation failed. This might be due to Safari's privacy settings. We're using an approximate location based on your IP address."
                : "Geolocation failed. We're using an approximate location based on your IP address.";

            this.$dispatch('useIpLocation');
        },

        handleGeolocationNotSupported() {
            this.locationFallbackMessage = "Your browser doesn't support geolocation. We're using an approximate location based on your IP address.";
            this.$dispatch('useIpLocation');
        }
    };
}

window.WeatherWidget = {
    init: initWeatherWidget
};