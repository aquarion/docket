/* Calendar CSS - Dynamically generated from calendar configuration */

/* Google Calendar Colors */
@foreach($calendars['google_calendars'] ?? [] as $name => $data)
a.cal-{{ $name }} {
background-color: {{ $data['color'] ?? '#000000' }};
}
.txtcal-{{ $name }} {
color: {{ $data['color'] ?? '#000000' }};
}
@endforeach

/* iCal Calendar Colors */
@foreach($calendars['ical_calendars'] ?? [] as $name => $data)
a.cal-{{ $name }} {
background-color: {{ $data['color'] ?? '#000000' }};
}
.txtcal-{{ $name }} {
color: {{ $data['color'] ?? '#000000' }};
}
@endforeach

/* Merged Calendar Colors - Striped backgrounds for overlapping events */
@php
$all_calendars = array_merge($calendars['google_calendars'] ?? [], $calendars['ical_calendars'] ?? []);
@endphp

@foreach($all_calendars as $alpha_name => $alpha_data)
@foreach($all_calendars as $beta_name => $beta_data)
@if($alpha_name != $beta_name)
@php
$alpha_rgb = App\Support\ColorHelper::hexToRGBA($alpha_data['color'] ?? '#000000');
$beta_rgb = App\Support\ColorHelper::hexToRGBA($beta_data['color'] ?? '#000000');
$alpha_dim = "rgba({$alpha_rgb[0]}, {$alpha_rgb[1]}, {$alpha_rgb[2]}, 0.5)";
$beta_dim = "rgba({$beta_rgb[0]}, {$beta_rgb[1]}, {$beta_rgb[2]}, 0.5)";
@endphp

/* {{ $alpha_name }}_{{ $beta_name }} */
a.cal-{{ $alpha_name }}-{{ $beta_name }}, a.cal-{{ $beta_name }}-{{ $alpha_name }} {
background: repeating-linear-gradient(
45deg,
{{ $alpha_dim }},
{{ $alpha_dim }} 3px,
{{ $beta_dim }} 3px,
{{ $beta_dim }} 6px
);
}
@endif
@endforeach
@endforeach

/* Explicit Merged Calendar Colors */
@foreach($calendars['merged_calendars'] ?? [] as $merge_key => $merge_data)
a.cal-{{ $merge_key }} {
background-color: {{ $merge_data['color'] ?? '#888888' }};
}
@endforeach