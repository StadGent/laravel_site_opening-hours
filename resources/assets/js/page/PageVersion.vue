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
          <div class="col-sm-12 cal" v-for="(cal, index) in calendars">
            <header class="cal-header">
              <div class="cal-img">{{ index }}</div>
              <span class="cal-name">{{ cal.label }}</span>
              <div class="cal-options">
                <span class="cal-lower">Lager</span>
                <span class="cal-higher">Hoger</span>
                <span class="cal-view">Bekijk</span>
                <span class="cal-drag"> <i class="glyphicon glyphicon-menu-hamburger"></i></span>
              </div>
            </header>
          </div>
        </div>
      </div>
      <div class="version-preview col-sm-6 col-md-7 col-lg-8">
        <year-calendar></year-calendar>
      </div>
    </div>
  </div>
</template>

<script>
import YearCalendar from '../components/YearCalendar.vue'

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
      return this.version.calendar || []
    }
  },
  components: {
    YearCalendar
  }
}
</script>
