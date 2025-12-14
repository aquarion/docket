/* jshint esversion: 9 */
/* jshint browser: true */

/**
 * Date utility functions to replace moment.js functionality
 * Organized as an object for better namespace management
 */
var DateUtils = {
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
	 * Get day of year for a given date (1-366)
	 * @param {Date} date - The date to calculate day of year for
	 * @returns {number} Day of year
	 */
	getDayOfYear: (date) => {
		const start = new Date(date.getFullYear(), 0, 0);
		const diff = date - start;
		const oneDay = 1000 * 60 * 60 * 24;
		return Math.floor(diff / oneDay);
	},

	/**
	 * Format a date according to specified format
	 * @param {Date} date - The date to format
	 * @param {string} format - Format string ('ddd D', 'YYYY-MM-DD', 'HH:mm')
	 * @returns {string} Formatted date string
	 */
	formatDate: (date, format) => {
		const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
		const _months = [
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
			return `${days[date.getDay()]} ${date.getDate()}`;
		} else if (format === "YYYY-MM-DD") {
			return date.toISOString().split("T")[0];
		} else if (format === "HH:mm") {
			return date.toTimeString().substr(0, 5);
		}
		return date.toString();
	},

	/**
	 * Convert milliseconds to human-readable duration
	 * @param {number} milliseconds - Duration in milliseconds
	 * @returns {string} Human-readable duration
	 */
	humanizeDuration: (milliseconds) => {
		const seconds = Math.floor(milliseconds / 1000);
		const minutes = Math.floor(seconds / 60);
		const hours = Math.floor(minutes / 60);
		const days = Math.floor(hours / 24);

		if (days > 0) {
			return days === 1 ? "a day" : `${days} days`;
		} else if (hours > 0) {
			return hours === 1 ? "an hour" : `${hours} hours`;
		} else if (minutes > 0) {
			return minutes === 1 ? "a minute" : `${minutes} minutes`;
		} else {
			return seconds === 1 ? "a second" : `${seconds} seconds`;
		}
	},

	/**
	 * Get relative time from now
	 * @param {Date} date - The date to compare with now
	 * @returns {string} Relative time string
	 */
	fromNow: (date) => {
		const now = new Date();
		const diff = date - now;
		const absDiff = Math.abs(diff);

		if (diff > 0) {
			return `in ${DateUtils.humanizeDuration(absDiff)}`;
		} else {
			return `${DateUtils.humanizeDuration(absDiff)} ago`;
		}
	},

	/**
	 * Subtract minutes from a date
	 * @param {Date} date - The original date
	 * @param {number} minutes - Number of minutes to subtract
	 * @returns {Date} New date with minutes subtracted
	 */
	subtractMinutes: (date, minutes) => {
		const result = new Date(date);
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
		const result = new Date(date);
		result.setDate(result.getDate() + days);
		return result;
	},

	/**
	 * Format date for calendar display with relative descriptions
	 * @param {Date} date - The date to format
	 * @returns {string} Calendar formatted string
	 */
	calendarFormat: (date) => {
		const now = new Date();
		const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
		const dateDay = new Date(
			date.getFullYear(),
			date.getMonth(),
			date.getDate(),
		);
		const diffDays = (dateDay - today) / (1000 * 60 * 60 * 24);

		if (diffDays === 0) {
			return `Today at ${DateUtils.formatDate(date, "HH:mm")}`;
		} else if (diffDays === 1) {
			return `Tomorrow at ${DateUtils.formatDate(date, "HH:mm")}`;
		} else if (diffDays === -1) {
			return `Yesterday at ${DateUtils.formatDate(date, "HH:mm")}`;
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
		const hours = date.getHours().toString().padStart(2, "0");
		const minutes = date.getMinutes().toString().padStart(2, "0");
		return `${hours}:${minutes}`;
	},

	/**
	 * Format date for display with ordinal suffix
	 * @param {Date} date - The date to format
	 * @returns {string} Formatted date with weekday, month, day, and ordinal
	 */
	formatDateWithOrdinal: (date) => {
		const options = {
			weekday: "long",
			year: "numeric",
			month: "short",
			day: "numeric",
		};
		const formatter = new Intl.DateTimeFormat("en", options);
		const parts = formatter.formatToParts(date).reduce((acc, part) => {
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
		const astart = new Date(a.start);
		const bstart = new Date(b.start);

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
		let max = new Date();
		for (let i = 0; i < events.length; i++) {
			const end = new Date(events[i].end);
			if (end > max) {
				max = end;
			}
		}
		return max;
	},
};
