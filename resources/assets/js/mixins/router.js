export const route = window.initialRoute || {
  uri: '/',
  page: 'home',
  id: 'home'
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
        if (hash[0] == 'service') {
          this.route.page = hash[3] ? 'calendar' : hash[2] ? 'channel' : hash[1] ? 'service' : 'home'
          this.$set(this.route, 'service', hash[1])
          this.$set(this.route, 'channel', parseInt(hash[2] || 0))
          this.$set(this.route, 'calendar', parseInt(hash[3] || 0))
        } else {
          this.route.page = hash[0]
          this.route.id = hash[1]
        }
      } else {
        this.route.page = window.location.hash.slice(1) || 'home'
      }
      // var colon = window.location.hash.indexOf(':')
      // if (colon > 4) {
      //  this.route.page = window.location.hash.slice(2, colon)
      //  this.$emit('id', window.location.hash.slice(1 + window.location.hash.indexOf(':')))
      // } else {
      // }
    }
  }
}

export default {
  data() {
    return {
      route
    }
  }
}
