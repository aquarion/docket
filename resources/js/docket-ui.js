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
      relEl,
      delta,
      relativeText,
      lastRelativeText,
      fadeInOut,
      duration_until_start,
      duration_until_end,
      relTexts,
      i;

    mnow = new Date();
    todayEvents = DocketCalendar.getTodayEvents();

    for (i = 0; i < todayEvents.length; i++) {
      var event = todayEvents[i];
      relEl = document.getElementById("until_" + event.databaseId);

      if (!relEl) {
        continue;
      }

      duration_until_start = event.start - mnow;
      duration_until_end = event.end - mnow;

      // If ongoing
      if (duration_until_start <= 0 && duration_until_end > 0) {
        duration = duration_until_end;
        relativeText = DateUtils.generateRelativeText(
          duration,
          "event ends in ",
        );
      } else if (duration_until_start > 0) {
        // Future event
        duration = duration_until_start;
        relativeText = DateUtils.generateRelativeText(duration, "starts in ");
      } else {
        // Past event - calculate time since it ended
        duration = Math.abs(duration_until_end);
        relativeText = DateUtils.generateRelativeText(
          duration,
          "ended ",
          " ago",
        );
      }

      // Animation management
      lastRelativeText = relEl.getAttribute("data-last-text");
      fadeInOut = false;

      if (lastRelativeText && lastRelativeText !== relativeText) {
        fadeInOut = true;
      }

      if (fadeInOut) {
        // Fade out
        relEl.style.opacity = "0";
        setTimeout(
          (function (element, text) {
            return function () {
              element.innerHTML = text;
              element.setAttribute("data-last-text", text);
              // Fade back in
              element.style.opacity = "";
            };
          })(relEl, relativeText),
          150,
        );
      } else {
        relEl.innerHTML = relativeText;
        relEl.setAttribute("data-last-text", relativeText);
      }
    }
  },

  /**
   * Update day/night theme based on sun position
   */
  updateTheme: function (forceTo) {
    var timeOfDay, body;

    timeOfDay = this.getTimeOfDay();
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
      now = new Date();
      sunstate = SunCalc.getTimes(
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
      console.error("Error calculating sun times:", error);
      // Fallback to simple time-based calculation
      var hour = now.getHours();
      return hour >= 6 && hour < 18 ? "day" : "night";
    }
  },

  switchTheme: function (theme, bypass) {
    var body = document.body;
    var currentTheme = body.getAttribute("data-theme");

    if (theme === currentTheme && !bypass) {
      return;
    }

    // Remove all existing theme classes
    body.classList.remove("nighttime", "daytime", "theme-night", "theme-day");

    // Add the new theme class
    if (theme === "night") {
      body.classList.add("nighttime");
    } else if (theme === "day") {
      body.classList.add("daytime");
    }

    // Update the body data-theme attribute
    body.setAttribute("data-theme", theme);

    // Store theme preference in localStorage
    localStorage.setItem("theme", theme);

    console.log("Theme switched to:", theme);
  },

  applyInitialTheme: function () {
    // Check for stored theme preference
    var storedTheme = localStorage.getItem("theme");

    if (storedTheme) {
      this.switchTheme(storedTheme);
    } else {
      // Default to light theme if no stored preference
      this.switchTheme("day");
    }
  },

  initializeThemeToggle: function () {
    var themeToggleButton = document.getElementById("theme-toggle");

    if (!themeToggleButton) return;

    // Remove any existing event listeners
    themeToggleButton.onclick = null;

    var self = this;

    themeToggleButton.addEventListener("click", function () {
      var body = document.body;
      var currentTheme = body.getAttribute("data-theme");
      var newTheme = currentTheme === "night" ? "day" : "night";

      self.switchTheme(newTheme);
    });
  },

  initializeFestivalSelector: function () {
    var festivalSelect = document.getElementById("festival-select");
    if (!festivalSelect) return;

    festivalSelect.addEventListener("change", function () {
      var festival = this.value;
      var url = new URL(window.location);

      if (festival && festival !== "") {
        url.searchParams.set("festival", festival);
      } else {
        url.searchParams.delete("festival");
      }

      window.location = url.toString();
    });
  },

  showCalendarSelector: function () {
    var modal = document.getElementById("calendar-selector-modal");
    if (modal) {
      modal.classList.add("show");
    }
  },

  hideCalendarSelector: function () {
    var modal = document.getElementById("calendar-selector-modal");
    if (modal) {
      modal.classList.remove("show");
    }
  },

  initializeCalendarSelector: function () {
    var openBtn = document.getElementById("calendar-selector-btn");
    var modal = document.getElementById("calendar-selector-modal");
    var closeBtn = document.getElementById("close-calendar-selector");

    if (!openBtn || !modal || !closeBtn) return;

    var self = this;

    openBtn.addEventListener("click", function (e) {
      e.preventDefault();
      self.showCalendarSelector();
    });

    closeBtn.addEventListener("click", function (e) {
      e.preventDefault();
      self.hideCalendarSelector();
    });

    // Close modal when clicking on the backdrop
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        self.hideCalendarSelector();
      }
    });

    // Close modal on Escape key
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal.classList.contains("show")) {
        self.hideCalendarSelector();
      }
    });
  },

  initializeCalendarSetLinks: function () {
    var calendarLinks = document.querySelectorAll(".calendar-set-link");

    for (var i = 0; i < calendarLinks.length; i++) {
      calendarLinks[i].addEventListener("click", function (e) {
        e.preventDefault();
        var setKey =
          this.closest(".calendar-set-item").getAttribute("data-set-id");
        var url = new URL(window.location);

        if (setKey && setKey !== "all") {
          url.searchParams.set("calendar_set", setKey);
        } else {
          url.searchParams.delete("calendar_set");
        }

        window.location = url.toString();
      });
    }
  },

  /**
   * Initialize settings modal (legacy function name)
   */
  initSettings: function () {
    // This is just a wrapper for the new function name
    this.initializeSettingsModal();
  },

  /**
   * Close settings modal (legacy function)
   */
  closeSettings: function () {
    var modal = document.getElementById("settings-modal");
    if (modal) {
      modal.classList.remove("show");
    }
  },

  initializeSettingsModal: function () {
    var settingsBtn = document.getElementById("settings-btn");
    var modal = document.getElementById("settings-modal");
    var closeBtn = modal ? modal.querySelector(".modal-close") : null;

    if (!settingsBtn || !modal || !closeBtn) return;

    var self = this;

    settingsBtn.addEventListener("click", function (e) {
      e.preventDefault();
      modal.style.display = "flex";
      modal.classList.add("show");
    });

    closeBtn.addEventListener("click", function (e) {
      e.preventDefault();
      modal.classList.remove("show");
      modal.style.display = "none";
    });

    // Close modal when clicking on the backdrop
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        modal.classList.remove("show");
        modal.style.display = "none";
      }
    });

    // Close modal on Escape key
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal.classList.contains("show")) {
        modal.classList.remove("show");
        modal.style.display = "none";
      }
    });
  },

  initializeTabs: function () {
    var tabs = document.querySelectorAll(".tab-button");

    for (var i = 0; i < tabs.length; i++) {
      tabs[i].addEventListener("click", function (e) {
        e.preventDefault();

        var targetId = this.getAttribute("data-tab");

        // Remove active class from all tabs and content
        var allTabs = document.querySelectorAll(".tab-button");
        var allContent = document.querySelectorAll(".tab-content");

        for (var j = 0; j < allTabs.length; j++) {
          allTabs[j].classList.remove("active");
        }

        for (var k = 0; k < allContent.length; k++) {
          allContent[k].classList.remove("active");
        }

        // Add active class to clicked tab
        this.classList.add("active");

        // Add active class to target content
        var targetTab = document.getElementById(targetId);
        if (targetTab) {
          targetTab.classList.add("active");
        }
      });
    }
  },

  initialize: function () {
    this.applyInitialTheme();
    this.initializeThemeToggle();
    this.initializeFestivalSelector();
    this.initializeCalendarSelector();
    this.initializeCalendarSetLinks();
    this.initializeSettingsModal();
    this.initializeTabs();
  },
};

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  DocketUI.initialize();
});

// Also initialize on tab visibility change
document.addEventListener("visibilitychange", function () {
  if (!document.hidden) {
    DocketUI.initializeCalendarSelector();
    DocketUI.initializeCalendarSetLinks();
    DocketUI.initializeSettingsModal();
    DocketUI.initializeTabs();
  }
});

// Make DocketUI available globally
window.DocketUI = DocketUI;
