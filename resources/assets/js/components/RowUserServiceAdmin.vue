<template>
  <tbody @click="href('#!service/'+s.id)">
    <tr>
      <td>
        <a :href="'#!service/'+s.id">{{ s.label }}</a>
      </td>
      <td>
        <div>{{ role }}</div>
      </td>
      <td class="td-btn td-clickstop text-right" @click.stop="manageUsers">
        <button class="btn btn-primary btn-icon">
          <i class="glyphicon glyphicon-user"></i>
        </button>
      </td>
    </tr>
  </tbody>
</template>

<script>
import RowServiceMixin from '../mixins/RowService'

export default {
  props: ['role-of'],
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
    manageUsers () {
      this.href('#!service/' + this.s.id)
      setTimeout(() => {
        this.route.tab2 = 'users'
      }, 100)
    }
  }
}
</script>
