import { expandUser } from './users.js'

const userDefault = {
  id: 1,
  name: 'Voornaam Naam',
  roles: [],
  admin: false,
  owner: false,
  basic: true
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
      return this.user.admin || (this.user.roles.find(r => r.service == this.route.service) || {}).role === 'owner'
    }
  },
  methods: {
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
