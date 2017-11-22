<template>

    <tr>
      <td class="td-btn" @click="activate(s)">
        <button class="btn btn-default btn-icon">
         Activeer
        </button>
      </td>
      <td class="td-sortable">{{ s.label }}</td>

    </tr>

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
