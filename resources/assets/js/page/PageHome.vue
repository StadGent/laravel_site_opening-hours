<template>
  <div class="container">
    <h1>{{ isAdmin ? 'Admin' : 'Mijn diensten' }}</h1>
    <form class="pull-right">
      <div class="form-group">
        <input v-model="query" @input="route.offset=0" class="form-control" placeholder="Zoek dienst" style="max-width:300px" type="search">
      </div>
    </form>
    <div v-if="isAdmin">
      <div class="btn-group">
        <button type="button" class="btn btn-primary" :class="{active: !route.tab}" @click="route.tab=0">Toon diensten</button>
        <button type="button" class="btn btn-primary" :class="{active: route.tab=='users'}" @click="route.tab='users'">Toon gebruikers</button>
      </div>
      <button type="button" class="btn btn-link btn-disabled" :class="{active: route.tab=='admin'}" @click="route.tab='admin'" disabled>Toon administrators</button>
    </div>
    <div v-else>
      <button type="button" class="btn btn-default" @click="requestService">Vraag toegang tot een dienst</button>
    </div>

    <!-- Users -->
    <div v-if="isAdmin&&route.tab==='users'" class="row">
      <div v-if="!users.length" style="padding:5em 0;">
        <h3 class="text-muted">
          Er zijn nog geen gebruikers op het platform. Mogelijke oorzaken:
          <br>U hebt niet genoeg rechten.
          <br>Er liep iets fout.
        </h3>
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
    <div v-else class="row">
      <div v-if="!services.length" style="padding:5em 0;">
        <h3 class="text-muted">U hebt nog geen toegang tot diensten</h3>
      </div>
      <div v-else-if="!filteredServices.length" style="padding:5em 0;">
        <h1>Deze zoekopdracht leverde geen resultaten op</h1>
      </div>
      <table v-else-if="isAdmin" class="table table-hover table-service-admin">
        <thead>
          <tr>
            <th-sort by="label">Didenst</th-sort>
            <th>Status</th>
            <th-sort by="updated_at">Aangepast</th-sort>
            <th class="text-right">Beheer</th>
          </tr>
        </thead>
        <tbody is="row-service-admin" v-for="s in pagedServices" :s="s"></tbody>
      </table>
      <table v-else class="table table-hover table-service">
        <thead>
          <tr>
            <th-sort by="label">Diefnst</th-sort>
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
      return this.query ? this.services.filter(s => (s.label || '').toLowerCase().indexOf(this.query.toLowerCase()) !== -1) : this.services
    },
    sortedServices () {
      return this.order ? this.filteredServices.slice().sort(orderBy(this.order)) : this.filteredServices
    },
    pagedServices () {
      var services = this.sortedServices.slice(this.route.offset || 0, this.route.offset + pageSize)
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
    },

    // Users
    filteredUsers () {
      return this.query ? this.users.filter(u => (u.name || '').indexOf(this.query) !== -1) : this.users
    },
    sortedUsers () {
      return this.order ? this.filteredUsers.slice().sort(orderBy(this.order)) : this.filteredUsers
    },
    pagedUsers () {
      return this.sortedUsers.slice(this.route.offset || 0, this.route.offset + pageSize)
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
