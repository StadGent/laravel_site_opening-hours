<template>
    <tr>
      <td class="td-sortable">
        <a :href="'#!service/'+s.id">{{ s.label }}</a>
      </td>
      <td class="td-sortable">
          <a v-if="s.source && s.source.toUpperCase() === 'vesta'.toUpperCase()" :href="url" target="_blank">{{ s.source.toUpperCase() }}</a>
        <span v-else>{{ s.source || '' }}</span>
      </td>
      <td :class="getStatusClass(s.status)">
        <span
          :data-toggle="getStatusTooltip(s.status) ? 'tooltip' : null"
          :title="getStatusTooltip(s.status)"
        >
          <span class="pre-wrap">{{ s.status }}</span> &nbsp;
          <i class="glyphicon glyphicon-info-sign" v-if="getStatusTooltip(s.status)"></i>
        </span>
      </td>
      <td class="td-sortable" v-text="s.end_date">
      </td>
      <td  class="text-muted td-sortable" :title="s.updated_at">
        <div>{{ s.updated_at | date }}</div>
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
  mixins: [RowServiceMixin]
}
</script>
