import { hasActiveOh, expiresOn } from '../lib.js'

export const modal = {
  email: null,
  label: null,
  srv: null,
  strict: null,
  start_date: null,
  end_date: null,
  text: null
}

export default {
  data() {
    return {
      modal
    }
  },
  computed: {
    modalActive() {
      for (const key in this.modal) {
        if (key !== 'srv' && this.modal[key]) {
          return true
        }
      }
    }
  },
  methods: {
    modalClose() {
      for (const key in this.modal) {
        this.modal[key] = null
      }
    },
    requestService() {
      console.log('req')
      this.modal.text = 'requestService'
    },

    newChannel(srv) {
      this.modal.text = 'newChannel'
      this.modal.label = ''
      this.modal.srv = srv
    },
    newVersion(srv) {
      this.modal.text = 'newVersion'
      this.modal.label = ''
      this.modal.start_date = '2016-01-01'
      this.modal.end_date = '2021-01-01'
      this.modal.srv = srv

      const expires = expiresOn(hasActiveOh(this.$parent.routeChannel))
      console.log(expires)
      console.log(inert(this.$parent.routeVersion))
      if (expires) {
        this.modal.start_date = expires.slice(0, 4) + '-01-01'
        this.modal.end_date = (parseInt(expires.slice(0, 4), 10) + 5) + '-01-01'
      }
    },
    newCalendar(srv) {
      this.modal.text = 'newCalendar'
      this.modal.srv = srv
    },

    editVersion(v) {
      this.modal.text = 'newVersion'
      Object.assign(this.modal, v)
    },

    newUser(srv) {
      this.modal.text = 'newUser'
      this.modal.srv = srv
    },
    newRole(srv) {
      this.modal.text = 'newRole'
      this.modal.srv = srv
    }
  }
}
