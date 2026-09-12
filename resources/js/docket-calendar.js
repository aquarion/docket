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
		var keys, i, name, cal;

		twoWeeks = DateUtils.addDays(new Date(), 30);

		// Fetch JSON calendar data
		// `end` is parsed by the backend with strtotime() in the app's
		// timezone (UTC) and passed straight through as Google Calendar's
		// exclusive timeMax, so it must stay a UTC calendar date - not the
		// viewer's local date used elsewhere for display bucketing.
		fetch(
			"/all-calendars?end=" +
				twoWeeks.toISOString().split("T")[0] +
				"&calendar_set=" +
				DocketConfig.constants.CALENDAR_SET,
		)
			.then((response) => {
				if (!response.ok) throw new Error("HTTP " + response.status);
				return response.json();
			})
			.then((data) => {
				// Check if response contains an error (authentication failures)
				if (data.error) {
					throw new Error(data.error);
				}
				return data;
			})
			.then(this.updateCallback.bind(this))
			.catch((error) => {
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
			keys = Object.keys(DocketConfig.constants.ICAL_CALENDARS);
			for (i = 0; i < keys.length; i++) {
				name = keys[i];
				cal = DocketConfig.constants.ICAL_CALENDARS[name];
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
	 * Get today's events for relative time display
	 * @returns {Array} Array of today's events
	 */
	getTodayEvents: () => {
		var now,
			nowF,
			todayEvents,
			allEventsEntries,
			i,
			_set,
			setEvents,
			events,
			j,
			event,
			start,
			end;

		now = new Date();
		nowF = DateUtils.formatDate(now, "YYYY-MM-DD"); // Today's date in YYYY-MM-DD format
		todayEvents = [];

		// Combine all events from different sources
		events = [];
		allEventsEntries = Object.entries(DocketConfig.allEvents);
		for (i = 0; i < allEventsEntries.length; i++) {
			_set = allEventsEntries[i][0];
			setEvents = allEventsEntries[i][1];
			events = events.concat(setEvents);
		}

		// Filter events for today
		for (j = 0; j < events.length; j++) {
			event = events[j];

			// Skip events without proper dates
			if (!event.start || !event.end) {
				continue;
			}

			start = DateUtils.parseEventDate(event.start);
			end = DateUtils.parseEventDate(event.end);

			// Skip events with invalid dates
			if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
				continue;
			}

			// All-day end dates are exclusive (the day after the event's last
			// covered day, per RFC 5545 and Google Calendar's convention) -
			// even one with a non-midnight time-of-day, e.g. an Outlook/
			// Apple all-day event recurring at a fixed local time - so
			// compare against the last day the event actually covers.
			// exclusiveEnd (set once at each event's origin) is the
			// authoritative signal for this, since allDay alone also
			// covers ordinary timed events that just happen to run long
			// (processSingleEvent) or got heuristically promoted by
			// updateNextUp - both have a real, literal end instant that
			// must be compared as-is instead.
			var inclusiveEnd = event.exclusiveEnd
				? DateUtils.addDays(end, -1)
				: end;

			// Check if event is today (starts today, ends today, or spans today)
			var startDateF = DateUtils.formatDate(start, "YYYY-MM-DD");
			var endDateF = DateUtils.formatDate(inclusiveEnd, "YYYY-MM-DD");

			if (
				startDateF === nowF ||
				endDateF === nowF ||
				(startDateF < nowF && endDateF > nowF)
			) {
				// Push a copy with start/end normalized to real Dates - the
				// shared DocketConfig.allEvents object can carry a bare
				// "YYYY-MM-DD" string (Google all-day events), which
				// DocketUI.updateUntil would otherwise subtract directly,
				// producing NaN ("NaN seconds ago") instead of a duration.
				todayEvents.push(Object.assign({}, event, { start: start, end: end }));
			}
		}

		// Sort events by start time
		todayEvents.sort(
			(a, b) => DateUtils.parseEventDate(a.start) - DateUtils.parseEventDate(b.start),
		);

		return todayEvents;
	},

	/**
	 * Callback for JSON calendar updates
	 * @param {Object} data - Calendar data
	 */
	updateCallback: (data, _info, _third) => {
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
	updateIcal: (calendarUrl, start, end, timezone, name, callback) => {
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
			.then((response) => {
				if (!response.ok)
					throw new Error(
						"HTTP " + response.status + ": " + response.statusText,
					);
				return response.text();
			})
			.then((data) => {
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
				rangeStart = ICAL.Time.fromJSDate(start, true);
				rangeEnd = ICAL.Time.fromJSDate(endRange, true);
				allDayMinutes = 1440;
				events = [];

				// Process events
				for (index = 0; index < eventComps.length; index++) {
					try {
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
					} catch (eventError) {
						NotificationUtils.error(
							`Error processing event in calendar ${name}: ${eventError.message}`,
						);
						console.warn(
							`Event processing error in ${name}:`,
							eventError,
							item,
						);
					}
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
				// See processSingleEvent for why this is tracked separately
				// from allDay.
				exclusiveEnd:
					item.getFirstPropertyValue("dtstart").isDate === true ||
					item.getFirstPropertyValue("x-microsoft-cdo-alldayevent") ===
						"TRUE" ||
					item.getFirstPropertyValue("x-apple-allday") === "TRUE",
			});
		}
	},

	/**
	 * Process a single (non-recurring) event
	 */
	processSingleEvent: (item, allDayMinutes, name, events) => {
		var dtstart,
			dtend,
			minutesLength,
			eventTitle,
			allDay,
			dtstartProp,
			dtendProp;

		// Safely get date properties
		dtstartProp = item.getFirstPropertyValue("dtstart");
		dtendProp = item.getFirstPropertyValue("dtend");

		if (!dtstartProp) {
			NotificationUtils.warning(
				`Event missing start date in calendar: ${name}`,
			);
			return;
		}

		if (!dtendProp) {
			NotificationUtils.warning(`Event missing end date in calendar: ${name}`);
			return;
		}

		try {
			dtstart = dtstartProp.toJSDate();
			dtend = dtendProp.toJSDate();
		} catch (error) {
			NotificationUtils.error(
				`Invalid date in calendar: ${name} - ${error.message}`,
			);
			return;
		}

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
			// Distinct from allDay (which also covers ordinary timed events
			// that just happen to run long): true only when `end` is
			// genuinely an exclusive day-after-last-covered-day boundary -
			// a real date-only (VALUE=DATE) value, or an event explicitly
			// flagged all-day by Outlook/Apple even if its DTSTART/DTEND
			// are date-times (e.g. a recurring all-day event anchored at a
			// fixed local time).
			exclusiveEnd:
				dtendProp.isDate === true ||
				item.getFirstPropertyValue("x-microsoft-cdo-alldayevent") ===
					"TRUE" ||
				item.getFirstPropertyValue("x-apple-allday") === "TRUE",
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
