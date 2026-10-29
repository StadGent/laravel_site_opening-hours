# Gebruikers en rollen

## Rollen

| Rol | Naam in de UI | Bereik |
| --- | --- | --- |
| Admin | Admin | Globaal: alle diensten en gebruikers |
| Editor | Redacteur | Globaal: openingsuren van alle diensten |
| Owner | Beheerder van een dienst | Per dienst |
| Member | Lid van een dienst | Per dienst |

Een gebruiker heeft ofwel een globale rol (Admin of Redacteur), ofwel één of
meer rollen per dienst. Een Admin kan geen rol per dienst krijgen.

## Rechten

| Actie | Admin | Redacteur | Beheerder | Lid |
| --- | --- | --- | --- | --- |
| Gebruiker verwijderen | x | | | |
| Gebruiker Admin maken | enkel super admin | | | |
| Gebruiker uitnodigen als Beheerder of Lid | alle diensten | | eigen dienst | |
| Rol van een gebruiker in een dienst wijzigen of intrekken | alle diensten | | eigen dienst | |
| Dienst activeren / deactiveren | x | | | |
| Kanalen aanmaken en wijzigen | alle diensten | alle diensten | eigen dienst | eigen dienst |
| Kanalen verwijderen | alle diensten | | eigen dienst | eigen dienst |
| Versies en kalenders beheren | alle diensten | alle diensten | eigen dienst | eigen dienst |

Niemand kan zijn eigen rol wijzigen of zichzelf verwijderen.

De regels zitten in `app/Policies/UserPolicy.php` en in de middleware
`admin`, `isOwner` en `hasRoleInService` (`app/Http/Middleware`).

## Super admin

Eén gebruiker is de **super admin**, ingesteld met `SUPER_ADMIN_ID` (standaard
`1`, de admin die `./artisan db:seed` aanmaakt). Enkel de super admin kan:

- de Admin-rol toekennen of intrekken;
- iemand uitnodigen als Admin, of een bestaande Admin uitnodigen met een andere
  rol (de Admin-rol vervalt dan);
- een Admin verwijderen.

!!! warning
    Zorg dat `SUPER_ADMIN_ID` naar een bestaande gebruiker met de Admin-rol
    verwijst, anders kan niemand nog admins beheren via de UI.

## Uitnodigen en registreren

1. Een Admin of Beheerder nodigt iemand uit met een e-mailadres, een rol en
   één of meer diensten.
2. Bestaat de gebruiker nog niet, dan wordt hij aangemaakt zonder wachtwoord en
   krijgt hij de mail **"Activeer uw gebruiker"** met een link naar
   `/register/confirm/{token}`. Daar kiest hij een wachtwoord.
3. Elke uitnodiging stuurt ook de mail **"U werd toegang verleend tot een
   dienst"** met de lijst van diensten.

Wachtwoord vergeten verstuurt de mail **"Reset wachtwoord"**. Alle mails gaan
via SMTP.
