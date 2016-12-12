import { Hub } from '../lib.js'
 
export const services = window.initialServices || []

export default {
  data () {
    return {
      services
    }
  },
  computed: {
    routeService () {
      return this.services.find(s => s.id === this.route.service) || {}
    },
    routeChannel () {
      return this.routeService.availableChannel && this.routeService.availableChannel[this.route.channel] || {}
    },
    routeVersion () {
      return this.routeChannel.oh && this.routeChannel.oh[this.route.version] || {}
    },
    routeCalendar () {
      return this.routeVersion.calendar && this.routeVersion.calendar[this.route.calendar] || {}
    }
  },
  methods: {
    fetchServices () {
      return this.$http.get('/api/services.json')
        .then(({ data }) => {
          this.services = data || []
        })
    }
  },
  mounted () {
    this.fetchServices()
    Hub.$on('createChannel', modal => {
      console.log('Create channel on', modal)
      setTimeout(() => {
        if (!modal.srv) {
          return console.error('createChannel: service is missing')
        }
        var index = this.services.findIndex(s => s.id === modal.srv.id)
        if (index === -1) {
          return console.error('createChannel: service is not found')
        }
        var srv = this.services[index]
        if (!srv) {
          return console.error('createChannel: service is invalid')
        }
        if(!srv.availableChannel) {
          srv.availableChannel = []
        }
        srv.availableChannel.push({
          id: Math.floor(Math.random() * 1000),
          label: modal.label,
          created_at: new Date().toJSON().slice(0, 19),
          created_by: this.user.name,
          updated_at: new Date().toJSON().slice(0, 19),
          updated_by: this.user.name,
          oh: []
        })
        this.$set(this.services, index, srv)
        this.modalClose()
      }, 100)
    })
  }
}
