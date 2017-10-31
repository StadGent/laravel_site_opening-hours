<template>
  <tbody @click="href('#!service/'+s.service_id)">
    <tr>
      <td>
        <a :href="'#!service/'+s.service_id">{{ s.label }}</a>
      </td>
      <td>
        <div>{{$root.translateRole(s.role)}}</div>
      </td>
    </tr>
  </tbody>
</template>

<script>

export default {
  props: ['s'],
}
</script>
