# Openingsuren

Het openingsurenplatform (de "Openingsuren Invoermodule") centraliseert het
beheer van de openingsuren van de diensten van Stad Gent en hun kanalen
(loket, telefonisch, na afspraak, ...).

Medewerkers beheren de openingsuren in een webinterface. Het platform stelt ze
daarna beschikbaar:

- via een **publieke REST API** (JSON, JSON-LD, HTML en platte tekst), die
  onder meer stad.gent, Visit Gent en RADAR gebruiken;
- als **Linked Open Data** (SPARQL / Virtuoso);
- in **VESTA**, het CRM met de zakenpartners van de stad, zodat de oude
  koppelingen (o.a. InfoFiches) up-to-date blijven.

## Overzicht

| | |
| --- | --- |
| Status | Productie |
| Omgevingen | [Productie](https://openingsuren.gent.be/), [QA](https://openingsuren.qa.gent.be/), [Test](https://openingsuren.dv.gent.be/) |
| Technologie | Laravel 12, PHP 8.3, MySQL/MariaDB, Vue 2 (gebundeld met Rollup) |
| Authenticatie | Lokale gebruikers (Laravel auth), API-tokens via Laravel Passport |
| Queue | Laravel queue met **exact één** worker (supervisor) |
| Logging | Syslog (ident `openingsuren`, facility `LOCAL4`) in Kibana-formaat |
| Integraties | VESTA (SOAP), SPARQL/LOD (Virtuoso), RecreateX (SOAP), iCal-feestdagenkalenders van stad.gent |
| Broncode | [StadGent/laravel_site_opening-hours](https://github.com/StadGent/laravel_site_opening-hours) |
| Owner | d09-web-developers |

## Inhoud

### Functioneel

- [Diensten en kanalen](functioneel/diensten-en-kanalen.md)
- [Openingsuren beheren](functioneel/openingsuren.md)
- [Gebruikers en rollen](functioneel/gebruikers-en-rollen.md)
- [Publieke API](functioneel/api.md)

### Technisch

- [Architectuur](technisch/architectuur.md)
- [Integraties](technisch/integraties.md)
- [Beheer en deployment](technisch/beheer.md)
- [Lokale ontwikkeling](technisch/lokale-ontwikkeling.md)
- [Debuggen](technisch/debuggen.md)
