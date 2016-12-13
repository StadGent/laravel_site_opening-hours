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
    <button v-if="!route.tab2" type="button" class="btn btn-primary" @click="newChannel(srv)">+ Nieuw kanaal</button>

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
            <th>Rol</th>
            <th-sort by="verified">Actief</th-sort>
            <th class="text-right">Nodig uit</th>
            <th class="text-right">Ontzeg toegang</th>
          </tr>
        </thead>
        <tbody is="row-user-owner" v-for="u in sortedUsers" :u="u"></tbody>
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
            <th-sort by="label">Kanaal</th-sort>
            <th-sort by="status">Status</th-sort>
            <th-sort by="updated_at">Laatst aangepast</th-sort>
            <th class="text-right">Verwijder</th>
            <th class="text-right">Bewerk</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(channel, index) in filteredChannels" @click="href('#!channel/'+[srv.id,index].join('/'))">
            <td>
              <a :href="'#!channel/'+[srv.id,index].join('/')">{{ channel.label }}</a>
            </td>
            <td>{{ channel | toChannelStatus }}</td>
            <td class="text-muted">
              <div>{{ channel.updated_at | date }}</div>
              <div>{{ channel.updated_by }}</div>
            </td>
            <td class="text-right">
              <a :href="'#!channel/'+[srv.id,index].join('/')" class="btn btn-icon btn-default">
                <i class="glyphicon glyphicon-trash"></i>
              </a>
            </td>
            <td class="text-right">
              <a :href="'#!channel/'+[srv.id,index].join('/')" class="btn btn-icon btn-primary">
                <i class="glyphicon glyphicon-pencil"></i>
              </a>
            </td>
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
import RowUserOwner from '../components/RowUserOwner.vue'

import { toChannelStatus, orderBy } from '../lib.js'

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
      return this.srv.channels || []
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
  filters: {
    toChannelStatus
  },
  components: {
    RowUserOwner,
    ThSort
  }
}
</script>
