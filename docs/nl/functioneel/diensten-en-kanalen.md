# Diensten en kanalen

## Diensten

Een **dienst** is een organisatie of locatie waarvoor openingsuren beheerd
kunnen worden, bijvoorbeeld "Dienst Burgerzaken" of een zwembad.

Diensten worden niet in het platform zelf aangemaakt. Ze worden opgehaald uit
de Linked Data van de stad (`openinghours:fetch-services`, zie
[Integraties](../technisch/integraties.md#diensten-ophalen-uit-lod)). Elke
dienst heeft een **bron** (`source`):

| Bron | Herkomst | Openingsuren beheerd in |
| --- | --- | --- |
| `vesta` | Zakenpartners in VESTA waarvoor "Publiceren in LOD" aangevinkt is | Openingsurenplatform (en doorgestuurd naar VESTA) |
| `recreatex` | Sportlocaties uit RecreateX | RecreateX (geïmporteerd, niet bewerkbaar in de UI) |
| `publicToilets` | Publieke toiletten (GIS) | Openingsurenplatform |
| `crm` | Contacten uit het Visit Gent CRM | Openingsurenplatform |

!!! note
    Bestaande diensten worden bij een nieuwe fetch bijgewerkt op basis van
    bron + identifier. Een dienst verwijderen of hernoemen gebeurt in de bron
    (VESTA, RecreateX, ...), niet in het openingsurenplatform.

### Actief en inactief (draft)

Een nieuwe dienst staat standaard op **draft** (inactief). Een draft-dienst is
niet zichtbaar in de publieke API en wordt niet naar VESTA geschreven. Enkel een
Admin kan een dienst activeren of deactiveren.

Bij het deactiveren van een VESTA-dienst worden de openingsuren in VESTA
leeggemaakt.

## Kanalen

Een **kanaal** is een manier waarop een dienst bereikbaar is, bijvoorbeeld
"Loket", "Telefonisch" of "Na afspraak". Een dienst heeft één of meer kanalen,
elk met hun eigen openingsuren.

Elk kanaal heeft:

- een **naam** (label);
- een **type**: `Algemeen`, `Telefonisch` of `Na afspraak`;
- een **gewicht** (`weight`) dat de volgorde bepaalt (laagste eerst).

Verwijderde kanalen worden soft-deleted en ook uit de Linked Data verwijderd.
