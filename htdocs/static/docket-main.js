/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint devel: true */

/**
 * Main Docket application controller
 * Coordinates all modules and manages application lifecycle
 */
var Docket = {
	// Configuration
	config: {
		refreshIntervalMs: 1000,
		updateIntervalMs: 5000,
		hourlyIntervalMs: 1000 * 60 * 60,
		secondPerRefresh: null, // Will be set from DocketConfig.constants.SECONDS_PER_REFRESH
	},

	// Constants
	constants: {
		PROGRESS_THRESHOLD: 1.02,
		MILLISECONDS_PER_DAY: 86400000,
		DAYS_PER_MONTH: 30, // Approximate for range calculations
		MINUTES_PER_DAY: 1440,
	},

	/**
	 * Initialize the application
	 */
	init: function () {
		this.config.secondsPerRefresh =
			DocketConfig.constants.SECONDS_PER_REFRESH || 1800;

		// Initialize UI
		DocketUI.updateDateTime();
		DocketUI.updateTheme();

		// Setup calendar
		DocketCalendar.setup();

		// Setup event handlers
		this.setupEventHandlers();

		// Start timers
		this.startTimers();
	},

	/**
	 * Setup event handlers
	 */
	setupEventHandlers: function () {
		var datetimeEl = document.getElementById("datetime");
		if (datetimeEl) {
			datetimeEl.addEventListener("click", function () {
				window.location.reload(true);
			});
		}
	},

	/**
	 * Start application timers
	 */
	startTimers: function () {
		var self = this;

		// Store interval IDs for cleanup
		this.intervals = [];

		// Hourly timer for potential maintenance tasks
		this.intervals.push(
			window.setInterval(function () {
				debug("Hourly maintenance check");
				// Could be used for cache cleanup, timezone updates, etc.
			}, this.config.hourlyIntervalMs),
		);

		// Regular updates (5 seconds)
		this.intervals.push(
			window.setInterval(function () {
				DocketUI.updateDateTime();
				DocketUI.updateUntil();
				DocketUI.updateTheme();
			}, this.config.updateIntervalMs),
		);

		// Refresh timer with circular progress
		this.intervals.push(
			window.setInterval(function () {
				if (
					CircleProgress.trackPercent <=
					Docket.constants.PROGRESS_THRESHOLD
				) {
					CircleProgress.animate(CircleProgress.trackPercent, "countdown");
					CircleProgress.trackPercent += 1 / self.config.secondsPerRefresh;
				} else {
					debug("Refreshing");
					CircleProgress.trackPercent = 0;
					DocketCalendar.setup();
				}
			}, this.config.refreshIntervalMs),
		);
	},

	/**
	 * Cleanup timers
	 */
	cleanup: function () {
		if (this.intervals) {
			this.intervals.forEach(function (intervalId) {
				clearInterval(intervalId);
			});
			this.intervals = [];
		}
	},

	/**
	 * Error handling
	 */
	handleError: function (error) {
		console.log("--- Error Follows:");
		console.log(error);
	}
};

// Initialize circle progress and start application
if (document.getElementById("countdown")) {
	CircleProgress.drawCircle("countdown");
}

/**
 * Initialize application when DOM is ready and required elements exist
 * Uses retry logic to ensure all necessary DOM elements are available
 */
function initWhenReady() {
	// Check if essential DOM elements exist
	var requiredElements = ['datetime', 'nextUp'];
	var allPresent = requiredElements.every(function(id) {
		return document.getElementById(id) !== null;
	});
	
	if (allPresent) {
		Docket.init();
	} else {
		// Retry in a short time if elements aren't ready
		debug('Required DOM elements not ready, retrying...');
		// Show warning if we've been retrying for too long
		if (!initWhenReady.retryCount) initWhenReady.retryCount = 0;
		initWhenReady.retryCount++;
		if (initWhenReady.retryCount > 100) { // After 1 second of retries
			NotificationUtils.warning('Some page elements are missing. Calendar may not work properly.', 5000);
			console.warn('Required DOM elements still not ready after 100 retries');
			return;
		}
		setTimeout(initWhenReady, 10);
	}
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initWhenReady);
} else {
	initWhenReady();
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
	Docket.cleanup();
});