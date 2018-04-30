<template>
    <tr>
      <td class="td-sortable">
        <a :href="'#!service/'+s.id">{{ s.label }}</a>
      </td>
      <td class="td-sortable">
        <a v-if="s.source && s.source.toUpperCase() === 'vesta'.toUpperCase()" :href="url" target="_blank">{{ s.source.toUpperCase() }}</a>
        <span v-else>{{ s.source || '' }}</span>
      </td>
      <td :class="statusClass">
        <span
          data-toggle="tooltip"
          :title="statusTooltip"
        >
          <span class="pre-wrap">{{ statusMessage }}</span> &nbsp;
          <i class="glyphicon glyphicon-info-sign" v-if="statusTooltip"></i>
        </span>
      </td>
      <td class="text-muted td-sortable" :title="s.updated_at">
        <div>{{ s.updated_at | date }}</div>
        <div>{{ s.updated_by }}</div>
      </td>
    </tr>
</template>

<script>
import RowServiceMixin from '../mixins/RowService'

export default {
  mixins: [RowServiceMixin]
}
</script>
