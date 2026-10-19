<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Opening hours</title>
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="{{ asset('css/app.css') }}?2">
</head>

<body>
  <div id="app">Loading...</div>


  {{--
  <script @cspNonce>
  var initialUsers = {!! json_encode($users) !!}
  var initialServices = {!! json_encode($services) !!}
  var initialRoute = {!! json_encode($route) !!}
  </script>
  --}}

  <script @cspNonce>

  // bugfix: remove old cookie
  document.cookie = "laravel_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";


  var initialUser = {!! json_encode(Auth::user()) !!};
  initialUser.admin = {!! json_encode(Auth::user()->hasRole('Admin')) !!};
  initialUser.editor = {!! json_encode(Auth::user()->hasRole('Editor')) !!};
  Laravel = {!! json_encode([ 'csrfToken' => csrf_token() ]) !!};
  var appName = {!! json_encode(config('app.name')) !!};

  window.vesta = {
      "source" : '{!! env('VESTA_SOURCE_URL') !!}'
  }
  </script>

  <script @cspNonce src="{{ asset('js/chunks/vendor.min.js') }}?2"></script>
  <script @cspNonce src="{{ asset('js/chunks/lib.js') }}?2"></script>

  @if (env('APP_DEBUG'))
  <script type="text/javascript" @cspNonce>
  Vue.config.debug = true
  Vue.config.devtools = true
  </script>
  @endif

  <script @cspNonce src="{{ asset('js/bundle.js') }}?2"></script>
</body>

</html>
