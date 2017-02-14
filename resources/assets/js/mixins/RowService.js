import {
  hasCal,
  hasChannels,
  toChannelAlert,
  toChannelStatus
} from '../lib.js'

var exampleChannel = {
  "@type": "Channel",
  "label": "Loket",
  "oh": [{
    "@type": "oh:OpeningHours",
    "active": true,
    "calendar": [{
      "@type": "oh:Calendar",
      "label": "Super uitzonderingen",
      "rdfcal": [{}]
    }, {
      "@type": "oh:Calendar",
      "label": "Nationale feestdagen",
      "rdfcal": [{}]
    }, {
      "@type": "oh:Calendar",
      "label": "Normale uren",
      "rdfcal": [{}]
    }]
  }]
}

export default {
  props: ['s'],
  computed: {
    rowspan() {
      var s = this.s
      return 1 + (this.countChannels || 1)
    },
    hasChannels() {
      return hasChannels(this.s)
    },
    countChannels() {
      return this.hasChannels.length
    },
    countCals(h) {
      var s = this.s
      return hasChannels(this.s).map(v => hasCal(v).length).reduce((a, b) => a + b, 0)
    },
    old() {
      return this.s.updated_at ? (Date.now() - new Date(this.s.updated_at)) / 1000 / 3600 / 24 : 0
    },
    statusClass() {
      return {
        'text-success': this.statusMessage === '✓ In orde',
        'warning': this.statusMessage !== '✓ In orde',
        'small': this.statusMessage.length > 20
      }
    },
    statusMessage() {
      if (this.user.admin) {
        // if (!this.activeUsers.length && !this.ghostUsers.length) {
        //   return 'Geen gebruikers'
        // }
        if (!this.ghostUsers) {
          return 'Geen actieve gebruikers'
        }
      }
      const channelAlerts = this.hasChannels.filter(toChannelAlert)
      if (channelAlerts.length) {
        return channelAlerts.map(c => toChannelStatus(c, true)).join('\n')
      }
      if (!this.countChannels) {
        return 'Geen kanalen'
      }
      if (this.old > 200) {
        return 'Verouderd'
      }
      return '✓ In orde'
    }
  }
}
