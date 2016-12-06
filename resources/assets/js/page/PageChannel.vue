<template>
  <div class="container">
    <h1>{{ ch.label || 'Kanaal zonder naam' }}</h1>
  </div>
</template>

<script>

export default {
  name: 'channel',
  data () {
    return {
      msg: 'Hello Vue!'
    }
  },
  computed: {
    ch () {
      var service = this.$parent.services.find(s => s.id === this.route.service)
      if (!service) {
        return {}
      }
      return service.availableChannel[this.route.channel] || {}
    }
  }
}
</script>
