<template>
  <tbody>
    <tr :class="{'warning':!u.verified}" @click="href('#!user/'+u.id)">
      <td>
        {{ u.name }}
      </td>
      <td>
        {{ u.email }}
      </td>
      <td @click.stop>
        <div class="pull-left">rol</div>
      </td>
      <td v-if="u.verified" class="text-success">&checkmark;</td>
      <td v-else class="text-warning">&cross;</td>
      <td class="td-btn text-right">
        <button @click="invite" class="btn btn-default btn-icon" v-if="!u.verified">
          <i class="glyphicon glyphicon-envelope"></i>
        </button>
      </td>
      <td class="td-btn text-right" @click.stop>
        <button @click="$parent.banUser(u)" class="btn btn-default btn-icon">
          <i class="glyphicon glyphicon-ban-circle"></i>
        </button>
      </td>
    </tr>
  </tbody>
</template>

<script>
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
      // invite user
      alert('to implement: invite ' + u.name)
    }
  }
}
</script>
