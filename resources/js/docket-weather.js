/**
 * Weather management functions for fetching and processing weather data from Open-Meteo
 */

var weatherCodes = {
	0: "☀️",
	1: "🌤️",
	2: "⛅",
	3: "☁️",
	45: "🌫️",
	48: "🌫️",
	51: "🌦️",
	53: "🌦️",
	55: "🌦️",
	56: "❄️",
	57: "❄️",
	61: "🌧️",
	63: "🌧️",
	65: "🌧️",
	66: "❄️",
	67: "❄️",
	71: "❄️",
	73: "❄️",
	75: "❄️",
	77: "❄️",
	80: "🌦️",
	81: "🌦️",
	82: "🌧️",
	85: "❄️",
	86: "❄️",
	95: "⛈️",
	96: "⛈️",
	99: "⛈️",
};

// biome-ignore-start lint/correctness/noUnusedVariables: DocketWeather is used globally
var DocketWeather = {
	// biome-ignore-end lint/correctness/noUnusedVariables: DocketWeather is used globally
	weatherData: {},
	lastFetchTime: 0,
	cacheDurationMs: 60 * 60 * 1000, // 1 hour

	/**
	 * Fetch weather data from Open-Meteo
	 * @returns {Promise} Promise that resolves when weather data is fetched
	 */
	fetch: function () {
		var now = Date.now();
		if (now - this.lastFetchTime < this.cacheDurationMs) {
			NotificationUtils.debug("Using cached weather data");
			return Promise.resolve(this.weatherData);
		}

		var lat = DocketConfig.constants.LATITUDE || 51.5074;
		var lon = DocketConfig.constants.LONGITUDE || -0.1278;
		var url =
			"https://api.open-meteo.com/v1/forecast?latitude=" +
			lat +
			"&longitude=" +
			lon +
			"&daily=weather_code&timezone=auto";

		return fetch(url)
			.then((response) => {
				if (!response.ok)
					throw new Error("Weather API HTTP " + response.status);
				return response.json();
			})
			.then(
				function (data) {
					if (data.daily?.time && data.daily.weather_code) {
						// Clear old data to prevent stale entries
						this.weatherData = {};
						this.lastFetchTime = Date.now();

						// Use the shorter length to avoid undefined values
						var len = Math.min(
							data.daily.time.length,
							data.daily.weather_code.length,
						);
						for (var i = 0; i < len; i++) {
							var date = data.daily.time[i];
							var code = data.daily.weather_code[i];
							this.weatherData[date] = this.getEmoji(code);
						}

						NotificationUtils.debug("Weather data updated");
						// Re-render events if DocketEvents is available
						if (
							typeof DocketEvents !== "undefined" &&
							DocketEvents.updateNextUp
						) {
							DocketEvents.updateNextUp();
						}
					}
					return this.weatherData;
				}.bind(this),
			)
			.catch(
				function (error) {
					console.error("Failed to fetch weather data:", error);
					this.lastFetchTime = Date.now(); // throttle retries during outages
					return this.weatherData;
				}.bind(this),
			);
	},

	/**
	 * Get weather emoji for a specific date
	 * @param {string} dateF - Date in YYYY-MM-DD format
	 * @returns {string} Weather emoji or empty string if not found
	 */
	getWeatherForDate: function (dateF) {
		return this.weatherData[dateF] || "";
	},

	/**
	 * Get weather emoji for a WMO code
	 * @param {number} code - WMO weather code
	 * @returns {string} Weather emoji
	 */
	getEmoji: (code) => weatherCodes[code] || "",
};

// Make DocketWeather available globally
window.DocketWeather = DocketWeather;
