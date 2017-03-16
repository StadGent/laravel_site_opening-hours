import {
  hasActiveOh,
  hasOh,
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
        'text-success': this.statusMessage === '✓ Volledig',
        'warning': this.statusMessage !== '✓ Volledig',
        'small': this.statusMessage.length > 20
      }
    },
    statusMessage() {
      // Service without channels
      if (!this.countChannels) {
        return 'Geen kanalen'
      }

      // Not every channel of the service has at least 1 version
      if (!this.hasChannels.filter(ch => hasOh(ch).length).length) {
        return 'Ontbrekende kalender(s)'
      }

      // Not every channel of the service has at least 1 active version
      if (!this.hasChannels.filter(ch => hasActiveOh(ch).length).length) {
        return 'Ontbrekende actieve kalender(s)'
      }

      return '✓ Volledig'
    },

    // TODO: refactor into structured set of messages
    statusTooltip() {
      switch (this.statusMessage) {
        case 'Geen kanalen': return 'Deze dienst heeft geen kanalen.'
        case 'Ontbrekende kalender(s)': return 'Minstens 1 van de kanalen van deze dienst heeft geen versies.'
        case 'Ontbrekende actieve kalender(s)': return 'Alle kanalen hebben een versie maar minstens 1 kanaal heeft geen versie die nu geldt. Een versie geldt niet als deze verlopen is of pas in de toekomst actief wordt.'
      }
    }
  },
  methods: {
    newRoleFromOverview () {
      this.newRole(this.s)
      this.href('#!service/' + this.s.id)
      this.route.tab2 = 'users'
    }
  },
  mounted () {
    $('[data-toggle="tooltip"]').tooltip()
  }
}
