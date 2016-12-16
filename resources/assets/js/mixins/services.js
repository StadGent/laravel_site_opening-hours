import { Hub } from '../lib.js'

import { createVersion } from '../defaults.js'
 
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
      return this.routeService.channels && this.routeService.channels.find(c => c.id === this.route.channel) || {}
    },
    routeVersion () {
      return this.routeChannel.openinghours && this.routeChannel.openinghours.find(o => o.id === this.route.version) || {}
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
      this.$http.post('/api/channels', channel).then(({ data }) => {
        this.fetchServices()
        this.modalClose()
        console.log(data, data.id)
        this.toChannel(data.id)
      }).catch(error => {
        console.warn(error)
      })
    })

    Hub.$on('createVersion', input => {
      const version = Object.assign(createVersion(), input)
      if (!version.channel_id) {
        version.channel_id = this.route.channel
      }
      console.log('Create version', inert(version))

      this.$http.post('/api/openinghours', version).then(({ data }) => {
        this.fetchServices()
        this.modalClose()
        this.toVersion(data.id)
      })
    })
  }
}
