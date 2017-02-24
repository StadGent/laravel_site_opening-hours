<template>
  <tbody>
    <tr :class="{'warning':!u.verified}" @click="href('#!user/'+u.id)">
      <td>
        {{ u.name }}
      </td>
      <td>
        {{ u.email }}
      </td>
      <td @click.stop class="td-clickstop">
        <div>{{ u.roles.length }} dienst{{ u.roles.length === 1 ? '' : 'en' }}</div>
        <div>
          <div v-for="r in u.roles">
            {{ r.role === 'Owner' ? 'Eigenaar' : 'Lid' }}
            &nbsp;
            <a :href="'#!service/' + r.service_id">{{ $root.serviceById(r.service_id).label }}</a>
          </div>
        </div>
      </td>
      <td v-if="u.verified" class="text-success">&checkmark;</td>
      <td v-else class="text-warning">&cross;</td>
      <td class="td-btn text-right" @click.stop>
        <button @click="rm" class="btn btn-default btn-icon">
          <i class="glyphicon glyphicon-trash"></i>
        </button>
      </td>
    </tr>
  </tbody>
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
    invite () {
      Hub.$emit('inviteUser', this.u)
    },
    rm () {
      Hub.$emit('deleteUser', this.u)
    }
  }
}
</script>
