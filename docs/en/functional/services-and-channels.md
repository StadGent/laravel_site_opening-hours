# Services and channels

## Services

A **service** is an organisation or location whose opening hours can be
managed, for example "Dienst Burgerzaken" or a swimming pool.

Services are not created in the platform itself. They are fetched from the
city's Linked Data (`openinghours:fetch-services`, see
[Integrations](../technical/integrations.md#fetching-services-from-lod)). Every
service has a **source** (`source`):

| Source | Origin | Opening hours managed in |
| --- | --- | --- |
| `vesta` | Business partners in VESTA with "Publiceren in LOD" checked | The opening hours platform (and pushed to VESTA) |
| `recreatex` | Sports venues from RecreateX | RecreateX (imported, not editable in the UI) |
| `publicToilets` | Public toilets (GIS) | The opening hours platform |
| `crm` | Contacts from the Visit Gent CRM | The opening hours platform |

!!! note
    Existing services are updated on a new fetch based on source + identifier.
    Removing or renaming a service happens in the source (VESTA, RecreateX,
    ...), not in the opening hours platform.

### Active and inactive (draft)

A new service is a **draft** (inactive) by default. A draft service is not
visible in the public API and is not written to VESTA. Only an Admin can
activate or deactivate a service.

When a VESTA service is deactivated, its opening hours in VESTA are cleared.

## Channels

A **channel** is a way to reach a service, for example "Loket" (counter),
"Telefonisch" (phone) or "Na afspraak" (by appointment). A service has one or
more channels, each with its own opening hours.

Every channel has:

- a **name** (label);
- a **type**: `Algemeen`, `Telefonisch` or `Na afspraak`;
- a **weight** (`weight`) that sets the order (lowest first).

Deleted channels are soft-deleted and also removed from the Linked Data.
