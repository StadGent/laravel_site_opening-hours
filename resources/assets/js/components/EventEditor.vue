<template>
  <div @change="sync">
    <div class="row" :class="{ 'has-error text-danger': !isUntilValid }" v-if="event.rrule && $parent.cal.layer" style="margin-bottom:15px;">
      <div :class="'col-xs-' + (closinghours ? 5 : 6)">
        <label class="control-label">{{ closinghours ? 'Gesloten' : 'Geldig' }} {{ options.freq==RRule.DAILY ? 'van' : 'op' }}</label>
        <pikaday class="form-control" v-model="eventStartDate" :options="pikadayStart" />
      </div>
      <div :class="'col-xs-' + (closinghours ? 5 : 6)" v-if="eventUntilSet||show.endDate">
        <label class="control-label">tot en met</label>
        <pikaday class="form-control inp-until" v-model="eventUntil" :options="pikadayUntil" />
      </div>
      <div :class="'col-xs-' + (closinghours ? 5 : 6)" v-else>
        <label class="control-label"><a href="#" @click.prevent="show.endDate=1">tot en met...</a></label>
      </div>
      <div class="col-xs-2" v-if="closinghours">
        <div class="close close--col" style="padding-top: 30px;" @click="$emit('rm')">&times;</div>
      </div>
    </div>


    <div class="form-horizontal" v-if="event.rrule">
      <!-- Choose the period -->
      <div class="form-group" v-if="!prevEventSameLabel && $parent.cal.layer">
        <label class="col-xs-3 control-label">Regelmaat</label>
        <div class="col-xs-4" @change="setFreq">
          <select v-model="options.freq" class="form-control">
            <option :value="RRule.YEARLY">Jaarlijks</option>
            <option :value="RRule.MONTHLY">Maandelijks</option>
            <option :value="RRule.WEEKLY">Wekelijks</option>
            <option :value="RRule.DAILY">Dagelijks</option>
          </select>
        </div>
        <div class="col-xs-5" v-if="options.freq==RRule.MONTHLY">
          <select v-model="options.interval" class="form-control">
            <option :value="null" v-if="options.interval!=1">elke maand</option>
            <option :value="1" v-if="options.interval==1">elke maand</option>
            <option :value="2">tweemaandelijks</option>
            <option :value="3">elk kwartaal</option>
            <option :value="4">viermaandelijks</option>
          </select>
        </div>
        <div class="col-xs-5" v-if="options.freq==RRule.WEEKLY">
          <select v-model="options.interval" class="form-control">
            <option :value="null" v-if="options.interval!=1">elke week</option>
            <option :value="1" v-if="options.interval==1">elke week</option>
            <option :value="2">tweewekelijks</option>
            <option :value="3">driewekelijks</option>
            <option :value="4">vierwekelijks</option>
          </select>
        </div>
        <div class="col-xs-5" v-if="options.freq==RRule.DAILY">
          <select v-model="options.interval" class="form-control">
            <option :value="1">elke dag</option>
            <option :value="2">om de dag</option>
            <option :value="3">om de drie dagen</option>
            <option :value="4">om de vier dagen</option>
            <option :value="5">om de vijf dagen</option>
            <option :value="6">om de zes dagen</option>
          </select>
        </div>
      </div>

      <!-- Yearly -->
      <div v-if="options.freq==RRule.YEARLY">

        <!-- bymonthday + bymonth -->
        <div class="form-group" @change="byMonthDay">
          <label class="col-xs-3 control-label">
            <input type="radio" :name="event.start_date" :checked="!options.byweekday" class="pull-left">
            op
          </label>
          <div class="col-xs-9" style="padding-top: 8px">
            {{ eventStartDayMonth }}
          </div>
        </div>
        <!-- bysetpos + byweekday + bymonth -->
        <div class="form-group">
          <label class="col-xs-3 control-label" @change="byWeekDay">
            <input type="radio" :name="event.start_date" :checked="!options.bymonthday" class="pull-left">
            op de
          </label>
          <div class="col-xs-3" @change="byWeekDay">
            <select v-model="options.bysetpos" class="form-control">
              <option :value="null">-</option>
              <option value="1" >eerste</option>
              <option value="2">tweede</option>
              <option value="3">derde</option>
              <option value="-2">voorlaatste</option>
              <option value="-1">laatste</option>
            </select>
          </div>
          <div class="col-xs-3">
            <select v-model="options.byweekday" class="form-control">
              <option :value="null">-</option>
              <option value="0">maandag</option>
              <option value="1">dinsdag</option>
              <option value="2">woensdag</option>
              <option value="3">donderdag</option>
              <option value="4">vrijdag</option>
              <option value="5">zaterdag</option>
              <option value="6">zondag</option>
              <option value="0,1,2,3,4,5,6">dag</option>
              <option value="0,1,2,3,4">weekdag</option>
              <option value="5,6">weekend</option>
            </select>
          </div>
          <div class="col-xs-3">
            <select v-model="options.bymonth" class="form-control">
              <option value="1">januari</option>
              <option value="2">februari</option>
              <option value="3">maart</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Monthly -->
      <div v-else-if="options.freq==RRule.MONTHLY">
      <div>
        <!-- bymonthday + bymonth -->
        <div class="form-group" @change="byMonthDay">
          <label class="col-xs-3 control-label">
            <input type="radio" :name="event.start_date" :checked="!options.byweekday" class="pull-left">
            op dag
          </label>
          <div class="col-xs-9" style="padding-top: 8px">
            {{ eventStartDayMonth.slice(0, 2) }}
          </div>
        </div>
        <!-- bysetpos + byweekday + bymonth -->
        <div class="form-group">
          <label class="col-xs-3 control-label" @change="byWeekDay">
            <input type="radio" :name="event.start_date" :checked="!options.bymonthday" class="pull-left">
            op de
          </label>
          <div class="col-xs-3">
            <select v-model="options.bysetpos" class="form-control">
              <option :value="null">-</option>
              <option value="0" >-</option>
              <option value="1" >eerste</option>
              <option value="2">tweede</option>
              <option value="3">derde</option>
              <option value="-2">voorlaatste</option>
              <option value="-1">laatste</option>
            </select>
          </div>
          <div class="col-xs-3">
            <select v-model="options.byweekday" class="form-control">
              <option :value="null">-</option>
              <option value="0">maandag</option>
              <option value="1">dinsdag</option>
              <option value="2">woensdag</option>
              <option value="3">donderdag</option>
              <option value="4">vrijdag</option>
              <option value="5">zaterdag</option>
              <option value="6">zondag</option>
              <option value="0,1,2,3,4,5,6">dag</option>
              <option value="0,1,2,3,4">weekdag</option>
              <option value="5,6">weekend</option>
            </select>
          </div>
          <div class="col-xs-3">
            <select v-model="options.bymonth" class="form-control">
              <option value="1">januari</option>
              <option value="2">februari</option>
              <option value="3">maart</option>
            </select>
          </div>
        </div></div>
      </div>

      <!-- Weekly -->
      <div v-else-if="options.freq==RRule.WEEKLY">
        <div class="form-inline-always" :class="{ 'has-error text-danger': eventStartTime > eventEndTime }">
          <multi-day-select :options="fullDays" :parent="options" prop="byweekday" @change="toggleWeekday"></multi-day-select>
          <span v-if="!closinghours">
            van
            <input type="text" class="form-control control-time inp-startTime" v-model.lazy="eventStartTime" placeholder="_ _ : _ _">
            tot
            <input type="text" class="form-control control-time inp-endTime" v-model.lazy="eventEndTime" placeholder="_ _ : _ _">
          </span>
          <div class="close" @click="$emit('rm')">&times;</div>
        </div>
        <div v-if="!nextEventSameLabel">
          <button type="button" class="btn btn-link" @click="$emit('add-event', prop, event)"><b>+</b> Voeg meer dagen toe</button>
        </div>
      </div>

      <!-- Dailu -->
      <div v-if="options.freq!=RRule.WEEKLY&&!closinghours">
        <div class="form-inline-always text-center" :class="{ 'has-error text-danger': eventStartTime > eventEndTime }">
          van
          <input type="text" class="form-control control-time inp-startTime" v-model.lazy="eventStartTime" placeholder="_ _ : _ _">
          tot
          <input type="text" class="form-control control-time inp-endTime" v-model.lazy="eventEndTime" placeholder="_ _ : _ _">
          <div class="close" @click="$emit('rm')">&times;</div>
        </div>
      </div>
    </div>

    <!-- <pre>{{event}}</pre> -->

    <div class="row" v-if="!nextEventSameLabel">
      <br>
    </div>

    <!-- Single event: not recurring -->
    <div v-if="!event.rrule">
      gewone lijst van dagen
    </div>
  </div>
