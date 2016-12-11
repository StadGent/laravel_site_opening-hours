export const route = window.initialRoute || {
  uri: '/',
  page: 'home',
  tab: null,
  tab2: null,
  id: 'home',
  offset: 0,
  service: -1,
  channel: -1,
  calendar: -1
}

function hasCalendarSelected(hash) {
  return ['service', 'channel', 'calendar'].indexOf(hash.split(':')[0]) !== -1
}

export const addListener = {
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
          route.calendar = parseInt(hash[3] || -1)
        }
        route.page = hash[0]
        route.id = hash[1]
      } else {
        route.page = window.location.hash.slice(1) || 'home'
      }

      if (hasCalendarSelected(this.route.page)) {
        window.location.replace('#!' + [route.page, route.service, route.channel, route.calendar].join('/'))
      }

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
    href(v) {
      window.location.href = v
    },
    toChannel(c) {
      console.log(c)
      route.page = 'channel'
      route.channel = parseInt(c || 0)
    },
    toCalendar(c) {
      route.page = 'calendar'
      route.calendar = parseInt(c || 0)
    },
    refresh(c) {
      route.page = 'calendar'
      route.calendar = parseInt(c || 0)
    }
  }
}
