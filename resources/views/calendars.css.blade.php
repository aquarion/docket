/* Calendar CSS - Generated from calendars configuration */
/* This file is dynamically generated based on calendar settings */

body {
margin: 0;
padding: 0;
font-family: 'Sometype Mono', monospace;
}

.theme-daytime {
background-color: #ffffff;
color: #333333;
}

.theme-nighttime {
background-color: #1a1a1a;
color: #e0e0e0;
}

.calendar {
margin: 20px;
padding: 15px;
border-radius: 8px;
box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

@if(config('app.debug'))
.debug-info {
position: fixed;
bottom: 10px;
right: 10px;
padding: 5px 10px;
background: rgba(0,0,0,0.7);
color: #fff;
font-size: 12px;
border-radius: 4px;
}
@endif