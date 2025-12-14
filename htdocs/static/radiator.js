/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint devel: true */

/**
 * Radiator Calendar Application
 * Main application object for managing calendar display and interactions
 */
var Radiator = {
  // Configuration
  config: {
    refreshIntervalMs: 1000,
    updateIntervalMs: 5000,
    hourlyIntervalMs: 1000 * 60 * 60,
    secondsPerRefresh: null // Will be set from RadiatorConfig.constants.SECONDS_PER_REFRESH
  },
  
  // Constants
  constants: {
    PROGRESS_THRESHOLD: 1.02,
    MILLISECONDS_PER_DAY: 86400000,
    DAYS_PER_MONTH: 30, // Approximate for range calculations
    MINUTES_PER_DAY: 1440
  },

  /**
   * Initialize the application
   */
  init: function() {
    this.config.secondsPerRefresh = RadiatorConfig.constants.SECONDS_PER_REFRESH || 1800;

    // Initialize UI
    this.ui.updateDateTime();
    this.ui.updateTheme();

    // Setup calendar
    this.calendar.setup();

    // Setup event handlers
    this.setupEventHandlers();
    
    // Start timers
    this.startTimers();
  },

  /**
   * Setup event handlers
   */
  setupEventHandlers: function() {
    var datetimeEl = document.getElementById("datetime");
    if (datetimeEl) {
      datetimeEl.addEventListener("click", function () {
        window.location.reload(true);
      });
    }
  },

  /**
   * Start application timers
   */
  startTimers: function() {
    var self = this;
    
    // Store interval IDs for cleanup
    this.intervals = [];

    // Hourly timer for potential maintenance tasks
    this.intervals.push(window.setInterval(function () {
      debug("Hourly maintenance check");
      // Could be used for cache cleanup, timezone updates, etc.
    }, this.config.hourlyIntervalMs));

    // Regular updates (5 seconds)
    this.intervals.push(window.setInterval(function () {
      self.ui.updateDateTime();
      self.ui.updateUntil();
      self.ui.updateTheme();
    }, this.config.updateIntervalMs));

    // Refresh timer with circular progress
    this.intervals.push(window.setInterval(function () {
      if (self.circleProgress.trackPercent <= Radiator.constants.PROGRESS_THRESHOLD) {
        self.circleProgress.animate(self.circleProgress.trackPercent, "countdown");
        self.circleProgress.trackPercent += 1 / self.config.secondsPerRefresh;
      } else {
        debug("Refreshing");
        self.circleProgress.trackPercent = 0;
        self.calendar.setup();
      }
    }, this.config.refreshIntervalMs));
  },

  /**
   * Cleanup timers
   */
  cleanup: function() {
    if (this.intervals) {
      this.intervals.forEach(function(intervalId) {
        clearInterval(intervalId);
      });
      this.intervals = [];
    }
  }
};

/**
 * Circle progress animation functionality
 */
Radiator.circleProgress = {
  trackPercent: 0,
  x: 0,
  y: 0,
  radius: false,
  curPerc: 0,
  counterClockwise: false,
  circ: Math.PI * 2,
  quart: Math.PI / 2,

  /**
   * Initialize and draw the circle
   * @param {string} id - Canvas element ID
   */
  drawCircle: function (id) {
    var canvas = document.getElementById(id);
    if (!canvas) {
      NotificationUtils.warning('Canvas element not found: ' + id);
      return;
    }
    var context = canvas.getContext("2d");
    if (!context) {
      NotificationUtils.warning('Could not get 2d context for canvas: ' + id);
      return;
    }
    this.x = canvas.width / 2;
    this.y = canvas.height / 2;
    this.radius = 10;
    context.lineWidth = 3;
    this.endPercent = 85;
    this.curPerc = 0;
    context.strokeStyle = "#ad2323";
    this.animate(0, id);
  },

  /**
   * Animate the circle progress
   * @param {number} current - Current progress (0-1)
   * @param {string} id - Canvas element ID
   */
  animate: function (current, id) {
    var canvas = document.getElementById(id);
    var context = canvas.getContext("2d");

    context.clearRect(0, 0, canvas.width, canvas.height);
    context.beginPath();
    context.arc(
      this.x,
      this.y,
      this.radius,
      -this.quart,
      this.circ * current - this.quart,
      false
    );
    context.stroke();
  }
};

