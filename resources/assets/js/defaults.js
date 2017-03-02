const defaultStart = '2016-01-01T00:00:00'
const defaultEnd = '2016-01-02T00:00:00'
const defaultUntil = '2021-01-01'

const firstEventStart = '2016-01-01T09:00:00'
const firstEventEnd = '2016-01-01T17:00:00'

export function createFirstEvent() {
  return {
    start_date: firstEventStart,
    end_date: firstEventEnd,
    until: defaultUntil,
    rrule: 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
    label: '1'
  }
}

export function createEvent(label) {
  return {
    start_date: new Date().toJSON().slice(0, 11) + '00:00:00',
    end_date: new Date(new Date().valueOf() + 36e5 * 24).toJSON().slice(0, 11) + '00:00:00',
    until: new Date(new Date().valueOf() + 36e5 * 24).toJSON().slice(0, 11) + '00:00:00',
    rrule: 'FREQ=DAILY',
    label: label || '1'
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

export function createCalendar(layer) {
  return {
    closinghours: true,
    layer: layer,
    label: 'Uitzondering',
    priority: -layer,
    events: [createEvent('1')]
  }
}

export function createVersion() {
  return {
    active: true,
    start_date: defaultStart,
    end_date: defaultUntil,
    priority: 0,
    label: 'Openingsuren 2016 tot en met 2020',
    calendars: []
  }
}

export function createChannel() {
  return {
    label: 'Nieuw kanaal',
    openinghours: [createVersion()]
  }
}
