<template>
  <tbody @click="href('#!service/'+s.id)">
    <tr>
      <td >
        <a :href="'#!service/'+s.id">{{ s.label }}</a>
      </td>
      <td>
        <div>{{ role }}</div>
      </td>
    </tr>
  </tbody>
</template>

<script>
import RowServiceMixin from '../mixins/RowService'

export default {
  props: ['roleOf'],
  mixins: [RowServiceMixin],
  computed: {
    roleObj () {
      return this.roleOf.roles && this.roleOf.roles.find(r => r.service_id === this.s.id) || {}
    },
    role () {
      return this.roleObj.role || 'Geen rol'
    }
  },
  methods: {
    rmService () {

    }
  }
}
</script>
