<template>
  <tbody>
    <tr>
      <td class="td-btn" @click="activate(s)">
        <button class="btn btn-default btn-icon">
          <i class="glyphicon glyphicon-ok"></i>
        </button>
      </td>
      <td>{{ s.label }}</td>
    </tr>
  </tbody>
</template>

<script>
import RowServiceMixin from '../mixins/RowService'
import { Hub } from '../lib.js'

export default {
  methods: {
    activate (service) {
      if (!service.draft) {
        return alert('Deze dienst is al geactiveerd')
      }
      Hub.$emit('activateService', service)
    }
  },
  mixins: [RowServiceMixin]
}
</script>
