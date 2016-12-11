<template>
  <div class="container">
    <div class="pull-right">
      <a href="#channel:edit" @click.prevent="editing" class="btn btn-success" v-if="editing">Openingsuren bewaren</a>
      <a href="#channel:edit" @click.prevent="refresh" class="btn btn-primary" v-else>Openingsuren bewerken</a>
      <a href="#" @click.prevent="refresh" class="text-muted" v-if="editing">Wijzigingen annuleren</a>
    </div>
    <h1>{{ ch.label || 'Kanaal zonder naam' }} <small>{{ srv.label || 'Dienst zonder naam' }}</small></h1>
    <p>
      <a href="#" @click.prevent="editing=0">Tonen</a> &middot;
      <a href="#" @click.prevent="editing=1">Bewerken</a>
    </p>
    <div v-if="editing">
      
    <div class="panel panel-default panel-move" href="#" v-for="(cal, index) in calendars">
      <div class="panel-heading" @click.prevent="toCalendar(index)">
        <svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 180 180" width="16px">
          <path d="M38.937,128.936L0,89.999l38.935-38.936l0.001,29.982h42.11V38.937l-29.982,0L90.001,0l38.935,38.936l-29.982,0v42.109  h42.109l-0.001-29.982L180,90.001l-38.936,38.935l-0.001-29.982h-42.11v42.109l29.982,0L89.999,180l-38.936-38.936l29.982,0V98.954  H38.937L38.937,128.936z"></path>
        </svg>
        {{ cal.label }}
      </div>
      <div class="panel-body">
        d
      </div>
    </div>
    </div>
    <div v-else>
      <div style="height:calc(100vh - 300px);background:#ccc"></div>
    </div>
    <div style="padding-top:10em">
      <h3>Debug info</h3>
      <pre v-text="ch"></pre>
    </div>
  </div>
</template>

<script>
export default {
  name: 'channel',
  data () {
    return {
      editing: 0,
      msg: 'Hello Vue!'
    }
  },
  computed: {
    srv () {
      return this.$parent.services.find(s => s.id === this.route.service) || {}
    },
    ch () {
      var service = this.$parent.services.find(s => s.id === this.route.service)
      if (!service) {
        return {}
      }
      return service.availableChannel[this.route.channel] || {}
    },
    calendars () {
      return this.ch && this.ch.oh && this.ch.oh.calendar || []
    }
  }
}
</script>
