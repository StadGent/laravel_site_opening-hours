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
        <div class="pull-left">{{ u.roles.length }}: </div>
        <div style="margin-left:20px;">
          <div v-for="r in u.roles">
          {{ r.service }}
          <input type="radio" value="Owner" v-model="r.role">
          <input type="radio" value="Member" v-model="r.role">
          </div>
        </div>
      </td>
      <td v-if="u.verified" class="text-success">&checkmark;</td>
      <td v-else class="text-warning">&cross;</td>
      <td class="td-btn text-right">
        <button @click="invite" class="btn btn-default btn-icon" v-if="!u.verified">
          <i class="glyphicon glyphicon-envelope"></i>
        </button>
      </td>
      <td class="td-btn text-right">
        <button @click="rmUser" class="btn btn-default btn-icon">
          <i class="glyphicon glyphicon-trash"></i>
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
    },
    rmUser () {
      // invite user
      alert('to implement: remove user ' + u.name)
    }
  }
}
</script>
