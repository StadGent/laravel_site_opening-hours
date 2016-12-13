<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
</head>
<body>
  <h3>Bevestiging van je registratie op het openingsurenplatform</h3>
  <p>
    Klik op onderstaande link om je registratie te voltooien.
  </p>
  <h3>
    <a href='{{ url("register/confirm/{$token}") }}'>Registratie bevestigen</a>
  </h3>
  <p>
    Klik alleen op deze link als je deze registratie zelf aangevraagd had.
  </p>
</body>
</html>