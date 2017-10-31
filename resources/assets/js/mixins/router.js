export const route = window.initialRoute || {
  uri: '/',
  page: 'home',
  tab: null,
  tab2: null,
  id: 'home',
  offset: 0,
  user: null,
  service: -1,
  channel: -1,
  version: -1,
  calendar: -1
}

function hasCalendarSelected(hash) {
  return ['service', 'channel', 'version', 'calendar'].indexOf(hash.split(':')[0]) !== -1
}

export const rootRouterMixin = {
  mounted() {
    this.hashchange()
    window.addEventListener('hashchange', this.hashchange)
  },
  methods: {
    hashchange(evt) {
      if (evt && evt.preventDefault) {
        evt.preventDefault()
      }
      var hash = window.location.hash
      if (hash.startsWith('#!')) {
        hash = hash.slice(2).split('/')
        if (hasCalendarSelected(hash[0])) {
          route.service = parseInt(hash[1] || -1)
          route.channel = parseInt(hash[2] || -1)
          route.version = parseInt(hash[3] || -1)
          route.calendar = parseInt(hash[4] || -1)
        }
        route.page = hash[0]
        route.id = hash[1]
        route.tab2 = null
      } else {
        route.page = window.location.hash.slice(1) || 'home'
        route.tab = null
      }

      if (this.route.page === 'user' || this.route.page === 'home') {
        route.service = -1
        route.channel = -1
        route.version = -1
        route.calendar = -1
      }
      this.replaceHash()

      return false
    }
  }
}

export default {
  data() {
    return {
      route
    }
  },
  methods: {
    // Simulate clicking a link and let it trigger a hashchange event
    href(v) {
      window.location.href = v
    },
    // Update the hash to match the current route without triggering a hashchange event
    replaceHash() {
      if (hasCalendarSelected(this.route.page)) {
        window.location.replace('#!' + [route.page, route.service, route.channel, route.version, route.calendar].join('/'))
      }
    },
    toChannel(c) {
      route.page = 'channel'
      if (typeof c !== 'undefined') {
        route.channel = parseInt(c || 0)
      }
      this.replaceHash()
    },
    toVersion(c) {
      console.log('going to version');
      route.page = 'version'
      if (typeof c !== 'undefined') {
        route.version = parseInt(c || 0)
      }
      route.calendar = -1
      this.replaceHash()
    },
    toCalendar(c) {
      route.page = 'calendar'
      if (typeof c !== 'undefined') {
        route.calendar = parseInt(c || 0)
      }
      this.replaceHash()
    },
    refresh(c) {
      window.location.reload()
    }
  }
}
