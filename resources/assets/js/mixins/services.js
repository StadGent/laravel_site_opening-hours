import {fetchError, Hub} from '../lib.js';
import {createVersion, createFirstCalendar} from '../defaults.js';

export default {
    data() {
        return {
            services: window.initialServices || [],
            versionDataQueue: [],
            serviceLock: false,
            channelDataQueue: [],
        };
    },
    created() {
        this.fetchServices();
    },
    computed: {
        isRecreatex() {
            return this.routeService.source === 'recreatex';
        },
        routeService() {
            return this.services.find(s => s.id === this.route.service) || {};
        },
        routeChannel() {
            return this.routeService.channels && this.routeService.channels.find(c => c.id === this.route.channel) || {};
        },
        routeVersion() {

            if (this.services.length === 0) return {};

            //check if global routeChannel has values
            if (Object.keys(this.routeChannel).length === 0) return {};

            if (this.routeChannel.openinghours
                && this.routeChannel.openinghours.find(o => o.id === this.route.version)
                && !this.routeChannel.openinghours.find(o => o.id === this.route.version).fetched) {
                this.fetchVersion();
                return {};
            }

            return this.routeChannel.openinghours.find(o => o.id === this.route.version) || {};
        },
        routeCalendar() {
            return this.routeVersion.calendars && this.routeVersion.calendars.find(c => c.id === this.route.calendar) || {};
        },
        serviceIndex() {
            return this.services.findIndex(s => {
                return s.id === this.route.service;
            })
        }
    },
    methods: {
        updateService() {
            let index = this.serviceIndex;
            if (index === -1) return;
            this.$set(this.services, index, this.routeService);
        },
        fetchServices() {
            this.statusUpdate(null, {active: true});

            this.serviceLock = true;

            return this.$http.get('/api/ui/services')
                .then(({data}) => {
                    this.services = data || [];
                })
                .then(() => {
                    this.serviceLock = false;
                })
                .then(() => {
                    //fetch channel in case of direct url access.
                    if (this.route.channel > -1) {
                        this.fetchChannels();
                    }
                })
                .then(this.statusReset)
                .catch(fetchError);
        },
        fetchChannels() {
            this.statusUpdate(null, {active: true});

            //return if channels are already being fetched for the routeService.
            if (this.channelDataQueue.indexOf(this.route.service) !== -1) return;

            //check if global services array is already populated
            if (Object.keys(this.routeService).length === 0) return;

            //check if routeservice is part of services array
            if (this.serviceIndex === -1) {
                return;
            }

            //now we can fetch the channels
            this.channelDataQueue.push(this.route.service);

            return this.$http.get('/api/ui/services/' + this.route.service + '/channels')
                .then(({data}) => {
                    this.$set(this.routeService, 'channels', data);
                })
                .then(this.fetchUsers(this.route.service))
                .then(() => {
                    this.channelDataQueue = this.channelDataQueue.filter(service => service !== this.route.service);
                    this.updateService();
                })
                .then(this.statusReset)
                .catch(fetchError);
        },
        fetchVersion() {
            this.statusUpdate(null, {active: true});

            if (!this.route.version || this.route.version < 1) return;

            //return if versions are already being fetched for the routeVersion.
            if (this.versionDataQueue.indexOf(this.route.version) !== -1) return;

            this.versionDataQueue.push(this.route.version);

            return this.$http.get('/api/ui/openinghours/' + this.route.version)
                .then(({data}) => {
                    this.applyVersionData(data);
                    this.versionDataQueue = this.versionDataQueue.filter(version => version !== this.route.version);
                })
                .then(this.statusReset)
                .catch(fetchError);
        },
        applyVersionData(data) {

            let index = this.routeChannel.openinghours ? this.routeChannel.openinghours.findIndex(o => o.id === data.id) : -1;

            if (index === -1) {
                this.versionDataQueue.push({data});
                console.warn('version placed in queue', inert(data));
                return;
            }

            Object.assign(data, {fetched: true});
            this.$set(this.routeChannel.openinghours, index, data);
            this.updateService();
        },
        serviceById(id) {
            return this.services.find(s => s.id === id) || {};
        },
        fetchPresets(next) {
            //todo save these
            Vue.http.get('/api/ui/presets')
                .then(({data}) => {
                    next(data);
                }).catch(fetchError);
        },
        patchServiceStatus (service) {
            this.statusUpdate(null, {active: true});

            this.$http.put('/api/ui/services/' + service.id, {draft: service.draft})
                .then(({data}) => {
                    service.draft = data.draft;
                })
                .then(this.statusReset)
                .catch(fetchError);
        }
    },
    mounted() {

        Hub.$on('fetchChannels', this.fetchChannels);
        Hub.$on('activateService', service => {

            if (!service.id) {
                return console.error('activateService: id is missing');
            }
            service.draft = false;

            this.patchServiceStatus(service);
        });
        Hub.$on('deactivateService', service => {

            if (!service.id) {
                return console.error('deactivateService: id is missing');
            }
            service.draft = true;

            this.patchServiceStatus(service);
        });
        Hub.$on('createChannel', channel => {
            this.statusUpdate(null, {active: true});

            if (!channel.srv) {
                return console.error('createChannel: service is missing');
            }

            channel.service_id = channel.srv && channel.srv.id;
            this.$http.post('/api/ui/channels', channel)
                .then(({data}) => {
                    this.fetchChannels();
                    this.modalClose();
                    this.toChannel(data.id);
                })
                .then(this.statusReset)
                .catch(fetchError)
        });
        Hub.$on('deleteChannel', channel => {
            this.statusUpdate(null, {active: true});

            if (!channel.id) {
                return console.error('deleteChannel: id is missing');
            }
            if (!confirm('Zeker dat je dit kanaal wil verwijderen?')) {
                return;
            }
            this.$http.delete('/api/ui/channels/' + channel.id)
                .then(() => {
                    this.fetchServices();
                    this.modalClose();
                })
                .then(this.statusReset)
                .catch(fetchError);
        });
        Hub.$on('createVersion', input => {
            this.statusUpdate(null, {active: true});

            const version = Object.assign(createVersion(), input);
            if (!version.channel_id) {
                version.channel_id = this.route.channel;
            }
            if (!version.service_id) {
                version.service_id = this.route.service;
            }

            // This will trigger 4 API requests
            // * create new version
            // * refresh all services/channels/versions
            // * create first calendar in newly created version
            // * get first calendar
            // The user can now edit the first calendar of the new version
            this.$http.post('/api/ui/openinghours', version)
                .then(({data}) => {
                    this.modalClose();
                    this.fetchChannels().then(() => {
                        Hub.$emit('createCalendar', Object.assign(createFirstCalendar(data), {
                            openinghours_id: data.id
                        }), 'calendar');
                    });


                })
                .then(this.statusReset)
                .catch(fetchError)
        });
        Hub.$on('updateVersion', version => {
            this.statusUpdate(null, {active: true});

            if (!version || !version.id) {
                return console.warn('id is missing', version);
            }

            this.$http.put('/api/ui/openinghours/' + version.id, version)
                .then(({data}) => {
                    this.fetchServices();
                    this.modalClose();
                })
                .then(this.statusReset)
                .catch(fetchError);
        });
        Hub.$on('deleteVersion', version => {
            this.statusUpdate(null, {active: true});

            if (!version || !version.id) {
                return console.warn('id is missing', version);
            }
            if (!confirm('Zeker dat je deze versie wil verwijderen?')) {
                return;
            }

            this.$http.delete('/api/ui/openinghours/' + version.id)
                .then(() => {
                    this.modalClose();
                    this.toChannel(version.channel_id);
                    this.fetchServices();
                })
                .then(this.statusReset)
                .catch(fetchError);
        });
        Hub.$on('createCalendar', (calendar, done) => {
            this.statusUpdate(null, {active: true});

            if (!calendar.openinghours_id) {
                calendar.openinghours_id = this.route.version;
            }

            if (calendar.id) {
                this.$http.put('/api/ui/calendars/' + calendar.id, calendar)
                    .then(({data}) => {

                        const index = this.routeVersion.calendars.findIndex(c => c.id === data.id);
                        if (index === -1) {
                            return;
                        }
                        this.$set(this.routeVersion.calendars, index, data);
                        this.updateService();

                        done && this.toVersion(data.openinghours_id);
                    })
                    .then(this.statusReset)
                    .catch(fetchError)
            } else {
                this.$http.post('/api/ui/calendars', calendar)
                    .then(({data}) => {

                        //todo why??
                        //calendarEditor filters on cal.layer... but this is not a field in the calendar model
                        data.layer = -data.priority;

                        if (!this.routeVersion.calendars) {
                            this.$set(this.routeVersion, 'calendars', [])
                        }
                        this.routeVersion.calendars.push(data);
                        this.toVersion(data.openinghours_id);
                        this.toCalendar(data.id);
                    })
                    .then(this.statusReset)
                    .catch(fetchError);
            }
        });
        Hub.$on('deleteCalendar', calendar => {
            this.statusUpdate(null, {active: true});

            if (!calendar.id) {
                return console.warn('deleteCalendar: id is missing');
            }
            if (calendar.label !== 'Uitzondering' && !confirm('Zeker dat je deze kalender wil verwijderen?')) {
                return;
            }

            this.$http.delete('/api/ui/calendars/' + calendar.id)
                .then(() => {
                    this.fetchVersion(true);
                    this.toVersion();
                })
                .then(this.statusReset)
                .catch(fetchError)
        })
    }
}
