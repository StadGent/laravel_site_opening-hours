<template>
  <tbody>
    <tr>
      <td :rowspan="rowspan">
        <a :href="'#!service/'+s.id">{{ s.label }}</a>
        <div>
          <!-- 2 versies -->
        </div>
      </td>
      <td :class="statusClass" :rowspan="rowspan">
        {{ statusMessage }}
      </td>
      <td :rowspan="rowspan" class="text-muted">
        <div>{{ s.updated_at | date }}</div>
        <div>{{ s.updated_by }}</div>
      </td>
    </tr>
    <tr v-for="(channel, ch) in s.availableChannel">
      <td>
        <a :href="'#!service/'+[s.id,ch].join('/')">{{ channel.label }}</a>
      </td>
      <td>
        <ol class="table-list">
          <li v-for="(calendar, cal) in channel.hours.calendar" :class="{'text-danger':!calendar.rdfcal||!calendar.rdfcal.length}">
            <a :href="'#!service/'+[s.id,ch,cal].join('/')">{{ calendar.label }}</a>
          </li>
        </ol>
        <a href="#" class="text-danger" v-if="!channel.hours.calendar||!channel.hours.calendar.length">Toevoegen...</a>
      </td>
    </tr>
    <tr v-if="!s.availableChannel">
      <td>
        <a href="#" class="text-danger">Toevoegen...</a></td>
      <td></td>
    </tr>
  </tbody>
</template>

<script>
export default {
  props: ['s'],
  computed: {
    rowspan () {
      var s = this.s
      return 1 + (this.countChannels || 1)
    },
    countChannels () {
      var s = this.s
      return s && s.availableChannel && s.availableChannel.length || 0
    },
    countCals (h) {
      var s = this.s
      return (s.availableChannel || []).map(v => this.countCal(v), 0).reduce((a, b) => a + b, 0)
    },
    old () {
      return this.s.updated_at ? (Date.now() - new Date(this.s.updated_at)) / 1000 / 3600 / 24 : 0
    },
    statusClass () {
      return !this.countChannels || !this.countCals || this.old > 200 ? 'warning' : 'text-success'
    },
    statusMessage () {
      if (!this.countChannels) {
        return 'Geen kanalen'
      }
      if (!this.countCals) {
        return 'Niet ingevoerd'
      }
      if (this.old > 200) {
        return 'Verouderd'
      }
      return '✓ In orde'
    }
  },
  methods: {
    countCal (h) {
      return h && h.hours && h.hours.calendar ? h.hours.calendar.length : 0
    }
  }
}
</script>
