<template>
  <tbody @click="href('#!service/'+s.id)">
    <tr>
      <td>
        <a :href="'#!service/'+s.id">{{ s.label }}</a>
      </td>
      <td class="pre-wrap" :class="statusClass" :rowspan="rowspan" v-text="statusMessage"></td>
      <td :rowspan="rowspan" class="text-muted">
        <div>{{ s.updated_at | date }}</div>
        <div>{{ s.updated_by }}</div>
      </td>
    </tr>
  </tbody>
</template>

<script>
import RowServiceMixin from '../mixins/RowService'

export default {
  mixins: [RowServiceMixin]
}
</script>
