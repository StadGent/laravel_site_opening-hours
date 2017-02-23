<template>
  <input type="text" :value="value">
</template>

<script>
var dateFormatter = date => new Date(date).toDateString()
if (Intl && Intl.DateTimeFormat) {
  dateFormatter = date => new Intl.DateTimeFormat('nl', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric' 
  }).format(new Date(date))
}

export default {
  props: {
    value: null,
    options: {
      default () {
        return {}
      }
    }
  },
  computed: {
    opts () {
      return Object.assign({
        format: dateFormatter,
        onSelect: (date) => {
          // Correct for timezone
          date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
          this.$emit('input', date.toJSON().slice(0, 10))
        }
      }, this.options, {
        field: this.$el
      })
    }
  },
  mounted () {
    this.pikaday = new Pikaday(this.opts)
    this.$nextTick(() => {
      this.$el.value = this.opts.format(this.value)
    })
  },
  beforeDestroy () {
    this.pikaday.destroy()
  },
  watch: {
    value (date, old) {
      this.$nextTick(() => {
      if (this.pikaday && this.pikaday.getDate().toJSON().slice(0, 10) !== date) {
          this.pikaday.setDate(date)
          this.$el.value = this.opts.format(date)
        }
      })
    }
  }
}
</script>
