/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint devel: true */

/**
 * Event processing and display functions
 */
var DocketEvents = {
	/**
	 * Update the next upcoming events display
	 */
	updateNextUp: function () {
		var now = new Date();
		var nowF = now.toISOString().split("T")[0];
		var days = {};
		days[nowF] = { allday: [], events: [] };

		// Combine all events from different sources
		var events = [];
		for (const [set, setEvents] of Object.entries(DocketConfig.allEvents)) {
			events = events.concat(setEvents);
		}

		events.sort(dateSort);

		// Create day containers for the date range
		var maxDate = new Date(findFurthestDate(events));
		var thisDay = new Date();
		while (thisDay < maxDate) {
			thisDay.setDate(thisDay.getDate() + 1);
			var thisDayF = thisDay.toISOString().split("T")[0];
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

			var startF = start.toISOString().split("T")[0];

			// Handle events that started before today
			if (!days[startF] && end > now) {
				startF = now.toISOString().split("T")[0];
				var endOfDay = new Date(now);
				endOfDay.setHours(23, 59, 59, 999);

				if (end >= endOfDay) {
					debug(
						"Adjusting event to all day: " +
							thisEvent.title +
							" as it started before today",
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
				debug(
					"Setting all day for: " +
						thisEvent.title +
						" as it is midnight to midnight",
				);
				debug(start.toISOString() + " to " + end.toISOString());
				thisEvent.allDay = true;
			}

			if (thisEvent.allDay) {
				this.processAllDayEvent(thisEvent, start, end, now, days);
			} else if (days[startF]) {
				debug("Adding event: " + thisEvent.title + " to " + startF);
				days[startF].events.push(thisEvent);
			} else {
				debug(
					"Skipping event: " +
						thisEvent.title +
						" as it is not in the next two weeks (" +
						startF +
						")",
				);
			}
		}

		this.renderEventsList(days);
		this.setupEventClickHandlers();

		// Parse emojis and update relative times
		twemoji.parse(document.body);
		DocketUI.updateUntil();
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
		// Remove existing handlers to prevent duplicates
		var existingElements = document.querySelectorAll(
			"#nextUp dd, #nextUp span.day-events span",
		);
		for (var j = 0; j < existingElements.length; j++) {
			existingElements[j].removeEventListener("click", this.eventClickHandler);
		}

		// Store handler reference for removal
		if (!this.eventClickHandler) {
			this.eventClickHandler = function (event) {
				console.log("Clicked on event:" + event.target.innerText);
				try {
					console.log(JSON.parse(decodeURI(this.getAttribute("data"))));
				} catch (error) {
					console.error("Error parsing event data:", error);
				}
			};
		}

		var elements = document.querySelectorAll(
			"#nextUp dd, #nextUp span.day-events span",
		);
		for (var i = 0; i < elements.length; i++) {
			elements[i].addEventListener("click", this.eventClickHandler);
		}
	}
};