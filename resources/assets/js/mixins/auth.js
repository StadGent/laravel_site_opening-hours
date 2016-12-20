import { expandUser } from './users.js'

const userDefault = {
  id: 1,
  name: 'Voornaam Naam',
  roles: [],
  admin: false
}
lsDefault('user', userDefault)

Object.assign(userDefault, window.initialUser || ls('user'))

export const user = expandUser(userDefault)

export default {
  data () {
    return {
      user
    }
  },
  computed: {
    isOwner () {
      return this.user.admin || this.routeService && this.routeService.users && this.routeService.users.find(u => u.user_id == this.user.id && u.role === 'Owner')
    }
  },
  methods: {
    isOwnerOf (service) {
      if (typeof service !== 'object') {
        return false
      }
      return this.user.admin || service && service.users && service.users.find(u => u.user_id == this.user.id && u.role === 'Owner')
    },
    logout() {
      console.log('ha')
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
