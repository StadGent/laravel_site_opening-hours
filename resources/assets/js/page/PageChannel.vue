<template>
  <div class="container">
    <h1>Versies <small>{{ channel.label }}</small></h1>

    <button type="button" class="btn btn-primary" @click="newVersion" :disabled="$root.isRecreatex">+ Nieuwe versie</button>

    <div v-if="!versions||!versions.length" class="table-message">
      <h3 class="text-muted">Er werden nog geen versies voor dit kanaal aangemaakt.</h3>
      <p>
        <button class="btn btn-primary btn-lg" @click="newVersion" :disabled="$root.isRecreatex">Voeg een eerste versie toe</button>
      </p>
    </div>
    <div v-else>
      <table class="table table-hover">
        <thead>
          <tr>
            <th-sort by="label">Versie</th-sort>
            <th-sort by="start_date">Geldig van</th-sort>
            <th-sort by="end_date">Verloopt op</th-sort>
            <th class="text-right">Verwijder</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="version in sortedVersions"
            @click="href('#!version/'+[srv.id,route.channel,version.id].join('/'))"
            :class="{ 'success text-success': isActive(version) }"
          >
            <td>
              <a :href="'#!version/'+[srv.id,route.channel,version.id].join('/')">{{ version.label || 'Zonder label' }}</a>
            </td>
            <td :title="version.start_date">{{ version.start_date | date }}</td>
            <td :title="version.end_date">{{ version.end_date | date }}</td>
            <td class="td-btn text-right" @click.stop>
              <button class="btn btn-icon btn-default" @click="deleteVersion(version)" :disabled="$root.isRecreatex">
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
import { Hub, orderBy, isInUseOn } from '../lib.js'

const today = new Date().toJSON().slice(0, 10)

export default {
  name: 'channel',
  data () {
    return {
      order: null,
      // query: null,
      editing: 0,
      msg: 'Hello Vue!'
    }
  },
  computed: {
    srv () {
      return this.$root.services.find(s => s.id === this.route.service) || {}
    },
    channel () {
      return this.$root.routeChannel || {}
    },
    versions () {
      return this.$root.routeChannel.openinghours || []
    },
    filteredVersions () {
      return this.query ? this.versions.filter(s => s.label.indexOf(this.query) !== -1) : this.versions
    },
    sortedVersions () {
      return this.order ? this.filteredVersions.slice().sort(orderBy(this.order)) : this.filteredVersions
    }
  },
  methods: {
    isActive (v) {
      return isInUseOn(v, today)
    },
    deleteVersion (v) {
      Hub.$emit('deleteVersion', v)
    }
  },
  mounted () {
    // Dev mode, if there is only 1 version, just open it
    if (this.versions.length === 1) {
      // this.toVersion(0)
    }
  },
  components: {
    ThSort
  }
}
</script>
