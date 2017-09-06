export const VERSION_YEARS = 2

const startYear = new Date().getFullYear()
const untilYear = startYear + VERSION_YEARS

const defaultStart = startYear + '-01-01T00:00:00'
const defaultEnd = startYear + '-01-02T00:00:00'
const defaultUntil = (untilYear - 1) + '-12-31'

const firstEventStart = startYear + '-01-01T09:00:00'
const firstEventEnd = startYear + '-01-01T17:00:00'

export function createFirstEvent(version) {
  return {
    start_date: version.start_date + 'T09:00:00',
    end_date: version.start_date + 'T17:00:00',
    until: version.end_date + 'T00:00:00',
    rrule: 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
    label: '1'
  }
}

export function createEvent({ label, start_date, end_date, rrule, until }) {
  return {
    start_date: (start_date || new Date()).toJSON().slice(0, 11) + '09:00:00',
    end_date: (start_date || new Date()).toJSON().slice(0, 11) + '17:00:00',
    until: (until || new Date(start_date.valueOf() + 36e5 * 24)).toJSON().slice(0, 11) + '00:00:00',
    rrule: rrule || 'FREQ=DAILY',
    label: (label || '1').toString()
  }
}

export function createFirstCalendar(version) {
  return {
    closinghours: false,
    layer: 0,
    label: 'Normale uren',
    priority: 0,
    events: [createFirstEvent(version)]
  }
}

export function createCalendar(layer, { start_date }) {
  return {
    closinghours: true,
    layer: layer,
    label: 'Uitzondering',
    priority: -layer,
    events: [createEvent({
      start_date,
      label: '1'
    })]
  }
}

export function createVersion() {
  return {
    active: true,
    start_date: defaultStart.slice(0, 10),
    end_date: defaultUntil.slice(0, 10),
    priority: 0,
    label: 'Openingsuren ' + startYear + ' tot en met ' + (untilYear - 1),
    calendars: []
  }
}

export function createChannel() {
  return {
    label: 'Nieuw kanaal',
    openinghours: [createVersion()]
  }
}

export const presets = [
  // All these events take 1 day and repeat yearly
  { start_date: '2016-01-01', rrule: 'FREQ=YEARLY', label: 'Nieuwjaarsdag' },
  { start_date: '2016-02-14', rrule: 'FREQ=YEARLY', label: 'Valentijn' },
  { start_date: '2016-05-01', rrule: 'FREQ=YEARLY', label: 'Feest vd Arbeid' },
  { start_date: '2016-07-11', rrule: 'FREQ=YEARLY', label: 'Feest Vlaamse Gemeenschap' },
  { start_date: '2016-07-21', rrule: 'FREQ=YEARLY', label: 'Nationale Feestdag' },
  { start_date: '2016-08-15', rrule: 'FREQ=YEARLY', label: 'OLV Hemelvaart' },
  { start_date: '2016-09-27', rrule: 'FREQ=YEARLY', label: 'Feest Federatie Wallonie-Brussel' },
  { start_date: '2016-10-31', rrule: 'FREQ=YEARLY', label: 'Halloween' },
  { start_date: '2016-11-01', rrule: 'FREQ=YEARLY', label: 'Allerheiligen' },
  { start_date: '2016-11-02', rrule: 'FREQ=YEARLY', label: 'Allerzielen' },
  { start_date: '2016-11-11', rrule: 'FREQ=YEARLY', label: 'Wapenstilstand W.O. I' },
  { start_date: '2016-11-15', rrule: 'FREQ=YEARLY', label: 'Feestdag van de Duitstalige Gemeenschap' },
  { start_date: '2016-11-15', rrule: 'FREQ=YEARLY', label: 'Koningsdag' },
  { start_date: '2016-12-25', rrule: 'FREQ=YEARLY', label: 'Kerstmis' },
  { start_date: '2016-12-26', rrule: 'FREQ=YEARLY', label: 'Tweede Kerstdag' },

  // Ended means the event is over at that date. `until` is one day before that
  { start_date: '2017-02-27', ended: '2017-03-06', label: 'Krokusvakantie 2017' },
  { start_date: '2017-03-26', ended: '2017-03-27', label: 'Zomeruur 2017' },
  { start_date: '2017-04-03', ended: '2017-04-18', label: 'Paasvakantie 2017' },
  { start_date: '2017-04-16', ended: '2017-04-17', label: 'Pasen 2017' },
  { start_date: '2017-04-17', ended: '2017-04-18', label: 'Paasmaandag 2017' },
  { start_date: '2017-04-20', ended: '2017-04-21', label: 'Secretaressedag 2017' },
  { start_date: '2017-05-14', ended: '2017-05-15', label: 'Moederdag 2017' },
  { start_date: '2017-05-25', ended: '2017-05-26', label: 'Onze Lieve Heer Hemelvaart 2017' },
  { start_date: '2017-05-25', ended: '2017-05-27', label: 'Hemelvaartvakantie 2017' },
  { start_date: '2017-06-04', ended: '2017-06-05', label: 'Pinksteren (Pinksterdag) 2017' },
  { start_date: '2017-06-05', ended: '2017-06-06', label: 'Pinkstermaandag 2017' },
  { start_date: '2017-06-11', ended: '2017-06-12', label: 'Vaderdag 2017' },
  { start_date: '2017-07-01', ended: '2017-09-01', label: 'Zomervakantie 2017' },
  { start_date: '2017-10-29', ended: '2017-10-30', label: 'Winteruur 2017' },
  { start_date: '2017-10-30', ended: '2017-11-06', label: 'Herfstvakantie 2017' },
  { start_date: '2017-12-25', ended: '2018-01-08', label: 'Kerstvakantie 2017' },
  { start_date: '2018-02-12', ended: '2018-02-19', label: 'Krokusvakantie 2018' },
  { start_date: '2018-04-02', ended: '2018-04-03', label: 'Paasmaandag 2018' },
  { start_date: '2018-04-02', ended: '2018-04-16', label: 'Paasvakantie 2018' },
  { start_date: '2018-05-10', ended: '2018-05-11', label: 'Onze Lieve Heer Hemelvaart 2018' },
  { start_date: '2018-05-10', ended: '2018-05-14', label: 'Hemelvaartvakantie 2018' },
  { start_date: '2018-05-20', ended: '2018-05-21', label: 'Pinksteren (Pinksterdag) 2018' },
  { start_date: '2018-05-21', ended: '2018-05-22', label: 'Pinkstermaandag 2018' },
  { start_date: '2018-07-01', ended: '2018-09-01', label: 'Zomervakantie 2018' },
  { start_date: '2018-10-29', ended: '2018-11-05', label: 'Herfstvakantie 2018' },
  { start_date: '2018-12-24', ended: '2019-01-07', label: 'Kerstvakantie 2018' },
]
