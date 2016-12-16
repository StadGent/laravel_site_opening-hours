<template>
  <div @change="sync">

    <div class="row" v-if="event.rrule && $parent.cal.layer" style="margin-bottom:15px;">
      <div class="col-xs-6">
        <label class="control-label">{{ closinghours ? 'Gesloten' : 'Geldig' }} {{ options.freq==RRule.DAILY ? 'van' : 'op' }}</label>
        <input type="date" class="form-control" v-model="eventStartDate" placeholder="van">
      </div>
      <div class="col-xs-6" v-if="eventUntilSet||show.endDate">
        <label class="control-label">tot en met</label>
        <input type="date" class="form-control" v-model="eventUntil" placeholder="van">
      </div>
      <div class="col-xs-6" v-else>
        <label class="control-label"><a href="#" @click.prevent="show.endDate=1">tot en met...</a></label>
      </div>
    </div>


    <div class="form-horizontal" v-if="event.rrule">
      <!-- Choose the period -->
      <div class="form-group" v-if="!prevEventSameLabel">
        <label class="col-xs-3 control-label">Regelmaat</label>
        <div class="col-xs-4">
          <select v-model="options.freq" class="form-control">
            <option :value="RRule.YEARLY">Jaarlijks</option>
            <option :value="RRule.MONTHLY">Maandelijks</option>
            <option :value="RRule.WEEKLY">Wekelijks</option>
            <option :value="RRule.DAILY">Dagelijks</option>
          </select>
        </div>
        <div class="col-xs-5" v-if="options.freq==RRule.MONTHLY">
          <select v-model="options.interval" class="form-control">
            <option :value="1">elke maand</option>
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
            <option :value="7">om de vijf dagen</option>
            <option :value="6">om de zes dagen</option>
          </select>
        </div>
      </div>

      <!-- Yearly -->
      <div v-if="options.freq==RRule.YEARLY">

        <!-- bymonthday + bymonth -->
        <div class="form-group" @change="byMonthDay">
          <label class="col-xs-3 control-label">
            <input type="radio" :name="event.start_date" :checked="!options.byweekday">
            op
          </label>
          <div class="col-xs-9" style="padding-top: 8px">
            {{ eventStartDayMonth }}
          </div>
        </div>
        <!-- bysetpos + byweekday + bymonth -->
        <div class="form-group">
          <label class="col-xs-3 control-label" @change="byWeekDay">
            <input type="radio" :name="event.start_date" :checked="!options.bymonthday">
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
              <option value="2">woesndag</option>
              <option value="3">donderdag</option>
              <option value="4">vrijdag</option>
              <option value="5">zaterdag</option>
              <option value="6">zondag</option>
              <option value="0,1,2,3,4,5,6" selected>dag</option>
              <option value="0,1,2,3,4">weekdag</option>
              <option value="5,6">weekend</option>
            </select>
          </div>
          <div class="col-xs-3">
            <select v-model="options.bymonth" class="form-control">
              <option value="1" selected>januari</option>
              <option value="2">februari</option>
              <option value="3">maart</option>
            </select>
          </div>
        </div>
      </div>

      <div v-if="options.freq==RRule.MONTHLY">
        month
      </div>
      <div v-if="options.freq==RRule.WEEKLY">
        <div class="form-inline-always">
          <multi-day-select :options="fullDays" :parent="options" prop="byweekday" @change="toggleWeekday"></multi-day-select>
          van
          <input type="text" class="form-control control-time" v-model="eventStart" placeholder="_ _ : _ _">
          tot
          <input type="text" class="form-control control-time" v-model="eventEnd" placeholder="_ _ : _ _">
          <div class="close">&times;</div>
        </div>
        <div v-if="!nextEventSameLabel">
          <button class="btn btn-link"><b>+</b> Voeg meer dagen toe</button>
        </div>
      </div>
      <div v-if="options.freq==RRule.DAILY">
        <div class="form-inline-always text-center">
          van
          <input type="text" class="form-control control-time" v-model="eventStart" placeholder="_ _ : _ _">
          tot
          <input type="text" class="form-control control-time" v-model="eventEnd" placeholder="_ _ : _ _">
        </div>
      </div>
    </div>

    <div class="row" v-if="!nextEventSameLabel">
      <hr>
    </div>

    <!-- Single event: not recurring -->
    <div v-if="!event.rrule">
      gewone lijst van dagen
    </div>
  </div>
