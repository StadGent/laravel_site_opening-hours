<template>
  <div>
    <div class="calendar" :class="{'calendar-topview':true}"></div>
    <p style="text-align: right">
      <button class="btn btn-default" @click="printme">Print</button>
    </p>
  </div>
</template>

<script>
import { _throttle, Hub, toTime, dateAfter } from '../lib.js'

const currentYear = new Date().getFullYear();

// Human friendly duration
function toDuration (n) {
  n /= 1000 
  if (n < 100) {
    return n + ' seconds'
  }
  n /= 60 
  if (n < 100) {
    return n + ' minutes'
  }
  n /= 60 
  if (n < 100) {
    return n + ' hours'
  }
  n /= 24 
  if (n < 100) {
    return n + ' days'
  }
}

// Cast strings, ints and dates to date only
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

// Cast strings, ints and dates to datetime
function toDatetime (str) {
  if (str && str.toJSON) {
    return str
  }
  if (typeof str === 'number') {
    return new Date(str)
  }
  if (typeof str !== 'string') {
    console.warn('Unexpected type in toDatetime', typeof str)
    return str
  }
  return new Date(Date.parse(str))
}

// Transform a date to ICS format
function toIcsDate (str) {
  let obj = toDate(str)
  if (!obj) {
    obj = new Date()
  }
  str = obj.toJSON()
  return str.slice(0, 4) + str.slice(5, 7) + str.slice(8, 10) + 'T020000Z'
}

// Transform a datetime to ICS format
function toIcsDatetime (str) {
  let obj = toDate(str)
  if (!obj) {
    obj = new Date()
  }
  str = obj.toJSON()
  return str.slice(0, 4) + str.slice(5, 7) + str.slice(8, 13) + str.slice(14, 16) + str.slice(17, 19) + 'Z'
}

// RRule can be expensive to calculate
const rruleCache = {}
function rruleToStarts(rule) {
  if (rule.indexOf('undefined') > 0 || rule.indexOf('BYMINUTE') > 0 || rule.indexOf('BYSECOND') > 0) {
    return console.error('Bad rules!', rule) || []
  }
  const cache = rruleCache[rule]
  return cache /* || console.debug('miss', rule) */ || (rruleCache[rule] = rrulestr(rule).all())
}

// Transform a vevent to an event that bootstrap-year can use
function expandEvent (e, layer, closinghours, dtstart, until) {
  const startDate = toDatetime(e.start_date)
  const endDate = toDatetime(e.end_date) 
  // Subtract 1000 (1 second) to avoid drawing on the next day
  const duration = ((endDate - startDate) || (36e5 * 24)) - 1000

  const hours = closinghours ? 'gesloten' : e.start_date.slice(11, 16) + ' - ' + e.end_date.slice(11, 16)

  // console.log(toDuration(endDate - startDate))
  if (typeof e.rrule !== 'string' || !e.rrule) {
    return {
      layer,
      startDate,
      endDate
    }
  }
  if (!e.rrule.includes('FREQ=')) {
    console.log('weird event', inert(e))
    return []
  }
  e.until = e.until || until
  // console.log(startDate, until)
  const limitedRule = keepRuleWithin(e)
  return rruleToStarts(limitedRule).map(start => {
    const end = dateAfter(start, duration)
    if (e.rrule.includes('WEEKLY')) {
      // console.log(e, start)
    }
    if (e.rrule.includes('DAILY')) {
      // console.log(duration, start.toISOString(), end.toISOString())
    }
    return {
      layer: layer,
      startDate: start,
      endDate: end,
      hours: hours
    }
  })
  return []
}

// Add dtstart and until
function keepRuleWithin (e) {
  let rule = e.rrule
  if (rule.indexOf('DTSTART=') === -1) {
    rule += ';DTSTART=' + toIcsDatetime(e.start_date)
  }
  if (rule.indexOf('UNTIL=') === -1) {
    rule += ';UNTIL=' + toIcsDatetime(e.until)
  }
  return rule
}

// Callback for every day that is currently visible
function customDayRenderer(elem, date) {
}

// Callback for every day that contains events
function customDataSourceRenderer (elem, date, events) {
  const parent = elem.parent()
  const layer = events[events.length - 1].layer
  const tooltip = events.filter(e => e.layer == layer).map(e => e.hours).join('\n')
  parent.addClass('layer').addClass('layer-' + layer)
  elem.attr('title', tooltip)
}

export default {
  props: ['oh'],
  computed: {
    versionStart () {
      return this.oh.start_date || (currentYear + '-01-01')
    },
    versionEnd () {
      return this.oh.end_date || '2018-01-01'
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
              expandEvent(e, c.layer, c.closinghours, this.versionStart, this.versionEnd)
            ),
         [])
       ),
      [])
    }
  },
  methods: {
    render: _throttle(function () {
      if (!this.$el) {
        return console.warn('Not yet mounted')
      }
      this.elem = $(this.$el).find('.calendar').calendar({
        customDataSourceRenderer,
        customDayRenderer,
        dataSource: this.allEvents,
        language: 'nl',
        startYear: this.versionStart.slice(0, 4),
        maxDate: toDate(this.versionEnd),
        minDate: toDate(this.versionStart),
        style: 'custom'
      })
      window.fadeInTime = 0
      setTimeout(() => {
        $('.layer>.day-content').tooltip({
          container: '.version-preview'
        })
      }, 300)
    }, 500, { leading: true }),
    printme () {
      Hub.$emit('printme')
    }
  },
  mounted () {
    window.fadeInTime = 1000
    this.render()
    // setTimeout(() => this.render(), 1000)
  },
  watch: {
    allEvents () {
      // Execute in timeout to avoid blocking
      setTimeout(() => {
        this.render()
      })
    }
  }
}
</script>
