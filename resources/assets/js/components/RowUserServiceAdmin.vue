<template>
  <tbody @click="href('#!service/'+s.service_id)">
    <tr>
      <td>
        <a :href="'#!service/' + s.service_id">{{ s.label }}</a>
      </td>
      <td>
        <div>{{$root.translateRole(s.role)}}</div>
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

export default {
  props: ['s'],
  methods: {
    manageUsers () {
      this.href('#!service/' + this.s.service_id);
      setTimeout(() => {
        this.route.tab2 = 'users'
      }, 100)
    }
  }
}
</script>
