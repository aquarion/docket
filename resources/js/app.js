// Import external libraries from node_modules
import ICAL from "ical.js";
import SunCalc from "suncalc";
import twemoji from "@twemoji/api";

// Make libraries available globally
window.ICAL = ICAL;
window.SunCalc = SunCalc;
window.twemoji = twemoji;

// Import application JavaScript files
import "../../public/static/js/date-utils.js";
import "../../public/static/js/notification-utils.js";
import "../../public/static/js/circle-progress.js";
import "../../public/static/js/docket-ui.js";
import "../../public/static/js/docket-calendar.js";
import "../../public/static/js/docket-events.js";
import "../../public/static/js/docket-main.js";
