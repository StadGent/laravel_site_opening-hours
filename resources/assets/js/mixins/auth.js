export default {
  data () {
    const userDefault = {
      id: 0,
      name: 'Voornaam Naam',
      roles: [],
      admin: false
    };
    lsDefault('user', userDefault);

    Object.assign(userDefault, window.initialUser || ls('user'));

    return {
      user: userDefault
    }
  },
  computed: {
    isDev () {
      return this.$root.user.email === 'dev@dev.dev'
    },
    isAdmin () {
      return this.$root.user.admin
    },
    isEditor() {
      return this.$root.user.editor
    },
    isOwner () {
      return this.isOwnerOf(this.srv || this.$root.routeService)
    },
    isMember () {
      return this.isMemberOf(this.srv || this.$root.routeService)
    }
  },
  methods: {
    isOwnerOf (service) {
      return this.isAdmin || service && service.users && service.users.find(u => u.id == this.$root.user.id && u.role === 'Owner')
    },
    isMemberOf (service) {
      return this.isAdmin || service && service.users && service.users.find(u => u.id == this.$root.user.id && u.role === 'Member')
    },
    logout() {
      return this.$http.post('/logout')
        .catch(() => true)
        .then(() => {
          Object.assign(this.user, {
            id: 0,
            name: 'Naamloos'
          });
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
        console.log('Update user', inert(this.user));
        ls('user', v)
      }
    }
  }
};
