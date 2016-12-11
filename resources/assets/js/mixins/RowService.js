const today = new Date().toJSON().slice(0, 10)

/** Service functions **/

function hasChannels(s) {
  return s && s.availableChannel || []
}
// Calendars in the first OH of the channels of a service
function countCals(s) {
  return hasChannels(s).map(ch => hasCal(ch).length).reduce((a, b) => a + b, 0)
}

/** Channel functions **/

function hasOh(ch) {
  return ch && ch.oh || []
}

function hasCal(ch) {
  return ch && ch.oh && ch.oh[0] && ch.oh[0].calendar || []
}

// Get active OH of a channel
function hasActiveOh(ch) {
  return ch && ch.oh && ch.oh.filter(x => x.active) || []
}

// Get active expiring OH of a channel
function hasExpiringOh(ch) {
  return ch && ch.oh && (ch.oh.find(x => x.active) || {}).calendar || []
}

function toChannelStatus(ch) {
  const oh = hasActiveOh(ch)
  let dtend = today


  let nextOh = oh.find(x => isInUseOn(x, today))
  if (!nextOh) {
    return dtend
  } else {
    dtend = nextOh.dtend
  }


  console.log(validUntil)
  return validUntil
}

/** OH functions **/

function isInUseOn(oh, date) {
  console.log(oh['@type'], date)
  return (oh.dtstart ? oh.dtstart < date : true) && (oh.dtend ? oh.dtend > date : true)
}

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

console.log(hasActiveOh(exampleChannel))
console.log('?')

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
      return this.statusMessage === '✓ In orde' ? 'text-success' : 'warning'
    },
    statusMessage() {
      if (this.user.admin) {
        if (!this.activeUsers.length && !this.ghostUsers.length) {
          return 'Geen gebruikers'
        } else if (!this.ghostUsers) {
          return 'Geen actieve gebruikers'
        }
      }
      const status = this.hasChannels.map(toChannelStatus).join('\n')
      if (status) {
        return status
      }
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
  }
}
