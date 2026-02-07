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
   * Initialize settings modal
   */
  initSettings: function () {
    var modal, btn, closeBtn;

    modal = document.getElementById("settings-modal");
    btn = document.getElementById("settings-btn");
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
        DocketUI.closeSettings();
      });
    }

    // Close on background click
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        DocketUI.closeSettings();
      }
    });

    // Close on escape key
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal.classList.contains("show")) {
        DocketUI.closeSettings();
      }
    });
  },

  /**
   * Close settings modal
   */
  closeSettings: function () {
    var modal = document.getElementById("settings-modal");
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
    var modal, btn, closeBtn, checkLegacyAuthBtn;

    modal = document.getElementById("auth-settings-modal");
    checkLegacyAuthBtn = document.getElementById("check-legacy-auth-btn");
    closeBtn = modal ? modal.querySelector(".modal-close") : null;

    if (!modal) {
      return;
    }

    // Legacy authentication check button
    if (checkLegacyAuthBtn) {
      checkLegacyAuthBtn.addEventListener("click", function () {
        // Open auth modal
        modal.style.display = "flex";
        setTimeout(function () {
          modal.classList.add("show");
        }, 10);

        // Load authentication status
        DocketUI.loadAuthStatus();
      });
    }

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
    var i;
    if (accountNames.length === 0) {
      container.innerHTML =
        '<p class="auth-no-accounts">No Google Calendar accounts found in current calendar set.</p>';
      return;
    }

    for (i = 0; i < accountNames.length; i++) {
      var accountName = accountNames[i];
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
    }
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

// Calendar Management Functions
window.showCalendarManagement = function () {
  var section = document.getElementById("calendar-management-section");
  if (section) {
    section.style.display = section.style.display === "none" ? "block" : "none";
    if (section.style.display === "block") {
      loadCalendarSources();
      loadCalendarSets();
    }
  }
};

window.showAddCalendarModal = function () {
  // TODO: Implement add calendar modal
  NotificationUtils.info("Calendar source management coming soon!");
};

window.showAddCalendarSetModal = function () {
  // TODO: Implement add calendar set modal
  NotificationUtils.info("Calendar set management coming soon!");
};

function loadCalendarSources() {
  var list = document.getElementById("calendar-sources-list");
  if (!list) return;

  list.innerHTML = '<div class="loading">Loading calendar sources...</div>';

  fetch("/api/calendar-sources", {
    headers: {
      Authorization: "Bearer " + (window.userToken || ""),
      Accept: "application/json",
    },
  })
    .then(function (response) {
      if (response.ok) {
        return response.json();
      }
      throw new Error("Failed to load calendar sources");
    })
    .then(function (data) {
      renderCalendarSources(data.data || []);
    })
    .catch(function (error) {
      list.innerHTML =
        '<div class="error">Failed to load calendar sources: ' +
        error.message +
        "</div>";
    });
}

function loadCalendarSets() {
  var list = document.getElementById("calendar-sets-list");
  if (!list) return;

  list.innerHTML = '<div class="loading">Loading calendar sets...</div>';

  fetch("/api/calendar-sets", {
    headers: {
      Authorization: "Bearer " + (window.userToken || ""),
      Accept: "application/json",
    },
  })
    .then(function (response) {
      if (response.ok) {
        return response.json();
      }
      throw new Error("Failed to load calendar sets");
    })
    .then(function (data) {
      renderCalendarSets(data.data || []);
    })
    .catch(function (error) {
      list.innerHTML =
        '<div class="error">Failed to load calendar sets: ' +
        error.message +
        "</div>";
    });
}

function renderCalendarSources(sources) {
  var list = document.getElementById("calendar-sources-list");
  if (!list) return;

  if (sources.length === 0) {
    list.innerHTML =
      '<div class="empty-state">No calendar sources configured. Add one to get started!</div>';
    return;
  }

  var html = sources
    .map(function (source) {
      return (
        '<div class="calendar-source-item">' +
        '<div class="calendar-source-info">' +
        '<span class="calendar-source-color" style="background-color: ' +
        source.color +
        '"></span>' +
        (source.emoji
          ? '<span class="calendar-source-emoji">' + source.emoji + "</span>"
          : "") +
        '<span class="calendar-source-name">' +
        source.name +
        "</span>" +
        '<span class="calendar-source-type">' +
        source.type +
        "</span>" +
        "</div>" +
        '<div class="calendar-source-actions">' +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="editCalendarSource(' +
        source.id +
        ')">Edit</button>' +
        '<button type="button" class="btn btn-sm btn-danger" onclick="deleteCalendarSource(' +
        source.id +
        ')">Delete</button>' +
        "</div>" +
        "</div>"
      );
    })
    .join("");

  list.innerHTML = html;
}

function renderCalendarSets(sets) {
  var list = document.getElementById("calendar-sets-list");
  if (!list) return;

  if (sets.length === 0) {
    list.innerHTML =
      '<div class="empty-state">No calendar sets configured. Add one to get started!</div>';
    return;
  }

  var html = sets
    .map(function (set) {
      return (
        '<div class="calendar-set-item">' +
        '<div class="calendar-set-info">' +
        (set.emoji
          ? '<span class="calendar-set-emoji">' + set.emoji + "</span>"
          : "") +
        '<span class="calendar-set-name">' +
        set.name +
        "</span>" +
        '<span class="calendar-set-count">' +
        set.calendar_count +
        " calendars</span>" +
        (set.is_default
          ? '<span class="calendar-set-default">Default</span>'
          : "") +
        "</div>" +
        '<div class="calendar-set-actions">' +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="editCalendarSet(' +
        set.id +
        ')">Edit</button>' +
        '<button type="button" class="btn btn-sm btn-danger" onclick="deleteCalendarSet(' +
        set.id +
        ')">Delete</button>' +
        "</div>" +
        "</div>"
      );
    })
    .join("");

  list.innerHTML = html;
}

window.editCalendarSource = function (id) {
  NotificationUtils.info("Edit calendar source #" + id + " - coming soon!");
};

window.deleteCalendarSource = function (id) {
  if (!confirm("Are you sure you want to delete this calendar source?")) {
    return;
  }

  fetch("/api/calendar-sources/" + id, {
    method: "DELETE",
    headers: {
      Authorization: "Bearer " + (window.userToken || ""),
      Accept: "application/json",
    },
  })
    .then(function (response) {
      if (response.ok) {
        NotificationUtils.success("Calendar source deleted successfully");
        loadCalendarSources();
      } else {
        throw new Error("Failed to delete calendar source");
      }
    })
    .catch(function (error) {
      NotificationUtils.error(
        "Failed to delete calendar source: " + error.message,
      );
    });
};

window.editCalendarSet = function (id) {
  NotificationUtils.info("Edit calendar set #" + id + " - coming soon!");
};

window.deleteCalendarSet = function (id) {
  if (!confirm("Are you sure you want to delete this calendar set?")) {
    return;
  }

  fetch("/api/calendar-sets/" + id, {
    method: "DELETE",
    headers: {
      Authorization: "Bearer " + (window.userToken || ""),
      Accept: "application/json",
    },
  })
    .then(function (response) {
      if (response.ok) {
        NotificationUtils.success("Calendar set deleted successfully");
        loadCalendarSets();
      } else {
        throw new Error("Failed to delete calendar set");
      }
    })
    .catch(function (error) {
      NotificationUtils.error(
        "Failed to delete calendar set: " + error.message,
      );
    });
};

// Initialize calendar management tabs
document.addEventListener("DOMContentLoaded", function () {
  var tabButtons = document.querySelectorAll(".calendar-tab-button");
  var tabContents = document.querySelectorAll(".calendar-tab-content");

  tabButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      var tabName = button.getAttribute("data-tab");

      // Update button states
      tabButtons.forEach(function (btn) {
        btn.classList.remove("active");
      });
      button.classList.add("active");

      // Update tab content visibility
      tabContents.forEach(function (content) {
        content.classList.remove("active");
      });

      var targetTab = document.getElementById(tabName + "-tab");
      if (targetTab) {
        targetTab.classList.add("active");
      }
    });
  });
});

// Make DocketUI available globally
window.DocketUI = DocketUI;
