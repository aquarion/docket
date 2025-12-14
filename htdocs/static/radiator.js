/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint jquery: true */
/* jshint devel: true */

circle = {
  track_circle_percent: 0,

  x: 0,
  y: 0,
  radius: false,
  curPerc: 0,
  counterClockwise: false,
  circ: Math.PI * 2,
  quart: Math.PI / 2,

  drawCircle: function (id) {
    canvas = document.getElementById(id);
    context = canvas.getContext("2d");
    circle.x = canvas.width / 2;
    circle.y = canvas.height / 2;
    circle.radius = 10;
    context.lineWidth = 3;
    circle.endPercent = 85;
    circle.curPerc = 0;

    context.strokeStyle = "#ad2323";
    //  context.shadowOffsetX = 3;
    //  context.shadowOffsetY = 3;
    //  context.shadowBlur = 4;
    //  context.shadowColor = '#656565';

    circle.animate(0, id);
  },

  animate: function (current, id) {
    canvas = document.getElementById(id);
    context = canvas.getContext("2d");

    context.clearRect(0, 0, canvas.width, canvas.height);
    context.beginPath();
    context.arc(
      circle.x,
      circle.y,
      circle.radius,
      -circle.quart,
      circle.circ * current - circle.quart,
      false
    );
    context.stroke();
  },
};

circle.drawCircle("countdown");

function dateOrdinal(d) {
  return 31 == d || 21 == d || 1 == d
    ? "st"
    : 22 == d || 2 == d
    ? "nd"
    : 23 == d || 3 == d
    ? "rd"
    : "th";
}

var handleError = function (error) {
  console.log("--- Error Follows:");
  console.log(error);
};

dateSort = function (a, b) {
  astart = new Date(a.start);
  bstart = new Date(b.start);

  if (astart == bstart) {
    return 0;
  } else if (astart > bstart) {
    return 1;
  } else {
    return -1;
  }
};

findFurthestDate = function (events) {
  max = new Date();
  var i = 0;
  for (; i < events.length; i++) {
    end = new Date(events[i].end);
    if (end > max) {
      max = end;
    }
  }
  return max;
};

calendarUpdateCallback = function (data, info, third) {
  allEvents.json_cals = data;
  updateNextUp();
};

