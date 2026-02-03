/* Docket JavaScript - Calendar functionality */

/**
* Application configuration and constants
*/
var DocketConfig = {
// Global application state
allEvents: {},

// Application constants
constants: {
CALENDAR_SET: "{{ $calendar_set ?? 'all' }}",
FESTIVAL: "{{ $festival ?? '' }}",
DEBUG: @if(isset($git_branch)) true @else false @endif,
LATITUDE: "{{ config('services.location.latitude', 51.5074) }}",
LONGITUDE: "{{ config('services.location.longitude', -0.1278) }}",
SECONDS_PER_REFRESH: 300, // in seconds. 300 == 5 minutes

// Filter configuration
FILTER_OUT_LIST: {!! json_encode(config('calendars.filter_out', [])) !!},
FILTER_OUT_REGEXES: ["^\/"],

// Calendar format configuration
UNTIL_CALENDAR_FORMAT: {
sameDay: '[Today]',
nextDay: '[Tomorrow]',
nextWeek: 'dddd',
lastDay: '[Yesterday]',
lastWeek: '[Last] dddd',
sameElse: 'DD/MM/YYYY'
},

// iCal calendars configuration
ICAL_CALENDARS: {
@foreach($ical_calendars ?? [] as $name => $cal)
"{{ $name }}": {
"color": "{{ $cal['color'] ?? '#000000' }}",
"name": "{{ $cal['name'] ?? $name }}",
"src": "{{ $cal['src'] ?? '' }}",
"emoji": "{{ $cal['emoji'] ?? '' }}",
"proxy_url": "{{ route('icalproxy', ['cal' => $name]) }}&calendar_set={{ $calendar_set ?? 'all' }}"
}@if(!$loop->last),@endif

@endforeach
},

// Google calendars configuration
GOOGLE_CALENDARS: {
@foreach($google_calendars ?? [] as $name => $cal)
"{{ $name }}": {
"color": "{{ $cal['color'] ?? '#000000' }}",
"name": "{{ $cal['name'] ?? $name }}",
"src": "{{ $cal['src'] ?? '' }}",
"emoji": "{{ $cal['emoji'] ?? '' }}"
}@if(!$loop->last),@endif

@endforeach
},

// Merged calendars configuration
MERGED_CALENDARS: {!! json_encode($merged_calendars ?? []) !!}
}
};

/**
* Initialize calendar elements
*/
function initCalendars() {
const calendars = document.querySelectorAll('.calendar');
calendars.forEach(calendar => {
const calendarId = calendar.dataset.calendar;
console.log('Initializing calendar:', calendarId);
// Calendar initialization logic here
});
}

document.addEventListener('DOMContentLoaded', function() {
console.log('Docket calendar application loaded');

// Initialize calendars
initCalendars();

// Twemoji parser
if (typeof twemoji !== 'undefined') {
twemoji.parse(document.body);
}

// Initialize festival selector in debug mode
initFestivalSelector();
});

/**
* Initialize the festival selector for debug mode testing
*/
function initFestivalSelector() {
const festivalSelect = document.getElementById('festival-select');
if (!festivalSelect) {
return; // Festival selector only exists in debug mode
}

festivalSelect.addEventListener('change', function(e) {
console.log('Festival changed to:', e.target.value);
const selectedFestival = e.target.value;
const url = new URL(window.location.href);
const params = new URLSearchParams(url.search);

if (selectedFestival === 'none') {
params.delete('festival');
} else {
params.set('festival', selectedFestival);
}

url.search = params.toString();
console.log('Navigating to:', url.toString());
window.location.href = url.toString();
});
}