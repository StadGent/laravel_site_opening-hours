<template>
    <tr :class="{'warning':!u.verified}" @click="href('#!user/'+u.id)">
      <td class="td-sortable">
        <a v-if="this.$root.isAdmin" :href="'#!user/'+u.id">{{ u.name }}</a>
      </td>
      <td class="td-sortable">
        {{ u.email }}
      </td>
      <td @click.stop class="td-clickstop td-sortable">
        <div v-if="! u.roles.length">-</div>
        <div>
          <div v-for="r in u.roles">
            {{$root.translateRole(r.role)}}
            &nbsp;
            <a :href="'#!service/' + r.service_id">{{ $root.serviceById(r.service_id).label }}</a>
          </div>
        </div>
      </td>
      <td v-if="u.verified" class="text-success td-sortable">&checkmark;</td>
      <td v-else class="text-warning td-sortable">&cross;</td>
      <td class="td-btn text-right" @click.stop>
        <button @click="rm" class="btn btn-default btn-icon">
          <i class="glyphicon glyphicon-trash"></i>
        </button>
      </td>
    </tr>
</template>

<script>
import { Hub } from '../lib.js'

export default {
  props: ['u'],
  computed: {
    statusClass () {
      return !this.u || !this.u || this.u > 200 ? 'warning' : 'text-success'
    },
    statusMessage () {
      if (!this.u) {
        return 'Geen kanalen'
      }
      if (!this.u) {
        return 'Niet ingevoerd'
      }
      if (this.u > 200) {
        return 'Verouderd'
      }
      return '✓ In orde'
    }
  },
  methods: {
    rm () {
      Hub.$emit('deleteUser', this.u)
    }
  }
}
</script>
