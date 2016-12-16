<template>
  <div class="container">
    <h1>{{ channel.label }} versies <small>{{ $parent.routeService.label || 'Dienst zonder naam' }}</small></h1>

    <button v-if="!route.tab2" type="button" class="btn btn-primary" @click="newChannel(srv)">+ Nieuw kanaal</button>

      <div v-if="!versions||!versions.length" style="padding:5em 0;">
        <h1>Empty state</h1>
        <p>
          <button class="btn btn-primary btn-lg" @click="newVersion">Voeg een eerste versie toe</button>
        </p>
      </div>
      <table v-else class="table">
        <thead>
          <tr>
            <th-sort by="label">Actief</th-sort>
            <th-sort by="start_date">Geldig van</th-sort>
            <th-sort by="end_date">Geldig tot</th-sort>
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
            <td class="text-right">
              <a :href="'#!channel/'+[srv.id,version.id].join('/')" class="btn btn-icon btn-default">
                <i class="glyphicon glyphicon-trash"></i>
              </a>
            </td>
            <td class="text-right">
              <a :href="'#!channel/'+[srv.id,version.id].join('/')" class="btn btn-icon btn-primary">
                <i class="glyphicon glyphicon-pencil"></i>
              </a>
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