</template>


<script>
import MultiDaySelect from '../components/MultiDaySelect.vue'
import { cleanEmpty, toTime, toDatetime, dateAfter } from '../lib.js'

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
      const event = this.parent[this.prop]
      if (!event.until) {
        this.$set(event, 'until', this.versionEndDate)
      }
      return event || {}
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
        let startDate = toDatetime(this.event.start_date)
        const duration = endDate - startDate
        console.log(duration)
        // Keep duration the same if it's shorter than 2 days
        if (!v) {
          return console.warn('did not select date')
        }
        this.event.start_date = v + ((this.event.start_date || '').slice(10, 19) || 'T00:00:00')
        if (duration < 36e5 * 48) {
          this.event.end_date = dateAfter(toDatetime(this.event.start_date), duration).toJSON().slice(0, 19)
          console.log('enddate', this.event.end_date)
        }

        if (this.options.bymonthday) {
          this.options.bymonthday = toDatetime(this.event.start_date).getDate()
        }
      }
    },
    eventEndDate: {
      get () {
        return (this.event.end_date || '').slice(0, 10)
      },
      set () {
        this.event.end_date = v + ((this.event.end_date || '').slice(10, 19) || 'T00:00:00')
      }
    },
    eventStart: {
      get () {
        return toTime(this.event.start_date)
      },
      set () {

      }
    },
    eventEnd: {
      get () {
        return toTime(this.event.end_date) ||  toTime(this.event.start_date)
      },
      set () {
        
      }
    },
    eventUntilSet () {
      console.log('check until')
      return this.eventUntil !== this.versionEndDate
    },
    eventUntil: {
      get () {
        console.log(this.event.until, this.versionEndDate)
        return toDatetime(this.event.until || this.versionEndDate).toJSON().slice(0, 10)
      },
      set (v) {
        console.log('set until ', v)
        this.event.until = new Date(Date.parse(v)).toJSON().slice(0, 19)
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
      console.log(toDateString(this.$parent.$parent.version.end_date))
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
    }
  },
  methods: {
    byMonthDay () {
      console.log('monthday')
      delete this.options.byweekday
      this.options.bymonthday = toDate(this.event.start_date).getDate()
    },
    byWeekDay () {
      console.log('weekday')
      delete this.options.bymonthday
      this.options.byweekday = this.options.byweekday || '1'
      this.options.bysetpos = this.options.bysetpos || '0'
    },
    toggleRecurring () {
      const rule = this.event.rrule
      this.event.rrule = this.event.rrule ? '' : this.event.oldrrule || 'FREQ=WEEKLY'
      this.event.oldrrule = rule 
    },
    toggleWeekday (day) {
      day = parseInt(day, 10)
      if (!this.options.byweekday) {
        this.options.byweekday = [day]
        return
      }
      const index = this.options.byweekday.indexOf(day)
      if (index !== -1) {
        this.options.byweekday.splice(index, 1)
      } else {
        this.options.byweekday.push(day)
        this.options.byweekday.sort((a, b) => a - b)
      }
      if (!this.options.byweekday.length) {
        this.options.byweekday = null
      }
      this.sync()
    },
    sync () {
      // console.log('sync', this.options.freq, new RRule(opts).toString())
      setTimeout(() => {
        // for (const key in this.options) {
        //   if (this.options[key] === null) {
        //     delete this.options[key]
        //   }
        // }
        const freq = this.options.freq
        const byweekday = this.options.byweekday
        console.log(inert(this.options))
        this.options = cleanEmpty(this.options)
        this.options.byweekday = byweekday || null
        this.options.freq = freq || 0
        delete this.options.until
        delete this.options.dtstart
        console.log(inert(this.options))
        this.$set(this.event, 'rrule', new RRule(this.options).toString())
      }, 100)
    }
  },
  created () {
    this.RRule = RRule || {}
  },
  components: {
    MultiDaySelect
  }
}
</script>
