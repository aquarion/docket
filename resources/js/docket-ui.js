/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint devel: true */

/**
 * UI update and theming functions
 */
// biome-ignore-start lint/correctness/noUnusedVariables: DocketUI is used globally
var DocketUI = {
  // biome-ignore-end lint/correctness/noUnusedVariables: DocketUI is used globally
  /**
   * Update date and time display
   */
  updateDateTime: function () {
    var now, time, strToday, dateEl, timeEl, datetimeEl;

    now = new Date();
    time = DateUtils.formatTime(now);
    strToday = DateUtils.formatDateWithOrdinal(now);

    dateEl = document.getElementById("date");
    timeEl = document.getElementById("time");
    datetimeEl = document.getElementById("datetime");

    if (dateEl) dateEl.innerHTML = strToday;
    if (timeEl) timeEl.innerHTML = time;
    if (datetimeEl) {
      datetimeEl.innerHTML =
        '<div class="dt_time">' +
        time +
        '</div><div class="dt_date">' +
        strToday +
        "</div>";
      var callback =
        FestivalUtils &&
        FestivalUtils.getCallback &&
        FestivalUtils.getCallback("afterRenderDateTime");
      if (callback && typeof callback === "function") {
        callback(datetimeEl);
      }
    }
  },

  /**
   * Update relative time displays for today's events
   */
  updateUntil: function () {
    var mnow,
      todayEvents,
      duration,
      element,
      i,
      text,
      thisends,
      thisEvent,
      thisstarts,
      untilElement;

    mnow = new Date();
    todayEvents = document.querySelectorAll(".todayEvent");

    for (i = 0; i < todayEvents.length; i++) {
      element = todayEvents[i];
      text = "";
      thisEvent = element;
      thisends = new Date(thisEvent.getAttribute("eventends"));
      thisstarts = new Date(thisEvent.getAttribute("eventstarts"));

      if (mnow > thisends) {
        thisEvent.style.display = "none";
      } else if (thisstarts > mnow) {
        duration = DateUtils.humanizeDuration(Math.abs(thisends - thisstarts));
        text = DateUtils.fromNow(thisstarts) + " for " + duration;
      } else if (thisends > mnow) {
        text = "ends " + DateUtils.fromNow(thisends);
      }

      untilElement = thisEvent.querySelector(".until");
      if (untilElement) {
        untilElement.innerHTML = "(" + text + ")";
      }
    }

    return todayEvents;
  },

  /**
   * Update day/night theme based on sun position
   */
  updateTheme: function (forceTo) {
    var timeOfDay, body;

    timeOfDay = DocketUI.getTimeOfDay();
    body = document.body;

    if (forceTo === "day") timeOfDay = "day";
    if (forceTo === "night") timeOfDay = "night";

    if (timeOfDay === "night" && !body.classList.contains("nighttime")) {
      body.classList.remove("daytime");
      body.classList.add("nighttime");
    } else if (timeOfDay === "day" && !body.classList.contains("daytime")) {
      body.classList.remove("nighttime");
      body.classList.add("daytime");
    }
    return timeOfDay;
  },

  /**
   * Determine if it's day or night based on sun position
   * @returns {string} "day" or "night"
   */
  getTimeOfDay: function () {
    var now, sunstate;

    try {
      // Check if SunCalc is available
      if (typeof window.SunCalc === "undefined") {
        console.warn("SunCalc not yet loaded, defaulting to day");
        return "day";
      }

      now = new Date();
      sunstate = window.SunCalc.getTimes(
        now,
        DocketConfig.constants.LATITUDE,
        DocketConfig.constants.LONGITUDE,
      );

      if (now > sunstate.sunset || now < sunstate.sunrise) {
        return "night";
      } else {
        return "day";
      }
    } catch (error) {
      NotificationUtils.warning(
        "Error calculating day/night theme, using day mode",
      );
      console.warn("Error calculating time of day, defaulting to day:", error);
      return "day";
    }
  },

  /**
   * Initialize calendar selector modal
   */
  initCalendarSelector: function () {
    var modal, btn, closeBtn;

    modal = document.getElementById("calendar-selector-modal");
    btn = document.getElementById("calendar-selector-btn");
    closeBtn = modal ? modal.querySelector(".modal-close") : null;

    if (!modal || !btn) {
      return;
    }

    // Open modal
    btn.addEventListener("click", function () {
      modal.style.display = "flex";
      setTimeout(function () {
        modal.classList.add("show");
      }, 10);
    });

    // Close modal
    if (closeBtn) {
      closeBtn.addEventListener("click", function () {
        DocketUI.closeCalendarSelector();
      });
    }

    // Close on background click
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        DocketUI.closeCalendarSelector();
      }
    });

    // Close on escape key
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal.classList.contains("show")) {
        DocketUI.closeCalendarSelector();
      }
    });
  },

  /**
   * Close calendar selector modal
   */
  closeCalendarSelector: function () {
    var modal = document.getElementById("calendar-selector-modal");
    if (modal) {
      modal.classList.remove("show");
      setTimeout(function () {
        modal.style.display = "none";
      }, 300);
    }
  },
};

// Make DocketUI available globally
window.DocketUI = DocketUI;
