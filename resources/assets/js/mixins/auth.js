import { expandUser } from './users.js'

export default {
  data () {
    const userDefault = {
      id: 0,
      name: 'Voornaam Naam',
      roles: [],
      admin: false
    }
    lsDefault('user', userDefault)

    Object.assign(userDefault, window.initialUser || ls('user'))

    return {
      user: expandUser(userDefault)
    }
  },
  computed: {
    isDev () {
      return this.$root.user.email === 'dev@dev.dev'
    },
    isAdmin () {
      return this.$root.user.admin
    },
    isOwner () {
      return this.isOwnerOf(this.srv || this.$root.routeService)
    }
  },
  methods: {
    isOwnerOf (service) {
      return this.isAdmin || service && service.users && service.users.find(u => u.id == this.$root.user.id && u.role === 'Owner')
    },
    logout() {
      return this.$http.post('/logout')
        .catch(() => true)
        .then(() => {
          Object.assign(this.user, {
            id: 0,
            name: 'Naamloos'
          })
          window.location.reload()
        })
    }
  }
}

export const rootAuthMixin = {
  mounted () {
    setTimeout(() => {
      ls('user', this.user)
    }, 1000)
  },
  watch: {
    user: {
      deep: true,
      handler  (v) {
        console.log('Update user', inert(this.user))
        ls('user', v)
      }
    }
  }
}
