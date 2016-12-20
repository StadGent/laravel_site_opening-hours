import { Hub } from '../lib.js'

import { createVersion, createFirstCalendar } from '../defaults.js'

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
      this.$nextTick(() => {
        this.fetchVersion()
      })
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
    fetchVersion (invalidate) {
      if (!this.routeVersion) {
        return console.warn('no route version')
      }
      if (this.routeVersion.fetched && !invalidate) {
        return console.warn('version already fetched')
      }
      this.routeVersion.fetched = true
      return this.$http.get('/api/openinghours/' + this.route.version)
        .then(({ data }) => {
          const index = this.routeChannel.openinghours.findIndex(o => o.id === data.id)
          if (index === -1) {
            return console.warn('did not find this version', data)
          }
          Object.assign(data, { fetched: true })
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
    Hub.$on('deleteChannel', channel => {
      if (!channel.id) {
        return console.error('deleteChannel: id is missing')
      }
      this.$http.delete('/api/channels/' + channel.id).then(() => {
        this.fetchServices()
        this.modalClose()
      }).catch(error => {
        console.warn(error)
      })
    })

    Hub.$on('createVersion', input => {
      const version = Object.assign(createVersion(), input)
      if (!version.channel_id) {
        version.channel_id = this.route.channel
      }
      if (!version.service_id) {
        version.service_id = this.route.service
      }
      console.log('Create version', inert(version))

      this.$http.post('/api/openinghours', version).then(({ data }) => {
        this.fetchServices()
        this.modalClose()
        this.toVersion(data.id)
        Hub.$emit('createCalendar', Object.assign(createFirstCalendar(), {
          openinghours_id: data.id
        }))
      })
    })

    Hub.$on('createCalendar', (calendar, done) => {
      if (!calendar.openinghours_id) {
        calendar.openinghours_id = this.route.version
      }
      console.log('Create calendar', inert(calendar))

      if (calendar.id) {
        this.$http.put('/api/calendars/' + calendar.id, calendar).then(({ data }) => {
          const index = this.routeVersion.calendars.findIndex(c => c.id === data.id)
          if (index === -1) {
            console.log(inert(this.routeVersion.calendars))
            return console.warn('did not find this calendar', data)
          }
          this.$set(this.routeVersion.calendars, index, data)
          done && this.toVersion(data.openinghours_id)
        })
      } else {
        this.$http.post('/api/calendars/', calendar).then(({ data }) => {
          if (!this.routeVersion.calendars) {
            this.$set(this.routeVersion, 'calendars', [])
          }
          this.routeVersion.calendars.push(data)
          this.toCalendar(data.id)
        })
      }
    })
    Hub.$on('deleteCalendar', calendar => {
      if (!calendar.id) {
        return console.warn('deleteCalendar: id is missing')  
      }
      this.$http.delete('/api/calendars/' + calendar.id).then(() => {
        this.fetchVersion(true)
        this.toVersion()
      })
    })

    Hub.$on('editVersion', input => {
      const version = Object.assign(createVersion(), input)
      if (!version.channel_id) {
        version.channel_id = this.route.channel
      }
      console.log('Create version', inert(version))

      this.$http.post('/api/openinghours', version).then(({ data }) => {
        this.fetchServices()
        this.modalClose()
        this.toVersion(data.id)
        Hub.$emit('createCalendar', Object.assign(createFirstCalendar(), {
          openinghours_id: data.id
        }))
      })
    })
  }
}
