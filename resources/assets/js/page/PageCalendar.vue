<template>
  <div class="container">
    <h1>{{ cal.label || 'Kalender zonder naam' }}</h1>
    <div style="padding-top:10em">
      <h3>Debug info</h3>
      <pre v-text="cal"></pre>
    </div>
  </div>
</template>

<script>

export default {
  name: 'calendar',
  data () {
    return {
      msg: 'Hello calendar!'
    }
  },
  computed: {
    cal () {
      var service = this.$parent.services.find(s => s.id === this.route.service)
      if (!service) {
        return {}
      }
      var channel = service.availableChannel[this.route.channel]
      if (!channel) {
        return {}
      }
      return channel.oh.calendar[this.route.calendar] || {}
    }
  }
}
</script>
