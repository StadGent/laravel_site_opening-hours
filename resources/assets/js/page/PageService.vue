<template>
  <div class="container">
    <h1>{{ srv.label || 'Dienst zonder naam' }}</h1>
    <div v-if="isOwner" class="btn-group">
      <button type="button" class="btn btn-primary" :class="{active: !route.tab2}" @click="route.tab2=0">Toon kanalen</button>
      <button type="button" class="btn btn-primary" :class="{active: route.tab2}" @click="route.tab2='users'">Toon gebruikers</button>
    </div>
    <h2>{{ route.tab2 ? 'Gebruikers' : 'Kanalen'}}</h2>
    <button v-if="route.tab2" type="button" class="btn btn-primary" @click="newRole(srv)">+ Gebruiker uitnodigen</button>
    <button v-if="!route.tab2" type="button" class="btn btn-primary" @click="newChannel(srv)" :disabled="$root.isRecreatex">+ Nieuw kanaal</button>

    <div v-if="isOwner&&route.tab2==='users'">
      <div v-if="!filteredUsers.length" class="table-message">
        <h3 class="text-muted">Er werden nog geen gebruikers aan deze dienst toegevoegd.</h3>
        <p>
          <button class="btn btn-primary btn-lg" @click="newRole(srv)">Nodig een gebruiker uit</button>
        </p>
      </div>
      <table v-else class="table table-hover table-user">
        <thead>
          <tr>
            <th-sort by="name">Naam gebruiker</th-sort>
            <th-sort by="email">E-mailadres</th-sort>
            <th>Lid of eigenaar</th>
            <th-sort by="verified">Actief</th-sort>
            <th class="text-right">Ontzeg toegang tot dienst</th>
          </tr>
        </thead>
        <tbody>
          <tr is="row-user-owner" v-for="u in sortedUsers" :u="u"></tr>
        </tbody>
      </table>
    </div>

    <div v-else>
      <div v-if="!channels||!channels.length" class="table-message">
        <h3 class="text-muted">Er werden nog geen kanalen voor deze dienst aangemaakt.</h3>
        <p>
          <button class="btn btn-primary btn-lg" @click="newChannel(srv)" :disabled="$root.isRecreatex">Voeg een nieuw kanaal toe</button>
        </p>
      </div>
      <div v-else-if="!filteredChannels" class="table-message">
        <h1>Deze zoekopdracht leverde geen resultaten op</h1>
      </div>
      <table v-else class="table table-hover">
        <thead>
          <tr>
            <th-sort by="label">Kanaal</th-sort>
            <th>Status</th>
            <th-sort by="updated_at">Laatst aangepast</th-sort>
            <th class="text-right">Verwijder</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="channel in sortedChannels"
            @click="href('#!channel/'+[srv.id,channel.id].join('/'))"
            :class="{ 'success text-success': hasActiveOh(channel).length  }"
          >
            <td>
              <a :href="'#!channel/'+[srv.id,channel.id].join('/')">{{ channel.label }}</a>
            </td>
            <td>{{ channel | toChannelStatus }}</td>
            <td class="text-muted" :title="channel.updated_at">
              <div>{{ channel.updated_at | date }}</div>
              <div>{{ channel.updated_by }}</div>
            </td>
            <td class="td-btn text-right" @click.stop>
              <button @click="rmChannel(channel)" class="btn btn-icon btn-default" :disabled="$root.isRecreatex">
                <i class="glyphicon glyphicon-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import ThSort from '../components/ThSort.vue'
import RowUserOwner from '../components/RowUserOwner.vue'

import { toChannelStatus, hasActiveOh, orderBy, Hub } from '../lib.js'

export default {
  name: 'dienst',
  props: ['users'],
  data () {
    return {
      order: '',
      query: '',
      msg: '',
      service: null
    }
  },
  created() {
  },
  computed: {
    srv () {
      return this.$root.routeService
    },
    channels () {
      if(!this.srv.channels) {
          Hub.$emit('fetchChannels');
      }
      return this.srv.channels || []
    },
    filteredChannels () {
      return this.query ? this.channels.filter(s => s.label.indexOf(this.query) !== -1) : this.channels
    },
    sortedChannels () {
      return this.order ? this.filteredChannels.slice().sort(orderBy(this.order)) : this.filteredChannels
    },

    // Users
    filteredUsers () {
      return this.srv.users || {};
    },
    sortedUsers () {
      return this.order ? this.filteredUsers.slice().sort(orderBy(this.order)) : this.filteredUsers
    }
  },
  methods: {
    hasActiveOh,
    banUser (user) {
      Hub.$emit('deleteRole', {
        user_id: user.id,
        service_id: this.srv.id
      })
    },
    rmChannel (c) {
      Hub.$emit('deleteChannel', c)
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
