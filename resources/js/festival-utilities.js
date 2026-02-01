/* jshint esversion: 9 */
/* jshint browser: true */

/**
 * Festival-specific utilities and enhancements
 * Only loaded when a festival is active
 */
// biome-ignore-start lint/correctness/noUnusedVariables: FestivalUtils is used globally
var FestivalUtils = {
  // biome-ignore-end lint/correctness/noUnusedVariables: FestivalUtils is used globally

  /**
   * Easter-specific utilities
   */
  easter: {
    /**
     * Replace zeros with Easter egg emojis in time displays
     * @param {HTMLElement} container - Container element to process
     */
    replaceZerosWithEggs: (container) => {
      if (window.DocketConfig?.constants?.FESTIVAL !== "easter") return;

      // Replace zeros in time elements with egg emojis
      const timeElements = container.querySelectorAll(".event_dt, .dt_time");
      timeElements.forEach((el) => {
        el.innerHTML = el.innerHTML.replace(
          /0/g,
          '<span class="easter-egg">🥚</span>',
        );
      });
    },
  },

  /**
   * Get festival-specific callback for a given hook
   * @param {string} hook - Hook name (e.g., 'afterRenderEvents')
   * @returns {Function|null} Callback function or null if not applicable
   */
  getCallback: (hook) => {
    const festival = window.DocketConfig?.constants?.FESTIVAL;
    if (!festival) return null;

    if (festival === "easter") {
      if (hook === "afterRenderDateTime") {
        return FestivalUtils.easter.replaceZerosWithEggs;
      }
      if (hook === "afterRenderEvents") {
        return FestivalUtils.easter.replaceZerosWithEggs;
      }
    }

    return null;
  },
};

// Make FestivalUtils available globally
window.FestivalUtils = FestivalUtils;
