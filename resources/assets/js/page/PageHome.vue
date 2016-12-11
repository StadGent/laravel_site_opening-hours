<template>
  <div class="container">
    <h1>{{ user.admin ? 'Admin' : 'Mijn diensten' }}</h1>
    <form class="pull-right">
      <div class="form-group">
        <input v-model="query" @input="route.offset=0" class="form-control" placeholder="Zoek dienst" style="max-width:300px" type="search">
      </div>
    </form>
    <div class="btn-group" v-if="user.admin">
      <button type="button" class="btn btn-primary" :class="{active: !route.tab}" @click="route.tab=0">Toon diensten</button>
      <button type="button" class="btn btn-primary" :class="{active: route.tab=='users'}" @click="route.tab='users'">Toon gebruikers</button>
      <button type="button" class="btn btn-primary" :class="{active: route.tab=='admin'}" @click="route.tab='admin'">Toon administrators</button>
    </div>
    <div v-else>
      <button type="button" class="btn btn-default" @click="requestService">Vraag toegang tot een dienst</button>
    </div>

    <!-- Users -->
    <div v-if="user.admin&&route.tab==='users'">
      <div v-if="!users.length" style="padding:5em 0;">
        <h1>Empty state</h1>
        <p>
          <button class="btn btn-primary btn-lg" @click="newRole(srv)">Nodig een gebruiker uit</button>
        </p>
      </div>
      <div v-if="users.length&&!filteredUsers.length" style="padding:5em 0;">
        <h1>Deze zoekopdracht leverde geen resultaten op</h1>
      </div>
      <table v-if="filteredUsers.length" class="table table-hover table-user">
        <thead>
          <tr>
            <th-sort by="name">Naam gebruiker</th-sort>
            <th-sort by="email">E-mailadres</th-sort>
            <th-sort by="services">Diensten</th-sort>
            <th-sort by="verified">Actief</th-sort>
            <th class="text-right">Nodig uit</th>
            <th class="text-right">Verwijder</th>
          </tr>
        </thead>
        <tbody is="row-user" v-for="u in pagedUsers" :u="u"></tbody>
      </table>
      <pagination :total="filteredUsers.length"></pagination>
    </div>

    <!-- Services -->
    <div v-else>
      <div v-if="!services.length" style="padding:5em 0;">
        <h1>Empty state</h1>
        <p>
          Is alles wel juist geïnstalleerd?
        </p>
      </div>
      <div v-if="services.length&&!filteredServices.length" style="padding:5em 0;">
        <h1>Deze zoekopdracht leverde geen resultaten op</h1>
      </div>
      <table v-if="filteredServices.length&&user.admin" class="table table-hover table-service-admin">
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
        <tbody is="row-service-admin" v-for="s in pagedServices" :s="s"></tbody>
      </table>
      <table v-if="filteredServices.length&&!user.admin" class="table table-hover table-service">
        <thead>
          <tr>
            <th-sort by="label">Dienst</th-sort>
            <th>Status</th>
            <th-sort by="updated_at">Aangepast</th-sort>
            <th class="text-right">Beheer gebruikers</th>
            <th class="text-right">Bewerk</th>
          </tr>
        </thead>
        <tbody is="row-service" v-for="s in pagedServices" :s="s"></tbody>
      </table>
      <pagination :total="filteredServices.length"></pagination>
    </div>
  </div>
</template>

<script>
import { pageSize, default as Pagination } from '../components/Pagination.vue'
import RowService from '../components/RowService.vue'
import RowServiceAdmin from '../components/RowServiceAdmin.vue'
import RowUser from '../components/RowUser.vue'
import ThSort from '../components/ThSort.vue'

import { orderBy } from '../lib.js'

export default {
  name: 'home',
  props: ['services', 'users'],
  data () {
    return {
      order: 'name',
      query: ''
    }
  },
  computed: {

    // Services
    filteredServices () {
      return this.query ? this.services.filter(s => (s.label || '').indexOf(this.query) !== -1) : this.services
    },
    sortedServices () {
      return this.order ? this.filteredServices.slice().sort(orderBy(this.order)) : this.filteredServices
    },
    pagedServices () {
      var services = this.sortedServices.slice(this.route.offset || 0, this.route.offset + pageSize)
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
    },

    // Users
    filteredUsers () {
      return this.query ? this.users.filter(u => (u.name || '').indexOf(this.query) !== -1) : this.users
    },
    sortedUsers () {
      return this.order ? this.filteredUsers.slice().sort(orderBy(this.order)) : this.filteredUsers
    },
    pagedUsers () {
      return this.sortedUsers.slice(this.route.offset || 0, pageSize)
    }
  },
  components: {
    Pagination,
    RowService,
    RowServiceAdmin,
    RowUser,
    ThSort
  }
}
</script>
