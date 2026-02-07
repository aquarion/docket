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
  warning: function (message, duration) {
    duration = duration || 3000;
    if (typeof Toastify !== "undefined") {
      Toastify({
        text: message,
        duration: duration,
        gravity: "top",
        position: "center",
        style: {
          background: "#f39c12",
          color: "#ffffff",
          borderRadius: "8px",
          padding: "12px 16px",
          fontSize: "14px",
          maxWidth: "400px",
          boxShadow: "0 4px 12px rgba(0,0,0,0.15)",
        },
      }).showToast();
    } else {
      console.warn("Toastify not loaded, falling back to console warning");
    }
    console.warn(message);
  },

  /**
   * Display an error notification
   * @param {string} message - Error message to display
   * @param {number} [duration=5000] - How long to show the notification (ms)
   */
  error: function (message, duration) {
    duration = duration || 5000;
    if (typeof Toastify !== "undefined") {
      Toastify({
        text: message,
        duration: duration,
        gravity: "top",
        position: "center",
        style: {
          background: "#e74c3c",
          color: "#ffffff",
          borderRadius: "8px",
          padding: "12px 16px",
          fontSize: "14px",
          maxWidth: "400px",
          boxShadow: "0 4px 12px rgba(0,0,0,0.15)",
        },
      }).showToast();
    } else {
      console.warn("Toastify not loaded, falling back to console error");
    }
    console.error(message);
  },

  /**
   * Display a success notification
   * @param {string} message - Success message to display
   * @param {number} [duration=3000] - How long to show the notification (ms)
   */
  success: function (message, duration) {
    duration = duration || 3000;
    if (typeof Toastify !== "undefined") {
      Toastify({
        text: message,
        duration: duration,
        gravity: "top",
        position: "center",
        style: {
          background: "#27ae60",
          color: "#ffffff",
          borderRadius: "8px",
          padding: "12px 16px",
          fontSize: "14px",
          maxWidth: "400px",
          boxShadow: "0 4px 12px rgba(0,0,0,0.15)",
        },
      }).showToast();
    } else {
      console.warn("Toastify not loaded, falling back to console log");
    }
    console.log(message);
  },

  /**
   * Display an info notification
   * @param {string} message - Info message to display
   * @param {number} [duration=3000] - How long to show the notification (ms)
   */
  info: function (message, duration) {
    duration = duration || 3000;
    if (typeof Toastify !== "undefined") {
      Toastify({
        text: message,
        duration: duration,
        gravity: "top",
        position: "center",
        style: {
          background: "#3498db",
          color: "#ffffff",
          borderRadius: "8px",
          padding: "12px 16px",
          fontSize: "14px",
          maxWidth: "400px",
          boxShadow: "0 4px 12px rgba(0,0,0,0.15)",
        },
      }).showToast();
    } else {
      console.warn("Toastify not loaded, falling back to console log");
    }
    console.log(message);
  },

  /**
   * Log debug messages when in debug mode
   * @param {any} item - Item to log to console
   */
  debug: function (item) {
    if (typeof DocketConfig !== "undefined" && DocketConfig.constants.DEBUG) {
      console.log(item);
    }
  },
};

// Make NotificationUtils available globally
window.NotificationUtils = NotificationUtils;
