export const modal = {
  email: null,
  label: null,
  srv: null,
  strict: null,
  text: null
}

export default {
  data() {
    console.log(modal)
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
      this.modal.label = 'Algemeen'
      this.modal.srv = srv
    },
    newVersion(srv) {
      this.modal.text = 'newVersion'
      this.modal.label = 'Versie 1'
      this.modal.srv = srv
    },
    newCalendar(srv) {
      this.modal.text = 'newCalendar'
      this.modal.srv = srv
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
