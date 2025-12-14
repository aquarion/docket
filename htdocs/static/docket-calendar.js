/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint devel: true */

/**
 * Calendar management functions for fetching and processing calendar data
 */
var DocketCalendar = {
	/**
	 * Setup and refresh calendar data
	 */
	setup: function () {
		var twoWeeks = addDays(new Date(), 30);

		// Fetch JSON calendar data
		fetch(
			"/all-calendars.php?end=" +
				formatDate(twoWeeks, "YYYY-MM-DD") +
				"&version=" +
				DocketConfig.constants.VERSION,
		)
			.then(function (response) {
				if (!response.ok) throw new Error("HTTP " + response.status);
				return response.json();
			})
			.then(this.updateCallback.bind(this))
			.catch(function (error) {
				console.error("Failed to fetch calendar data:", error);
			});

		// Fetch iCal calendar data
		if (DocketConfig.constants.ICAL_CALENDARS) {
			for (const [name, cal] of Object.entries(
				DocketConfig.constants.ICAL_CALENDARS,
			)) {
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
	updateCallback: function (data, info, third) {
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
		debug(
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
					throw new Error("HTTP " + response.status + ": " + response.statusText);
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
				var msPerDay = 86400000;
				var approxDaysInMonth = 30;
				var endRange = new Date(new Date().getTime() + (msPerDay * approxDaysInMonth));
				var rangeStart = ICAL.Time.fromJSDate(start);
				var rangeEnd = ICAL.Time.fromJSDate(endRange);
				var allDayMinutes = 1440;
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
						continue;
					}

					// Skip recurrence exceptions
					if (event.isRecurrenceException()) {
						debug("Skipped: Exception");
						continue;
					}

					// Skip filtered events
					if (
						DocketConfig.constants.FILTER_OUT_LIST &&
						DocketConfig.constants.FILTER_OUT_LIST.indexOf(summary) !== -1
					) {
						debug("Skipped: Filtered");
						continue;
					}

					if (DocketConfig.constants.FILTER_OUT_REGEXES) {
						var skipEvent = false;
						for (const regexString of DocketConfig.constants.FILTER_OUT_REGEXES) {
							var regex = new RegExp(regexString);
							if (regex.test(summary)) {
								debug("Skipped: Filtered by regex " + regexString);
								skipEvent = true;
								break;
							}
						}
						if (skipEvent) continue;
					}

					// Skip events with / prefix
					if (summary && summary[0] === "/") {
						debug("Skipped: Filtered for / prefix");
						continue;
					}

					var duration = event.duration;

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
						DocketCalendar.processSingleEvent(item, allDayMinutes, name, events);
					}

					debug("/Event: " + summary);
				}

				DocketConfig.allEvents[calendarUrl] = events;
				DocketEvents.updateNextUp();
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
	processRecurringEvent: function(
		item,
		event,
		duration,
		rangeStart,
		rangeEnd,
		localTimeZone,
		allDayMinutes,
		name,
		events,
	) {
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
			var allDay = DocketCalendar.determineAllDay(item, minutesLength, allDayMinutes, title);

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