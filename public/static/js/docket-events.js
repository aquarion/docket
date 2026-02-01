/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint devel: true */

/**
 * Event processing and display functions
 */
// biome-ignore-start lint/correctness/noUnusedVariables: DocketEvents is used globally
var DocketEvents = {
	// biome-ignore-end lint/correctness/noUnusedVariables: DocketEvents is used globally
	/**
	 * Update the next upcoming events display
	 */
	updateNextUp: () => {
		var now,
			nowF,
			days,
			thisDayF,
			thisEvent,
			end,
			start,
			startF,
			endOfDay,
			i,
			events,
			maxDate,
			thisDay;

		now = new Date();
		nowF = now.toISOString().split("T")[0];
		days = {};

		days[nowF] = { allday: [], events: [] };

		// Combine all events from different sources
		events = [];
		for (const [_set, setEvents] of Object.entries(DocketConfig.allEvents)) {
			events = events.concat(setEvents);
		}

		events.sort(DateUtils.dateSort);

		// Create day containers for the date range
		maxDate = new Date(DateUtils.findFurthestDate(events));
		thisDay = new Date();
		while (thisDay < maxDate) {
			thisDay.setDate(thisDay.getDate() + 1);
			thisDayF = thisDay.toISOString().split("T")[0];
			if (!days[thisDayF]) {
				days[thisDayF] = { allday: [], events: [] };
			}
		}

		// Process each event
		for (i = 0; i < events.length; i++) {
			thisEvent = events[i];
			end = new Date(thisEvent.end);
			start = new Date(thisEvent.start);

			// Skip past events
			if (end < now) {
				continue;
			}

			startF = start.toISOString().split("T")[0];

			// Handle events that started before today
			if (!days[startF] && end > now) {
				startF = now.toISOString().split("T")[0];
				endOfDay = new Date(now);
				endOfDay.setHours(23, 59, 59, 999);

				if (end >= endOfDay) {
					NotificationUtils.debug(
						`Adjusting event to all day: ${thisEvent.title} as it started before today`,
					);
					thisEvent.allDay = true;
				} else {
					start = new Date(endOfDay);
				}
			}

			// Detect midnight-to-midnight events
			if (
				start.getHours() === 0 &&
				start.getMinutes() === 0 &&
				end.getHours() === 0 &&
				end.getMinutes() === 0 &&
				thisEvent.allDay !== true
			) {
				NotificationUtils.debug(
					`Setting all day for: ${thisEvent.title} as it is midnight to midnight`,
				);
				NotificationUtils.debug(
					`${start.toISOString()} to ${end.toISOString()}`,
				);
				thisEvent.allDay = true;
			}

			if (thisEvent.allDay) {
				DocketEvents.processAllDayEvent(thisEvent, start, end, now, days);
			} else if (days[startF]) {
				NotificationUtils.debug(
					`Adding event: ${thisEvent.title} to ${startF}`,
				);
				days[startF].events.push(thisEvent);
			} else {
				NotificationUtils.debug(
					`Skipping event: ${thisEvent.title} as it is not in the next two weeks (${startF})`,
				);
			}
		}

		DocketEvents.renderEventsList(days);
		DocketEvents.setupEventClickHandlers();

		// Parse emojis and update relative times
		twemoji.parse(document.body);
		DocketUI.updateUntil();
	},

	/**
	 * Process an all-day event across multiple days
	 */
	processAllDayEvent: (thisEvent, start, end, now, days) => {
		var showedStarted,
			startF,
			durationHours,
			_startedToday,
			xEvent,
			xEnd,
			startedToday;

		showedStarted = false;

		if (start < now) {
			start = new Date();
		}

		startF = start.toISOString().split("T")[0];
		durationHours = (end - start) / (1000 * 60 * 60) - 24;

		if (days[startF]) {
			showedStarted = true;
			_startedToday = true;
			xEvent = Object.assign({}, thisEvent);

			if (durationHours > 0) {
				xEnd = DateUtils.subtractMinutes(end, 1);
				xEvent.title = `${xEvent.title}<span class='until'>(until ${DateUtils.calendarFormat(xEnd)})</span>`;
			}

			days[startF].allday.push(xEvent);
		}

		// Handle multi-day events
		while (durationHours > 0) {
			startedToday = false;
			start = DateUtils.addDays(start, 1);
			startF = DateUtils.formatDate(start, "YYYY-MM-DD");

			if (days[startF] && !showedStarted) {
				days[startF].allday.push(thisEvent);
				showedStarted = true;
				startedToday = true;
			}

			durationHours -= 24;

			if (durationHours < 1 && !startedToday) {
				xEvent = Object.assign({}, thisEvent);
				xEvent.title = `${xEvent.title} ends`;
				days[startF].allday.push(xEvent);
			}
		}
	},

	/**
	 * Render the events list HTML
	 */
	renderEventsList: (days) => {
		var output, day, dayTitle, nextUpEl;

		output = "<dl>";

		for (const [date, data] of Object.entries(days)) {
			day = new Date(date);
			dayTitle = DocketEvents.getDayTitle(day);

			NotificationUtils.debug(
				`Start Day ${DateUtils.formatDate(day, "YYYY-MM-DD")}`,
			);
			NotificationUtils.debug(data);

			output += `<dt>${dayTitle}: `;

			// Merge duplicate all-day events
			DocketEvents.mergeAllDayEvents(data);

			// Render all-day events
			output += DocketEvents.renderAllDayEvents(data.allday);
			output += "</dt>";

			// Render timed events
			output += DocketEvents.renderTimedEvents(data.events);
		}

		output += "</dl>";
		nextUpEl = document.getElementById("nextUp");
		if (nextUpEl) {
			nextUpEl.innerHTML = output;
		}
	},

	/**
	 * Get display title for a day
	 */
	getDayTitle: (day) => {
		var nowDayOfYear, dayDayOfYear, title;

		nowDayOfYear = DateUtils.getDayOfYear(new Date());
		dayDayOfYear = DateUtils.getDayOfYear(day);

		if (nowDayOfYear === dayDayOfYear) {
			return "Today";
		} else if (nowDayOfYear + 1 === dayDayOfYear) {
			return "Tomorrow";
		} else {
			title = DateUtils.formatDate(day, "ddd D");
			title += `<sup>${DateUtils.dateOrdinal(day.getDate())}</sup>`;
			return title;
		}
	},

	/**
	 * Merge duplicate all-day events by title
	 */
	mergeAllDayEvents: (data) => {
		var mergedAllday, i, currentEvent, existingEvent;

		mergedAllday = [];

		for (i = 0; i < data.allday.length; i++) {
			currentEvent = data.allday[i];
			existingEvent = mergedAllday.find(
				(event) => event.title === currentEvent.title,
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
	renderAllDayEvents: (alldayEvents) => {
		var things, i, allday, classes, output;

		things = [];

		if (alldayEvents.length === 0) {
			NotificationUtils.debug("No allday events");
			return "";
		}

		for (i = 0; i < alldayEvents.length; i++) {
			allday = alldayEvents[i];
			classes = DocketEvents.getEventClasses(allday);

			things.push(
				`<span class="${classes}" data="${encodeURI(JSON.stringify(allday))}">${allday.title}</span>`,
			);
		}

		output = "";
		if (things.length === 1) {
			output = things[0];
		} else if (things.length > 1) {
			output = `${things.slice(0, -1).join(", ")} & ${things.pop()}`;
		}

		return output ? `<span class="day-events">${output}</span>` : "";
	},

	/**
	 * Render timed events HTML
	 */
	renderTimedEvents: (events) => {
		var output, i, thisEvent, starts, ends, classes, until;

		output = "";

		for (i = 0; i < events.length; i++) {
			thisEvent = events[i];
			NotificationUtils.debug(`Event: ${thisEvent.title}`);

			starts = new Date(thisEvent.start);
			ends = new Date(thisEvent.end);
			classes = `event ${DocketEvents.getEventClasses(thisEvent)}`;

			if (
				DateUtils.getDayOfYear(new Date()) === DateUtils.getDayOfYear(starts)
			) {
				classes += " todayEvent";
			}

			until = `(for ${DateUtils.humanizeDuration(Math.abs(ends - starts))})`;

			output += `<dd class="${classes}" eventstarts="${starts.toISOString()}" eventends="${ends.toISOString()}" data="${encodeURI(JSON.stringify(thisEvent))}">
					<span class="event_dt">${DateUtils.formatDate(starts, "HH:mm")}</span> 
					<span class="event_title">${thisEvent.title}</span> 
					<span class="until">${until}</span>
				</dd>`;
		}

		return output;
	},

	/**
	 * Get CSS classes for an event based on its calendars
	 */
	getEventClasses: (event) => {
		var classes;

		classes = "";

		if (event.calendars && event.calendars.length > 0) {
			if (event.calendars.length > 1) {
				classes += "txtcal-stripy ";
			}
			classes += `txtcal-${event.calendars.join("-")}`;
		}

		return classes;
	},

	/**
	 * Setup click handlers for events
	 */
	setupEventClickHandlers: () => {
		var existingElements, j, elements, i;

		// Remove existing handlers to prevent duplicates
		existingElements = document.querySelectorAll(
			"#nextUp dd, #nextUp span.day-events span",
		);
		for (j = 0; j < existingElements.length; j++) {
			existingElements[j].removeEventListener(
				"click",
				DocketEvents.eventClickHandler,
			);
		}

		// Store handler reference for removal
		if (!DocketEvents.eventClickHandler) {
			DocketEvents.eventClickHandler = function (event) {
				console.log(`Clicked on event:${event.target.innerText}`);
				try {
					console.log(JSON.parse(decodeURI(this.getAttribute("data"))));
				} catch (error) {
					console.error("Error parsing event data:", error);
				}
			};
		}

		elements = document.querySelectorAll(
			"#nextUp dd, #nextUp span.day-events span",
		);
		for (i = 0; i < elements.length; i++) {
			elements[i].addEventListener("click", DocketEvents.eventClickHandler);
		}
	},
};
