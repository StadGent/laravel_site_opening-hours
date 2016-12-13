<template>
  <tbody @click="href('#!service/'+s.id)">
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
    <tr v-for="(channel, ch) in s.channels" @click.stop="href('#!channel/'+[s.id,ch].join('/'))">
      <td>
        <a :href="'#!channel/'+[s.id,ch].join('/')">{{ channel.label }}</a>
      </td>
      <td>
        <ol class="table-list">
          <li v-for="(calendar, cal) in channel.openinghours.calendars" :class="{'text-danger':!calendar.rdfcal||!calendar.rdfcal.length}">
            <a :href="'#!calendar/'+[s.id,ch,cal].join('/')">{{ calendar.label }}</a>
          </li>
        </ol>
        <a href="#" class="text-danger" v-if="!channel.openinghours.calendars||!channel.openinghours.calendars.length">Toevoegen...</a>
      </td>
    </tr>
    <tr v-if="!s.channels">
      <td>
        <a href="#" class="text-danger">Toevoegen...</a></td>
      <td></td>
    </tr>
  </tbody>
</template>

<script>
import RowServiceMixin from '../mixins/RowService'

export default {
  mixins: [RowServiceMixin]
}
</script>
