<template>
  <div class="container">
    <form>
      <div class="form-group">
        <input v-model="query" class="form-control" placeholder="Zoek dienst" style="max-width:300px" type="search">
      </div>
    </form>
    <div v-if="!services.length" style="padding:5em 0;">
      <h1>Empty state</h1>
    </div>
    <div v-else v-if="!filteredServices.length" style="padding:5em 0;">
      <h1>Deze zoekopdracht leverde geen resultaten op</h1>
    </div>
    <table v-else class="table" :class="{busy}">
      <thead>
        <tr>
          <th>Dienst</th>
          <th>Status</th>
          <th>Aangepast</th>
          <th>Kanaal</th>
          <th>Kalenders</th>
        </tr>
      </thead>
      <tbody is="tbody-service" v-for="s in pagedServices" :s="s"></tbody>
    </table>
  </div>
</template>

<script>
import TbodyService from '../components/TbodyService.vue'

export default {
  name: 'home',
  props: ['services'],
  data () {
    return {
      query: ''
    }
  },
  computed: {
    filteredServices () {
      return this.query ? this.services.filter(s => s.label.indexOf(this.query) !== -1) : this.services
    },
    pagedServices () {
      return this.filteredServices.slice(this.route.offset || 0, 10)
    }
  },
  components: {
    TbodyService
  }
}
</script>
