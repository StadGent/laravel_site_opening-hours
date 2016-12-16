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
      return this.routeVersion.calendars && this.routeVersion.calendars.find(c => c.id === this.route.calendar) || {}
    }
  },
  methods: {
    fetchServices () {
      return this.$http.get('/api/services')
        .then(({ data }) => {
          this.services = data || []
        })
    },
    fetchVersion () {
      if (!this.routeVersion) {
        return console.warn('no route version')
      }
      return this.$http.get('/api/openinghours/' + this.route.version)
        .then(({ data }) => {
          const index = this.routeChannel.openinghours.findIndex(o => o.id === data.id)
          if (!index) {
            return console.warn('did not find this version', data)
          }
          this.$set(this.routeChannel.openinghours, index, data)
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

    Hub.$on('createCalendar', calendar => {
      if (!calendar.openinghours_id) {
        calendar.openinghours_id = this.route.version
      }
      console.log('Create calendar', inert(calendar))

      if (calendar.id) {
        this.$http.put('/api/calendars/' + calendar.id, calendar).then(({ data }) => {
          this.routeVersion.calendars.push(data)
          this.toCalendar(data.id)
        })
      } else {
        this.$http.post('/api/calendars/' + calendar.id, calendar).then(({ data }) => {
          this.routeVersion.calendars.push(data)
          this.toCalendar(data.id)
        })
      }
    })
  }
}
