/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint jquery: true */
/* jshint devel: true */

/**
 * Radiator Calendar Application
 * Main application object for managing calendar display and interactions
 */
var Radiator = {
  // Application state
  allEvents: {},
  
  // Configuration
  config: {
    refreshIntervalMs: 1000,
    updateIntervalMs: 5000,
    hourlyIntervalMs: 1000 * 60 * 60,
    secondsPerRefresh: null // Will be set from global SECONDS_PER_REFRESH
  },

  /**
   * Initialize the application
   */
  init: function() {
    this.config.secondsPerRefresh = window.SECONDS_PER_REFRESH || 1800;
    
    // Setup toastr
    toastr.options.closeDuration = 300;

    // Initialize UI
    this.ui.updateDateTime();
    this.ui.updateTheme();
    $("#nextUp").show();

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
    $("#datetime").on("click", function () {
      window.location.reload(true);
    });
  },

  /**
   * Start application timers
   */
  startTimers: function() {
    var self = this;

    // Hourly timer
    window.setInterval(function () {
      debug("On the hour");
    }, this.config.hourlyIntervalMs);

    // Regular updates (5 seconds)
    window.setInterval(function () {
      self.ui.updateDateTime();
      self.ui.updateUntil();
      self.ui.updateTheme();
    }, this.config.updateIntervalMs);

    // Refresh timer with circular progress
    window.setInterval(function () {
      if (self.circleProgress.trackPercent <= 1.02) {
        self.circleProgress.animate(self.circleProgress.trackPercent, "countdown");
        self.circleProgress.trackPercent += 1 / self.config.secondsPerRefresh;
      } else {
        debug("Refreshing");
        self.circleProgress.trackPercent = 0;
        self.calendar.setup();
      }
    }, this.config.refreshIntervalMs);
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
    var context = canvas.getContext("2d");
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

    $("#date").html(strToday);
    $("#time").html(time);
    $("#datetime").html(
      '<div class="dt_time">' + time + '</div>' +
      '<div class="dt_date">' + strToday + "</div>"
    );
  },

  /**
   * Update relative time displays for today's events
   */
  updateUntil: function() {
    var mnow = new Date();

    $(".todayEvent").each(function () {
      var text = "";
      var thisEvent = $(this);
      var thisends = new Date(thisEvent.attr("eventends"));
      var thisstarts = new Date(thisEvent.attr("eventstarts"));
      
      if (mnow > thisends) {
        thisEvent.hide();
      } else if (thisstarts > mnow) {
        var duration = humanizeDuration(Math.abs(thisends - thisstarts));
        text = fromNow(thisstarts) + " for " + duration;
      } else if (thisends > mnow) {
        text = "ends " + fromNow(thisends);
      }

      $(".until", thisEvent).html("(" + text + ")");
    });
    
    return $(".todayEvent");
  },

  /**
   * Update day/night theme based on sun position
   */
  updateTheme: function() {
    var timeOfDay = this.getTimeOfDay();
    var body = $("body");
    
    if (timeOfDay === "night" && !body.hasClass("nighttime")) {
      body.removeClass("daytime").addClass("nighttime");
    } else if (timeOfDay === "day" && !body.hasClass("daytime")) {
      body.removeClass("nighttime").addClass("daytime");
    }
  },

  /**
   * Determine if it's day or night based on sun position
   * @returns {string} "day" or "night"
   */
  getTimeOfDay: function() {
    var now = new Date();
    var sunstate = SunCalc.getTimes(now, window.LATITUDE, window.LONGITUDE);
    
    if (now > sunstate.sunset || now < sunstate.sunrise) {
      return "night";
    } else {
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
    $.get(
      "/all-calendars.php?end=" + formatDate(twoWeeks, "YYYY-MM-DD") + 
      "&version=" + window.VERSION,
      this.updateCallback
    );

    // Fetch iCal calendar data
    if (window.ICAL_CALENDARS) {
      for (const [name, cal] of Object.entries(window.ICAL_CALENDARS)) {
        this.updateIcal(cal.proxy_url, new Date(), twoWeeks, "GMT", name, Radiator.events.updateNextUp);
      }
    }
  },

  /**
   * Callback for JSON calendar updates
   * @param {Object} data - Calendar data
   */
  updateCallback: function(data, info, third) {
    Radiator.allEvents.json_cals = data;
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

    $.get(calendarUrl)
      .done(function (data) {
        try {
          var jcalData = ICAL.parse(data);
          var comp = new ICAL.Component(jcalData);
          var eventComps = comp.getAllSubcomponents("vevent");
        } catch (error) {
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
        var aDay = 86400000;
        var aMonth = aDay * 28;
        var endRange = new Date(new Date().getTime() + aMonth);
        var rangeStart = ICAL.Time.fromJSDate(start);
        var rangeEnd = ICAL.Time.fromJSDate(endRange);
        var allDayMinutes = 60 * 24;
        var events = [];

        // Process events
        $(eventComps).each(function (index, item) {
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
          if (window.filter_out_list && window.filter_out_list.indexOf(summary) !== -1) {
            debug("Skipped: Filtered");
            return;
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
        });

        Radiator.allEvents[calendarUrl] = events;
        Radiator.events.updateNextUp();
        callback(events);
      })
      .fail(function (error) {
        var errortext = error.statusText;
        if (error.responseJSON && error.responseJSON.message) {
          errortext = error.responseJSON.message;
        }
        
        toastr.error("Failed to load calendar: " + name + " - " + errortext);
        console.error("Failed to load calendar: " + name + " - " + errortext);
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
      console.warn("Allday flag not found for long event: " + title);
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
    for (const [set, setEvents] of Object.entries(Radiator.allEvents)) {
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
        this.processAllDayEvent(thisEvent, start, end, now, days);
      } else if (days[startF]) {
        debug("Adding event: " + thisEvent.title + " to " + startF);
        days[startF].events.push(thisEvent);
      } else {
        debug("Skipping event: " + thisEvent.title + 
              " as it is not in the next two weeks (" + startF + ")");
      }
    }

    this.renderEventsList(days);
    this.setupEventClickHandlers();
    
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
      var dayTitle = this.getDayTitle(day);
      
      debug("Start Day " + formatDate(day, "YYYY-MM-DD"));
      debug(data);
      
      output += "<dt>" + dayTitle + ": ";
      
      // Merge duplicate all-day events
      this.mergeAllDayEvents(data);
      
      // Render all-day events
      output += this.renderAllDayEvents(data.allday);
      output += "</dt>";
      
      // Render timed events
      output += this.renderTimedEvents(data.events);
    }
    
    output += "</dl>";
    $("#nextUp").html(output);
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
      var classes = this.getEventClasses(allday);
      
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
      var classes = "event " + this.getEventClasses(thisEvent);

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
    $("#nextUp dd, #nextUp span.day-events span").on("click", function (event) {
      console.log("Clicked on event:" + event.target.innerText);
      console.log(JSON.parse(decodeURI(this.getAttribute("data"))));
    });
  }
};

// Initialize circle progress and start application
Radiator.circleProgress.drawCircle("countdown");

// Startup when DOM is ready
$(function () {
  Radiator.init();
});
