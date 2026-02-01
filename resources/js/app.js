// Import external libraries from node_modules
import ICAL from "ical.js";
import SunCalc from "suncalc";
import twemoji from "@twemoji/api";

// Make libraries available globally BEFORE importing application files
window.ICAL = ICAL;
window.SunCalc = SunCalc;
window.twemoji = twemoji;

// Import application JavaScript files
// These files depend on the global variables set above
import "./festival-utilities.js";
import "./date-utils.js";
import "./notification-utils.js";
import "./circle-progress.js";
import "./docket-ui.js";
import "./docket-calendar.js";
import "./docket-events.js";
import "./docket-main.js";
