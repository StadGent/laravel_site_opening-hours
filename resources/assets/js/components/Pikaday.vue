<template>
  <input type="text" :value="value">
</template>

<script>
var dateFormatter = date => new Date(date).toDateString()
if (Intl && Intl.DateTimeFormat) {
  dateFormatter = date => new Intl.DateTimeFormat('nl', {
    year: 'numeric',
    month: 'short',
    day: 'numeric' 
  }).format(new Date(date))
}

const pikadayOptions = {
  i18n: {
    previousMonth : 'Vorige maand',
    nextMonth     : 'Volgende maand',
    months        : ['Januari','Februari','Maart','April','Mei','Juni','Juli','Augustus','September','Oktober','November','December'],
    weekdays      : ['Zondag','Maandag','Dinsdag','Woensdag','Donderdag','Vrijdag','Zaterdag'],
    weekdaysShort : ['Zo','Ma','Di','Wo','Do','Vr','Za'],
  }
}

export default {
  props: {
    value: null,
    options: null
  },
  computed: {
    opts () {
      return Object.assign({
        firstDay: 1,
        format: dateFormatter,
        onSelect: (date) => {
          // Correct for timezone
          date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
          this.$emit('input', date.toJSON().slice(0, 10))
        }
      },
      pikadayOptions,
      this.options || {},
      {
        field: this.$el
      })
    }
  },
  methods: {
    render () {
      this.pikaday && this.pikaday.destroy()
      this.pikaday = new Pikaday(this.opts)
      this.$nextTick(() => {
        this.$el.value = this.opts.format(this.value)
      })
    }
  },
  mounted () {
    this.render()
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
    },
    options () {
      this.render()
    }
  }
}
</script>
