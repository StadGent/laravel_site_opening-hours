<template>
  <div class="container">
    <h1>{{ usr.name || 'Naamloos' }} <small>diensten ({{ filteredServices.length }})</small></h1>

    <div v-if="usr.admin">
      Admins hebben toegang tot alle diensten.
    </div>
    <div v-else>
      <button class="btn btn-default" @click="newRoleForUser(usr)">Nodig uit voor een dienst</button>
    </div>

    <div v-if="user.id == usr.id" style="max-width:25em;margin:2em 0;padding: 1em;border:1px solid #ddd;">
      <p>
        Dit is je eigen profiel. Je kan je naam wijzigen
      </p>
      <p>
        <label>Naam</label>
        <input type="text" class="form-control" v-model="usr.name">
      </p>
    </div>

    <!-- Services -->
    <div v-if="!userServices.length" class="table-message">
      <h3 class="text-muted">Deze gebruiker heeft nog geen diensten</h3>
      <p>
        <button class="btn btn-lg btn-default" @click="newRoleForUser(usr)">Nodig uit voor een dienst</button>
      </p>
    </div>
    <div v-else-if="!filteredServices.length" class="table-message">
      <h1>Deze zoekopdracht leverde geen resultaten op</h1>
    </div>
    <div v-else-if="isAdmin" class="row">
      <table class="table table-hover table-service-admin">
        <thead>
          <tr>
            <th-sort by="label">Dienst</th-sort>
            <th>Rol</th>
            <th class="text-right">Beheer gebruikers</th>
          </tr>
        </thead>
        <tbody is="row-user-service-admin" v-for="s in sortedServices" :s="s" :role-of="usr"></tbody>
      </table>
    </div>
    <div v-else class="row">
      <table class="table table-hover table-service">
        <thead>
          <tr>
            <th-sort by="label">Dienst</th-sort>
            <th>Rol</th>
          </tr>
        </thead>
        <tbody is="row-user-service" v-for="s in sortedServices" :s="s" :role-of="usr"></tbody>
      </table>
    </div>
  </div>
</template>

<script>
import RowUserService from '../components/RowUserService.vue'
import RowUserServiceAdmin from '../components/RowUserServiceAdmin.vue'
import ThSort from '../components/ThSort.vue'

import { expandUser } from '../mixins/users.js'

import { orderBy } from '../lib.js'

export default {
  name: 'page-user',
  data () {
    return {
      fetchedUser: null,
      order: 'name',
      query: ''
    }
  },
  computed: {

    // User
    users () {
      return this.$root.users || []
    },
    usr () {
      return (this.$root.users && this.$root.users.find(u => u.id == this.route.id)) || this.fetchedUser || this.fetchUser(this.route.id) || {}
    },

    // Services
    services () {
      return this.$root.services || []
    },
    userServices () {
      return this.services.filter(s => s.users.find(u => u.id == this.route.id))
    },
    filteredServices () {
      return this.query ? this.userServices.filter(s => (s.label || '').indexOf(this.query) !== -1) : this.userServices
    },
    sortedServices () {
      const services = this.order ? this.filteredServices.slice().sort(orderBy(this.order)) : this.filteredServices
      if (this.isAdmin) {

        // TODO: do this enriching onload, like is done with users
        services.forEach(s => {
          Object.assign(s, {
            activeUsers: s.users.filter(u => u.verified),
            ghostUsers: s.users.filter(u => !u.verified)
          })
        })
      }
      return services
    }
  },
  methods: {
    fetchUser (id) {
      this.$http.get('/api/ui/users/' + id)
        .then(({ data }) => {
          this.fetchedUser = expandUser(data)
        })
    }
  },
  components: {
    RowUserService,
    RowUserServiceAdmin,
    ThSort
  }
}
</script>

