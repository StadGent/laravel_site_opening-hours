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
            <th v-if="canEditChannels" class="td-btn">
              <span class="sr-only">Volgorde</span>
            </th>
            <th-sort by="label">Kanaal</th-sort>
            <th>Type</th>
            <th>Status</th>
            <th-sort by="updated_at">Laatst aangepast</th-sort>
            <th>Laatste versie verloopt op</th>
            <th class="text-right" v-if="isAdmin || isOwner || isMember">Verwijder</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(channel, index) in sortedChannels"
            :key="channel.id"
            @click="href('#!channel/'+[srv.id,channel.id].join('/'))"
            :class="{ 'success text-success': hasActiveOh(channel).length, 'channel-dragging': dragIndex === index, 'channel-drop-before': dropIndex === index && dragIndex > index, 'channel-drop-after': dropIndex === index && dragIndex !== null && dragIndex < index }"
            :draggable="canReorder"
            @dragstart="dragStart(index, $event)"
            @dragover="dragOver(index, $event)"
            @drop.prevent="drop(index)"
            @dragend="dragEnd"
          >
            <td v-if="canEditChannels" class="td-btn" @click.stop>
              <!-- Keyboard users move the focused channel with the arrow keys. -->
              <button
                type="button"
                class="channel-order-handle"
                :disabled="!canReorder"
                :title="reorderTitle"
                @keydown.up.prevent="move(index, -1)"
                @keydown.down.prevent="move(index, 1)"
              >
                <i class="glyphicon glyphicon-move" aria-hidden="true"></i>
                <span class="sr-only">Verplaats {{ channel.label }} met de pijltjestoetsen</span>
              </button>
            </td>
            <td>
              <a :href="'#!channel/'+[srv.id,channel.id].join('/')">{{ channel.label }}</a>
            </td>
            <td>{{ channelType(channel.type_id) }}</td>
            <td>{{ channel | toChannelStatus }}</td>
            <td class="text-muted" :title="channel.updated_at">
              <div>{{ channel.updated_at | date }}</div>
              <div>{{ channel.updated_by }}</div>
            </td>
            <td class="text-muted">{{ expiryDate(channel) | simpleDate}}</td>
            <td class="td-btn text-right" v-if="isAdmin || isOwner || isMember" @click.stop>
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
      service: null,
      dragIndex: null,
      dropIndex: null
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
    types () {
        return this.$root.types
    },
    canEditChannels () {
      return (this.isAdmin || this.isOwner || this.isMember) && !this.$root.isRecreatex
    },
    // The order can only be changed when the list shows the saved order.
    canReorder () {
      return this.canEditChannels && !this.order && !this.query && this.channels.length > 1
    },
    reorderTitle () {
      if (this.order || this.query) {
        return 'Wis de sortering om de volgorde aan te passen'
      }
      return this.canReorder ? 'Sleep om de volgorde aan te passen' : ''
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
    move (from, offset) {
      this.reorder(from, from + offset)
    },
    reorder (from, to) {
      if (!this.canReorder || from === to || to < 0 || to >= this.channels.length) {
        return
      }
      const channels = this.channels.slice()
      channels.splice(to, 0, channels.splice(from, 1)[0])
      Hub.$emit('orderChannels', channels)
    },
    dragStart (index, event) {
      if (!this.canReorder) {
        event.preventDefault()
        return
      }
      this.dragIndex = index
      event.dataTransfer.effectAllowed = 'move'
      // Firefox only starts dragging when data is set.
      event.dataTransfer.setData('text/plain', String(index))
    },
    dragOver (index, event) {
      if (this.dragIndex === null) {
        return
      }
      event.preventDefault()
      event.dataTransfer.dropEffect = 'move'
      this.dropIndex = index
    },
    drop (index) {
      if (this.dragIndex !== null) {
        this.reorder(this.dragIndex, index)
      }
      this.dragEnd()
    },
    dragEnd () {
      this.dragIndex = null
      this.dropIndex = null
    },
    rmChannel (c) {
      Hub.$emit('deleteChannel', c)
    },
    channelType (id) {
        let type = this.types.find(t => t.id === id) || {};
        return type.name || '';
    },
    expiryDate(channel) {
      return channel.openinghours.map(oh => oh.end_date).sort().reverse()[0]
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
