<template>
  <div class="container">
    <h1>{{ channel.label || 'Kanaal zonder naam' }} {{ version.label || 'gewoon' }} <small>{{ service.label || 'Dienst zonder naam' }}</small></h1>

    <div class="btn-group">
      <button type="button" class="btn btn-primary" :class="{active: !tab}" @click="tab=0">Toon periodes</button>
      <button type="button" class="btn btn-primary" :class="{active: tab=='users'}" @click="tab='users'">Toon open en gesloten</button>
    </div>

    <div class="version-split">
      <div class="version-cals col-sm-6 col-md-5 col-lg-4">
        <calendar-editor v-if="$parent.routeCalendar.events" :cal="$parent.routeCalendar"></calendar-editor>
        <div v-else>
          <h2>Prioriteitenlijst periodes</h2>
          <p>
            De uren in de periode met de hoogste prioriteit bepalen de openingsuren voor de kalender.
          </p>
          <p>
            <button class="btn btn-primary" @click="addCalendar" v-if="reversedCalendars.length">Voeg uitzonderingen toe</button>
          </p>
          <div class="row">
            <div class="col-sm-12 cal" v-for="(cal, index) in reversedCalendars" @click="toCalendar(cal.layer)">
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
          <div v-if="reversedCalendars.length>1">
            <hr>
            <div class="text-center">
              <p>
                Om vakantieperiodes, nationale feestdagen of andere uitzondering in te stellen, druk op "<a href="#" @click.prevent="addCalendar">Voeg uitzonderingen toe</a>".
              </p>
              <p>
                <button class="btn btn-link" @click="addCalendar">of voeg manueel dagen toe</button>
              </p>
            </div>
          </div>
          <div v-else-if="reversedCalendars.length">
            <hr>
            <div class="text-center">
              <p>
                Je normale openingsuren zijn ingesteld.  
              </p>
              <p>
                Om vakantieperiodes, nationale feestdagen of andere uitzondering in te stellen, druk op <a href="#" @click.prevent="addCalendar">Voeg uitzonderingen toe</a>".
              </p>
              <p>
                <button class="btn btn-link" @click="addCalendar">of voeg manueel dagen toe</button>
              </p>
            </div>
          </div>
          <div v-if="!reversedCalendars.length">
            <hr>
            <div class="text-center">
              <p>
                <button class="btn btn-primary" @click="addCalendar">Importeer bestaande openingsuren</button>
              </p>
              <p>
                <button class="btn btn-link" @click="addCalendar">of voeg manueel dagen toe</button>
              </p>
            </div>
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
      if (!Array.isArray(this.$parent.routeVersion.calendars)) {
        this.$set(this.$parent.routeVersion, 'calendars', [])
        this.$nextTick(() => {
        this.fetchVersion()
        })
      }
      return this.$parent.routeVersion || {}
    },
    layeredVersion () {
      this.$set(this.version, 'calendars', this.calendars)
      return this.version
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
      this.layeredVersion.calendars.push(newCal)
      this.toCalendar(newCal.layer)
    },
    fetchVersion () {
      if (!this.route.version) {
        return
      }
      this.$http.get('/api/openinghours/' + this.route.version)
        .then(({ data }) => {
          this.$set(this.$parent.routeVersion, 'calendars', data.calendars)
        })
    }
  },
  components: {
    CalendarEditor,
    YearCalendar
  }
}
</script>
