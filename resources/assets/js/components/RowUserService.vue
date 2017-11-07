<template>
    <tr>
      <td>
        <a :href="'#!service/'+s.service_id">{{ s.label }}</a>
      </td>
      <td>
        <div>{{$root.translateRole(s.role)}}</div>
      </td>
    </tr>
</template>

<script>

export default {
  props: ['s'],
}
</script>
