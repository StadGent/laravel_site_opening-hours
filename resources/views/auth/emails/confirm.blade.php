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
];
$fontFamily = 'font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif;';
?>
@section('content')
<p>Bevestiging van je registratie op het openingsurenplatform</p>
<table style="{{ $style['body_action'] }}" align="center" width="100%" cellpadding="0" cellspacing="0">
  <tr>
      <td align="center">
           <a href="{{ $actionUrl }}"
              style="{{ $fontFamily }} {{ $style['button'] }} {{ $style['button-blue'] }}"
              class="button"
              target="_blank">Registratie bevestigen</a>
      </td>
  </tr>
</table>
<p>Klik alleen op deze link als je deze registratie zelf aangevraagd had.</p>

<!-- Salutation -->
<p style="{{ $style['paragraph'] }}">
    Met vriendelijke groeten,<br>{{ config('app.name') }}
</p>
 <table style="{{ $style['body_sub'] }}">
  <tr>
      <td style="{{ $fontFamily }}">
          <p style="{{ $style['paragraph-sub'] }}">
              Indien het niet lukt om de "Registratie bevestigen" knop te gebruiken,
              kknip en plak onderstaande URL in uw web browser:
          </p>

          <p style="{{ $style['paragraph-sub'] }}">
              <a style="{{ $style['anchor'] }}" href="{{ $actionUrl }}" target="_blank">
                  {{ $actionUrl }}
              </a>
          </p>
      </td>
  </tr>
</table>
@endsection
