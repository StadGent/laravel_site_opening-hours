import { fetchError, Hub } from '../lib.js'

import { createVersion, createFirstCalendar } from '../defaults.js'

export default {
  data () {
    return {
      services: window.initialServices || [],
      versionDataQueue: []
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
          this.versionDataQueue.forEach(this.applyVersionData)
          this.versionDataQueue = []
        }).catch(fetchError)
    },
    fetchVersion (invalidate) {
      if (!this.route.version || this.route.version < 1) {
        return console.warn('no route version')
      }
      if (this.routeVersion.fetched && !invalidate) {
        return // console.warn('version has been fetched')
      }
      if (this.fetchingVersion === this.route.version && !invalidate) {
        return // console.warn('version is being fetched')
      }
      this.fetchingVersion = this.route.version
      return this.$http.get('/api/openinghours/' + this.route.version)
        .then(this.applyVersionData)
        .catch(fetchError)
    },
    applyVersionData ({ data }) {
      const index = this.routeChannel.openinghours ? this.routeChannel.openinghours.findIndex(o => o.id === data.id) : -1
      if (index === -1) {
        this.versionDataQueue.push({ data })
        return // console.warn('version placed in queue', inert(data))
      }
      Object.assign(data, { fetched: true })
      this.$set(this.routeChannel.openinghours, index, data)
      this.fetchingVersion = 0
    },
    serviceById (id) {
      return this.services.find(s => s.id === id) || {}
    }
  },
  mounted () {
    this.fetchServices()
    Hub.$on('activateService', service => {
      if (!service.id) {
        return console.error('activateService: id is missing')
      }
      service.draft = false

      this.$http.put('/api/services/' + service.id, { draft: false }).then(({ data }) => {
        service.draft = data.draft
      }).catch(fetchError)
    })
    Hub.$on('createChannel', channel => {
      if (!channel.srv) {
        return console.error('createChannel: service is missing')
      }

      channel.service_id = channel.srv && channel.srv.id
      this.$http.post('/api/channels', channel).then(({ data }) => {
        this.fetchServices()
        this.modalClose()
        this.toChannel(data.id)
      }).catch(fetchError)
    })
    Hub.$on('deleteChannel', channel => {
      if (!channel.id) {
        return console.error('deleteChannel: id is missing')
      }
      if (!confirm('Zeker dat je dit kanaal wil verwijderen?')) {
        return
      }
      this.$http.delete('/api/channels/' + channel.id).then(() => {
        this.fetchServices()
        this.modalClose()
      }).catch(fetchError)
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

      // This will trigger 4 API requests
      // * create new version
      // * refresh all services/channels/versions
      // * create first calendar in newly created version
      // * get first calendar
      // The user can now edit the first calendar of the new version
      this.$http.post('/api/openinghours', version).then(({ data }) => {
        this.modalClose()
        this.fetchServices().then(() => {
          Hub.$emit('createCalendar', Object.assign(createFirstCalendar(data), {
            openinghours_id: data.id
          }), 'calendar')
        })
      }).catch(fetchError)
    })

    Hub.$on('updateVersion', version => {
      if (!version || !version.id) {
        return console.warn('id is missing', version)
      }

      this.$http.put('/api/openinghours/' + version.id, version).then(({ data }) => {
        this.fetchServices()
        this.modalClose()
      }).catch(fetchError)
    })

    Hub.$on('deleteVersion', version => {
      if (!version || !version.id) {
        return console.warn('id is missing', version)
      }
      if (!confirm('Zeker dat je deze versie wil verwijderen?')) {
        return
      }

      this.$http.delete('/api/openinghours/' + version.id).then(() => {
        this.modalClose()
        this.toChannel(version.channel_id)
        this.fetchServices()
      }).catch(fetchError)
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
        }).catch(fetchError)
      } else {
        this.$http.post('/api/calendars', calendar).then(({ data }) => {
          if (!this.routeVersion.calendars) {
            this.$set(this.routeVersion, 'calendars', [])
          }
          this.routeVersion.calendars.push(data)
          this.toVersion(data.openinghours_id)
          this.toCalendar(data.id)
        }).catch(fetchError)
      }
    })
    Hub.$on('deleteCalendar', calendar => {
      if (!calendar.id) {
        return console.warn('deleteCalendar: id is missing')
      }
      if (calendar.label !== 'Uitzondering' && !confirm('Zeker dat je deze kalender wil verwijderen?')) {
        return
      }

      this.$http.delete('/api/calendars/' + calendar.id).then(() => {
        this.fetchVersion(true)
        this.toVersion()
      }).catch(fetchError)
    })
  }
}
