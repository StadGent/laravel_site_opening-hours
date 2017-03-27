const startYear = new Date().getFullYear()
const untilYear = startYear + 5

const defaultStart = startYear + '-01-01T00:00:00'
const defaultEnd = startYear + '-01-02T00:00:00'
const defaultUntil = untilYear + '-01-01'

const firstEventStart = startYear + '-01-01T09:00:00'
const firstEventEnd = startYear + '-01-01T17:00:00'

export function createFirstEvent() {
  return {
    start_date: firstEventStart,
    end_date: firstEventEnd,
    until: defaultUntil,
    rrule: 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
    label: '1'
  }
}

export function createEvent({ label, start_date, end_date }) {
  return {
    start_date: (start_date || new Date()).toJSON().slice(0, 11) + '00:00:00',
    end_date: (start_date || new Date()).toJSON().slice(0, 11) + '00:00:00',
    until: new Date(start_date.valueOf() + 36e5 * 24).toJSON().slice(0, 11) + '00:00:00',
    rrule: 'FREQ=DAILY',
    label: (label || '1').toString()
  }
}

export function createFirstCalendar() {
  return {
    closinghours: false,
    layer: 0,
    label: 'Normale uren',
    priority: 0,
    events: [createFirstEvent()]
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
    start_date: defaultStart,
    end_date: defaultUntil,
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