updateNextUp = function () {
  var i = 0;

  now = moment();

  nowF = now.format("YYYY-MM-DD");

  days = {};

  days[nowF] = {
    allday: [],
    events: [],
  };

  events = [];

  for (const [set, set_events] of Object.entries(allEvents)) {
    events = events.concat(set_events);
  }

  events.sort(dateSort);

  maxDate = moment(findFurthestDate(events));

  thisDay = moment();
  while (thisDay < maxDate) {
    thisDay = thisDay.add(1, "d");

    thisDayF = thisDay.format("YYYY-MM-DD");

    if (!days[thisDayF]) {
      days[thisDayF] = {
        allday: [],
        events: [],
      };
    }
  }

  i = 0;

  for (; i < events.length; i++) {
    this_event = events[i];

    end = moment(this_event.end);
    start = moment(this_event.start);

    if (end < now) {
      //debug("Skipping event that has ended: " + this_event.title);
      continue;
    }

    startF = start.format("YYYY-MM-DD");

    if (!days[startF] && end > now) {
      startF = now.format("YYYY-MM-DD");
      if (end >= now.endOf("day")) {
        debug("Adjusting event to all day: " + this_event.title + " as it started before today");
        this_event.allDay = true;
      } else {
        start = now.endOf("day");
      }
    }

    if (
      start.hours() == 0 &&
      start.minutes() == 0 &&
      end.hours() == 0 &&
      end.minutes() == 0 &&
      this_event.allDay !== true
    ) {
      debug("Setting all day for: " + this_event.title + " as it is midnight to midnight");
      debug(start.format() + " to " + end.format());

      this_event.allDay = true;
    }

    if (this_event.allDay) {
      showed_started = false;
      if (start < now) {
        start = moment();
        startF = start.format("YYYY-MM-DD");
      }

      durationHours = (end - start) / (1000 * 60 * 60) - 24;
      if (days[startF]) {
        showed_started = true;
        started_today = true;
        const x_event = Object.assign({}, this_event);
        if (durationHours > 0) {
          x_end = end.subtract(1, "minutes");
          x_event.title =
            x_event.title +
            "<span class='until'>(until " +
            x_end.calendar(UNTIL_CALENDAR_FORMAT) +
            ")</span>";
        }
        days[startF].allday.push(x_event);
      }
      while (durationHours > 0) {
        started_today = false;
        start = start.add(1, "d");
        startF = start.format("YYYY-MM-DD");
        if (days[startF] && !showed_started) {
          days[startF].allday.push(this_event);
          showed_started = true;
          started_today = true;
        }
        durationHours -= 24;
        if (durationHours < 1 && !started_today) {
          const x_event = Object.assign({}, this_event);
          x_event.title = x_event.title + " ends";
          days[startF].allday.push(x_event);
        }
      }
    } else if (days[startF]) {
      debug("Adding event: " + this_event.title + " to " + startF);
      days[startF].events.push(this_event);
    } else {
      debug(
        "Skipping event: " +
          this_event.title +
          " as it is not in the next two weeks (" +
          startF +
          ")"
      );
    }
  }

  output = "<dl>";
  for (const [date, data] of Object.entries(days)) {
    day = moment(date);

    if (moment().dayOfYear() == day.dayOfYear()) {
      dayTitle = "Today";
    } else if (moment().dayOfYear() + 1 == day.dayOfYear()) {
      dayTitle = "Tomorrow";
    } else if (day.dayOfYear() >= moment().dayOfYear() + 7) {
      dayTitle = day.format("ddd D");
      dayTitle += "<sup>" + dateOrdinal(day) + "</sup>";
    } else {
      dayTitle = day.format("ddd D");
      dayTitle += "<sup>" + dateOrdinal(day) + "</sup>";
    }
    debug("Start Day " + day.format("YYYY-MM-DD"));
    debug(data);
    output += "<dt>" + dayTitle + ": ";
    var allday_index = 0;

    for (; allday_index < data.allday.length; allday_index++) {
      let mergedAllday = [];
      for (
        let allday_index = 0;
        allday_index < data.allday.length;
        allday_index++
      ) {
        const currentEvent = data.allday[allday_index];
        const existingEvent = mergedAllday.find(
          (event) => event.title === currentEvent.title
        );
        if (existingEvent) {
          existingEvent.calendars.push(...currentEvent.calendars);
        } else {
          mergedAllday.push(currentEvent);
        }
      }
      data.allday = mergedAllday;
    }

    allday_index = 0;
    allday_ouput = "";
    things = [];
    for (; allday_index < data.allday.length; allday_index++) {
      allday = data.allday[allday_index];

      classes = "";

      if (allday.calendars.length > 0) {
        if (allday.calendars.length > 1) {
          classes += "txtcal-stripy ";
        }
        classes += "txtcal-" + allday.calendars.join("-");
      }

      things.push('<span class="' + classes + '" data="' + encodeURI(JSON.stringify(allday)) + '">' + allday.title + "</span>");
    }
    if (things.length == 0) {
      debug("No allday events");
    } else if (things.length == 1) {
      allday_ouput += things[0];
    } else if (things.length > 1) {
      allday_ouput += things.slice(0, -1).join(", ") + " & " + things.pop();
    } else {
      debug(things);
    }
    if (allday_ouput) {
      output += `<span class="day-events">${allday_ouput}</span>`;
    }
    output += "</dt>";

    i = 0;

    for (; i < data.events.length; i++) {
      this_event = data.events[i];
      debug("Event: " + this_event.title);

      starts = moment(this_event.start);
      ends = moment(this_event.end);
      classes = "event ";

      if (this_event.calendars && this_event.calendars.length > 0) {
        if (this_event.calendars.length > 1) {
          classes += "txtcal-stripy ";
        }
        classes += "txtcal-" + this_event.calendars.join("-");
      }
      if (moment().dayOfYear() == starts.dayOfYear()) {
        classes += " todayEvent";
      }
      until = "(for " + moment.duration(starts.diff(ends)).humanize() + ")";
      output +=
        '<dd class="' +
        classes +
        '" eventstarts="' +
        starts.toISOString() +
        '" eventends="' +
        ends.toISOString() +
        '" data="' +
        encodeURI(JSON.stringify(this_event)) +
        '">' +
        '<span class="event_dt">' +
        starts.format("HH:mm") +
        "</span> " +
        '<span class="event_title">' +
        this_event.title +
        "</span> " +
        '<span class="until">' +
        until +
        "</span>" +
        "</dd>";
    }
  }

  output += "</dl>";
  $("#nextUp").html(output);

  $("#nextUp dd, #nextUp span.day-events span").on("click", function (currentdd) {
    console.log("Clicked on event:" + currentdd.innerText);
    console.log(JSON.parse(decodeURI(this.getAttribute("data"))));
  });

  twemoji.parse(document.body);

  update_until();
};

