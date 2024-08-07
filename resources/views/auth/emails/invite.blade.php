@extends('layouts.email')
<?php

$style = [
  /* Body ------------------------------ */

  'body_action' => 'width: 100%; margin: 30px auto; padding: 0; text-align: center;',
  'body_sub' => 'margin-top: 25px; padding-top: 25px; border-top: 1px solid #EDEFF2;',

  /* Type ------------------------------ */
  'anchor' => 'color: #3869D4;',
  'paragraph' => 'margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;',
  'paragraph-sub' => 'margin-top: 0; color: #74787E; font-size: 12px; line-height: 1.5em;',

  /* Buttons ------------------------------ */
  'button' => 'display: block; display: inline-block; width: 200px; min-height: 20px; padding: 10px;
                 background-color: #3869D4; border-radius: 3px; color: #ffffff; font-size: 15px; line-height: 25px;
                 text-align: center; text-decoration: none; -webkit-text-size-adjust: none;',

  'button-blue' => 'background-color: #3869D4;',

  'font_family' => 'font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif;',
];
?>
@section('content')
@if (count($services) > 1)
<p>Melding dat u toegevoegd werd aan volgende diensten op het openingsurenplatform:</p>
<ul>
  @foreach ($services as $service )
  <li>{{ $service->label }}</li>
  @endforeach
</ul>
@else
<p>Melding dat u toegevoegd werd aan de dienst "{{ $services->first()->label }}" op het openingsurenplatform </p>
@endif

@if (isset($actionUrl))
<p>Het blijkt ook dat u uw gebruiker nog niet geactiveerd staat momenteel.<br />
  Gelieve op onderstaande knop te klikken om zo een wachtwoord in
  te stellen en toegang te krijgen tot {{ $service }}</p>
<table style="{{ $style['body_action'] }}" align="center" width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center">
      <a href="{{ $actionUrl }}" style="{{ $style['font_family'] }} {{ $style['button'] }}
        {{ $style['button-blue'] }}" class="button" target="_blank">Gebruiker activeren</a>
    </td>
  </tr>
</table>
@else
<p>Deze mail is puur informatief, u hoeft verder geen actie te ondernemen.</p>
@endif

<!-- Salutation -->
<p style="{{ $style['paragraph'] }}">
  Met vriendelijke groeten,<br>{{ config('app.name') }}
</p>
@if (isset($actionUrl))
<table style="{{ $style['body_sub'] }}">
  <tr>
    <td style="{{ $style['font_family'] }}">
      <p style="{{ $style['paragraph-sub'] }}">
        Indien het niet lukt om de "Gebruiker activeren" knop te gebruiken,
        knip en plak onderstaande URL in uw web browser:
      </p>

      <p style="{{ $style['paragraph-sub'] }}">
        <a style="{{ $style['anchor'] }}" href="{{ $actionUrl }}" target="_blank">
          {{ $actionUrl }}
        </a>
      </p>
    </td>
  </tr>
</table>
@endif
@endsection
