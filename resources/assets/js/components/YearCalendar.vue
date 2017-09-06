<template>
  <div>
    <div class="calendar" :class="{'calendar-topview':true}"></div>
  </div>
</template>

<script>
import { _throttle, Hub, toTime, dateAfter } from '../lib.js'
import { rruleToStarts, keepRuleWithin, toDate, toDatetime, toIcsDate, toIcsDatetime } from '../util/rrule-helpers.js'

const currentYear = new Date().getFullYear();

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
    return {
      layer: layer,
      startDate: start,
      endDate: end,
      hours: hours
    }
  })
  return []
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
    versionStartYear () {
      return parseInt(this.versionStart.slice(0, 4))
    },
    versionEndYear () {
      return parseInt(this.versionEnd.slice(0, 4))
    },
    recurring () {
      return this.oh.calendars.m
    },
    allEvents () {
      if (!this.oh || !this.oh.calendars) {
        return ''
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
        dataSource: this.allEvents,
        language: 'nl',
        maxDate: toDate(this.versionEnd),
        minDate: toDate(this.versionStart),
        renderEnd: this.renderEnd,
        startYear: Math.min(Math.max(ls('startYear') || 0, this.versionStartYear), this.versionEndYear),
        style: 'custom'
      })
      window.fadeInTime = 0
    }, 500, { leading: true }),
    renderEnd (evt) {
      ls('startYear', evt.currentYear)
      setTimeout(() => {
        $('.layer>.day-content').tooltip({
          container: '.version-preview'
        })
      }, 300)
    },
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
