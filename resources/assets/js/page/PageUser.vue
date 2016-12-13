<template>
  <div class="container">
    <h1>{{ usr.name || 'Naamloos' }} <small>diensten ({{ filteredServices.length }})</small></h1>

    <div v-if="user.admin">
      Je bent admin
    </div>
    <div v-else>
      <button type="button" class="btn btn-primary" @click="requestService">+ Voeg diensten toe</button>
    </div>

    <div v-if="user.id == usr.id" style="max-width:25em;margin:2em 0;padding: 1em;border:1px solid #ddd;">
      <p>
        Dit is je eigen profiel. Je kan je naam wijzigen
      </p>
      <p>
      <label>Naam</label>
        <input type="text" class="form-control" v-model="user.name">
      </p>
    </div>

    <div v-if="user.id == usr.id" style="max-width:25em;margin:2em 0;padding: 1em;border:1px solid #ddd;">
      <p>
        <button @click="$parent.logout">Uitloggen</button>
      </p>
      <p>
    </div>

    <!-- Services -->
    <div v-if="!userServices.length" style="padding:5em 0;">
      <h1>Empty state</h1>
      <p>
        <button class="btn btn-primary btn-lg" @click="newRole(srv)">Voeg een dienst toe</button>
      </p>
    </div>
    <div v-else-if="!filteredServices.length" style="padding:5em 0;">
      <h1>Deze zoekopdracht leverde geen resultaten op</h1>
    </div>
    <table v-else-if="user.admin" class="table table-hover table-service-admin">
      <thead>
        <tr>
          <th-sort by="label">Dienst</th-sort>
          <th>Status</th>
          <th-sort by="updated_at">Aangepast</th-sort>
          <th-sort by="active">Actieve<br>gebruikers</th-sort>
          <th-sort by="ghosts">Non-actieve<br>gebruikers</th-sort>
          <th class="text-right">Beheer</th>
        </tr>
      </thead>
      <tbody is="row-service-admin" v-for="s in sortedServices" :s="s"></tbody>
    </table>
    <table v-else class="table table-hover table-service">
      <thead>
        <tr>
          <th-sort by="label">Dienst</th-sort>
          <th>Status</th>
          <th-sort by="updated_at">Aangepast</th-sort>
          <th class="text-right">Beheer gebruikers</th>
          <th class="text-right">Bewerk</th>
        </tr>
      </thead>
      <tbody is="row-service" v-for="s in sortedServices" :s="s"></tbody>
    </table>
  </div>
</template>

<script>
import RowService from '../components/RowService.vue'
import RowServiceAdmin from '../components/RowServiceAdmin.vue'
import ThSort from '../components/ThSort.vue'

import { orderBy } from '../lib.js'

export default {
  name: 'page-user',
  props: ['services', 'users'],
  data () {
    return {
      order: 'name',
      query: ''
    }
  },
  computed: {

    // User
    users () {
      return this.$parent.users || []
    },
    usr () {
      return this.route.id &&
        this.users.find(u => u.id == this.route.id) ||
        this.users.find(u => u.id == this.user.id) ||
        this.$parent.user ||
        console.warn('user page without user') || {
          name: 'Fout',
          services: []
        }
    },

    // Services
    services () {
      return this.$parent.services || []
    },
    userServices () {
      return this.usr.services ? this.services.filter(s => this.usr.services.indexOf(s.id) !== -1) : []
    },
    filteredServices () {
      return this.query ? this.userServices.filter(s => (s.label || '').indexOf(this.query) !== -1) : this.userServices
    },
    sortedServices () {
      const services = this.order ? this.filteredServices.slice().sort(orderBy(this.order)) : this.filteredServices
      if (this.user.admin) {
        if (!this.users[0]) {
          return []
        }

        // TODO: do this enriching onload, like is done with users
        services.forEach(v => {
          const users = this.users.filter(u => u.role[v.id])
          Object.assign(v, {
            activeUsers: users.filter(u => u.verified),
            ghostUsers: users.filter(u => !u.verified)
          })
        })
      }
      return services
    }
  },
  components: {
    RowService,
    RowServiceAdmin,
    ThSort
  }
}
</script>

