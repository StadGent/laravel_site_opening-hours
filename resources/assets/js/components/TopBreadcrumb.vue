<template>
  <div class="container container-breadcrumb">
    <ol class="breadcrumb">
      <li :class="{active:route.page=='home'}">
        <a href="#home">Overzicht</a>
      </li>

      <li v-if="!route.tab&&route.service>-1" :class="{active:route.page=='service'}">
        <a href="#service">{{ srv.label }}</a>
      </li>
      <li v-if="!route.tab&&route.channel>-1" :class="{active:route.page=='channel'}">
        <a href="#channel">Kanaal {{ route.channel + 1 }}</a>
      </li>
      <li v-if="!route.tab&&route.calendar>-1" :class="{active:route.page=='calendar'}">
        <a href="#calendar">Kalender {{ route.calendar + 1 }}</a>
      </li>

      <li v-if="route.page=='service'">
        {{ route.tab2 ? 'Gebruikers' : 'Kanalen' }}
      </li>
      <li v-if="route.page=='home'">
        {{ route.tab === 'admin' ? 'Administrators' : route.tab ? 'Gebruikers' : 'Diensten' }}
      </li>
      <li v-if="route.page=='user'">
        Diensten
      </li>
    </ol>
  </div>
</template>

<script>
export default {
  computed: {
    srv () {
      return this.service || this.$parent.services.find(s => s.id === this.route.service) || this.$parent.services[0] || {}
    },
    channels () {
      return this.srv && this.srv.availableChannel || []
    }
  }
}
</script>