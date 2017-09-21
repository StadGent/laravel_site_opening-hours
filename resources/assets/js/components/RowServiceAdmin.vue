<template>
    <tr>
      <td class="td-sortable">
        <a :href="'#!service/'+s.id">{{ s.label }}</a>
      </td>
      <td :class="statusClass">
        <span
          :data-toggle="statusTooltip ? 'tooltip' : null"
          :title="statusTooltip"
        >
          <span class="pre-wrap">{{ statusMessage }}</span> &nbsp;
          <i class="glyphicon glyphicon-info-sign" v-if="statusTooltip"></i>
        </span>
      </td>
      <td  class="text-muted td-sortable" :title="s.updated_at">
        <div>{{ s.updated_at | date }}</div>
        <div>{{ s.updated_by }}</div>
      </td>
      <td class="td-btn text-right" @click="deactivate(s)">
        <button class="btn btn-default btn-icon">
          <i class="glyphicon glyphicon-remove"></i>
        </button>
      </td>
    </tr>
</template>

<script>
import RowServiceMixin from '../mixins/RowService'
import { Hub } from '../lib.js'

export default {
  methods: {
      deactivate (service) {
          if (service.draft) {
              return alert('Deze dienst is al gedeactiveerd')
          }
          Hub.$emit('deactivateService', service)
      }
  },
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
