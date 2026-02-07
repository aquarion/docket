/* Calendar CSS - Dynamically generated from calendar configuration */

/* Google Calendar Colors */
@foreach($calendars['google_calendars'] ?? [] as $name => $data)
@php $safe_name = \App\Support\StringHelper::sanitizeCssClassName($name); @endphp
a.cal-{{ $safe_name }} {
background-color: {{ $data['color'] ?? '#000000' }};
}
.txtcal-{{ $safe_name }} {
color: {{ $data['color'] ?? '#000000' }};
}
@endforeach

/* iCal Calendar Colors */
@foreach($calendars['ical_calendars'] ?? [] as $name => $data)
@php $safe_name = \App\Support\StringHelper::sanitizeCssClassName($name); @endphp
a.cal-{{ $safe_name }} {
background-color: {{ $data['color'] ?? '#000000' }};
}
.txtcal-{{ $safe_name }} {
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
$safe_alpha = \App\Support\StringHelper::sanitizeCssClassName($alpha_name);
$safe_beta = \App\Support\StringHelper::sanitizeCssClassName($beta_name);
$alpha_rgb = App\Support\ColorHelper::hexToRGBA($alpha_data['color'] ?? '#000000');
$beta_rgb = App\Support\ColorHelper::hexToRGBA($beta_data['color'] ?? '#000000');
$alpha_dim = "rgba({$alpha_rgb[0]}, {$alpha_rgb[1]}, {$alpha_rgb[2]}, 0.5)";
$beta_dim = "rgba({$beta_rgb[0]}, {$beta_rgb[1]}, {$beta_rgb[2]}, 0.5)";
@endphp

/* {{ $alpha_name }}_{{ $beta_name }} */
a.cal-{{ $safe_alpha }}-{{ $safe_beta }}, a.cal-{{ $safe_beta }}-{{ $safe_alpha }},
.txtcal-{{ $safe_alpha }}-{{ $safe_beta }}, .txtcal-{{ $safe_beta }}-{{ $safe_alpha }} {
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

/* Explicit Merged Calendar Colors - Override text color only */
@foreach($calendars['merged_calendars'] ?? [] as $merge_key => $merge_data)
@php $safe_merge_key = \App\Support\StringHelper::sanitizeCssClassName($merge_key); @endphp
.txtcal-{{ $safe_merge_key }} {
color: {{ $merge_data['color'] ?? '#888888' }};
}
@endforeach