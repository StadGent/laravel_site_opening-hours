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
      <td class="td-btn text-right" @click.stop>
        <button class="btn btn-default btn-icon" v-if="isOwnerOf(s)" @click="newRoleFromOverview">
          <i class="glyphicon glyphicon-plus"></i>
          <i class="glyphicon glyphicon-user"></i>
        </button>
        <button class="btn btn-default btn-icon" v-if="isOwnerOf(s)" @click="route.tab2 = 'users'">
          Lijst
        </button>
        <span v-else>-</span>
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
