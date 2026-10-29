# Openingsuren beheren

De openingsuren van een kanaal zijn opgebouwd in drie niveaus:

```text
Kanaal
└── Versie (geldig van ... tot ...)
    ├── Normale uren          (basiskalender)
    ├── Uitzondering 1        (laag)
    └── Uitzondering 2        (laag)
        └── Events (herhalingsregels met tijdslot)
```

## Versies

Een **versie** is een set openingsuren met een geldigheidsperiode (startdatum
en einddatum), bijvoorbeeld "Openingsuren 2027". Een versie is actief als
vandaag binnen die periode valt.

Een versie kan gekopieerd worden. De kalenders van de kopie zijn dan nog niet
gepubliceerd en de events van de normale uren schuiven mee naar de nieuwe
startdatum.

## Kalenders (lagen)

Elke versie heeft één **basiskalender** ("Normale uren"), meestal met een
weekschema. Daarbovenop kunnen **uitzonderingen** als extra lagen toegevoegd
worden, bijvoorbeeld "Gesloten op feestdagen" of "Zomeruren".

- Een uitzondering is standaard een **sluitingskalender**: op de dagen die ze
  raakt is het kanaal de hele dag gesloten. Een uitzondering kan ook
  openingsuren bevatten (bv. aangepaste uren tijdens de zomer).
- **De bovenste laag wint.** Als meerdere kalenders op dezelfde dag van
  toepassing zijn, telt enkel de hoogste laag. Lagen kunnen in de UI van
  volgorde gewisseld worden.
- Een open uitzondering kan een gesloten uitzondering eronder overschrijven.
- Enkel **gepubliceerde** kalenders tellen mee voor de API, LOD en VESTA.

!!! note "Technisch"
    De laag wordt opgeslagen als `priority`: `0` voor de basiskalender, `-1`,
    `-2`, ... voor de uitzonderingen. De laagste waarde wint (zie
    `app/Models/Ical.php`).

## Events en herhalingsregels

Een kalender bevat **events**: een tijdslot (start- en einduur) met een
herhalingsregel in iCal-formaat (`RRULE`, bv. elke maandag tot en met vrijdag)
en een optionele einddatum (`until`).

## Feestdagen en vakanties (presets)

Bij het maken van een uitzondering kunnen voorgedefinieerde periodes gekozen
worden: feestdagen, schoolvakanties, Gentse Feesten en extra sluitingsdagen van
Stad Gent. Die komen uit iCal-bestanden op stad.gent en worden ingeladen met
`openinghours:fetch-od-holidays` (zie [Beheer](../technisch/beheer.md)).

## RecreateX-diensten

De openingsuren van sportlocaties (bron `recreatex`) komen uit RecreateX en
kunnen niet in de UI bewerkt worden. Zie
[Integraties](../technisch/integraties.md#recreatex).

## Waar komen wijzigingen terecht?

Elke wijziging aan een versie of kalender wordt automatisch (via de queue):

- naar de **Linked Data** geschreven;
- voor actieve versies van VESTA-diensten naar **VESTA** geschreven (een
  tekstuele samenvatting van de komende 2 maanden).

De publieke API leest rechtstreeks uit de database van het platform. Responses
mogen 15 minuten gecachet worden (`Cache-Control: max-age=900`).
