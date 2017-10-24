@extends('layouts.email') 
@section('content', $style)
<p>Bevestiging van je registratie op het openingsurenplatform</p>
<table style="{{ $style['body_action'] }}" align="center" width="100%" cellpadding="0" cellspacing="0">
  <tr>
      <td align="center">
           <a href="{{ $actionUrl }}"
              style="{{ $style['font_family'] }} {{ $style['button'] }} {{ $style['button-blue'] }}"
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
      <td style="{{ $style['font_family'] }}">
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
