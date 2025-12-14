/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint devel: true */

/**
 * UI update and theming functions
 */
var DocketUI = {
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
			var sunstate = SunCalc.getTimes(now, DocketConfig.constants.LATITUDE, DocketConfig.constants.LONGITUDE);
			
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