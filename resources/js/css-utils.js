/* jshint esversion: 9 */
/* jshint browser: true */

/**
 * CSS utility functions
 * Provides helpers for working with CSS classes and styles
 */
// biome-ignore-start lint/correctness/noUnusedVariables: CssUtils is used globally
var CssUtils = {
  // biome-ignore-end lint/correctness/noUnusedVariables: CssUtils is used globally

  /**
   * Sanitizes a string to be a valid CSS class name.
   * CSS class names must start with a letter, underscore, or hyphen,
   * and can contain letters, numbers, hyphens, and underscores.
   *
   * IMPORTANT: Keep this function in sync with the PHP version:
   * app/Support/StringHelper.php:StringHelper::sanitizeCssClassName()
   *
   * @param {string} name - The name to sanitize
   * @returns {string} Sanitized CSS class name
   */
  sanitizeCssClassName: function (name) {
    var sanitized, hexHash;

    // Remove any characters that aren't alphanumeric, hyphen, or underscore
    sanitized = name.replace(/[^a-zA-Z0-9\-_]/g, "-");

    // Ensure it doesn't start with a number or hyphen followed by number
    if (/^(\d|-\d)/.test(sanitized)) {
      sanitized = "cal-" + sanitized;
    }

    // Ensure it doesn't start with two hyphens (invalid)
    sanitized = sanitized.replace(/^--+/, "cal-");

    // Remove consecutive hyphens and underscores
    sanitized = sanitized.replace(/[-_]{2,}/g, "-");

    // Trim leading/trailing hyphens and underscores
    sanitized = sanitized.replace(/^[-_]+|[-_]+$/g, "");

    // If empty after sanitization, provide a fallback
    if (!sanitized) {
      // Simple hash function for fallback
      hexHash = 0;
      for (var i = 0; i < name.length; i++) {
        hexHash = (hexHash << 5) - hexHash + name.charCodeAt(i);
        hexHash = hexHash & hexHash; // Convert to 32-bit integer
      }
      sanitized = "calendar-" + Math.abs(hexHash).toString(16);
    }

    return sanitized;
  },
};

// Make CssUtils available globally
window.CssUtils = CssUtils;
