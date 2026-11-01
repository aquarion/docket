// Import polyfills first for iOS 12 compatibility
import "./ios12-polyfills.js";

import { initOtelBrowserErrors } from "@istic-co/otel-browser-errors";

try {
	initOtelBrowserErrors({
		endpoint: import.meta.env.VITE_OTEL_EXPORTER_OTLP_ENDPOINT,
		serviceName: `${import.meta.env.VITE_APP_NAME}-frontend`,
		serviceVersion: import.meta.env.VITE_APP_VERSION,
		environment: import.meta.env.VITE_APP_ENV,
		revision: import.meta.env.VITE_APP_PR_NUMBER,
		branch: import.meta.env.VITE_APP_BRANCH,
	});
} catch (err) {
	console.error("[otel] Failed to initialize browser error reporting:", err);
}

import twemoji from "@twemoji/api";
// Import external libraries from node_modules
import ICAL from "ical.js";
import * as SunCalc from "suncalc";
import "emoji-picker-element";

// Make libraries available globally BEFORE importing application files
window.ICAL = ICAL;
window.SunCalc = SunCalc;
window.twemoji = twemoji;

// Import application JavaScript files
// These files depend on the global variables set above
import "./festival-utilities.js";
import "./date-utils.js";
import "./css-utils.js";
import "./notification-utils.js";
import "./circle-progress.js";
import "./calendar-management/calendar-manager.js";
import "./docket-ui.js";
import "./docket-weather.js";
import "./docket-calendar.js";
import "./docket-events.js";
import "./docket-main.js";
