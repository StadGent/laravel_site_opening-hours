import {fetchError, Hub} from '../lib.js';
import {createVersion, createFirstCalendar} from '../defaults.js';

export default {
    data() {
        return {
            services: window.initialServices || [],
            versionDataQueue: [],
            serviceLock: false,
            channelDataQueue: [],
        }
    },
    created() {
        this.fetchServices();
    },
    computed: {
        isRecreatex() {
            return this.routeService.source === 'recreatex'
        },
        routeService() {

            //todo: why is this always called even if there is no routeService?
            if (this.route.service === -1) return {};

            //if the services array is empty: fetch services
            //todo: can we put a flag on it?
            //an empty array does not always mean they haven't been fetched yet.

            if (this.services.length === 0 && !this.serviceLock) {
                this.fetchServices();
            }

            //return the requested service
            return this.services.find(s => s.id === this.route.service) || {};
        },
        routeChannel() {

            if (this.services.length === 0) return {};
            if (!this.routeService.channels) return {};

            return this.routeService.channels.find(c => c.id === this.route.channel) || {};
        },
        routeVersion() {

            if (this.services.length === 0) return {};

            //check if global routeChannel has values
            if (Object.keys(this.routeChannel).length === 0) {
                return {}
            }

            if (this.routeChannel.openinghours
                && this.routeChannel.openinghours.find(o => o.id === this.route.version)
                && !this.routeChannel.openinghours.find(o => o.id === this.route.version).fetched) {
                this.fetchVersion();
                return {};
            }

            // todo: can this go??
            // this.$nextTick(() => {
            //     this.fetchVersion()
            // });

            return this.routeChannel.openinghours.find(o => o.id === this.route.version) || {}
        },
        routeCalendar() {

            if (this.services.length === 0) return {};

            if (this.route.calendar < 0) return {};

            return this.routeVersion.calendars && this.routeVersion.calendars.find(c => c.id === this.route.calendar) || {}
        }
    },
    methods: {
        fetchServices() {

            this.serviceLock = true;

            return this.$http.get('/api/ui/services')
                .then(({data}) => {

                    this.services = data || [];
                    this.serviceLock = false;


                    // legacy? used to fetch versions for channels...
                    // this.versionDataQueue.forEach(this.applyVersionData);
                    // this.versionDataQueue = [];

                    //check for active routeService and populate channels?

                    if (this.route.service !== -1) {
                        this.fetchChannels();
                    }

                }).catch(fetchError)
        },
        fetchChannels() {

            //return if channels are already being fetched for the routeService.
            if (this.channelDataQueue.indexOf(this.route.service) !== -1) {
                return;
            }

            //check if global services array is already populated
            if (Object.keys(this.routeService).length === 0) {
                return
            }

            //check if routeservice is part of services array
            let index = this.services.findIndex(s => {
                return s.id === this.route.service;
            });
            if (index === -1) {
                console.warn('services: trying to fetch channels for unknown service...');
                return
            }

            //now we can fetch the channels
            this.channelDataQueue.push(this.route.service);

            return this.$http.get('/api/ui/channels/getChannelsByService/' + this.route.service)
                .then(({data}) => {

                    this.routeService.channels = data;
                    this.$set(this.services, index, this.routeService);

                    //remove the service from the channelDataQueue;
                    this.channelDataQueue = this.channelDataQueue.filter(service => service !== this.route.service);

                    if (this.route.version !== -1) {
                        this.fetchVersion();
                    }

                })

        },
        fetchVersion() {

            if (!this.route.version || this.route.version < 1) {
                console.warn('no route version');
                return;
            }

            //return if versions are already being fetched for the routeVersion.
            if (this.versionDataQueue.indexOf(this.route.version) !== -1) {
                return;
            }

            this.versionDataQueue.push(this.route.version);


            return this.$http.get('/api/ui/openinghours/' + this.route.version)
                .then(({data}) => {

                    this.applyVersionData(data);
                    this.versionDataQueue = this.versionDataQueue.filter(version => version !== this.route.version);
                })
                .catch(fetchError)
        }
        ,
        applyVersionData(data) {

            console.log('applyversiondata');

            const index = this.routeChannel.openinghours ? this.routeChannel.openinghours.findIndex(o => o.id === data.id) : -1;

            if (index === -1) {
                this.versionDataQueue.push({data});
                console.warn('version placed in queue', inert(data));
                return
            }

            Object.assign(data, {fetched: true});
            this.$set(this.routeChannel.openinghours, index, data);

            //todo this index is wrong!
            this.$set(this.services, index, this.routeService);
        }
        ,
        serviceById(id) {
            return this.services.find(s => s.id === id) || {}
        }
        ,
        fetchPresets(next) {
            Vue.http.get('/api/ui/presets')
                .then(({data}) => {
                    next(data);
                }).catch(fetchError)
        }
    },
    mounted() {

        Hub.$on('fetchChannels', this.fetchChannels);
        Hub.$on('activateService', service => {
            if (!service.id) {
                return console.error('activateService: id is missing')
            }
            service.draft = false;

            this.$http.put('/api/ui/services/' + service.id, {draft: false}).then(({data}) => {
                service.draft = data.draft
            }).catch(fetchError)
        });
        Hub.$on('deactivateService', service => {
            if (!service.id) {
                return console.error('deactivateService: id is missing')
            }
            service.draft = true;

            this.$http.put('/api/ui/services/' + service.id, {draft: true}).then(({data}) => {
                service.draft = data.draft
            }).catch(fetchError)
        });
        Hub.$on('createChannel', channel => {
            if (!channel.srv) {
                return console.error('createChannel: service is missing')
            }

            channel.service_id = channel.srv && channel.srv.id;
            this.$http.post('/api/ui/channels', channel).then(({data}) => {
                // this.fetchServices();
                this.fetchChannels();
                this.modalClose();
                this.toChannel(data.id)
            }).catch(fetchError)
        });
        Hub.$on('deleteChannel', channel => {
            if (!channel.id) {
                return console.error('deleteChannel: id is missing')
            }
            if (!confirm('Zeker dat je dit kanaal wil verwijderen?')) {
                return
            }
            this.$http.delete('/api/ui/channels/' + channel.id).then(() => {
                this.fetchServices();
                this.modalClose();
            }).catch(fetchError)
        });

        Hub.$on('createVersion', input => {
            const version = Object.assign(createVersion(), input);
            if (!version.channel_id) {
                version.channel_id = this.route.channel
            }
            if (!version.service_id) {
                version.service_id = this.route.service
            }
            console.log('Create version', inert(version));

            // This will trigger 4 API requests
            // * create new version
            // * refresh all services/channels/versions
            // * create first calendar in newly created version
            // * get first calendar
            // The user can now edit the first calendar of the new version
            this.$http.post('/api/ui/openinghours', version).then(({data}) => {
                this.modalClose();

                //why fetch services???
                // this.fetchServices().then(() => {
                //
                //     Hub.$emit('createCalendar', Object.assign(createFirstCalendar(data), {
                //         openinghours_id: data.id
                //     }), 'calendar')
                // })

                this.fetchChannels().then(() => {

                    Hub.$emit('createCalendar', Object.assign(createFirstCalendar(data), {
                        openinghours_id: data.id
                    }), 'calendar')

                });


            }).catch(fetchError)
        });

        Hub.$on('updateVersion', version => {
            if (!version || !version.id) {
                return console.warn('id is missing', version)
            }

            this.$http.put('/api/ui/openinghours/' + version.id, version).then(({data}) => {
                this.fetchServices();
                this.modalClose()
            }).catch(fetchError)
        });

        Hub.$on('deleteVersion', version => {
            if (!version || !version.id) {
                return console.warn('id is missing', version)
            }
            if (!confirm('Zeker dat je deze versie wil verwijderen?')) {
                return
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

                    //todo don't change entire service...
                    //todo why is the index always 0?
                    this.$set(this.services, this.services.findIndex(s => s.id === this.route.service), this.routeService);

                    done && this.toVersion(data.openinghours_id);
                }).catch(fetchError)
            } else {
                this.$http.post('/api/ui/calendars', calendar).then(({data}) => {

                    //todo why??
                    //calendarEditor filters on cal.layer... but this is not a field in the calendar model
                    data.layer = -data.priority;

                    if (!this.routeVersion.calendars) {
                        this.$set(this.routeVersion, 'calendars', [])
                    }
                    this.routeVersion.calendars.push(data);

                    this.toVersion(data.openinghours_id);
                    console.log("going to calendar");
                    this.toCalendar(data.id);
                }).catch(fetchError)
            }
        });
        Hub.$on('deleteCalendar', calendar => {
            if (!calendar.id) {
                return console.warn('deleteCalendar: id is missing')
            }
            if (calendar.label !== 'Uitzondering' && !confirm('Zeker dat je deze kalender wil verwijderen?')) {
                return
            }

            this.$http.delete('/api/ui/calendars/' + calendar.id).then(() => {

                this.fetchVersion(true);
                this.toVersion()
            }).catch(fetchError)
        })
    }
}
