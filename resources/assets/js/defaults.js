export const VERSION_YEARS = 2;

const startYear = new Date().getFullYear();
const untilYear = startYear + VERSION_YEARS;

const defaultStart = startYear + '-01-01T00:00:00';
const defaultEnd = startYear + '-01-02T00:00:00';
const defaultUntil = (untilYear - 1) + '-12-31';

const firstEventStart = startYear + '-01-01T09:00:00';
const firstEventEnd = startYear + '-01-01T17:00:00';

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
    start_date: (start_date || new Date()).toJSON().slice(0, 11) + '09:00:00Z',
    end_date: (start_date || new Date()).toJSON().slice(0, 11) + '17:00:00Z',
    until: (until || new Date(start_date.valueOf() + 36e5 * 24)).toJSON().slice(0, 11) + '00:00:00Z',
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

export function createCalendar(layer) {
  return {
    closinghours: true,
    layer: layer,
    label: 'Uitzondering',
    priority: -layer,
    events: []
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