function update_datetime() {
  now = new Date();
  mins = now.getMinutes();
  hours = now.getHours();
  if (mins < 10) {
    mins = "0" + mins;
  }
  if (hours < 10) {
    hours = "0" + hours;
  }
  time = hours + ":" + mins;

  var options = {
    weekday: "long",
    year: "numeric",
    month: "short",
    day: "numeric",
  };
  const o_date = new Intl.DateTimeFormat("en", options);
  const f_date = (m_ca, m_it) => Object({ ...m_ca, [m_it.type]: m_it.value });
  const m_date = o_date.formatToParts().reduce(f_date, {});

  const today = m_date;

  strToday =
    today.weekday +
    " " +
    today.month +
    " " +
    today.day +
    "<sup>" +
    dateOrdinal(today.day) +
    "</sup>";

  $("#date").html(strToday);
  $("#time").html(time);
  $("#datetime").html(
    '<div class="dt_time">' +
      time +
      '</div><div class="dt_date">' +
      strToday +
      "</div>"
  );
}

function update_until() {
  mnow = moment();

  $(".todayEvent").each(function () {
    let text = "";
    thisEvent = $(this);
    thisends = moment(thisEvent.attr("eventends"));
    thisstarts = moment(thisEvent.attr("eventstarts"));
    if (now > thisends) {
      thisEvent.hide();
    } else if (thisstarts > now) {
      duration = moment.duration(thisstarts.diff(thisends)).humanize();
      text = thisstarts.fromNow() + " for " + duration;
    } else if (thisends > now) {
      text = "ends " + thisends.fromNow();
    }

    $(".until", thisEvent).html("(" + text + ")");
  });
  return $(".todayEvent");
}

function update_ical(calendarUrl, start, end, timezone, name, callback) {
  debug(
    "Updating " +
      calendarUrl +
      " from " +
      start.toDate() +
      " to " +
      end.toDate() +
      " in " +
      timezone +
      " as " +
      name
  );
  $.get(calendarUrl)
    .done(function (data) {
      var jcalData = false;
      var comp = false;
      var eventComps = false;
      // parse the ics data
      try {
        jcalData = ICAL.parse(data);
        comp = new ICAL.Component(jcalData);
        eventComps = comp.getAllSubcomponents("vevent");
      } catch (error) {
        console.warn("Couldn't Parse " + calendarUrl);
        return;
      }

      if (comp.getFirstSubcomponent("vtimezone")) {
        for (const tzComponent of comp.getAllSubcomponents("vtimezone")) {
          debug(
            "Registering Timezone: " + tzComponent.getFirstPropertyValue("tzid")
          );
          debug(tzComponent.getFirstPropertyValue("tzid"));
          const tz = new ICAL.Timezone({
            tzid: tzComponent.getFirstPropertyValue("tzid"),
            component: tzComponent,
          });

          if (!ICAL.TimezoneService.has(tz.tzid)) {
            ICAL.TimezoneService.register(tz.tzid, tz);
          }
        }
      }
      comp = ICAL.helpers.updateTimezones(comp);

      localTimeZone = ICAL.Timezone.utcTimezone;

      a_day = 86400000;
      a_month = a_day * 28;
      end = new Date(new Date().getTime() + a_month);

      var rangeStart = ICAL.Time.fromJSDate(start.toDate());
      var rangeEnd = ICAL.Time.fromJSDate(end);
      var all_day_minutes = 60 * 24;

      var events = [];
      $(eventComps).each(function (index, item) {
        var event = new ICAL.Event(item);
        debug("Event: " + item.getFirstPropertyValue("summary"));
        debug(item);

        if (item.getFirstPropertyValue("class") == "PRIVATE") {
          debug("Skipped: Private");
          return;
        }
        if (event.isRecurrenceException()) {
          debug("Skipped: Exception");
          return;
        }
        if (
          filter_out_list.indexOf(item.getFirstPropertyValue("summary")) != -1
        ) {
          debug("Skipped: Filtered");
          debug(item);
          return;
        }
        if (item.getFirstPropertyValue("summary")[0] == "/") {
          debug("Skipped: Filtered for / prefix");
          debug(item);
          return;
        }

        duration = event.duration;

        if (event.isRecurring()) {
          var expand = new ICAL.RecurExpansion({
            component: item,
            dtstart: item.getFirstPropertyValue("dtstart"),
          });

          var next = true;

          var break_out = false;

          while (next && !break_out) {
            next = expand.next();

            if (!next) {
              break;
            }

            next = next.convertToZone(localTimeZone);

            if (next.compare(rangeStart) < 0) {
              debug(">> Too early " + rangeStart.toString());
              continue;
            } else if (next.compare(rangeEnd) > 0) {
              debug(">> Too late " + rangeEnd.toString());
              break_out = true;
              break;
            } else {
              debug("Repeating " + next.toString());
            }

            var end = next.clone();
            end.addDuration(duration);

            minutes_length = duration.toSeconds() / 60;

            var title = item.getFirstPropertyValue("summary");


            if (item.getFirstPropertyValue("x-microsoft-cdo-alldayevent") == "TRUE") {
              allDay = true;
              debug("Setting all day for: " + title + " from Microsoft Calendar flag");
            } else if (item.getFirstPropertyValue("x-apple-allday") == "TRUE") {
              allDay = true;
              debug("Setting all day for: " + title + " from Apple Calendar flag");
            } else if ( minutes_length >= all_day_minutes) {
                console.warn("Allday flag not found for long event: " + title);

            }

            events.push({
              title: title,
              start: next.toJSDate(),
              end: end.toJSDate(),
              location: item.getFirstPropertyValue("location"),
              calendars: [name],
              allDay: allDay,
            });
            next = expand.next();
          }
        } else {
          // end if recurring

          dtstart = item.getFirstPropertyValue("dtstart").toJSDate();
          dtend = item.getFirstPropertyValue("dtend").toJSDate();

          minutes_length = (dtend - dtstart) / (1000 * 60);
          var eventTitle = item.getFirstPropertyValue("summary");

          if (minutes_length >= all_day_minutes) {
            allDay = true;
          } else {
            allDay = false;
          }

          events.push({
            title: eventTitle,
            start: dtstart,
            end: dtend,
            location: item.getFirstPropertyValue("location"),
            calendars: [name],
            allDay: allDay,
          });
        }

        debug("/Event: " + item.getFirstPropertyValue("summary"));
      });
      allEvents[calendarUrl] = events;
      updateNextUp();
      callback(events);
    }) // end done
    .fail(function (error) {
      errortext = error.statusText;
      if (error.responseJSON && error.responseJSON.message) {
        errortext = error.responseJSON.message;
      }

      toastr.error(
        "Failed to load calendar: " + name + " - " + errortext
      );
      
      console.error("Failed to load calendar: " + name + " - " + errortext);
    });
}

