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

  /**
   * Initialize authentication settings modal
   */
  initAuthSettings: function () {
    var modal, btn, closeBtn, checkAuthBtn;

    modal = document.getElementById("auth-settings-modal");
    checkAuthBtn = document.getElementById("check-auth-btn");
    closeBtn = modal ? modal.querySelector(".modal-close") : null;

    if (!modal || !checkAuthBtn) {
      return;
    }

    // Open auth settings modal
    checkAuthBtn.addEventListener("click", function () {
      // Close the calendar selector modal first
      DocketUI.closeCalendarSelector();

      // Open auth modal
      modal.style.display = "flex";
      setTimeout(function () {
        modal.classList.add("show");
      }, 10);

      // Load authentication status
      DocketUI.loadAuthStatus();
    });

    // Close modal
    if (closeBtn) {
      closeBtn.addEventListener("click", function () {
        DocketUI.closeAuthSettings();
      });
    }

    // Close on background click
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        DocketUI.closeAuthSettings();
      }
    });

    // Close on escape key
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal.classList.contains("show")) {
        DocketUI.closeAuthSettings();
      }
    });
  },

  /**
   * Close authentication settings modal
   */
  closeAuthSettings: function () {
    var modal = document.getElementById("auth-settings-modal");
    if (modal) {
      modal.classList.remove("show");
      setTimeout(function () {
        modal.style.display = "none";
      }, 300);
    }
  },

  /**
   * Load and display authentication status
   */
  loadAuthStatus: function () {
    var loadingEl = document.getElementById("auth-loading");
    var contentEl = document.getElementById("auth-content");
    var errorEl = document.getElementById("auth-error");
    var accountsListEl = document.getElementById("auth-accounts-list");

    // Show loading state
    loadingEl.style.display = "flex";
    contentEl.style.display = "none";
    errorEl.style.display = "none";

    // Get current calendar_set from URL or default
    var urlParams = new URLSearchParams(window.location.search);
    var calendarSet = urlParams.get("calendar_set") || "all";

    // Fetch authentication status
    fetch("/auth/google/check?calendar_set=" + encodeURIComponent(calendarSet))
      .then(function (response) {
        if (!response.ok) {
          throw new Error(
            "HTTP " + response.status + ": " + response.statusText,
          );
        }
        return response.json();
      })
      .then(function (data) {
        if (data.error) {
          throw new Error(data.error);
        }

        // Hide loading, show content
        loadingEl.style.display = "none";
        contentEl.style.display = "block";

        // Render account status
        DocketUI.renderAccountStatus(data.account_status || {}, accountsListEl);
      })
      .catch(function (error) {
        console.error("Failed to load auth status:", error);

        // Hide loading, show error
        loadingEl.style.display = "none";
        errorEl.style.display = "flex";

        var errorMessageEl = document.getElementById("auth-error-message");
        if (errorMessageEl) {
          errorMessageEl.textContent =
            error.message || "Failed to check authentication status";
        }
      });
  },

  /**
   * Render account authentication status
   */
  renderAccountStatus: function (accountStatus, container) {
    if (!container) {
      return;
    }

    // Clear existing content
    container.innerHTML = "";

    var accountNames = Object.keys(accountStatus);
    if (accountNames.length === 0) {
      container.innerHTML =
        '<p class="auth-no-accounts">No Google Calendar accounts found in current calendar set.</p>';
      return;
    }

    accountNames.forEach(function (accountName) {
      var account = accountStatus[accountName];
      var accountEl = document.createElement("div");
      accountEl.className = "auth-account-item";

      var statusBadgeClass = account.authenticated
        ? "authenticated"
        : "not-authenticated";
      var statusBadgeText = account.authenticated
        ? "✓ Authenticated"
        : "❌ Not Authenticated";

      var actionsHTML = "";
      if (account.authenticated) {
        actionsHTML =
          '<button class="auth-action-btn danger" onclick="DocketUI.revokeAuth(\'' +
          accountName +
          "')\">🗑️ Revoke</button>";
      } else if (account.auth_url) {
        actionsHTML =
          '<a href="' +
          account.auth_url +
          '" class="auth-action-btn primary" target="_blank">🔑 Authenticate</a>';
      }

      accountEl.innerHTML =
        '<div class="auth-account-header">' +
        '<span class="auth-account-name">' +
        accountName +
        "</span>" +
        '<span class="auth-status-badge ' +
        statusBadgeClass +
        '">' +
        statusBadgeText +
        "</span>" +
        "</div>" +
        (account.error
          ? '<div class="auth-account-error">Error: ' + account.error + "</div>"
          : "") +
        '<div class="auth-account-actions">' +
        actionsHTML +
        "</div>";

      container.appendChild(accountEl);
    });
  },

  /**
   * Revoke authentication for an account
   */
  revokeAuth: function (accountName) {
    if (
      !confirm(
        "Are you sure you want to revoke authentication for account '" +
          accountName +
          "'? This will require re-authentication to access calendars.",
      )
    ) {
      return;
    }

    // Make DELETE request to revoke endpoint
    fetch("/auth/google/revoke?account=" + encodeURIComponent(accountName), {
      method: "DELETE",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error(
            "HTTP " + response.status + ": " + response.statusText,
          );
        }
        return response.json();
      })
      .then(function (data) {
        if (data.error) {
          throw new Error(data.error);
        }

        NotificationUtils.success(
          "🗑️ Authentication revoked for account: " + accountName,
        );

        // Reload auth status
        DocketUI.loadAuthStatus();
      })
      .catch(function (error) {
        console.error("Failed to revoke auth:", error);
        NotificationUtils.error(
          "❌ Failed to revoke authentication: " + error.message,
        );
      });
  },
};

// Make DocketUI available globally
window.DocketUI = DocketUI;
