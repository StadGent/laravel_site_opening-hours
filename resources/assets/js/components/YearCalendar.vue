<template>
  <div>
    <div class="calendar" :class="{'calendar-topview':true}"></div>
  </div>
</template>

<script>
const currentYear = new Date().getFullYear();

function toDate (str) {
  if (str && str.toJSON) {
    return toDate(str.toJSON())
  }
  if (typeof str === 'number') {
    return new Date(str)
  }
  if (typeof str !== 'string') {
    console.warn('Unexpected type in toDate', typeof str)
    return str
  }
  return new Date(str.slice(0, 4), parseInt(str.slice(5, 7), 10) - 1, parseInt(str.slice(8, 10), 10))
}

function toIcsDate (str) {
  let obj = toDate(str)
  if (!obj) {
    obj = new Date()
  }
  str = obj.toJSON()
  return str.slice(0, 4) + str.slice(5, 7) + str.slice(8, 10) + 'T10' + str.slice(14, 16) + str.slice(17, 19) + 'Z'
}

function dateAfter (date) {
  return new Date(date + 36e5 * 24)
}
function expandEvent (e, layer, dtstart, until) {
  if (typeof e.rrule !== 'string' || !e.rrule) {
    return {
      layer: layer,
      startDate: toDate(e.start_date),
      endDate: toDate(e.end_date) || toDate(e.start_date)
    }
  }
  try {
    keepRuleWithin(e, dtstart, until)
    return rrulestr(e.rrule).all().map(start => {
      return {
        layer: layer,
        startDate: toDate(start),
        endDate: dateAfter(toDate(start))
      }
    })
  } catch (e) {
    console.error(e)
  }
  return []
}

function keepRuleWithin (e, dtstart, until) {
  if (e.rrule.indexOf(';DTSTART=') === -1) {
    e.rrule += ';DTSTART=' + toIcsDate(dtstart)
  }
  if (e.rrule.indexOf(';UNTIL=') === -1) {
    e.rrule += ';UNTIL=' + toIcsDate(until)
  }
}
    
function customDayRenderer(element, date) {

}

function customDataSourceRenderer (elem, date, events) {
  const parent = elem.parent()
  parent.addClass('layer');
  elem.parent().addClass('layer-' + events[events.length - 1].layer);
}

export default {
  props: ['oh'],
  computed: {
    versionStart () {
      return this.oh.date_start || currentYear + '-01-01'
    },
    versionEnd () {
      return this.oh.date_end || '2018-01-01'
    },
    recurring () {
      return this.oh.calendars.m
    },
    allEvents () {
      if (!this.oh || !this.oh.calendars) {
        return 'meh'
      }
      return this.oh.calendars.reduce(
        (list, c) => list.concat(
          c.events.reduce(
            (evts, e) => evts.concat(
              expandEvent(e, c.layer, this.versionStart, this.versionEnd)
            ),
         [])
       ),
      [])
    }
  },
  methods: {
    render () {
      if (!this.elem) {
        return console.warn('Not yet mounted')
      }
      this.elem.setDataSource(this.allEvents)
    }
  },
  mounted () {
    this.elem = $(this.$el).find('.calendar').calendar({
      customDayRenderer,
      customDataSourceRenderer,
      language: 'nl',
      // displayWeekNumber: true,
      minDate: toDate('2016-01-01'),
      maxDate: toDate('2018-01-01'),
      style: 'custom',
      dataSource: [{
        id: 1,
        startDate: new Date(currentYear, 4, 1),
        endDate: new Date(currentYear, 4, 1),
        name: 'blub'
      }, {
        id: 2,
        startDate: new Date(currentYear, 2, 16),
        endDate: new Date(currentYear, 2, 16),
        name: 'blub'
      }, {
        id: 3,
        startDate: new Date(currentYear, 4, 28),
        endDate: new Date(currentYear, 4, 28),
        name: 'blub'
      }]
    })
  },
  watch: {
    allEvents () {
      this.render()
    }
  }
}
</script>
