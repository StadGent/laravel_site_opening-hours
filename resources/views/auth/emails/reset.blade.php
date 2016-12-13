<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
</head>
<body>
  <p>
    Klik op onderstaande link om je wachtwoord opnieuw in te stellen.
  </p>
  <h3>
    <a href="{{ $link = url('password/reset', $token).'?email='.urlencode($email) }}"> {{ $link }} </a>
  </h3>
</body>
</html>