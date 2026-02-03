/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint devel: true */

/**
 * Calendar management functions for fetching and processing calendar data
 */

// biome-ignore-start lint/correctness/noUnusedVariables: DocketCalendar is used globally
var DocketCalendar = {
  // biome-ignore-end lint/correctness/noUnusedVariables: DocketCalendar is used globally
  /**
   * Setup and refresh calendar data
   */
  setup: function () {
    var twoWeeks, _name, _cal;

    twoWeeks = DateUtils.addDays(new Date(), 30);

    // Fetch JSON calendar data
    fetch(
      "/all-calendars?end=" +
        DateUtils.formatDate(twoWeeks, "YYYY-MM-DD") +
        "&calendar_set=" +
        DocketConfig.constants.CALENDAR_SET,
    )
      .then(function (response) {
        if (!response.ok) throw new Error("HTTP " + response.status);
        return response.json();
      })
      .then(function (data) {
        // Check if response contains an error (authentication failures)
        if (data.error) {
          throw new Error(data.error);
        }
        return data;
      })
      .then(this.updateCallback.bind(this))
      .catch(function (error) {
        console.error("Failed to fetch calendar data:", error);

        // Check if this is an authentication error
        if (
          error.message.includes("authentication") ||
          error.message.includes("auth") ||
          error.message.includes("token")
        ) {
          NotificationUtils.error(
            "🔐 Google Calendar authentication expired. Click the settings icon to re-authenticate.",
            8000,
          );
        } else {
          NotificationUtils.error(
            "📅 Failed to load calendar events: " + error.message,
            6000,
          );
        }

        // Set empty calendar data so UI doesn't show stale data
        DocketConfig.allEvents.json_cals = {};
        DocketEvents.updateNextUp();
      });

    // Fetch iCal calendar data
    if (DocketConfig.constants.ICAL_CALENDARS) {
      var keys = Object.keys(DocketConfig.constants.ICAL_CALENDARS);
      for (var i = 0; i < keys.length; i++) {
        var name = keys[i];
        var cal = DocketConfig.constants.ICAL_CALENDARS[name];
        DocketCalendar.updateIcal(
          cal.proxy_url,
          new Date(),
          twoWeeks,
          "GMT",
          name,
          DocketEvents.updateNextUp,
        );
      }
    }
  },

  /**
   * Callback for JSON calendar updates
   * @param {Object} data - Calendar data
   */
  updateCallback: function (data, _info, _third) {
    DocketConfig.allEvents.json_cals = data;
    DocketEvents.updateNextUp();
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
  updateIcal: function (calendarUrl, start, end, timezone, name, callback) {
    NotificationUtils.debug(
      "Updating " +
        calendarUrl +
        " from " +
        start +
        " to " +
        end +
        " in " +
        timezone +
        " as " +
        name,
    );

    fetch(calendarUrl)
      .then(function (response) {
        if (!response.ok)
          throw new Error(
            "HTTP " + response.status + ": " + response.statusText,
          );
        return response.text();
      })
      .then(function (data) {
        var jcalData,
          comp,
          eventComps,
          tzid,
          tz,
          localTimeZone,
          msPerDay,
          approxDaysInMonth,
          endRange,
          rangeStart,
          rangeEnd,
          allDayMinutes,
          events,
          index,
          item,
          event,
          summary,
          skipEvent,
          regex,
          duration;

        try {
          jcalData = ICAL.parse(data);
          comp = new ICAL.Component(jcalData);
          eventComps = comp.getAllSubcomponents("vevent");
        } catch (_error) {
          NotificationUtils.warning(`Couldn't parse calendar: ${name}`);
          console.warn(`Couldn't Parse ${calendarUrl}`);
          return;
        }

        // Register timezones
        if (comp.getFirstSubcomponent("vtimezone")) {
          for (const tzComponent of comp.getAllSubcomponents("vtimezone")) {
            tzid = tzComponent.getFirstPropertyValue("tzid");
            NotificationUtils.debug(`Registering Timezone: ${tzid}`);

            tz = new ICAL.Timezone({
              tzid: tzid,
              component: tzComponent,
            });

            if (!ICAL.TimezoneService.has(tz.tzid)) {
              ICAL.TimezoneService.register(tz.tzid, tz);
            }
          }
        }

        comp = ICAL.helpers.updateTimezones(comp);
        localTimeZone = ICAL.Timezone.utcTimezone;
        msPerDay = 86400000;
        approxDaysInMonth = 30;
        endRange = new Date(Date.now() + msPerDay * approxDaysInMonth);
        rangeStart = ICAL.Time.fromJSDate(start);
        rangeEnd = ICAL.Time.fromJSDate(endRange);
        allDayMinutes = 1440;
        events = [];

        // Process events
        for (index = 0; index < eventComps.length; index++) {
          item = eventComps[index];
          event = new ICAL.Event(item);
          summary = item.getFirstPropertyValue("summary");

          NotificationUtils.debug(`Event: ${summary}`); // Skip private events
          if (item.getFirstPropertyValue("class") === "PRIVATE") {
            NotificationUtils.debug("Skipped: Private");
            continue;
          }

          // Strike through cancelled events (like Google calendar declined events)
          if (item.getFirstPropertyValue("status") === "CANCELLED") {
            NotificationUtils.debug("Marked as cancelled");
            summary = "<strike>" + summary + "</strike>";
          }

          // Skip recurrence exceptions
          if (event.isRecurrenceException()) {
            NotificationUtils.debug("Skipped: Exception");
            continue;
          }

          // Skip filtered events
          if (
            DocketConfig.constants.FILTER_OUT_LIST &&
            DocketConfig.constants.FILTER_OUT_LIST.indexOf(summary) !== -1
          ) {
            NotificationUtils.debug("Skipped: Filtered");
            continue;
          }

          if (DocketConfig.constants.FILTER_OUT_REGEXES) {
            skipEvent = false;
            for (const regexString of DocketConfig.constants
              .FILTER_OUT_REGEXES) {
              regex = new RegExp(regexString);
              if (regex.test(summary)) {
                NotificationUtils.debug(
                  `Skipped: Filtered by regex ${regexString}`,
                );
                skipEvent = true;
                break;
              }
            }
            if (skipEvent) continue;
          }

          // Skip events with / prefix
          if (summary && summary[0] === "/") {
            NotificationUtils.debug("Skipped: Filtered for / prefix");
            continue;
          }

          duration = event.duration;

          if (event.isRecurring()) {
            DocketCalendar.processRecurringEvent(
              item,
              event,
              duration,
              rangeStart,
              rangeEnd,
              localTimeZone,
              allDayMinutes,
              name,
              events,
            );
          } else {
            DocketCalendar.processSingleEvent(
              item,
              allDayMinutes,
              name,
              events,
            );
          }
          NotificationUtils.debug(`/Event: ${summary}`);
        }

        DocketConfig.allEvents[calendarUrl] = events;
        DocketEvents.updateNextUp();
        callback(events);
      })
      .catch((error) => {
        var errortext;

        errortext = error.message;
        NotificationUtils.error(
          `Failed to load calendar: ${name} - ${errortext}`,
        );
      });
  },

  /**
   * Process a recurring event
   */
  processRecurringEvent: (
    item,
    _event,
    duration,
    rangeStart,
    rangeEnd,
    localTimeZone,
    allDayMinutes,
    name,
    events,
  ) => {
    var expand, next, end, minutesLength, title, allDay;

    expand = new ICAL.RecurExpansion({
      component: item,
      dtstart: item.getFirstPropertyValue("dtstart"),
    });

    next = true;

    while (next) {
      next = expand.next();
      if (!next) break;

      next = next.convertToZone(localTimeZone);

      if (next.compare(rangeStart) < 0) {
        NotificationUtils.debug(`>> Too early ${rangeStart.toString()}`);
        continue;
      } else if (next.compare(rangeEnd) > 0) {
        NotificationUtils.debug(`>> Too late ${rangeEnd.toString()}`);
        break;
      }

      NotificationUtils.debug(`Repeating ${next.toString()}`);

      end = next.clone();
      end.addDuration(duration);
      minutesLength = duration.toSeconds() / 60;
      title = item.getFirstPropertyValue("summary");
      allDay = DocketCalendar.determineAllDay(
        item,
        minutesLength,
        allDayMinutes,
        title,
      );

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
  processSingleEvent: (item, allDayMinutes, name, events) => {
    var dtstart, dtend, minutesLength, eventTitle, allDay;

    dtstart = item.getFirstPropertyValue("dtstart").toJSDate();
    dtend = item.getFirstPropertyValue("dtend").toJSDate();
    minutesLength = (dtend - dtstart) / (1000 * 60);
    eventTitle = item.getFirstPropertyValue("summary");
    allDay = minutesLength >= allDayMinutes;

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
  determineAllDay: (item, minutesLength, allDayMinutes, title) => {
    if (item.getFirstPropertyValue("x-microsoft-cdo-alldayevent") === "TRUE") {
      NotificationUtils.debug(
        `Setting all day for: ${title} from Microsoft Calendar flag`,
      );
      return true;
    } else if (item.getFirstPropertyValue("x-apple-allday") === "TRUE") {
      NotificationUtils.debug(
        `Setting all day for: ${title} from Apple Calendar flag`,
      );
      return true;
    } else if (minutesLength >= allDayMinutes) {
      NotificationUtils.warning(
        `All-day flag not found for long event: ${title}`,
      );
      return false;
    }
    return false;
  },
};

// Make DocketCalendar available globally
window.DocketCalendar = DocketCalendar;
