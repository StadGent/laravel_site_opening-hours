<template>
  <div class="">
    <div class="wrapper-above-save">
      <div class="close" style="padding:10px 15px;margin: -10px -15px;" @click="route.calendar=-1">&times;</div>
      <h3>{{ cal.label }}</h3>
      <label v-if="cal.layer">
        <input type="checkbox" :checked="cal.closinghours" @change="toggleClosing"> Sluitingsuren
      </label>

      <event-editor v-for="(e, i) in cal.events" :parent="cal.events" :prop="i" @add-event="addEvent(i, e)" @rm="rmEvent(i)"></event-editor>

      <p v-if="cal.layer">
        <button @click="cal.events.push(createEvent())" class="btn btn-link">+ Voeg nieuwe periode of dag toe</button>
      </p>
    </div>

    <div class="">
      <button class="btn btn-primary">Bewaren</button>
    </div>

<!-- 
    <pre>{{ cal }}</pre>
    <pre class="cal-render" style="margin-top:10em">{{ events }}</pre> -->
  </div>
</template>

<script>
import EventEditor from '../components/EventEditor.vue'
import { createEvent } from '../defaults.js'
import { cleanEmpty } from '../lib.js'

const fullDays = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag']

export default {
  name: 'calendar-editor',
  props: ['cal', 'layer'],
  data () {
    return {
      // options: {}
      days: ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'],
      fullDays
    }
  },
  computed: {
    events () {
      return this.cal.events
    }
  },
  methods: {
    toggleClosing () {
      this.$set(this.cal, 'closinghours', !this.cal.closinghours)
    },
    addEvent (index, event) {
      console.log('add yes', index, event)
      event = Object.assign({}, event)
      this.cal.events.splice(index, 0, event)
    },
    rmEvent (index) {
      this.cal.events.splice(index, 1)
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
