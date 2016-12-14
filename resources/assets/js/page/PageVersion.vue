<template>
  <div class="container">
    <h1>{{ channel.label || 'Kanaal zonder naam' }} {{ version.label || 'gewoon' }} <small>{{ service.label || 'Dienst zonder naam' }}</small></h1>

    <div class="btn-group">
      <button type="button" class="btn btn-primary" :class="{active: !tab}" @click="tab=0">Toon periodes</button>
      <button type="button" class="btn btn-primary" :class="{active: tab=='users'}" @click="tab='users'">Toon open en gesloten</button>
    </div>

    <div class="version-split">
      <div class="version-cals col-sm-6 col-md-5 col-lg-4">
        <h2>Prioriteitenlijst periodes</h2>
        <p>
          De uren in de periode de hoogste prioriteit bepalen de openingsuren voor de kalender.
        </p>
        <div class="row">
          <div class="col-sm-12 cal" v-for="(cal, index) in reversedCalendars" @click="route.calendar=cal.layer">
            <header class="cal-header">
              <div class="cal-img" :class="'layer-'+cal.layer"></div>
              <span class="cal-name">{{ cal.label }}</span>
              <div class="cal-options">
                <span class="cal-lower" @click="swapLayers(cal.layer, cal.layer - 1)">Lager</span>
                <span class="cal-higher" @click="swapLayers(cal.layer, cal.layer + 1)">Hoger</span>
                <span class="cal-view">Bekijk</span>
                <span class="cal-drag"> <i class="glyphicon glyphicon-menu-hamburger"></i></span>
              </div>
            </header>
          </div>
        </div>
        <calendar-editor v-if="$parent.routeCalendar.events" :cal="$parent.routeCalendar"></calendar-editor>
      </div>
      <div class="version-preview col-sm-6 col-md-7 col-lg-8">
        <year-calendar :oh="version"></year-calendar>
      </div>
    </div>
  </div>
</template>

<script>
import YearCalendar from '../components/YearCalendar.vue'
import CalendarEditor from '../components/CalendarEditor.vue'

import { orderBy } from '../lib.js'

export default {
  name: 'version',
  data () {
    return {
      tab: null,
      editing: 0,
      msg: 'Hello Vue!'
    }
  },
  computed: {
    service () {
      return this.$parent.routeService || {}
    },
    channel () {
      return this.$parent.routeChannel || {}
    },
    version () {
      return this.$parent.routeVersion || {}
    },
    calendars () {
      const calendars = (this.version.calendars || [])
      calendars.sort(orderBy('-priority'))
      return calendars.map((c, i) => {
        c.layer = i
        return c
      })
    },
    reversedCalendars () {
      return inert(this.calendars).reverse()
    }
  },
  methods: {
    swapLayers (a, b) {
      a = this.calendars.find(c => c.layer === a) 
      b = this.calendars.find(c => c.layer === b) 
      if (a && b) {
        const p = a.priority
        a.priority = b.priority
        b.priority = p
      } else {
        console.warn('one of the layers was not found', a, b)
      }
    }
  },
  components: {
    CalendarEditor,
    YearCalendar
  }
}
</script>
