<template>
  <form class="wrapper-height100" @submit.prevent>
    <div class="wrapper-above-save">
      <div class="cal-img top-right" :class="'layer-'+cal.layer"></div>

      <!-- First calendar is always weekly -->
      <div v-if="!cal.layer">
        <h3>Stel de openingsuren in voor {{ $root.routeService.label }}. Op welke dagen is deze dienst normaal open?</h3>
        <p class="text-muted">Uitzonderingen kan je later instellen.</p>

        <event-editor v-for="(e, i) in cal.events" :parent="cal.events" :prop="i" @add-event="addEvent(i, e)" @rm="rmEvent(i)"></event-editor>

        <p v-if="!cal.events.length">
          <button type="button" @click="pushFirstEvent" class="btn btn-link">+ Voeg weekschema toe</button>
        </p>
      </div>

      <!-- Other calendars must be renamed -->
      <div v-else-if="cal.label=='Uitzondering'">
        <h3>Stel de uitzondering in.</h3>
        <div class="form-group">
          <label>Naam uitzondering</label>
          <input type="text" class="form-control" v-model="calLabel" placeholder="Brugdagen, collectieve sluitingsdagen, ..." autofocus>
          <div class="help-block">Kies een specifieke naam die deze uitzondering beschrijft.</div>
        </div>
      </div>

      <!-- Other calendars have more options -->
      <div v-else>
        <h3>{{ cal.label }}</h3>
        <label>
          <input type="checkbox" :checked="cal.closinghours" @change="toggleClosing"> Sluitingsuren
        </label>
        <br>

        <event-editor v-for="(e, i) in cal.events" :parent="cal.events" :prop="i" @add-event="addEvent(i, e)" @rm="rmEvent(i)"></event-editor>

        <p>
          <button type="button" @click="pushEvent" class="btn btn-link">+ Voeg nieuwe periode of dag toe</button>
        </p>
      </div>
    </div>

    <div class="wrapper-save-btn">
      <div class="col-xs-12 text-right">
        <button type="button" class="btn btn-default pull-left" @click="rmCalendar()">Verwijder</button>
        <button type="button" class="btn btn-default" @click="cancel">Annuleer</button>
        <button type="submit" class="btn btn-primary" @click="saveLabel" v-if="cal.label=='Uitzondering'">Volgende stap</button>
        <button type="button" class="btn btn-danger" @click="save" v-else-if="disabled" disabled>Sla op</button>
        <button type="button" class="btn btn-primary" @click="save" v-else>Sla op</button>
      </div>
    </div>

<!-- 
    <pre>{{ cal }}</pre>
    <pre class="cal-render" style="margin-top:10em">{{ events }}</pre> -->
  </form>
</template>

<script>
import EventEditor from '../components/EventEditor.vue'
import { createEvent, createFirstEvent } from '../defaults.js'
import { cleanEmpty, Hub, toDatetime } from '../lib.js'

const fullDays = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag']

export default {
  name: 'calendar-editor',
  props: ['cal', 'layer'],
  data () {
    return {
      // options: {}
      calLabel: '',
      days: ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'],
      fullDays
    }
  },
  computed: {
    events () {
      return this.cal.events
    },
    disabled () {
      if (this.events.filter(e => e.start_date > e.end_date).length) {
        return true
      }
    }
  },
  methods: {
    toggleClosing () {
      this.$set(this.cal, 'closinghours', !this.cal.closinghours)
    },
    pushEvent () {
      const start_date = toDatetime(this.$parent.version.start_date)
      this.cal.events.push(createEvent({
        start_date,
        label: this.cal.events.length + 1
      }))
    },
    pushFirstEvent () {
      this.cal.events.push(createFirstEvent())
    },
    addEvent (index, event) {
      console.log('add yes', index, event)
      event = Object.assign({}, event, { id: null })
      this.cal.events.splice(index, 0, event)
    },
    rmEvent (index) {
      this.cal.events.splice(index, 1)
    },
    cancel () {
      this.toVersion()
      this.$root.fetchVersion(true)
    },
    save () {
      if (this.disabled) {
        return console.warn('Expected valid calendar')
      }
      Hub.$emit('createCalendar', this.cal, true)
    },
    saveLabel () {
      if (!this.calLabel || this.calLabel === 'Uitzondering') {
        return console.warn('Expected calendar name')
      }
      this.cal.label = this.calLabel
      Hub.$emit('createCalendar', this.cal)
    },
    rmCalendar () {
      Hub.$emit('deleteCalendar', this.cal)
    }
  },
  created () {
    this.RRule = RRule || {}
  },
  mounted () {
    this.$set(this.cal, 'closinghours', !!this.cal.closinghours)
    if (!this.cal.events) {
      this.$set(this.cal, 'events', [])
    }
  },
  components: {
    EventEditor
  }
  // watch: {
  //   cal (v) {
  //     console.log('load cal', v)
  //     var options = RRule.parseString(this.rr)
  //     options.dtstart = new Date(2000, 1, 1)
  //     var r = new RRule(options)
  //     this.options = r.options
  //   },
  //   options: {
  //     deep: true,
  //     handler (v, o) {
  //       if (!v || !v.dtstart) {
  //         return
  //       }
  //       var str = new RRule(v).toString()
  //       if (this.cal.events && this.rr != str) {
  //         console.log('saves change to cal', str)
  //         this.cal.events[0].rrule = new RRule(v).toString()
  //       }
  //     }
  //   }
  // }
}
</script>
