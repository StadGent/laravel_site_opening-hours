<template>
  <div>
    <div class="calendar" :class="{'calendar-topview':true}"></div>
  </div>
</template>

<script>
var currentYear = new Date().getFullYear();

var redDateTime = new Date(currentYear, 2, 13).getTime();
var circleDateTime = new Date(currentYear, 1, 20).getTime();
var borderDateTime = new Date(currentYear, 0, 12).getTime();
    
function customDayRenderer(element, date) {

}
function last(arr) {
  return arr[arr.length - 1]
}

function customDataSourceRenderer (elem, date, events) {
  elem.parent().addClass('layer');
  elem.parent().addClass('layer-' + last(events).layer);

}

function toDate (str) {
  if (str && str.toJSON) {
    return toDate(str.toJSON())
  }
  if (typeof str !== 'string') {
    console.log(typeof str)
    return str
  }
  return new Date(str.slice(0, 4), parseInt(str.slice(5, 7), 10) - 1, parseInt(str.slice(8, 10), 10))
}

function expandEvent (e, layer) {
  if (typeof e.rrule !== 'string' || !e.rrule) {
    return {
      layer: layer,
      startDate: toDate(e.start_date),
      endDate: toDate(e.end_date) || toDate(e.start_date)
    }
  }
  console.log('rrule', e)
  return rrulestr(e.rrule).all().map(start => ({
    layer: layer,
    startDate: toDate(start),
    endDate: toDate(start)
  }))
}

export default {
  props: ['oh'],
  computed: {
    recurring () {
      return this.oh.calendars.m
    },
    allEvents () {
      if (!this.oh || !this.oh.calendars) {
        return 'meh'
      }
      return this.oh.calendars.reduce(
        (list, c, i) => list.concat(
          c.events.reduce(
            (evts, e) => evts.concat(
              expandEvent(e, i)
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
      minDate: '2016-01-01',
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
