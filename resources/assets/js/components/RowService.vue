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
      <td class="td-btn text-right" @click="route.tab2='users'">
        <button class="btn btn-default btn-icon" v-if="isOwnerOf(s)">
          <i class="glyphicon glyphicon-user"></i>
        </button>
      </td>
      <td class="td-btn text-right">
        <button class="btn btn-default btn-icon btn-subtle">
          <i class="glyphicon glyphicon-pencil"></i>
        </button>
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
