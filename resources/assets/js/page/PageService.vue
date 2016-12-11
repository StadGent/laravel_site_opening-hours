<template>
  <div class="container">
    <h1>Contactkanalen <small>{{ srv.label || 'Dienst zonder naam' }}</small></h1>

    <span v-if="isOwner">
      <div class="btn-group">
        <button type="button" class="btn btn-primary" :class="{active: !route.tab2}" @click="route.tab2=0">Toon kanalen</button>
        <button type="button" class="btn btn-primary" :class="{active: route.tab2}" @click="route.tab2='users'">Toon gebruikers</button>
      </div>
      <button v-if="route.tab2" type="button" class="btn btn-primary" @click="newRole(srv)">+ Gebruiker uitnodigen</button>
    </span>
    <button v-else type="button" class="btn btn-primary" @click="newChannel(srv)">+ Nieuw kanaal</button>

    <div v-if="isOwner&&route.tab2==='users'">
      <div v-if="!sortedUsers.length" style="padding:5em 0;">
        <h1>Empty state</h1>
        <p>
          <button class="btn btn-primary btn-lg" @click="newRole(srv)">Nodig een gebruiker uit</button>
        </p>
      </div>
      <table v-else class="table table-hover table-user">
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
        <tbody is="row-user" v-for="u in sortedUsers" :u="u"></tbody>
      </table>
    </div>

    <div v-else>
      <div v-if="!channels||!channels.length" style="padding:5em 0;">
        <h1>Empty state</h1>
        <p>
          <button class="btn btn-primary btn-lg" @click="newChannel(srv)">Voeg een nieuw kanaal toe</button>
        </p>
      </div>
      <div v-else-if="!filteredChannels" style="padding:5em 0;">
        <h1>Deze zoekopdracht leverde geen resultaten op</h1>
      </div>
      <table v-else class="table">
        <thead>
          <tr>
            <th>Kanaal</th>
            <th>Geldig tot</th>
            <th>Bewerk</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(channel, index) in filteredChannels">
            <td>
              <a :href="'#!channel/'+[srv.id,index].join('/')">{{ channel.label }}</a>
            </td>
            <td>{{ channel.dtend }}</td>
            <td>
              <a :href="'#!channel/'+[srv.id,index].join('/')" class="btn btn-icon btn-warning">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 528.899 528.899">
                  <path d="M328.883,89.125l107.59,107.589l-272.34,272.34L56.604,361.465L328.883,89.125z M518.113,63.177l-47.981-47.981 c-18.543-18.543-48.653-18.543-67.259,0l-45.961,45.961l107.59,107.59l53.611-53.611 C532.495,100.753,532.495,77.559,518.113,63.177z M0.3,512.69c-1.958,8.812,5.998,16.708,14.811,14.565l119.891-29.069 L27.473,390.597L0.3,512.69z"/>
                </svg>
              </a>
            </td>
            <td></td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div style="padding-top:10em">
      <h3>Debug info</h3>
      <pre v-text="srv"></pre>
    </div>
  </div>
</template>

<script>
import ThSort from '../components/ThSort.vue'
import RowUser from '../components/RowUser.vue'

import { orderBy } from '../lib.js'

export default {
  name: 'dienst',
  props: ['users'],
  data () {
    return {
      order: '',
      query: '',
      msg: 'Hello dienst!',
      service: null
    }
  },
  computed: {
    srv () {
      return this.service || this.$parent.services.find(s => s.id === this.route.service) || this.$parent.services[0] || {}
    },
    channels () {
      return this.srv.availableChannel || []
    },
    filteredChannels () {
      console.log(this.channels)
      return this.query ? this.channels.filter(s => s.label.indexOf(this.query) !== -1) : this.channels
    },
    sortedChannels () {
      return this.order ? this.filteredChannels.slice().sort(orderBy(this.order)) : this.filteredChannels
    },

    // Users
    filteredUsers () {
      return this.users.filter((u, i) => {
        for (var j = u.roles.length - 1; j >= 0; j--) {
          if (u.roles[j].service == this.srv.id) {
            return true
          }
        }
      })
    },
    sortedUsers () {
      return this.order ? this.filteredUsers.slice().sort(orderBy(this.order)) : this.filteredUsers
    }
  },
  components: {
    RowUser,
    ThSort
  }
}
</script>