function setup_calender() {
  two_weeks = moment().add(30, "d");

  $.get(
    "/all-calendars.php?end=" +
      two_weeks.format("YYYY-MM-DD") +
      "&version=" +
      VERSION,
    calendarUpdateCallback
  );

  // <iCal Calenders>

  for (const [name, cal] of Object.entries(ICAL_CALENDARS)) {
    update_ical(cal.proxy_url, moment(), two_weeks, "GMT", name, updateNextUp);
  }

  //</ iCal Calenders>
}

function when_is_it() {
  now = new Date();
  sunstate = SunCalc.getTimes(now, LATITUDE, LONGITUDE);
  if (now > sunstate.sunset) {
    return "night";
  } else if (now > sunstate.sunrise) {
    return "day";
  } else {
    return "night";
  }
}

function update_theme() {
  if (when_is_it() == "night" && !$("body").hasClass("nighttime")) {
    $("body").removeClass("daytime").addClass("nighttime");
  } else if (when_is_it() == "day" && !$("body").hasClass("daytime")) {
    $("body").removeClass("nighttime").addClass("daytime");
  }
}

// Startup
$(function () {
  toastr.options.closeDuration = 300;

  update_datetime();
  update_theme();
  $("#nextUp").show();

  // page is now ready, initialize the calendar...
  setup_calender();

  $("#datetime").on("click", function () {
    window.location.reload(true);
  });
}); // End Startup

// Hourly
window.setInterval(function () {
  debug("On the hour");
}, 1000 * 60 * 60);
// End Hourly

// Five Seconds
window.setInterval(function () {
  update_datetime();
  update_until();
  update_theme();
}, 5000); // End Five Seconds

window.setInterval(function () {
  if (circle.track_circle_percent <= 1.02) {
    circle.animate(circle.track_circle_percent, "countdown");
    circle.track_circle_percent += 1 / SECONDS_PER_REFRESH;
  } else {
    debug("Refreshing");
    circle.track_circle_percent = 0;

    setup_calender();
  }
}, 1000); // basically seconds, 1800 = 30 minutes
