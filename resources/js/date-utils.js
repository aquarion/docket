/**
 * Date utility functions to replace moment.js functionality
 * Organized as an object for better namespace management
 */
// biome-ignore-start lint/correctness/noUnusedVariables: DateUtils is used globally
var DateUtils = {
	// biome-ignore-end lint/correctness/noUnusedVariables: DateUtils is used globally
	/**
	 * Get ordinal suffix for a date (st, nd, rd, th)
	 * @param {number} d - Day of month (1-31)
	 * @returns {string} Ordinal suffix
	 */
	dateOrdinal: (d) =>
		31 === d || 21 === d || 1 === d
			? "st"
			: 22 === d || 2 === d
				? "nd"
				: 23 === d || 3 === d
					? "rd"
					: "th",

	/**
	 * True if a Date's local time-of-day is exactly midnight.
	 *
	 * A literal (non-exclusiveEnd) event whose end happens to land exactly
	 * on local midnight has still, in effect, ended at the close of the
	 * previous day - e.g. an event ending "tomorrow at 00:00" doesn't
	 * actually run into tomorrow. Used alongside (never instead of)
	 * exclusiveEnd, which is the authoritative signal for events whose
	 * end is deliberately an exclusive boundary regardless of its time
	 * of day.
	 * @param {Date} date
	 * @returns {boolean}
	 */
	isMidnight: (date) =>
		date.getHours() === 0 &&
		date.getMinutes() === 0 &&
		date.getSeconds() === 0 &&
		date.getMilliseconds() === 0,

	/**
	 * Absolute, ever-increasing day number for a given local calendar date -
	 * an opaque value only meaningful for ordering/equality comparisons
	 * (e.g. "is this tomorrow?"), not for display. Unlike a day-of-year
	 * ordinal, it doesn't reset at year boundaries, so Dec 31 and the
	 * following Jan 1 compare correctly as consecutive days. Built on
	 * buildUtcTime() (UTC has no DST) rather than diffing local Date
	 * instants directly, so a local calendar day that's 23 or 25 real
	 * elapsed hours across a DST transition still counts as exactly one day.
	 * @param {Date} date - The date to calculate the day number for
	 * @returns {number} Absolute day number
	 */
	getDayNumber: (date) => {
		var time = DateUtils.buildUtcTime(
			date.getFullYear(),
			date.getMonth(),
			date.getDate(),
		);
		var oneDay = 1000 * 60 * 60 * 24;
		return Math.round(time / oneDay);
	},

	/**
	 * Format a date according to specified format
	 * @param {Date} date - The date to format
	 * @param {string} format - Format string ('ddd D', 'YYYY-MM-DD', 'HH:mm')
	 * @returns {string} Formatted date string
	 */
	formatDate: (date, format) => {
		var days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
		var _months = [
			"Jan",
			"Feb",
			"Mar",
			"Apr",
			"May",
			"Jun",
			"Jul",
			"Aug",
			"Sep",
			"Oct",
			"Nov",
			"Dec",
		];

		if (format === "ddd D") {
			return days[date.getDay()] + " " + date.getDate();
		} else if (format === "YYYY-MM-DD") {
			// Build from local components, not toISOString() (which is UTC) -
			// this string is used as a calendar-day bucket key, so it must
			// match the day the viewer actually sees the date/time fall on.
			return (
				String(date.getFullYear()).padStart(4, "0") +
				"-" +
				String(date.getMonth() + 1).padStart(2, "0") +
				"-" +
				String(date.getDate()).padStart(2, "0")
			);
		} else if (format === "HH:mm") {
			return date.toTimeString().substr(0, 5);
		}
		return date.toString();
	},

	/**
	 * Parse an event's start/end value into a Date.
	 *
	 * Timed events arrive as either a Date (from ical.js) or a full
	 * ISO datetime string with an offset (from Google Calendar), both of
	 * which `new Date(...)` resolves to the correct instant. All-day
	 * events from Google Calendar arrive as a bare "YYYY-MM-DD" string
	 * with no timezone; `new Date(...)` would parse that as UTC midnight,
	 * shifting it a day for any viewer behind UTC. Treat it as a local
	 * calendar date instead, matching how ical.js resolves ICS all-day
	 * (floating) dates.
	 * @param {Date|string} value - The event's start or end value
	 * @returns {Date}
	 */
	parseEventDate: (value) => {
		if (value instanceof Date) {
			return new Date(value);
		}
		if (typeof value === "string" && /^\d{4}-\d{2}-\d{2}$/.test(value)) {
			var year = Number(value.slice(0, 4));
			var month = Number(value.slice(5, 7));
			var day = Number(value.slice(8, 10));

			// setFullYear(y, m, d)'s 3-argument form has none of the
			// Date(y, m, d) constructor's quirks: no "years 0-99 mean
			// 1900-1999" remapping (which would resolve Feb 29 against
			// the wrong century's leap-year-ness), and it doesn't touch
			// the time-of-day, so an explicit setHours(0,0,0,0) after it
			// reliably lands on local midnight regardless of the seed
			// Date's own local time.
			var result = new Date(0);
			result.setFullYear(year, month - 1, day);
			result.setHours(0, 0, 0, 0);

			// Out-of-range components (e.g. day 31 in a 30-day month)
			// silently roll over into a different date rather than
			// erroring; treat that as invalid instead of guessing.
			if (
				result.getFullYear() !== year ||
				result.getMonth() !== month - 1 ||
				result.getDate() !== day
			) {
				return new Date(Number.NaN);
			}

			return result;
		}
		return new Date(value);
	},

	/**
	 * UTC timestamp (ms) for local calendar components at midnight.
	 *
	 * Unlike Date.UTC(), which shares the Date(y, m, d) constructor's
	 * "years 0-99 mean 1900-1999" special case, this has no such quirk -
	 * useful for day-count arithmetic that must stay correct for early
	 * four-digit years.
	 * @param {number} year
	 * @param {number} month - 0-indexed, matching Date.UTC()
	 * @param {number} day
	 * @returns {number}
	 */
	buildUtcTime: (year, month, day) => {
		var result = new Date(0);
		result.setUTCFullYear(year, month, day);
		result.setUTCHours(0, 0, 0, 0);
		return result.getTime();
	},

	/**
	 * Convert milliseconds to human-readable duration
	 * @param {number} milliseconds - Duration in milliseconds
	 * @returns {string} Human-readable duration
	 */
	humanizeDuration: (milliseconds) => {
		var seconds = Math.floor(milliseconds / 1000);
		var minutes = Math.floor(seconds / 60);
		var hours = Math.floor(minutes / 60);
		var days = Math.floor(hours / 24);

		if (days > 0) {
			return days === 1 ? "a day" : days + " days";
		} else if (hours > 0) {
			return hours === 1 ? "an hour" : hours + " hours";
		} else if (minutes > 0) {
			return minutes === 1 ? "a minute" : minutes + " minutes";
		} else {
			return seconds === 1 ? "a second" : seconds + " seconds";
		}
	},

	/**
	 * Get relative time from now
	 * @param {Date} date - The date to compare with now
	 * @returns {string} Relative time string
	 */
	fromNow: (date) => {
		var now = new Date();
		var diff = date - now;
		var absDiff = Math.abs(diff);

		if (diff > 0) {
			return "in " + DateUtils.humanizeDuration(absDiff);
		} else {
			return DateUtils.humanizeDuration(absDiff) + " ago";
		}
	},

	/**
	 * Subtract minutes from a date
	 * @param {Date} date - The original date
	 * @param {number} minutes - Number of minutes to subtract
	 * @returns {Date} New date with minutes subtracted
	 */
	subtractMinutes: (date, minutes) => {
		var result = new Date(date);
		result.setMinutes(result.getMinutes() - minutes);
		return result;
	},

	/**
	 * Add days to a date
	 * @param {Date} date - The original date
	 * @param {number} days - Number of days to add
	 * @returns {Date} New date with days added
	 */
	addDays: (date, days) => {
		var result = new Date(date);
		result.setDate(result.getDate() + days);
		return result;
	},

	/**
	 * Format date for calendar display with relative descriptions
	 * @param {Date} date - The date to format
	 * @returns {string} Calendar formatted string
	 */
	calendarFormat: (date) => {
		var now = new Date();
		var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
		var dateDay = new Date(date.getFullYear(), date.getMonth(), date.getDate());
		var diffDays = (dateDay - today) / (1000 * 60 * 60 * 24);

		if (diffDays === 0) {
			return "Today at " + DateUtils.formatDate(date, "HH:mm");
		} else if (diffDays === 1) {
			return "Tomorrow at " + DateUtils.formatDate(date, "HH:mm");
		} else if (diffDays === -1) {
			return "Yesterday at " + DateUtils.formatDate(date, "HH:mm");
		} else if (diffDays > 0 && diffDays < 7) {
			return (
				DateUtils.formatDate(date, "ddd D") +
				" at " +
				DateUtils.formatDate(date, "HH:mm")
			);
		} else {
			return date.toLocaleDateString();
		}
	},

	/**
	 * Format time as HH:MM with zero padding
	 * @param {Date} date - The date to format time for
	 * @returns {string} Time formatted as HH:MM
	 */
	formatTime: (date) => {
		var hours = date.getHours().toString().padStart(2, "0");
		var minutes = date.getMinutes().toString().padStart(2, "0");
		return hours + ":" + minutes;
	},

	/**
	 * Format date for display with ordinal suffix
	 * @param {Date} date - The date to format
	 * @returns {string} Formatted date with weekday, month, day, and ordinal
	 */
	formatDateWithOrdinal: (date) => {
		var options = {
			weekday: "long",
			year: "numeric",
			month: "short",
			day: "numeric",
		};
		var formatter = new Intl.DateTimeFormat("en", options);
		var parts = formatter.formatToParts(date).reduce((acc, part) => {
			acc[part.type] = part.value;
			return acc;
		}, {});

		return (
			parts.weekday +
			" " +
			parts.month +
			" " +
			parts.day +
			"<sup>" +
			DateUtils.dateOrdinal(parseInt(parts.day, 10)) +
			"</sup>"
		);
	},

	/**
	 * Sort events by start date
	 * @param {Object} a - First event object with start property
	 * @param {Object} b - Second event object with start property
	 * @returns {number} Sort comparison result (-1, 0, 1)
	 */
	dateSort: (a, b) => {
		var astart = DateUtils.parseEventDate(a.start);
		var bstart = DateUtils.parseEventDate(b.start);

		if (astart.getTime() === bstart.getTime()) {
			return 0;
		} else if (astart > bstart) {
			return 1;
		} else {
			return -1;
		}
	},

	/**
	 * Find the furthest end date from an array of events
	 * @param {Array} events - Array of event objects with end property
	 * @returns {Date} The furthest end date
	 */
	findFurthestDate: (events) => {
		var max = new Date();
		for (var i = 0; i < events.length; i++) {
			if (!events[i].end) {
				continue; // Skip events without end dates
			}

			var end = DateUtils.parseEventDate(events[i].end);
			if (Number.isNaN(end.getTime())) {
				console.warn("Invalid end date found in event:", events[i]);
				continue; // Skip invalid dates
			}

			if (end > max) {
				max = end;
			}
		}
		return max;
	},
};
// Make DateUtils available globally
window.DateUtils = DateUtils;
