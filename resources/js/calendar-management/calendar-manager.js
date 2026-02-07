/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint devel: true */

/**
 * Calendar Management functionality for the manage page
 * Handles CRUD operations for calendar sets and sources
 */
var CalendarManager = {
  calendarSets: [],
  calendarSources: [],
  googleCalendars: [], // Store Google calendar data
  currentEditingSet: null,
  currentEditingSource: null,

  init: function () {
    this.loadCalendarSets();
    this.loadCalendarSources();
    this.setupEventListeners();
    this.setupEmojiPickers();
  },

  setupEventListeners: function () {
    var self = this;

    // Calendar set form submission
    var setForm = document.getElementById("calendar-set-form");
    if (setForm) {
      setForm.addEventListener("submit", function (e) {
        self.handleSetSubmit(e);
      });
    }

    // Calendar source form submission
    var sourceForm = document.getElementById("calendar-source-form");
    if (sourceForm) {
      sourceForm.addEventListener("submit", function (e) {
        self.handleSourceSubmit(e);
      });
    }

    // Modal close on background click
    var setModal = document.getElementById("calendar-set-modal");
    if (setModal) {
      setModal.addEventListener("click", function (e) {
        if (e.target === e.currentTarget) {
          self.hideSetModal();
        }
      });
    }

    var sourceModal = document.getElementById("calendar-source-modal");
    if (sourceModal) {
      sourceModal.addEventListener("click", function (e) {
        if (e.target === e.currentTarget) {
          self.hideSourceModal();
        }
      });
    }

    var googleModal = document.getElementById(
      "google-calendar-selection-modal",
    );
    if (googleModal) {
      googleModal.addEventListener("click", function (e) {
        if (e.target === e.currentTarget) {
          self.hideGoogleCalendarModal();
        }
      });
    }
  },

  // Calendar Sets API methods
  loadCalendarSets: function () {
    var self = this;
    this.apiRequest("/api/calendar-sets")
      .then(function (response) {
        self.calendarSets = response.data || [];
        self.renderCalendarSets();
      })
      .catch(function (error) {
        console.error("Failed to load calendar sets:", error);
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.error("Failed to load calendar sets");
        }
        self.showCalendarSetsError();
      });
  },

  loadCalendarSources: function () {
    var self = this;
    this.apiRequest("/api/calendar-sources")
      .then(function (response) {
        self.calendarSources = response.data || [];
        self.renderCalendarSources();
      })
      .catch(function (error) {
        console.error("Failed to load calendar sources:", error);
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.error("Failed to load calendar sources");
        }
        self.showCalendarSourcesError();
      });
  },

  renderCalendarSets: function () {
    var loadingEl = document.getElementById("calendar-sets-loading");
    var listEl = document.getElementById("calendar-sets-list");
    var emptyEl = document.getElementById("calendar-sets-empty");

    if (!loadingEl || !listEl || !emptyEl) return;

    loadingEl.style.display = "none";

    if (this.calendarSets.length === 0) {
      listEl.style.display = "none";
      emptyEl.style.display = "block";
      return;
    }

    emptyEl.style.display = "none";
    listEl.style.display = "block";

    var html = "";
    for (var i = 0; i < this.calendarSets.length; i++) {
      var set = this.calendarSets[i];
      html += this.renderCalendarSetItem(set);
    }
    listEl.innerHTML = html;
  },

  renderCalendarSetItem: function (set) {
    return (
      '<div class="list-item">' +
      '<div class="item-info">' +
      '<div class="item-emoji">' +
      this.escapeHtml(set.emoji || "📋") +
      "</div>" +
      '<div class="item-details">' +
      "<h3>" +
      this.escapeHtml(set.name) +
      "</h3>" +
      '<div class="item-meta">' +
      "<span>Key: " +
      this.escapeHtml(set.key) +
      "</span>" +
      "<span>Sources: " +
      (set.calendar_count || 0) +
      "</span>" +
      (set.is_default ? "<span>Default</span>" : "") +
      "</div>" +
      "</div>" +
      "</div>" +
      '<div class="item-actions">' +
      '<button class="btn btn-toggle ' +
      (set.is_active ? "active" : "") +
      '" onclick="CalendarManager.toggleSet(' +
      set.id +
      ", " +
      !set.is_active +
      ')">' +
      (set.is_active ? "Active" : "Inactive") +
      "</button>" +
      '<button class="btn btn-edit" onclick="CalendarManager.editSet(' +
      set.id +
      ')">Edit</button>' +
      '<button class="btn btn-delete" onclick="CalendarManager.deleteSet(' +
      set.id +
      ')">Delete</button>' +
      "</div>" +
      "</div>"
    );
  },

  renderCalendarSources: function () {
    var loadingEl = document.getElementById("calendar-sources-loading");
    var listEl = document.getElementById("calendar-sources-list");
    var emptyEl = document.getElementById("calendar-sources-empty");

    if (!loadingEl || !listEl || !emptyEl) return;

    loadingEl.style.display = "none";

    if (this.calendarSources.length === 0) {
      listEl.style.display = "none";
      emptyEl.style.display = "block";
      return;
    }

    emptyEl.style.display = "none";
    listEl.style.display = "block";

    var html = "";
    for (var i = 0; i < this.calendarSources.length; i++) {
      var source = this.calendarSources[i];
      html += this.renderCalendarSourceItem(source);
    }
    listEl.innerHTML = html;
  },

  renderCalendarSourceItem: function (source) {
    return (
      '<div class="list-item">' +
      '<div class="item-info">' +
      '<div class="item-emoji">' +
      this.escapeHtml(source.emoji || "🔗") +
      "</div>" +
      '<div class="item-details">' +
      "<h3>" +
      this.escapeHtml(source.name) +
      "</h3>" +
      '<div class="item-meta">' +
      "<span>Type: " +
      this.escapeHtml(source.type) +
      "</span>" +
      "<span>Key: " +
      this.escapeHtml(source.key) +
      "</span>" +
      '<span class="color-circle" style="background-color: ' +
      this.escapeHtml(source.color) +
      '"></span>' +
      "</div>" +
      "</div>" +
      "</div>" +
      '<div class="item-actions">' +
      '<button class="btn btn-toggle ' +
      (source.is_active ? "active" : "") +
      '" onclick="CalendarManager.toggleSource(' +
      source.id +
      ", " +
      !source.is_active +
      ')">' +
      (source.is_active ? "Active" : "Inactive") +
      "</button>" +
      '<button class="btn btn-edit" onclick="CalendarManager.editSource(' +
      source.id +
      ')">Edit</button>' +
      '<button class="btn btn-delete" onclick="CalendarManager.deleteSource(' +
      source.id +
      ')">Delete</button>' +
      "</div>" +
      "</div>"
    );
  },

  // Calendar Set Modal Methods
  showAddSetModal: function () {
    this.currentEditingSet = null;
    var titleEl = document.getElementById("calendar-set-modal-title");
    var formEl = document.getElementById("calendar-set-form");
    var modalEl = document.getElementById("calendar-set-modal");

    if (titleEl) titleEl.textContent = "Add Calendar Set";
    if (formEl) formEl.reset();
    if (modalEl) modalEl.classList.add("show");

    this.setupModalEmojiPickers("calendar-set-modal");
    this.populateCalendarSourcesSelection();
  },

  hideSetModal: function () {
    var modalEl = document.getElementById("calendar-set-modal");
    if (modalEl) modalEl.classList.remove("show");
    this.currentEditingSet = null;
    this.hideAllEmojiPickers();
  },

  editSet: function (setId) {
    var self = this;

    // First get the set details with its calendar sources
    this.apiRequest("/api/calendar-sets/" + setId, "GET")
      .then(function (response) {
        var set = response.data;
        self.currentEditingSet = set;

        var titleEl = document.getElementById("calendar-set-modal-title");
        var keyEl = document.getElementById("set-key");
        var nameEl = document.getElementById("set-name");
        var emojiEl = document.getElementById("set-emoji");
        var defaultEl = document.getElementById("set-default");
        var modalEl = document.getElementById("calendar-set-modal");

        if (titleEl) titleEl.textContent = "Edit Calendar Set";
        if (keyEl) keyEl.value = set.key || "";
        if (nameEl) nameEl.value = set.name || "";
        if (emojiEl) emojiEl.value = set.emoji || "";
        if (defaultEl) defaultEl.checked = set.is_default || false;
        if (modalEl) modalEl.classList.add("show");

        self.setupModalEmojiPickers("calendar-set-modal");
        self.populateCalendarSourcesSelection(set.calendar_sources);
      })
      .catch(function (error) {
        console.error("Failed to load calendar set details:", error);
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.error("Failed to load calendar set details");
        }
      });
  },

  handleSetSubmit: function (e) {
    e.preventDefault();
    var self = this;

    var formData = new FormData(e.target);
    var data = {
      key: formData.get("key"),
      name: formData.get("name"),
      emoji: formData.get("emoji"),
      is_default: formData.get("is_default") === "on",
      calendar_sources: this.getSelectedCalendarSources(),
    };

    var url = this.currentEditingSet
      ? "/api/calendar-sets/" + this.currentEditingSet.id
      : "/api/calendar-sets";
    var method = this.currentEditingSet ? "PUT" : "POST";

    this.apiRequest(url, method, data)
      .then(function (response) {
        if (typeof NotificationUtils !== "undefined") {
          var message = self.currentEditingSet
            ? "Calendar set updated successfully"
            : "Calendar set created successfully";
          NotificationUtils.success(message);
        }
        self.hideSetModal();
        self.loadCalendarSets();
      })
      .catch(function (error) {
        console.error("Failed to save calendar set:", error);
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.error(
            "Failed to save calendar set: " + error.message,
          );
        }
      });
  },

  toggleSet: function (setId, isActive) {
    var self = this;
    this.apiRequest("/api/calendar-sets/" + setId, "PUT", {
      is_active: isActive,
    })
      .then(function (response) {
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.success(
            "Calendar set " + (isActive ? "activated" : "deactivated"),
          );
        }
        self.loadCalendarSets();
      })
      .catch(function (error) {
        console.error("Failed to toggle calendar set:", error);
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.error("Failed to update calendar set");
        }
      });
  },

  deleteSet: function (setId) {
    if (!confirm("Are you sure you want to delete this calendar set?")) {
      return;
    }

    var self = this;
    this.apiRequest("/api/calendar-sets/" + setId, "DELETE")
      .then(function (response) {
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.success("Calendar set deleted successfully");
        }
        self.loadCalendarSets();
      })
      .catch(function (error) {
        console.error("Failed to delete calendar set:", error);
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.error("Failed to delete calendar set");
        }
      });
  },

  // Calendar Source Modal Methods
  showAddSourceModal: function (type) {
    this.currentEditingSource = null;
    var titleEl = document.getElementById("calendar-source-modal-title");
    var formEl = document.getElementById("calendar-source-form");
    var colorEl = document.getElementById("source-color");
    var typeEl = document.getElementById("source-type");
    var modalEl = document.getElementById("calendar-source-modal");

    if (titleEl) titleEl.textContent = "Add Calendar Source";
    if (formEl) formEl.reset();
    if (colorEl) colorEl.value = "#3788d8";
    if (type && typeEl) typeEl.value = type;
    if (modalEl) modalEl.classList.add("show");

    this.setupModalEmojiPickers("calendar-source-modal");
  },

  hideSourceModal: function () {
    var modalEl = document.getElementById("calendar-source-modal");
    if (modalEl) modalEl.classList.remove("show");
    this.currentEditingSource = null;
    this.hideAllEmojiPickers();
  },

  editSource: function (sourceId) {
    var source = null;
    for (var i = 0; i < this.calendarSources.length; i++) {
      if (this.calendarSources[i].id === sourceId) {
        source = this.calendarSources[i];
        break;
      }
    }

    if (!source) {
      if (typeof NotificationUtils !== "undefined") {
        NotificationUtils.error("Calendar source not found");
      }
      return;
    }

    this.currentEditingSource = source;
    var titleEl = document.getElementById("calendar-source-modal-title");
    var keyEl = document.getElementById("source-key");
    var nameEl = document.getElementById("source-name");
    var typeEl = document.getElementById("source-type");
    var srcEl = document.getElementById("source-src");
    var colorEl = document.getElementById("source-color");
    var emojiEl = document.getElementById("source-emoji");
    var modalEl = document.getElementById("calendar-source-modal");

    if (titleEl) titleEl.textContent = "Edit Calendar Source";
    if (keyEl) keyEl.value = source.key || "";
    if (nameEl) nameEl.value = source.name || "";
    if (typeEl) typeEl.value = source.type || "ical";
    if (srcEl) srcEl.value = source.src || "";
    if (colorEl) colorEl.value = source.color || "#3788d8";
    if (emojiEl) emojiEl.value = source.emoji || "";
    if (modalEl) modalEl.classList.add("show");

    this.setupModalEmojiPickers("calendar-source-modal");
  },

  handleSourceSubmit: function (e) {
    e.preventDefault();
    var self = this;

    var formData = new FormData(e.target);
    var data = {
      key: formData.get("key"),
      name: formData.get("name"),
      type: formData.get("type"),
      src: formData.get("src"),
      color: formData.get("color"),
      emoji: formData.get("emoji"),
    };

    var url = this.currentEditingSource
      ? "/api/calendar-sources/" + this.currentEditingSource.id
      : "/api/calendar-sources";
    var method = this.currentEditingSource ? "PUT" : "POST";

    this.apiRequest(url, method, data)
      .then(function (response) {
        if (typeof NotificationUtils !== "undefined") {
          var message = self.currentEditingSource
            ? "Calendar source updated successfully"
            : "Calendar source created successfully";
          NotificationUtils.success(message);
        }
        self.hideSourceModal();
        self.loadCalendarSources();
      })
      .catch(function (error) {
        console.error("Failed to save calendar source:", error);
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.error(
            "Failed to save calendar source: " + error.message,
          );
        }
      });
  },

  toggleSource: function (sourceId, isActive) {
    var self = this;
    this.apiRequest("/api/calendar-sources/" + sourceId, "PUT", {
      is_active: isActive,
    })
      .then(function (response) {
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.success(
            "Calendar source " + (isActive ? "activated" : "deactivated"),
          );
        }
        self.loadCalendarSources();
      })
      .catch(function (error) {
        console.error("Failed to toggle calendar source:", error);
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.error("Failed to update calendar source");
        }
      });
  },

  deleteSource: function (sourceId) {
    if (!confirm("Are you sure you want to delete this calendar source?")) {
      return;
    }

    var self = this;
    this.apiRequest("/api/calendar-sources/" + sourceId, "DELETE")
      .then(function (response) {
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.success("Calendar source deleted successfully");
        }
        self.loadCalendarSources();
      })
      .catch(function (error) {
        console.error("Failed to delete calendar source:", error);
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.error("Failed to delete calendar source");
        }
      });
  },

  // Google Calendar Selection Methods
  showGoogleCalendarModal: function () {
    this.loadGoogleCalendars();
    var modalEl = document.getElementById("google-calendar-selection-modal");
    if (modalEl) modalEl.classList.add("show");
  },

  hideGoogleCalendarModal: function () {
    var modalEl = document.getElementById("google-calendar-selection-modal");
    var listEl = document.getElementById("google-calendars-list");
    if (modalEl) modalEl.classList.remove("show");
    if (listEl) listEl.innerHTML = "";
  },

  loadGoogleCalendars: function () {
    var self = this;
    var loadingEl = document.getElementById("google-calendars-loading");
    var listEl = document.getElementById("google-calendars-list");
    var errorEl = document.getElementById("google-calendars-error");
    var addBtn = document.getElementById("add-google-calendars-btn");

    if (loadingEl) loadingEl.style.display = "block";
    if (listEl) listEl.style.display = "none";
    if (errorEl) errorEl.style.display = "none";
    if (addBtn) addBtn.disabled = true;

    this.apiRequest("/api/google-calendars")
      .then(function (response) {
        var calendars = response.data || [];
        if (loadingEl) loadingEl.style.display = "none";

        if (calendars.length === 0) {
          if (errorEl) {
            errorEl.innerHTML =
              "No Google calendars found. Please make sure you're authenticated with Google and have calendars available.";
            errorEl.style.display = "block";
          }
          return;
        }

        // Store calendar data for later use
        self.googleCalendars = calendars;
        self.renderGoogleCalendars(calendars);
        if (listEl) listEl.style.display = "block";
      })
      .catch(function (error) {
        console.error("Failed to load Google calendars:", error);
        if (loadingEl) loadingEl.style.display = "none";
        if (errorEl) errorEl.style.display = "block";
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.error("Failed to load Google calendars");
        }
      });
  },

  renderGoogleCalendars: function (calendars) {
    var listEl = document.getElementById("google-calendars-list");
    var addBtn = document.getElementById("add-google-calendars-btn");
    if (!listEl) return;

    var html = "";
    for (var i = 0; i < calendars.length; i++) {
      var calendar = calendars[i];
      var colorStyle = calendar.backgroundColor
        ? 'style="background-color: ' +
          this.escapeHtml(calendar.backgroundColor) +
          '"'
        : 'style="background-color: #3788d8"';

      html +=
        '<div class="google-calendar-item" data-calendar-id="' +
        this.escapeHtml(calendar.id) +
        '">' +
        '<input type="checkbox" class="google-calendar-checkbox" ' +
        'id="gcal-' +
        this.escapeHtml(calendar.id) +
        '" ' +
        'onchange="CalendarManager.toggleGoogleCalendarSelection()">' +
        '<div class="google-calendar-info">' +
        '<div class="google-calendar-name">' +
        this.escapeHtml(calendar.summary) +
        "</div>" +
        '<div class="google-calendar-details">' +
        (calendar.primary
          ? '<span class="google-calendar-primary">Primary</span>'
          : "") +
        "<span>Access: " +
        this.escapeHtml(calendar.access_role) +
        "</span>" +
        "</div>" +
        "</div>" +
        '<div class="google-calendar-color" ' +
        colorStyle +
        "></div>" +
        "</div>";
    }

    listEl.innerHTML = html;

    // Add click handlers for calendar items
    var items = listEl.querySelectorAll(".google-calendar-item");
    for (var i = 0; i < items.length; i++) {
      items[i].addEventListener("click", function (e) {
        if (e.target.type === "checkbox") return;
        var checkbox = this.querySelector(".google-calendar-checkbox");
        checkbox.checked = !checkbox.checked;
        CalendarManager.toggleGoogleCalendarSelection();
      });
    }

    this.toggleGoogleCalendarSelection();
  },

  toggleGoogleCalendarSelection: function () {
    var checkboxes = document.querySelectorAll(
      ".google-calendar-checkbox:checked",
    );
    var addBtn = document.getElementById("add-google-calendars-btn");

    // Update visual selection
    var items = document.querySelectorAll(".google-calendar-item");
    for (var i = 0; i < items.length; i++) {
      var checkbox = items[i].querySelector(".google-calendar-checkbox");
      if (checkbox.checked) {
        items[i].classList.add("selected");
      } else {
        items[i].classList.remove("selected");
      }
    }

    // Enable/disable add button
    if (addBtn) {
      addBtn.disabled = checkboxes.length === 0;
      addBtn.textContent =
        checkboxes.length === 0
          ? "Add Selected"
          : "Add " +
            checkboxes.length +
            " Calendar" +
            (checkboxes.length === 1 ? "" : "s");
    }
  },

  addSelectedGoogleCalendars: function () {
    var checkboxes = document.querySelectorAll(
      ".google-calendar-checkbox:checked",
    );
    if (checkboxes.length === 0) {
      if (typeof NotificationUtils !== "undefined") {
        NotificationUtils.warning("Please select at least one calendar");
      }
      return;
    }

    var sources = [];
    for (var i = 0; i < checkboxes.length; i++) {
      var checkbox = checkboxes[i];
      var calendarId = checkbox.id.replace("gcal-", "");
      var item = checkbox.closest(".google-calendar-item");
      var name = item.querySelector(".google-calendar-name").textContent;

      // Get the original calendar data from the Google API response
      var calendarData = this.findGoogleCalendarById(calendarId);
      var color = "#3788d8"; // default color

      if (calendarData && calendarData.backgroundColor) {
        color = calendarData.backgroundColor;
      } else {
        // Fallback: try to extract color from the color div style
        var colorDiv = item.querySelector(".google-calendar-color");
        var backgroundColor = colorDiv.style.backgroundColor || "#3788d8";
        color = this.rgbToHex(backgroundColor);
      }

      sources.push({
        key: "google_" + calendarId,
        name: name,
        type: "google",
        src: calendarId,
        color: color,
        emoji: "📅",
      });
    }

    var self = this;
    this.apiRequest("/api/calendar-sources/batch", "POST", { sources: sources })
      .then(function (response) {
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.success(
            response.message || "Google calendars added successfully",
          );
        }
        self.hideGoogleCalendarModal();
        self.loadCalendarSources();
      })
      .catch(function (error) {
        console.error("Failed to add Google calendars:", error);
        if (typeof NotificationUtils !== "undefined") {
          NotificationUtils.error(
            "Failed to add Google calendars: " + error.message,
          );
        }
      });
  },

  // Emoji Picker Methods
  setupEmojiPickers: function () {
    var self = this;

    // Setup emoji picker buttons
    var buttons = document.querySelectorAll(".emoji-picker-btn");
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].addEventListener("click", function (e) {
        self.showEmojiPicker(e);
      });
    }

    // Make emoji display inputs clickable
    var inputs = document.querySelectorAll(".emoji-display");
    for (var i = 0; i < inputs.length; i++) {
      inputs[i].addEventListener("click", function (e) {
        var targetId = e.target.id;
        var button = document.querySelector(
          '.emoji-picker-btn[data-target="' + targetId + '"]',
        );
        if (button) {
          button.click();
        }
      });
    }

    // Close emoji picker when clicking outside
    document.addEventListener("click", function (e) {
      if (
        !e.target.closest(".emoji-picker-popup") &&
        !e.target.closest(".emoji-picker-btn")
      ) {
        self.hideAllEmojiPickers();
      }
    });
  },

  setupModalEmojiPickers: function (modalId) {
    var modal = document.getElementById(modalId);
    if (!modal) return;

    var self = this;
    var buttons = modal.querySelectorAll(".emoji-picker-btn");
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].removeEventListener("click", this.showEmojiPicker);
      buttons[i].addEventListener("click", function (e) {
        self.showEmojiPicker(e);
      });
    }

    var inputs = modal.querySelectorAll(".emoji-display");
    for (var i = 0; i < inputs.length; i++) {
      inputs[i].addEventListener("click", function (e) {
        var targetId = e.target.id;
        var button = document.querySelector(
          '.emoji-picker-btn[data-target="' + targetId + '"]',
        );
        if (button) {
          button.click();
        }
      });
    }
  },

  showEmojiPicker: function (e) {
    e.preventDefault();
    e.stopPropagation();

    var button = e.currentTarget;
    var targetId = button.getAttribute("data-target");
    var targetInput = document.getElementById(targetId);

    if (!targetInput) {
      console.error("Target input not found:", targetId);
      return;
    }

    this.hideAllEmojiPickers();

    var popup = document.getElementById("emoji-picker-popup-" + targetId);
    if (!popup) {
      popup = document.createElement("div");
      popup.id = "emoji-picker-popup-" + targetId;
      popup.className = "emoji-picker-popup";

      var picker = document.createElement("emoji-picker");
      picker.setAttribute("data-target", targetId);

      picker.addEventListener("emoji-click", function (event) {
        targetInput.value = event.detail.unicode;
        CalendarManager.hideAllEmojiPickers();
      });

      popup.appendChild(picker);
      document.body.appendChild(popup);
    }

    var buttonRect = button.getBoundingClientRect();
    popup.style.position = "fixed";
    popup.style.top = buttonRect.bottom + 5 + "px";
    popup.style.left = buttonRect.left + "px";
    popup.classList.add("show");
  },

  hideAllEmojiPickers: function () {
    var popups = document.querySelectorAll(".emoji-picker-popup");
    for (var i = 0; i < popups.length; i++) {
      popups[i].classList.remove("show");
    }
  },

  // Error handling methods
  showCalendarSetsError: function () {
    var loadingEl = document.getElementById("calendar-sets-loading");
    var listEl = document.getElementById("calendar-sets-list");
    var emptyEl = document.getElementById("calendar-sets-empty");

    if (loadingEl) loadingEl.style.display = "none";
    if (listEl) listEl.style.display = "none";
    if (emptyEl) {
      emptyEl.innerHTML =
        "Failed to load calendar sets. Please try refreshing the page.";
      emptyEl.style.display = "block";
    }
  },

  showCalendarSourcesError: function () {
    var loadingEl = document.getElementById("calendar-sources-loading");
    var listEl = document.getElementById("calendar-sources-list");
    var emptyEl = document.getElementById("calendar-sources-empty");

    if (loadingEl) loadingEl.style.display = "none";
    if (listEl) listEl.style.display = "none";
    if (emptyEl) {
      emptyEl.innerHTML =
        "Failed to load calendar sources. Please try refreshing the page.";
      emptyEl.style.display = "block";
    }
  },

  // Calendar Source Selection Methods
  populateCalendarSourcesSelection: function (selectedSources) {
    var selectionEl = document.getElementById("calendar-sources-selection");
    if (!selectionEl) return;

    selectedSources = selectedSources || [];
    var selectedIds = selectedSources.map(function (source) {
      return source.id;
    });

    if (this.calendarSources.length === 0) {
      selectionEl.innerHTML =
        '<div class="empty-text">No calendar sources available. <a href="#" onclick="CalendarManager.showAddSourceModal(\'ical\')">Add one first</a>.</div>';
      return;
    }

    var html = "";
    for (var i = 0; i < this.calendarSources.length; i++) {
      var source = this.calendarSources[i];
      var isSelected = selectedIds.indexOf(source.id) !== -1;

      html +=
        '<div class="checkbox-item">' +
        '<label class="checkbox-label">' +
        '<input type="checkbox" name="calendar_sources[]" value="' +
        source.id +
        '" ' +
        (isSelected ? "checked" : "") +
        ">" +
        '<span class="checkbox-content">' +
        '<span class="source-emoji">' +
        this.escapeHtml(source.emoji || "🔗") +
        "</span>" +
        '<span class="source-name">' +
        this.escapeHtml(source.name) +
        "</span>" +
        '<span class="source-type">(' +
        this.escapeHtml(source.type) +
        ")</span>" +
        "</span>" +
        "</label>" +
        "</div>";
    }

    selectionEl.innerHTML = html;
  },

  getSelectedCalendarSources: function () {
    var checkboxes = document.querySelectorAll(
      'input[name="calendar_sources[]"]:checked',
    );
    var selectedIds = [];

    for (var i = 0; i < checkboxes.length; i++) {
      selectedIds.push(parseInt(checkboxes[i].value));
    }

    return selectedIds;
  },

  // Utility methods
  apiRequest: function (url, method, data) {
    method = method || "GET";

    var options = {
      method: method,
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": document
          .querySelector('meta[name="csrf-token"]')
          .getAttribute("content"),
      },
    };

    if (data) {
      options.body = JSON.stringify(data);
    }

    return fetch(url, options).then(function (response) {
      if (!response.ok) {
        var errorMessage = "Request failed with status " + response.status;
        return response.json().then(
          function (errorData) {
            if (errorData.message) {
              errorMessage = errorData.message;
            } else if (errorData.errors) {
              errorMessage = Object.values(errorData.errors).flat().join(", ");
            }
            throw new Error(errorMessage);
          },
          function () {
            throw new Error(errorMessage);
          },
        );
      }
      return response.json();
    });
  },

  escapeHtml: function (text) {
    if (!text) return "";
    var div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  },

  // Find Google calendar data by ID
  findGoogleCalendarById: function (calendarId) {
    for (var i = 0; i < this.googleCalendars.length; i++) {
      if (this.googleCalendars[i].id === calendarId) {
        return this.googleCalendars[i];
      }
    }
    return null;
  },

  // Convert RGB color to hex format
  rgbToHex: function (color) {
    if (!color) return "#3788d8";

    // If already hex, return as-is
    if (color.startsWith("#")) {
      return color;
    }

    // Handle rgb() format
    var rgbMatch = color.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
    if (rgbMatch) {
      var r = parseInt(rgbMatch[1], 10);
      var g = parseInt(rgbMatch[2], 10);
      var b = parseInt(rgbMatch[3], 10);
      return (
        "#" +
        this.componentToHex(r) +
        this.componentToHex(g) +
        this.componentToHex(b)
      );
    }

    // Default fallback
    return "#3788d8";
  },

  // Convert color component to hex
  componentToHex: function (c) {
    var hex = c.toString(16);
    return hex.length === 1 ? "0" + hex : hex;
  },
};

// Make CalendarManager available globally
window.CalendarManager = CalendarManager;
