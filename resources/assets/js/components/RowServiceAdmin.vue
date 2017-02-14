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
      <td class="td-btn text-right">
        <button class="btn btn-primary btn-icon">
          <i class="glyphicon glyphicon-pencil"></i>
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
  },
  methods: {
    rmService () {

    }
  }
}
</script>
