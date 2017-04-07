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
  props: ['options', 'value'],
  computed: {
    model: {
      get () {
        let a = this.value
        if (typeof a === 'string') {
          a = a.split(',').map(s => parseInt(s))
        }
        return a || []
      },
      set (v) {
        this.$emit('input', v && v.join(',') || null)
      }
    }
  },
  methods: {
    change (elem) {
      const value = parseInt(elem.val())
      const index = this.model.indexOf(value)
      if (index !== -1) {
        this.model.splice(index, 1)
      } else {
        this.model.push(value)
        this.model.sort((a, b) => a - b)
      }
      this.model = this.model
    }
  },
  mounted () {
    loadScript('bootstrap-multiselect', () => {
      $(this.$el.firstChild).multiselect({
        onChange: this.change,
        buttonText (selected) {
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
