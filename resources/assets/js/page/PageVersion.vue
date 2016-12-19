<template>
  <div class="container">
    <h1>{{ channel.label || 'Kanaal zonder naam' }} <small>{{ version.label || '' }}</small></h1>

    <!-- Version actions -->
    <div class="pull-right">
      <div class="btn-group">
        <button type="button" class="btn btn-default" :class="{active: !tab}" @click="tab=0">Toon periodes</button>
        <button type="button" class="btn btn-default" :class="{active: tab=='users'}" @click="tab='users'">Toon open en gesloten</button>
      </div>
    </div>

    <!-- Calender view options -->
    <p>
      <button type="button" class="btn btn-default" @click="editVersion">Versie naam bewerken</button>
      <button type="button" class="btn btn-default" @click="editVersion">Versie geldigheidsperiode bewerken</button>
    </p>

    <div class="version-split">
      <div class="version-cals col-sm-6 col-md-5 col-lg-4">
        <!-- Editing a calendar -->
        <calendar-editor v-if="$parent.routeCalendar.events" :cal="$parent.routeCalendar"></calendar-editor>

        <!-- Showing list of calendars -->
        <div v-else>
          <h2>Prioriteitenlijst periodes</h2>
          <p>
            De uren in de periode met de hoogste prioriteit bepalen de openingsuren voor de kalender.
          </p>
          <p>
            <button class="btn btn-primary" @click="addCalendar" v-if="reversedCalendars.length">Voeg uitzonderingen toe</button>
          </p>
          <div class="row">
            <div class="col-sm-12 cal" v-for="cal in reversedCalendars" @click="toCalendar(cal.id)">
              <header class="cal-header">
                <div class="cal-img" :class="'layer-'+cal.layer"></div>
                <span class="cal-name">{{ cal.label }}</span>
                <div class="cal-options">
                  <span class="cal-lower" @click.stop="swapLayers(cal.layer, cal.layer - 1)">Lager</span>
                  <span class="cal-higher" @click.stop="swapLayers(cal.layer, cal.layer + 1)">Hoger</span>
                  <span class="cal-view">Bekijk</span>
                  <span class="cal-drag"> <i class="glyphicon glyphicon-menu-hamburger"></i></span>
                </div>
              </header>
            </div>
          </div>

          <!-- Encourage to add calendars after first one -->
          <div class="text-center" v-if="reversedCalendars.length===1">
            <p style="padding-top:3em">
              Je normale openingsuren zijn ingesteld.  
            </p>
            <p>
              Om vakantieperiodes, nationale feestdagen of andere uitzondering in te stellen, druk op "<a href="#" @click.prevent="addCalendar">Voeg uitzonderingen toe</a>".
            </p>
          </div>

          <!-- This should never happen -->
          <div v-if="!reversedCalendars.length">
            <p>
              <button class="btn btn-link" @click="addCalendar">voeg openingsuren toe</button>
            </p>
          </div>
        </div>
      </div>
      <div class="version-preview col-sm-6 col-md-7 col-lg-8">
        <year-calendar :oh="layeredVersion"></year-calendar>
      </div>
    </div>
  </div>
</template>

<script>
import YearCalendar from '../components/YearCalendar.vue'
import CalendarEditor from '../components/CalendarEditor.vue'

import { createCalendar, createFirstCalendar } from '../defaults.js'
import { orderBy, Hub } from '../lib.js'

export default {
  name: 'page-version',
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
    layeredVersion () {
      return Object.assign({}, this.version, { calendar: this.calendars })
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
    },
    addCalendar () {
      const newCal = this.calendars.length ? createCalendar(this.calendars.length) : createFirstCalendar()
      console.log(inert(newCal))
      Hub.$emit('createCalendar', newCal)
    },
    editVersion () {
      console.log('edit version')
      Hub.$emit('editVersion', newCal)
    }
  },
  components: {
    CalendarEditor,
    YearCalendar
  }
}
</script>