</template>


<script>
import MultiDaySelect from '../components/MultiDaySelect.vue'
import Pikaday from '../components/Pikaday.vue'

import { cleanEmpty, toTime, toDatetime, dateAfter } from '../lib.js'
import { stringToHM } from '../util/stringToHM'

const fullDays = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag']
const fullMonths = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december']

// Returns 10 character ISO string if date is valid
// or empty string if not
function toDateString (date, otherwise) {
  return !date ? toDateString(otherwise, new Date()) : typeof date === 'string' ? date.slice(0, 10) :
    date.toJSON ? date.toJSON().slice(0, 10) : toDateString(otherwise, new Date())
}

export default {
  name: 'event-editor',
  props: ['parent', 'prop'],
  data () {
    return {
      // options: {}
      days: ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'],
      show: {
        endDate: 0
      },
      fullDays
    }
  },
  computed: {
    closinghours () {
      return this.$parent.cal.closinghours
    },
    // If the label of the previous event is the same, you can not choose the period
    prevEventDivider () {
      return !this.parent[this.prop - 1] || (this.parent[this.prop - 1] || {}).label != this.event.label
    },
    // If the label of the previous event is the same, you can not choose the period
    prevEventSameLabel () {
      return this.event.label && (this.parent[this.prop - 1] || {}).label == this.event.label
    },
    // If the label of the next event is not the same, you can add weekly hours
    nextEventSameLabel () {
      return this.event.label && (this.parent[this.prop + 1] || {}).label == this.event.label
    },
    // The current event being edited
    event () {
      const event = this.parent[this.prop] || {}

      if (!event.until) {
        this.$set(event, 'until', this.versionEndDate)
      }
      if (event.start_date < this.versionStartDate) {
        this.$set(event, 'start_date', this.versionStartDate)
      }
      if (event.until > this.versionEndDate) {
        this.$set(event, 'until', this.versionEndDate)
      }

      return event
    },
    eventStartDayMonth () {
      return toDatetime(this.event.start_date).getDate() + ' ' + fullMonths[toDatetime(this.event.start_date).getMonth()]
    },
    eventStartDate: {
      get () {
        return (this.event.start_date || '').slice(0, 10)
      },
      set (v) {
        const endDate = toDatetime(this.event.end_date)
        const startDate = toDatetime(this.event.start_date)
        const duration = endDate - startDate
        // console.debug('duration', duration)

        // Keep duration the same if it's shorter than 2 days
        if (!v) {
          return console.warn('did not select date')
        }
        this.event.start_date = v + ((this.event.start_date || '').slice(10, 19) || 'T00:00:00')
        if (duration < 36e5 * 48) {
          // Force end_date to be on same date as start_date
          this.event.end_date = this.event.start_date.slice(0, 11) + this.event.end_date.slice(11, 19)
          // console.debug('enddate', this.event.end_date)
        }

        if (this.options.bymonthday) {
          this.options.bymonthday = toDatetime(this.event.start_date).getDate()
        }
        if (!this.isUntilValid) {
          this.warnTime('.inp-until')
        }
      }
    },
    eventEndDate: {
      get () {
        return (this.event.end_date || '').slice(0, 10)
      },
      set () {
        // Force end_date to be on same date as start_date
        this.event.end_date = v + ((this.event.start_date || '').slice(10, 19) || 'T00:00:00')
      }
    },
    eventStartTime: {
      get () {
        return toTime(this.event.start_date)
      },
      set (v) {
        v = stringToHM(v)
        if (!/\d\d:\d\d/.test(v)) {
          return
        }
        if (this.eventEndTime === '00:00') {
          this.eventEndTime = '23:59'
        }
        if (this.eventEndTime < v) {
          this.warnTime('.inp-startTime')
        }
        this.event.start_date = this.event.start_date.slice(0, 11) + v + ':00'
      }
    },
    eventEndTime: {
      get () {
        return toTime(this.event.end_date)
      },
      set (v) {
        v = stringToHM(v)
        if (!/\d\d:\d\d/.test(v)) {
          return
        }
        if (v === '00:00') {
          v = '23:59'
        }
        if (this.eventStartTime > v) {
          this.warnTime('.inp-endTime')
        }
        // Force end_date to be on same date as start_date
        this.event.end_date = this.event.start_date.slice(0, 11) + v + ':00'
      }
    },
    eventUntilSet () {
      // console.debug('check until')
      return this.eventUntil !== this.versionEndDate
    },
    eventUntil: {
      get () {
        // console.debug('until', this.event.until, this.versionEndDate)
        return toDatetime(this.event.until || this.versionEndDate).toJSON().slice(0, 10)
      },
      set (v) {
        // console.debug('set until ', v)
        this.event.until = new Date(Date.parse(v)).toJSON().slice(0, 10)
        if (!this.isUntilValid) {
          this.warnTime('.inp-until')
        }
      }
    },
    pikadayStart () {
      return {
        minDate: toDatetime(this.$root.routeVersion.start_date),
        maxDate: toDatetime(this.$root.routeVersion.end_date)
      }
    },
    pikadayUntil () {
      return {
        minDate: toDatetime(this.$root.routeVersion.start_date),
        maxDate: toDatetime(this.$root.routeVersion.end_date)
      }
    },
    hasDates () {
      return typeof this.options.freq === 'number'
    },
    // Parsed recurring rule of first event
    options() {
      if (!this.event.rrule) {
        return {}
      }
      var opts = RRule.parseString(this.event.rrule) || {}
      if(opts.byweekday) {
        opts.byweekday = opts.byweekday.map(d => typeof d === 'number' ? d : d.weekday)
      }
      opts.wkst = null
      return opts
    },
    versionEndDate() {
      // console.debug(toDateString(this.$parent.$parent.version.end_date))
      return toDateString(this.$parent.$parent.version.end_date)
    },
    // RRule object based on options
    rrule () {
      return new RRule(this.options)
    },
    rruleString () {
      return this.rrule.toString()
    },
    rruleAll () {
      return this.rrule.all()
    },
    isUntilValid () {
      return this.event.start_date.slice(0, 10) < this.event.until.slice(0, 10)
    }
  },
  methods: {
    warnTime (selector) {
      const elem = $(this.$el).find(selector)
      elem.tooltip({
        title: 'Het begintijdstip moet vroeger vallen dan het eindtijdstip.',
        toggle: 'manual'
      })
      setTimeout(() => {
        elem.tooltip('show')
      }, 300)
      setTimeout(() => {
        elem.tooltip('hide').tooltip('destroy')
      }, 3000)
    },
    setFreq () {
      // console.debug('set freq')
      if (this.options.freq === RRule.MONTHLY) {
        this.byMonthDay()
      }
    },
    byMonthDay () {
      // console.debug('monthday')
      delete this.options.byweekday
      this.options.bymonthday = toDatetime(this.event.start_date).getDate()
    },
    byWeekDay () {
      // console.debug('weekday')
      delete this.options.bymonthday
      this.options.byweekday = this.options.byweekday || 0
      this.options.bysetpos = this.options.bysetpos && this.options.bysetpos < 8 ? this.options.bysetpos : 1
    },
    toggleRecurring () {
      const rule = this.event.rrule
      this.event.rrule = this.event.rrule ? '' : this.event.oldrrule || 'FREQ=WEEKLY'
      this.event.oldrrule = rule
    },
    toggleWeekday (day) {
      day = parseInt(day, 10)
      // console.debug(day)
      if (!this.options.byweekday) {
        this.options.byweekday = []
      }
      const index = this.options.byweekday.indexOf(day)
      if (index !== -1) {
        this.options.byweekday.splice(index, 1)
      } else {
        this.options.byweekday.push(day)
        this.options.byweekday.sort((a, b) => a - b)
      }
      if (!this.options.byweekday.length) {
        this.options.byweekday = []
      }
      // console.debug(this.options.byweekday)
      this.sync()
    },
    sync () {
      // console.log('sync', this.options.freq, new RRule(opts).toString())
      setTimeout(() => {
        delete this.options.byhour
        delete this.options.bysecond
        delete this.options.byminute
        if (this.options.interval < 2) {
          delete this.options.interval
        }
        if (!this.options.byweekday || !this.options.byweekday.length) {
          delete this.options.byweekday
        }
        if (this.options.freq > 1) {
          delete this.options.bymonthday
        }
        const freq = this.options.freq
        const byweekday = this.options.byweekday
        this.options = cleanEmpty(this.options)
        this.options.byweekday = byweekday || []
        this.options.freq = freq || 0
        this.options.until = this.options.dtstart = new Date(Date.UTC(2016, 0, 1))
        let rule = new RRule(this.options).toString()
          .replace(';DTSTART=20160101T000000Z', '')
          .replace(';UNTIL=20160101T000000Z', '')
        this.$set(this.event, 'rrule', rule)
        // console.debug(rule)
      }, 100)
    }
  },
  created () {
    this.RRule = RRule || {}
  },
  components: {
    MultiDaySelect,
    Pikaday
  }
}
</script>
