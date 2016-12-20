<template>
  <div class="container">
    <h1>Versies <small>{{ channel.label }}</small></h1>

    <button v-if="!route.tab2" type="button" class="btn btn-primary" @click="newVersion()">+ Nieuwe versie</button>

      <div v-if="!versions||!versions.length" style="padding:5em 0;">
        <h3 class="text-muted">Er werden nog geen versies voor dit kanaal aangemaakt.</h3>
        <p>
          <button class="btn btn-primary btn-lg" @click="newVersion">Voeg een eerste versie toe</button>
        </p>
      </div>
      <table v-else class="table table-hover">
        <thead>
          <tr>
            <th-sort by="label">Actief</th-sort>
            <th-sort by="start_date">Geldig van</th-sort>
            <th-sort by="end_date">Verloopt op</th-sort>
            <th class="text-right">Verwijder</th>
            <th class="text-right">Bewerk</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="version in versions" @click="href('#!version/'+[srv.id,route.channel,version.id].join('/'))">
            <td>
              <a :href="'#!version/'+[srv.id,route.channel,version.id].join('/')">{{ version.label || 'Zonder label' }}</a>
            </td>
            <td>{{ version.start_date }}</td>
            <td>{{ version.end_date }}</td>
            <td class="td-btn text-right">
              <button class="btn btn-icon btn-default">
                <i class="glyphicon glyphicon-trash"></i>
              </button>
            </td>
            <td class="td-btn text-right">
              <button class="btn btn-icon btn-primary">
                <i class="glyphicon glyphicon-pencil"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
  </div>
</template>

<script>
import ThSort from '../components/ThSort.vue'

export default {
  name: 'channel',
  data () {
    return {
      editing: 0,
      msg: 'Hello Vue!'
    }
  },
  computed: {
    srv () {
      return this.$parent.services.find(s => s.id === this.route.service) || {}
    },
    channel () {
      return this.$parent.routeChannel || {}
    },
    versions () {
      return this.$parent.routeChannel.openinghours || []
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