/**
 * UI update functions
 */
Radiator.ui = {
  /**
   * Update date and time display
   */
  updateDateTime: function() {
    var now = new Date();
    var time = formatTime(now);
    var strToday = formatDateWithOrdinal(now);

    var dateEl = document.getElementById("date");
    var timeEl = document.getElementById("time");
    var datetimeEl = document.getElementById("datetime");
    
    if (dateEl) dateEl.innerHTML = strToday;
    if (timeEl) timeEl.innerHTML = time;
    if (datetimeEl) {
      datetimeEl.innerHTML =
        '<div class="dt_time">' + time + '</div>' +
        '<div class="dt_date">' + strToday + "</div>";
    }
  },

  /**
   * Update relative time displays for today's events
   */
  updateUntil: function() {
    var mnow = new Date();
    var todayEvents = document.querySelectorAll(".todayEvent");

    for (var i = 0; i < todayEvents.length; i++) {
      var element = todayEvents[i];
      var text = "";
      var thisEvent = element;
      var thisends = new Date(thisEvent.getAttribute("eventends"));
      var thisstarts = new Date(thisEvent.getAttribute("eventstarts"));
      
      if (mnow > thisends) {
        thisEvent.style.display = 'none';
      } else if (thisstarts > mnow) {
        var duration = humanizeDuration(Math.abs(thisends - thisstarts));
        text = fromNow(thisstarts) + " for " + duration;
      } else if (thisends > mnow) {
        text = "ends " + fromNow(thisends);
      }

      var untilElement = thisEvent.querySelector(".until");
      if (untilElement) {
        untilElement.innerHTML = "(" + text + ")";
      }
    }
    
    return todayEvents;
  },

  /**
   * Update day/night theme based on sun position
   */
  updateTheme: function() {
    var timeOfDay = this.getTimeOfDay();
    var body = document.body;
    
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
  getTimeOfDay: function() {
    try {
      var now = new Date();
      var sunstate = SunCalc.getTimes(now, RadiatorConfig.constants.LATITUDE, RadiatorConfig.constants.LONGITUDE);
      
      if (now > sunstate.sunset || now < sunstate.sunrise) {
        return "night";
      } else {
        return "day";
      }
    } catch (error) {
      NotificationUtils.warning('Error calculating day/night theme, using day mode');
      console.warn('Error calculating time of day, defaulting to day:', error);
      return "day";
    }
  }
};

/**
 * Calendar management functions
 */
Radiator.calendar = {
  /**
   * Setup and refresh calendar data
   */
  setup: function() {
    var twoWeeks = addDays(new Date(), 30);

    // Fetch JSON calendar data
    fetch("/all-calendars.php?end=" + formatDate(twoWeeks, "YYYY-MM-DD") + 
          "&version=" + RadiatorConfig.constants.VERSION)
      .then(function(response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
      })
      .then(this.updateCallback.bind(this))
      .catch(function(error) {
        console.error('Failed to fetch calendar data:', error);
      });

    // Fetch iCal calendar data
    if (RadiatorConfig.constants.ICAL_CALENDARS) {
      for (const [name, cal] of Object.entries(RadiatorConfig.constants.ICAL_CALENDARS)) {
        this.updateIcal(cal.proxy_url, new Date(), twoWeeks, "GMT", name, Radiator.events.updateNextUp);
      }
    }
  },

  /**
   * Callback for JSON calendar updates
   * @param {Object} data - Calendar data
   */
  updateCallback: function(data, info, third) {
    RadiatorConfig.allEvents.json_cals = data;
    Radiator.events.updateNextUp();
  },

  /**
   * Update iCal calendar data
   * @param {string} calendarUrl - URL to fetch calendar from
   * @param {Date} start - Start date range
   * @param {Date} end - End date range  
   * @param {string} timezone - Timezone
   * @param {string} name - Calendar name
   * @param {Function} callback - Success callback
   */
  updateIcal: function(calendarUrl, start, end, timezone, name, callback) {
    debug("Updating " + calendarUrl + " from " + start + " to " + end + 
          " in " + timezone + " as " + name);

    fetch(calendarUrl)
      .then(function(response) {
        if (!response.ok) throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        return response.text();
      })
      .then(function (data) {
        try {
          var jcalData = ICAL.parse(data);
          var comp = new ICAL.Component(jcalData);
          var eventComps = comp.getAllSubcomponents("vevent");
        } catch (error) {
          NotificationUtils.warning("Couldn't parse calendar: " + name);
          console.warn("Couldn't Parse " + calendarUrl);
          return;
        }

        // Register timezones
        if (comp.getFirstSubcomponent("vtimezone")) {
          for (const tzComponent of comp.getAllSubcomponents("vtimezone")) {
            var tzid = tzComponent.getFirstPropertyValue("tzid");
            debug("Registering Timezone: " + tzid);
            
            var tz = new ICAL.Timezone({
              tzid: tzid,
              component: tzComponent,
            });

            if (!ICAL.TimezoneService.has(tz.tzid)) {
              ICAL.TimezoneService.register(tz.tzid, tz);
            }
          }
        }
        
        comp = ICAL.helpers.updateTimezones(comp);
        var localTimeZone = ICAL.Timezone.utcTimezone;
        var msPerDay = Radiator.constants.MILLISECONDS_PER_DAY;
        var approxDaysInMonth = Radiator.constants.DAYS_PER_MONTH;
        var endRange = new Date(new Date().getTime() + (msPerDay * approxDaysInMonth));
        var rangeStart = ICAL.Time.fromJSDate(start);
        var rangeEnd = ICAL.Time.fromJSDate(endRange);
        var allDayMinutes = Radiator.constants.MINUTES_PER_DAY;
        var events = [];

        // Process events
        for (var index = 0; index < eventComps.length; index++) {
          var item = eventComps[index];
          var event = new ICAL.Event(item);
          var summary = item.getFirstPropertyValue("summary");
          
          debug("Event: " + summary);

          // Skip private events
          if (item.getFirstPropertyValue("class") === "PRIVATE") {
            debug("Skipped: Private");
            return;
          }
          
          // Skip recurrence exceptions
          if (event.isRecurrenceException()) {
            debug("Skipped: Exception");
            return;
          }
          
          // Skip filtered events
          if (RadiatorConfig.constants.FILTER_OUT_LIST && RadiatorConfig.constants.FILTER_OUT_LIST.indexOf(summary) !== -1) {
            debug("Skipped: Filtered");
            return;
          }

          if (RadiatorConfig.constants.FILTER_OUT_REGEXES) {
            for (const regexString of RadiatorConfig.constants.FILTER_OUT_REGEXES) {
              var regex = new RegExp(regexString);
              if (regex.test(summary)) {
                debug("Skipped: Filtered by regex " + regexString);
                return;
              }
            }
          }
          
          // Skip events with / prefix
          if (summary && summary[0] === "/") {
            debug("Skipped: Filtered for / prefix");
            return;
          }

          var duration = event.duration;

          if (event.isRecurring()) {
            Radiator.calendar.processRecurringEvent(item, event, duration, rangeStart, 
              rangeEnd, localTimeZone, allDayMinutes, name, events);
          } else {
            Radiator.calendar.processSingleEvent(item, allDayMinutes, name, events);
          }

          debug("/Event: " + summary);
        }

        RadiatorConfig.allEvents[calendarUrl] = events;
        Radiator.events.updateNextUp();
        callback(events);
      })
      .catch(function (error) {
        var errortext = error.message;
        
        NotificationUtils.error("Failed to load calendar: " + name + " - " + errortext);
      });
  },

  /**
   * Process a recurring event
   */
  processRecurringEvent: function(item, event, duration, rangeStart, rangeEnd, 
                                 localTimeZone, allDayMinutes, name, events) {
    var expand = new ICAL.RecurExpansion({
      component: item,
      dtstart: item.getFirstPropertyValue("dtstart"),
    });

    var next = true;

    while (next) {
      next = expand.next();
      if (!next) break;

      next = next.convertToZone(localTimeZone);

      if (next.compare(rangeStart) < 0) {
        debug(">> Too early " + rangeStart.toString());
        continue;
      } else if (next.compare(rangeEnd) > 0) {
        debug(">> Too late " + rangeEnd.toString());
        break;
      }

      debug("Repeating " + next.toString());

      var end = next.clone();
      end.addDuration(duration);
      var minutesLength = duration.toSeconds() / 60;
      var title = item.getFirstPropertyValue("summary");
      var allDay = this.determineAllDay(item, minutesLength, allDayMinutes, title);

      events.push({
        title: title,
        start: next.toJSDate(),
        end: end.toJSDate(),
        location: item.getFirstPropertyValue("location"),
        calendars: [name],
        allDay: allDay,
      });
    }
  },

  /**
   * Process a single (non-recurring) event
   */
  processSingleEvent: function(item, allDayMinutes, name, events) {
    var dtstart = item.getFirstPropertyValue("dtstart").toJSDate();
    var dtend = item.getFirstPropertyValue("dtend").toJSDate();
    var minutesLength = (dtend - dtstart) / (1000 * 60);
    var eventTitle = item.getFirstPropertyValue("summary");
    var allDay = minutesLength >= allDayMinutes;

    events.push({
      title: eventTitle,
      start: dtstart,
      end: dtend,
      location: item.getFirstPropertyValue("location"),
      calendars: [name],
      allDay: allDay,
    });
  },

  /**
   * Determine if an event should be marked as all-day
   */
  determineAllDay: function(item, minutesLength, allDayMinutes, title) {
    if (item.getFirstPropertyValue("x-microsoft-cdo-alldayevent") === "TRUE") {
      debug("Setting all day for: " + title + " from Microsoft Calendar flag");
      return true;
    } else if (item.getFirstPropertyValue("x-apple-allday") === "TRUE") {
      debug("Setting all day for: " + title + " from Apple Calendar flag");
      return true;
    } else if (minutesLength >= allDayMinutes) {
      NotificationUtils.warning("All-day flag not found for long event: " + title);
      return false;
    }
    return false;
  }
};

/**
 * Error handling
 */
Radiator.handleError = function(error) {
  console.log("--- Error Follows:");
  console.log(error);
};

/**
 * Event processing and display functions
 */
Radiator.events = {
  /**
   * Update the next upcoming events display
   */
  updateNextUp: function() {
    var now = new Date();
    var nowF = now.toISOString().split('T')[0];
    var days = {};
    days[nowF] = { allday: [], events: [] };

    // Combine all events from different sources
    var events = [];
    for (const [set, setEvents] of Object.entries(RadiatorConfig.allEvents)) {
      events = events.concat(setEvents);
    }

    events.sort(dateSort);

    // Create day containers for the date range
    var maxDate = new Date(findFurthestDate(events));
    var thisDay = new Date();
    while (thisDay < maxDate) {
      thisDay.setDate(thisDay.getDate() + 1);
      var thisDayF = thisDay.toISOString().split('T')[0];
      if (!days[thisDayF]) {
        days[thisDayF] = { allday: [], events: [] };
      }
    }

    // Process each event
    for (var i = 0; i < events.length; i++) {
      var thisEvent = events[i];
      var end = new Date(thisEvent.end);
      var start = new Date(thisEvent.start);

      // Skip past events
      if (end < now) {
        continue;
      }

      var startF = start.toISOString().split('T')[0];

      // Handle events that started before today
      if (!days[startF] && end > now) {
        startF = now.toISOString().split('T')[0];
        var endOfDay = new Date(now);
        endOfDay.setHours(23, 59, 59, 999);
        
        if (end >= endOfDay) {
          debug("Adjusting event to all day: " + thisEvent.title + " as it started before today");
          thisEvent.allDay = true;
        } else {
          start = new Date(endOfDay);
        }
      }

      // Detect midnight-to-midnight events
      if (start.getHours() === 0 && start.getMinutes() === 0 &&
          end.getHours() === 0 && end.getMinutes() === 0 &&
          thisEvent.allDay !== true) {
        debug("Setting all day for: " + thisEvent.title + " as it is midnight to midnight");
        debug(start.toISOString() + " to " + end.toISOString());
        thisEvent.allDay = true;
      }

      if (thisEvent.allDay) {
        Radiator.events.processAllDayEvent(thisEvent, start, end, now, days);
      } else if (days[startF]) {
        debug("Adding event: " + thisEvent.title + " to " + startF);
        days[startF].events.push(thisEvent);
      } else {
        debug("Skipping event: " + thisEvent.title + 
              " as it is not in the next two weeks (" + startF + ")");
      }
    }

    Radiator.events.renderEventsList(days);
    Radiator.events.setupEventClickHandlers();
    
    // Parse emojis and update relative times
    twemoji.parse(document.body);
    Radiator.ui.updateUntil();
  },

  /**
   * Process an all-day event across multiple days
   */
  processAllDayEvent: function(thisEvent, start, end, now, days) {
    var showedStarted = false;
    
    if (start < now) {
      start = new Date();
    }
    
    var startF = start.toISOString().split('T')[0];
    var durationHours = (end - start) / (1000 * 60 * 60) - 24;
    
    if (days[startF]) {
      showedStarted = true;
      var startedToday = true;
      var xEvent = Object.assign({}, thisEvent);
      
      if (durationHours > 0) {
        var xEnd = subtractMinutes(end, 1);
        xEvent.title = xEvent.title + 
          "<span class='until'>(until " + calendarFormat(xEnd) + ")</span>";
      }
      
      days[startF].allday.push(xEvent);
    }

    // Handle multi-day events
    while (durationHours > 0) {
      var startedToday = false;
      start = addDays(start, 1);
      startF = formatDate(start, 'YYYY-MM-DD');
      
      if (days[startF] && !showedStarted) {
        days[startF].allday.push(thisEvent);
        showedStarted = true;
        startedToday = true;
      }
      
      durationHours -= 24;
      
      if (durationHours < 1 && !startedToday) {
        var xEvent = Object.assign({}, thisEvent);
        xEvent.title = xEvent.title + " ends";
        days[startF].allday.push(xEvent);
      }
    }
  },

  /**
   * Render the events list HTML
   */
  renderEventsList: function(days) {
    var output = "<dl>";
    
    for (const [date, data] of Object.entries(days)) {
      var day = new Date(date);
      var dayTitle = Radiator.events.getDayTitle(day);
      
      debug("Start Day " + formatDate(day, "YYYY-MM-DD"));
      debug(data);
      
      output += "<dt>" + dayTitle + ": ";
      
      // Merge duplicate all-day events
      Radiator.events.mergeAllDayEvents(data);
      
      // Render all-day events
      output += Radiator.events.renderAllDayEvents(data.allday);
      output += "</dt>";
      
      // Render timed events
      output += Radiator.events.renderTimedEvents(data.events);
    }
    
    output += "</dl>";
    var nextUpEl = document.getElementById("nextUp");
    if (nextUpEl) {
      nextUpEl.innerHTML = output;
    }
  },

  /**
   * Get display title for a day
   */
  getDayTitle: function(day) {
    var nowDayOfYear = getDayOfYear(new Date());
    var dayDayOfYear = getDayOfYear(day);
    
    if (nowDayOfYear === dayDayOfYear) {
      return "Today";
    } else if (nowDayOfYear + 1 === dayDayOfYear) {
      return "Tomorrow";
    } else {
      var title = formatDate(day, "ddd D");
      title += "<sup>" + dateOrdinal(day.getDate()) + "</sup>";
      return title;
    }
  },

  /**
   * Merge duplicate all-day events by title
   */
  mergeAllDayEvents: function(data) {
    var mergedAllday = [];
    
    for (var i = 0; i < data.allday.length; i++) {
      var currentEvent = data.allday[i];
      var existingEvent = mergedAllday.find(
        function(event) { return event.title === currentEvent.title; }
      );
      
      if (existingEvent) {
        existingEvent.calendars.push(...currentEvent.calendars);
      } else {
        mergedAllday.push(currentEvent);
      }
    }
    
    data.allday = mergedAllday;
  },

  /**
   * Render all-day events HTML
   */
  renderAllDayEvents: function(alldayEvents) {
    if (alldayEvents.length === 0) {
      debug("No allday events");
      return "";
    }

    var things = [];
    
    for (var i = 0; i < alldayEvents.length; i++) {
      var allday = alldayEvents[i];
      var classes = Radiator.events.getEventClasses(allday);
      
      things.push('<span class="' + classes + '" data="' + 
                 encodeURI(JSON.stringify(allday)) + '">' + 
                 allday.title + "</span>");
    }

    var output = "";
    if (things.length === 1) {
      output = things[0];
    } else if (things.length > 1) {
      output = things.slice(0, -1).join(", ") + " & " + things.pop();
    }

    return output ? '<span class="day-events">' + output + '</span>' : "";
  },

  /**
   * Render timed events HTML
   */
  renderTimedEvents: function(events) {
    var output = "";
    
    for (var i = 0; i < events.length; i++) {
      var thisEvent = events[i];
      debug("Event: " + thisEvent.title);

      var starts = new Date(thisEvent.start);
      var ends = new Date(thisEvent.end);
      var classes = "event " + Radiator.events.getEventClasses(thisEvent);

      if (getDayOfYear(new Date()) === getDayOfYear(starts)) {
        classes += " todayEvent";
      }

      var until = "(for " + humanizeDuration(Math.abs(ends - starts)) + ")";
      
      output += '<dd class="' + classes + '" ' +
                'eventstarts="' + starts.toISOString() + '" ' +
                'eventends="' + ends.toISOString() + '" ' +
                'data="' + encodeURI(JSON.stringify(thisEvent)) + '">' +
                '<span class="event_dt">' + formatDate(starts, "HH:mm") + "</span> " +
                '<span class="event_title">' + thisEvent.title + "</span> " +
                '<span class="until">' + until + "</span>" +
                "</dd>";
    }
    
    return output;
  },

  /**
   * Get CSS classes for an event based on its calendars
   */
  getEventClasses: function(event) {
    var classes = "";
    
    if (event.calendars && event.calendars.length > 0) {
      if (event.calendars.length > 1) {
        classes += "txtcal-stripy ";
      }
      classes += "txtcal-" + event.calendars.join("-");
    }
    
    return classes;
  },

  /**
   * Setup click handlers for events
   */
  setupEventClickHandlers: function() {
    // Remove existing handlers to prevent duplicates
    var existingElements = document.querySelectorAll("#nextUp dd, #nextUp span.day-events span");
    for (var j = 0; j < existingElements.length; j++) {
      existingElements[j].removeEventListener("click", this._eventClickHandler);
    }
    
    // Store handler reference for removal
    if (!this._eventClickHandler) {
      this._eventClickHandler = function (event) {
        console.log("Clicked on event:" + event.target.innerText);
        try {
          console.log(JSON.parse(decodeURI(this.getAttribute("data"))));
        } catch (error) {
          console.error('Error parsing event data:', error);
        }
      };
    }
    
    var elements = document.querySelectorAll("#nextUp dd, #nextUp span.day-events span");
    for (var i = 0; i < elements.length; i++) {
      elements[i].addEventListener("click", this._eventClickHandler);
    }
  }
};

// Initialize circle progress and start application
if (document.getElementById("countdown")) {
  Radiator.circleProgress.drawCircle("countdown");
}

/**
 * Initialize application when DOM is ready and required elements exist
 * Uses retry logic to ensure all necessary DOM elements are available
 */
function initWhenReady() {
  // Check if essential DOM elements exist
  var requiredElements = ['datetime', 'nextUp'];
  var allPresent = requiredElements.every(function(id) {
    return document.getElementById(id) !== null;
  });
  
  if (allPresent) {
    Radiator.init();
  } else {
    // Retry in a short time if elements aren't ready
    debug('Required DOM elements not ready, retrying...');
    // Show warning if we've been retrying for too long
    if (!initWhenReady.retryCount) initWhenReady.retryCount = 0;
    initWhenReady.retryCount++;
    if (initWhenReady.retryCount > 100) { // After 1 second of retries
      NotificationUtils.warning('Some page elements are missing. Calendar may not work properly.', 5000);
      console.warn('Required DOM elements still not ready after 100 retries');
      return;
    }
    setTimeout(initWhenReady, 10);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initWhenReady);
} else {
  initWhenReady();
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
  Radiator.cleanup();
});
