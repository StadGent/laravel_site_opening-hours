import {fetchError, Hub} from '../lib.js'

import {createVersion, createFirstCalendar} from '../defaults.js'

export default {
    data() {
        return {
            services: window.initialServices || [],
            versionDataQueue: [],
            serviceLock: false,
            channelDataQueue: [],
        }
    },
    computed: {
        isRecreatex() {
            return this.routeService.source === 'recreatex'
        },
        routeService() {
            return this.services.find(s => s.id === this.route.service) || {}
        },
        routeChannel() {
            return this.routeService.channels && this.routeService.channels.find(c => c.id === this.route.channel) || {}
        },
        routeVersion() {
            this.$nextTick(() => {
                this.fetchVersion()
            });
            return this.routeChannel.openinghours && this.routeChannel.openinghours.find(o => o.id === this.route.version) || {}
        },
        routeCalendar() {
            return this.routeVersion.calendars && this.routeVersion.calendars.find(c => c.id === this.route.calendar) || {}
        }
    },
    methods: {
        fetchServices() {

            //prevent multiple fetches
            if (this.serviceLock) return;
            this.serviceLock = true;

            return this.$http.get('/api/ui/services')
                .then(({data}) => {
                    this.services = data || [];

                    //todo, can this go??
                    if (this.versionDataQueue.length > 0) {
                        console.info('%cIt seems the loop in fetchServices is not useless after all... inform Bart', 'background: #8A0034; color: #FFF')
                    }
                    this.versionDataQueue.forEach(this.applyVersionData);
                    this.versionDataQueue = [];
                })
                .then(() => {
                    this.serviceLock = false
                })
                .then(() => {
                    console.info('services fetched')
                })
                .then(() => {
                    //fetch channel in case of direct url access.
                    if (this.route.channel > -1) {
                        this.fetchChannels();
                    }
                })
                .catch(fetchError)
        },
        fetchChannels() {

            //return if channels are already being fetched for the routeService.
            if (this.channelDataQueue.indexOf(this.route.service) !== -1) return;

            //check if global services array is already populated
            if (Object.keys(this.routeService).length === 0) return;

            //now we can fetch the channels
            this.channelDataQueue.push(this.route.service);

            return this.$http.get('/api/ui/channels/getChannelsByService/' + this.route.service)
                .then(({data}) => {
                    this.$set(this.routeService, 'channels', data);
                })
                .then(() => {
                    this.channelDataQueue = this.channelDataQueue.filter(service => service !== this.route.service);
                })
                .then(() => {
                    console.info('channels fetched');
                })
                .then(() => {
                    //in case the version was fetched before the channel,
                    //it will be in the versionDataQueue
                    this.versionDataQueue.forEach(this.applyVersionData);
                    this.versionDataQueue = [];
                })
                .catch(fetchError);
        },
        fetchVersion(invalidate) {
            if (!this.route.version || this.route.version < 1) {
                console.warn('no route version');
                return;
            }
            if (this.routeVersion.fetched && !invalidate) {
                console.warn('version has been fetched');
                return;
            }
            if (this.fetchingVersion === this.route.version && !invalidate) {
                console.warn('version is being fetched');
                return;
            }
            this.fetchingVersion = this.route.version;

            return this.$http.get('/api/ui/openinghours/' + this.route.version)
                .then(this.applyVersionData)
                .then(() => {
                    console.info('version fetched');
                })
                .catch(fetchError)
        },
        applyVersionData({data}) {
            let index = this.routeChannel.openinghours ? this.routeChannel.openinghours.findIndex(o => o.id === data.id) : -1;
            if (index === -1) {
                this.versionDataQueue.push({data});
                console.warn('version placed in queue', inert(data));
                return;
            }
            Object.assign(data, {fetched: true});
            this.$set(this.routeChannel.openinghours, index, data);
            this.fetchingVersion = 0;
        },
        serviceById(id) {
            return this.services.find(s => s.id === id) || {};
        },
        //todo maybe save the presets in a new global array
        fetchPresets(next) {
            Vue.http.get('/api/ui/presets')
                .then(({data}) => {
                    next(data);
                })
                .catch(fetchError)
        }
    },
    created() {
        this.fetchServices();
    },
    mounted() {
        Hub.$on('fetchChannels', this.fetchChannels);
        Hub.$on('activateService', service => {
            if (!service.id) {
                return console.error('activateService: id is missing');
            }
            service.draft = false;

            this.$http.put('/api/ui/services/' + service.id, {draft: false}).then(({data}) => {
                service.draft = data.draft;
            }).catch(fetchError)
        });
        Hub.$on('deactivateService', service => {
            if (!service.id) {
                return console.error('deactivateService: id is missing');
            }
            service.draft = true;

            this.$http.put('/api/ui/services/' + service.id, {draft: true}).then(({data}) => {
                service.draft = data.draft;
            }).catch(fetchError)
        });
        Hub.$on('createChannel', channel => {
            if (!channel.srv) {
                return console.error('createChannel: service is missing');
            }

            channel.service_id = channel.srv && channel.srv.id;
            this.$http.post('/api/ui/channels', channel).then(({data}) => {
                this.fetchServices();
                this.modalClose();
                this.toChannel(data.id);
            }).catch(fetchError)
        });
        Hub.$on('deleteChannel', channel => {
            if (!channel.id) {
                return console.error('deleteChannel: id is missing');
            }
            if (!confirm('Zeker dat je dit kanaal wil verwijderen?')) {
                return;
            }
            this.$http.delete('/api/ui/channels/' + channel.id).then(() => {
                this.fetchServices();
                this.modalClose();
            }).catch(fetchError)
        });
        Hub.$on('createVersion', input => {
            const version = Object.assign(createVersion(), input)
            if (!version.channel_id) {
                version.channel_id = this.route.channel;
            }
            if (!version.service_id) {
                version.service_id = this.route.service;
            }
            console.log('Create version', inert(version));

            // This will trigger 4 API requests
            // * create new version
            // * refresh all services/channels/versions
            // * create first calendar in newly created version
            // * get first calendar
            // The user can now edit the first calendar of the new version
            this.$http.post('/api/ui/openinghours', version).then(({data}) => {
                this.modalClose()
                this.fetchServices().then(() => {
                    Hub.$emit('createCalendar', Object.assign(createFirstCalendar(data), {
                        openinghours_id: data.id
                    }), 'calendar')
                })
            }).catch(fetchError)
        });
        Hub.$on('updateVersion', version => {
            if (!version || !version.id) {
                return console.warn('id is missing', version);
            }

            this.$http.put('/api/ui/openinghours/' + version.id, version).then(({data}) => {
                this.fetchServices();
                this.modalClose();
            }).catch(fetchError)
        });
        Hub.$on('deleteVersion', version => {
            if (!version || !version.id) {
                return console.warn('id is missing', version);
            }
            if (!confirm('Zeker dat je deze versie wil verwijderen?')) {
                return;
            }

            this.$http.delete('/api/ui/openinghours/' + version.id).then(() => {
                this.modalClose();
                this.toChannel(version.channel_id);
                this.fetchServices();
            }).catch(fetchError)
        });
        Hub.$on('createCalendar', (calendar, done) => {
            if (!calendar.openinghours_id) {
                calendar.openinghours_id = this.route.version;
            }
            console.log('Create calendar', inert(calendar));

            if (calendar.id) {
                this.$http.put('/api/ui/calendars/' + calendar.id, calendar).then(({data}) => {
                    const index = this.routeVersion.calendars.findIndex(c => c.id === data.id);
                    if (index === -1) {
                        console.log(inert(this.routeVersion.calendars));
                        return console.warn('did not find this calendar', data);
                    }
                    this.$set(this.routeVersion.calendars, index, data);
                    done && this.toVersion(data.openinghours_id);
                }).catch(fetchError)
            } else {
                this.$http.post('/api/ui/calendars', calendar).then(({data}) => {
                    if (!this.routeVersion.calendars) {
                        this.$set(this.routeVersion, 'calendars', []);
                    }
                    this.routeVersion.calendars.push(data);
                    this.toVersion(data.openinghours_id);
                    this.toCalendar(data.id);
                }).catch(fetchError)
            }
        });
        Hub.$on('deleteCalendar', calendar => {
            if (!calendar.id) {
                return console.warn('deleteCalendar: id is missing');
            }
            if (calendar.label !== 'Uitzondering' && !confirm('Zeker dat je deze kalender wil verwijderen?')) {
                return;
            }

            this.$http.delete('/api/ui/calendars/' + calendar.id).then(() => {
                this.fetchVersion(true);
                this.toVersion();
            }).catch(fetchError)
        });
    }
}
