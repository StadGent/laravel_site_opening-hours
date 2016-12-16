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
      return this.routeService.channels && this.routeService.channels[this.route.channel] || {}
    },
    routeVersion () {
      return this.routeChannel.openinghours && this.routeChannel.openinghours[this.route.version] || {}
    },
    routeCalendar () {
      return this.routeVersion.calendars && this.routeVersion.calendars.find(c => c.layer === this.route.calendar) || {}
    }
  },
  methods: {
    fetchServices () {
      return this.$http.get('/api/services')
        .then(({ data }) => {
          this.services = data || []
        })
    }
  },
  mounted () {
    this.fetchServices()
    Hub.$on('createChannel', channel => {
      if (!channel.srv) {
        return console.error('createChannel: service is missing')
      }

      channel.service_id = channel.srv && channel.srv.id
      this.$http.post('/api/channels', channel).then(() => {
        this.fetchServices()
        this.modalClose()
        Hub.$emit('createRolec')
      }).catch(error => {
        console.warn(error)
      })
    })

    Hub.$on('createOpeninghours', openinghours => {
      openinghours.service_id = openinghours.srv && openinghours.srv.id
      console.log('Create openinghours on', inert(openinghours))

      this.$http.post('/api/openinghours', openinghours).then(() => {
        if (!openinghours.srv) {
          return console.error('createopeninghours: service is missing')
        }
        var index = this.services.findIndex(s => s.id === openinghours.srv.id)
        if (index === -1) {
          return console.error('createopeninghours: service is not found')
        }
        var srv = this.services[index]
        if (!srv) {
          return console.error('createopeninghours: service is invalid')
        }
        if(!srv.openinghours) {
          srv.openinghours = []
        }
        srv.openinghours.push({
          id: Math.floor(Math.random() * 1000),
          label: openinghours.label,
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
