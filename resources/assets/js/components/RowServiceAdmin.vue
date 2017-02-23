<template>
  <tbody @click="href('#!service/'+s.id)">
    <tr>
      <td >
        <a :href="'#!service/'+s.id">{{ s.label }}</a>
      </td>
      <td class="pre-wrap" :class="statusClass" :rowspan="rowspan" v-text="statusMessage"></td>
      <td  class="text-muted">
        <div>{{ s.updated_at | date }}</div>
        <div>{{ s.updated_by }}</div>
      </td>
      <td class="td-btn text-right" @click.stop>
        <button class="btn btn-default btn-icon" @click="newRoleFromOverview">
          <i class="glyphicon glyphicon-plus"></i>
          <i class="glyphicon glyphicon-user"></i>
        </button>
        <button class="btn btn-default btn-icon" @click="route.tab2 = 'users'">
          Lijst
        </button>
      </td>
    </tr>
  </tbody>
</template>

<script>
import RowServiceMixin from '../mixins/RowService'

export default {
  mixins: [RowServiceMixin],
  computed: {
    activeUsers () {
      return this.s.activeUsers.map(u => u.email) || []
    },
    ghostUsers () {
      return this.s.ghostUsers.map(u => u.email) || []
    }
  }
}
</script>
