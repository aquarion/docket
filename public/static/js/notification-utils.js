/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint devel: true */

/**
 * Notification utility functions for user feedback and logging
 */
// biome-ignore-start lint/correctness/noUnusedVariables: NotificationUtils is used globally
var NotificationUtils = {
  // biome-ignore-end lint/correctness/noUnusedVariables: NotificationUtils is used globally
  /**
   * Display a warning notification
   * @param {string} message - Warning message to display
   * @param {number} [duration=3000] - How long to show the notification (ms)
   */
  warning: (message, duration) => {
    duration = duration || 3000;
    if (typeof Toastify !== "undefined") {
      Toastify({
        text: message,
        duration: duration,
        style: {
          background: "#f39c12",
        },
      }).showToast();
    }
    console.warn(message);
  },

  /**
   * Display an error notification
   * @param {string} message - Error message to display
   * @param {number} [duration=5000] - How long to show the notification (ms)
   */
  error: (message, duration) => {
    duration = duration || 5000;
    if (typeof Toastify !== "undefined") {
      Toastify({
        text: message,
        duration: duration,
        style: {
          background: "#e74c3c",
        },
      }).showToast();
    }
    console.error(message);
  },

  /**
   * Log debug messages when in debug mode
   * @param {any} item - Item to log to console
   */
  debug: (item) => {
    if (typeof DocketConfig !== "undefined" && DocketConfig.constants.DEBUG) {
      console.log(item);
    }
  },
};

// Make NotificationUtils available globally
window.NotificationUtils = NotificationUtils;
