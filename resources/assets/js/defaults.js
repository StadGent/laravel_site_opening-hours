export function createEvent(label) {
  return {
    start_date: new Date().toJSON().slice(0, 11) + '00:00:00',
    end_date: new Date(new Date() + 36e5 * 24).toJSON().slice(0, 11) + '00:00:00',
    until: new Date(new Date() + 36e5 * 24).toJSON().slice(0, 11) + '00:00:00',
    rrule: 'FREQ=DAILY',
    label: label || '1'
  }
}

export function createCalendar(layer) {
  return {
    closinghours: true,
    layer: layer,
    label: layer,
    priority: -layer,
    events: [createEvent()]
  }
}
