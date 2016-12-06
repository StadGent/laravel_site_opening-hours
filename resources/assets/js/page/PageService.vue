<template>
  <div class="container">
    <h1>{{ srv.label || 'Dienst zonder naam' }}</h1>
    <div class="panel">
      <div class="panel-body">
        choos
      </div>
    </div>
  </div>
</template>

<script>

export default {
  name: 'dienst',
  data () {
    return {
      msg: 'Hello dienst!',
      service: null
    }
  },
  computed: {
    srv () {
      return this.service || this.$parent.services[0] || {}
    }
  }
}
</script>
