/* Docket JavaScript - Calendar functionality */

document.addEventListener('DOMContentLoaded', function() {
console.log('Docket calendar application loaded');

// Initialize calendars
initCalendars();

// Twemoji parser
if (typeof twemoji !== 'undefined') {
twemoji.parse(document.body);
}
});

function initCalendars() {
const calendars = document.querySelectorAll('.calendar');
calendars.forEach(calendar => {
const calendarId = calendar.dataset.calendar;
console.log('Initializing calendar:', calendarId);
// Calendar initialization logic here
});
}