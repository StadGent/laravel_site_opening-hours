# Managing opening hours

The opening hours of a channel are built in three levels:

```text
Channel
└── Version (valid from ... until ...)
    ├── Normale uren          (base calendar)
    ├── Uitzondering 1        (layer)
    └── Uitzondering 2        (layer)
        └── Events (recurrence rules with a time slot)
```

## Versions

A **version** is a set of opening hours with a validity period (start date and
end date), for example "Openingsuren 2027". A version is active when today
falls within that period.

A version can be copied. The calendars of the copy are not yet published, and
the events of the normal hours shift along to the new start date.

## Calendars (layers)

Every version has one **base calendar** ("Normale uren", normal hours), usually
with a weekly schedule. **Exceptions** can be added on top as extra layers, for
example "Closed on public holidays" or "Summer hours".

- An exception is a **closing calendar** by default: on the days it covers, the
  channel is closed all day. An exception can also contain opening hours (e.g.
  adjusted hours during summer).
- **The top layer wins.** When several calendars apply to the same day, only
  the highest layer counts. Layers can be reordered in the UI.
- An open exception can override a closed exception below it.
- Only **published** calendars count for the API, LOD and VESTA.

!!! note "Technical"
    The layer is stored as `priority`: `0` for the base calendar, `-1`, `-2`,
    ... for the exceptions. The lowest value wins (see `app/Models/Ical.php`).

## Events and recurrence rules

A calendar contains **events**: a time slot (start and end time) with a
recurrence rule in iCal format (`RRULE`, e.g. every Monday to Friday) and an
optional end date (`until`).

## Public holidays and school holidays (presets)

When creating an exception, predefined periods can be selected: public
holidays, school holidays, the Gentse Feesten and extra closing days of the City
of Ghent. They come from iCal files on stad.gent and are loaded with
`openinghours:fetch-od-holidays` (see [Operations](../technical/operations.md)).

## RecreateX services

The opening hours of sports venues (source `recreatex`) come from RecreateX and
cannot be edited in the UI. See
[Integrations](../technical/integrations.md#recreatex).

## Where do changes end up?

Every change to a version or calendar is automatically (through the queue):

- written to the **Linked Data**;
- for active versions of VESTA services, written to **VESTA** (a textual
  summary of the next 2 months).

The public API reads directly from the platform's database. Responses may be
cached for 15 minutes (`Cache-Control: max-age=900`).
