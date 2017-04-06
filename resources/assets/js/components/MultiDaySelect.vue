<template>
  <span>
    <select class="multi-day-select" multiple="multiple" v-model="model">
      <option v-for="(opt, index) in options" :value="index">{{ opt }}</option>
    </select>
  </span>
</template>

<script>
import { loadScript } from '../lib.js'

const days = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo']

export default {
  name: 'multi-select',
  props: ['options', 'parent', 'prop'],
  computed: {
    model () {
      let a = this.parent[this.prop]
      if (typeof a === 'string') {
        a = a.split(',')
      }
      return a || []
    }
  },
  methods: {
    change (elem, checked) {
      this.$emit('change', parseInt(elem.val()))
    }
  },
  mounted () {
    if (!this.parent[this.prop]) {
      this.parent[this.prop] = []
    }
    loadScript('bootstrap-multiselect', () => {
      $(this.$el.firstChild).multiselect({
        onChange: this.change,
        buttonText (selected, b) {
          if (!selected.length) {
            return 'ongeldig'
          }
          const text = []
          let rangeStart = selected[0].value
          let rangeEnd = selected[0].value
          for (var i = 1; i < selected.length; i++) {
            const value = selected[i].value
            if (rangeEnd == value - 1) {
              rangeEnd = value
            } else {
              text.push(rangeEnd === rangeStart ? days[rangeEnd] : days[rangeStart] + ' - ' + days[rangeEnd])
              rangeEnd = value
              rangeStart = value
            }
          }
          text.push(rangeEnd === rangeStart ? days[rangeEnd] : days[rangeStart] + ' - ' + days[rangeEnd])
          return text.join(', ')
        }
      }).multiselect('select', this.model)
    })
  },
  beforeDestroy () {
    $(this.$el).multiselect('destroy')
  }
}
</script>
