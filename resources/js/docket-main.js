/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint devel: true */

/**
 * Main Docket application controller
 * Coordinates all modules and manages application lifecycle
 */
// biome-ignore-start lint/correctness/noUnusedVariables: Docket is used globally
var Docket = {
  // biome-ignore-end lint/correctness/noUnusedVariables: Docket is used globally
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
    DocketUI.initSettings();

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
    var datetimeEl;

    datetimeEl = document.getElementById("datetime");
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
    // Store interval IDs for cleanup
    this.intervals = [];
    var self = this;

    // Hourly timer for potential maintenance tasks
    this.intervals.push(
      window.setInterval(function () {
        NotificationUtils.debug("Hourly maintenance check");
        // Could be used for cache cleanup, timezone updates, etc.
      }, self.config.hourlyIntervalMs),
    );

    // Regular updates (5 seconds)
    this.intervals.push(
      window.setInterval(function () {
        DocketUI.updateDateTime();
        DocketUI.updateUntil();
        DocketUI.updateTheme();
      }, self.config.updateIntervalMs),
    );

    // Refresh timer with circular progress
    this.intervals.push(
      window.setInterval(function () {
        if (
          CircleProgress.trackPercent <= Docket.constants.PROGRESS_THRESHOLD
        ) {
          CircleProgress.animate(CircleProgress.trackPercent, "countdown");
          CircleProgress.trackPercent += 1 / self.config.secondsPerRefresh;
        } else {
          NotificationUtils.debug("Refreshing");
          CircleProgress.trackPercent = 0;
          self.checkFestivalChange();
          DocketCalendar.setup();
        }
      }, self.config.refreshIntervalMs),
    );
  },

  /**
   * Check if festival has changed and reload if needed
   */
  checkFestivalChange: function () {
    var xhr = new XMLHttpRequest();
    xhr.open(
      "GET",
      "/docket.js?calendar_set=" + DocketConfig.constants.CALENDAR_SET,
      true,
    );
    xhr.setRequestHeader("Cache-Control", "no-store");
    xhr.setRequestHeader("Accept", "application/javascript");
    xhr.onload = function () {
      if (xhr.status === 200) {
        // Extract current festival from response
        var festivalMatch = xhr.responseText.match(/FESTIVAL:\s*"([^"]*)"/);
        var currentFestival = festivalMatch ? festivalMatch[1] : "";
        var previousFestival = DocketConfig.constants.FESTIVAL;

        if (currentFestival !== previousFestival) {
          NotificationUtils.debug(
            "Festival changed from '" +
              previousFestival +
              "' to '" +
              currentFestival +
              "', reloading...",
          );
          window.location.reload(true);
        }
      }
    };
    xhr.onerror = function () {
      console.error("Failed to check festival change:", xhr.statusText);
    };
    xhr.send();
  },

  /**
   * Cleanup timers
   */
  cleanup: function () {
    var i;
    if (this.intervals) {
      for (i = 0; i < this.intervals.length; i++) {
        clearInterval(this.intervals[i]);
      }
      this.intervals = [];
    }
  },

  /**
   * Error handling
   */
  handleError: function (error) {
    console.log("--- Error Follows:");
    console.log(error);
  },
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
  var requiredElements, allPresent;

  // Check if SunCalc is loaded
  if (typeof window.SunCalc === "undefined") {
    if (!initWhenReady.retryCount) initWhenReady.retryCount = 0;
    initWhenReady.retryCount++;
    if (initWhenReady.retryCount > 50) {
      console.warn("SunCalc not loaded after retries, initializing anyway");
      Docket.init();
      return;
    }
    setTimeout(initWhenReady, 10);
    return;
  }

  // Check if essential DOM elements exist
  requiredElements = ["datetime", "nextUp"];
  allPresent = requiredElements.every(
    (id) => document.getElementById(id) !== null,
  );

  if (allPresent) {
    Docket.init();
  } else {
    // Retry in a short time if elements aren't ready
    NotificationUtils.debug("Required DOM elements not ready, retrying...");
    // Show warning if we've been retrying for too long
    if (!initWhenReady.retryCount) initWhenReady.retryCount = 0;
    initWhenReady.retryCount++;
    if (initWhenReady.retryCount > 100) {
      // After 1 second of retries
      NotificationUtils.warning(
        "Some page elements are missing. Calendar may not work properly.",
        5000,
      );
      console.warn("Required DOM elements still not ready after 100 retries");
      return;
    }
    setTimeout(initWhenReady, 10);
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initWhenReady);
} else {
  initWhenReady();
}

// Make Docket available globally
window.Docket = Docket;

// Cleanup on page unload
window.addEventListener("beforeunload", function () {
  Docket.cleanup();
});
