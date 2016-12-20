<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Opening hours</title>
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,400i,600" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="{{ asset('css/app.css') }}">
</head>

<body>
  <div id="app">Loading...</div>

  {{--
  <script>
  var initialUsers = {!! json_encode($users) !!}
  var initialServices = {!! json_encode($services) !!}
  var initialRoute = {!! json_encode($route) !!}
  </script>
  --}}

  <script>
  var initialUser = {!! json_encode(Auth::user()) !!};
  initialUser.admin = {!! json_encode(Auth::user()->hasRole('Admin')) !!};
  Laravel = {!! json_encode([ 'csrfToken' => csrf_token() ]) !!};
  </script>

  <script src="{{ asset('js/chunks/vendor.min.js') }}"></script>
  <script src="{{ asset('js/chunks/lib.js') }}"></script>

  @if (env('APP_DEBUG'))
  <script type="text/javascript">
  Vue.config.debug = true
  Vue.config.devtools = true
  </script>
  @endif

  <script src="{{ asset('js/bundle.js') }}"></script>
</body>

</html>
