import {fetchError, Hub} from '../lib.js';
import {createVersion, createFirstCalendar} from '../defaults.js';
import {API_PREFIX, ID_MISSING} from "../constants.js";
import {SERVICE_COMPLETE, SERVICE_INACTIVE_OH, SERVICE_MISSING_OH, SERVICE_NO_CH} from "../constants";
import {hasActiveOh, hasOh} from "../lib";

export default {
    data() {
        return {
            services: window.initialServices || [],
            versionDataQueue: [],
            serviceLock: false,
            channelDataQueue: [],
            types: []
        };
    },
    created() {
        this.fetchTypes();
        this.fetchServices(0, 499);
        this.fetchServices(499);
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
        serviceEndDate(s) {
            if (!s.channels || !s.channels.length) {
                return s.end_date;
            }
            return s.channels.map(channel => {
                if (!channel.openinghours) {
                    return null
                }
                return channel.openinghours.map(oh => oh.end_date)
                    .sort((a, b) => {
                        if (a < b) {
                            return 1;
                        }
                        if (a > b) {
                            return -1
                        }
                        return 0
                    })[0]
            }).sort((a, b) => {
                if (a > b) {
                    return 1;
                }
                if (a < b) {
                    return -1
                }
                return 0
            })[0]
        },
        serviceStatus(s) {
            if (!s.channels) {
                if (s.countChannels === 0) {
                    return SERVICE_NO_CH;
                }
                if (s.has_missing_oh === 1 || s.has_missing_oh === true) {
                    return SERVICE_MISSING_OH;
                }
                if (s.has_inactive_oh === 1 || s.has_inactive_oh === true) {
                    return SERVICE_INACTIVE_OH;
                }
            } else {
                if (s.channels.length === 0) {
                    return SERVICE_NO_CH;
                }
                // Not every channel of the service has at least 1 version
                if (s.channels.filter(ch => !hasOh(ch).length).length) {
                    return SERVICE_MISSING_OH;
                }
                // Not every channel of the service has at least 1 active version
                if (s.channels.filter(ch => !hasActiveOh(ch).length).length) {
                    return SERVICE_INACTIVE_OH;
                }
            }
            return SERVICE_COMPLETE;
        },
        updateService() {
            let index = this.serviceIndex;
            if (index === -1) return;
            this.$set(this.services, index, this.routeService);
        },
        fetchTypes() {
            this.statusStart();
            let query = API_PREFIX + '/types';

            return this.$http.get(query)
                .then(({data}) => {
                    this.types = data;
                })
                .then(this.statusReset)
                .catch(fetchError);
        },
        fetchServices(offset, limit) {
            this.statusStart();
            this.serviceLock = true;

            let query = API_PREFIX + '/services';

            if (limit && offset) {
                query += '?offset=' + offset + '&limit=' + limit;
            } else if (limit) {
                query += '?limit=' + limit;
            } else if (offset) {
                query += '?offset=' + offset;
            }

            return this.$http.get(query)
                .then(({data}) => {
                    data.map(service => {
                        service.status = this.serviceStatus(service);
                    });
                    this.services = this.services.concat(data);
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
            this.statusStart();

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

            return this.$http.get(API_PREFIX + '/services/' + this.route.service + '/channels')
                .then(({data}) => {
                    this.$set(this.routeService, 'channels', data);
                    this.$set(this.routeService, 'status', this.serviceStatus(this.routeService));
                    this.$set(this.routeService, 'end_date', this.serviceEndDate(this.routeService));
                    this.serviceEndDate(this.routeService)
                })
                .then(() => {
                    this.fetchUsers(this.route.service)
                })
                .then(() => {
                    this.channelDataQueue = this.channelDataQueue.filter(service => service !== this.route.service);
                    this.updateService();
                })
                .then(this.statusReset)
                .catch(fetchError);
        },
        fetchVersion() {
            this.statusStart();

            if (!this.route.version || this.route.version < 1) return;

            //return if versions are already being fetched for the routeVersion.
            if (this.versionDataQueue.indexOf(this.route.version) !== -1) return;

            this.versionDataQueue.push(this.route.version);

            return this.$http.get(API_PREFIX + '/openinghours/' + this.route.version)
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
                return;
            }

            Object.assign(data, {fetched: true});
            this.$set(this.routeChannel.openinghours, index, data);
            this.updateService();
        },
        serviceById(id) {
            return this.services.find(s => s.id === id) || {};
        },
        fetchPresets(start, end, next) {
            Vue.http.get(API_PREFIX + '/presets?start_date=' + start + '&end_date=' + end)
                .then(({data}) => {
                    if (next) {
                        next(data);
                    }
                }).catch(fetchError);
        },
        patchServiceStatus(service, activate) {
            this.statusStart();

            if (!service.id) {
                this.statusUpdate(ID_MISSING);
                return;
            }
            service.draft = !activate;

            this.$http.put(API_PREFIX + '/services/' + service.id, {draft: service.draft})
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
            this.patchServiceStatus(service, true);
        });
        Hub.$on('deactivateService', service => {
            this.patchServiceStatus(service, false);
        });
        Hub.$on('createChannel', channel => {
            this.statusStart();

            if (!channel.srv) {
                this.statusUpdate(ID_MISSING);
                return;
            }

            if (channel.type_id === "null") {
                channel.type_id = null;
            }

            channel.service_id = channel.srv && channel.srv.id;
            this.$http.post(API_PREFIX + '/services/' + channel.service_id + '/channels', channel)
                .then(({data}) => {
                    this.routeService.channels.push(data);
                    this.routeService.status = this.serviceStatus(this.routeService);
                    this.routeService.end_date = this.serviceEndDate(this.routeService);
                    this.modalClose();
                    this.toChannel(data.id);
                })
                .then(this.statusReset)
                .catch(fetchError)
        });
        Hub.$on('updateChannel', channel => {
            this.statusStart();

            if (!channel || !channel.id) {
                this.statusUpdate(ID_MISSING);
                return;
            }

            if (!channel.service_id) {
                this.statusUpdate(ID_MISSING);
                return;
            }

            if (channel.type_id === "null") {
                channel.type_id = null;
            }

            this.$http.put(API_PREFIX + '/services/' + channel.service_id + '/channels/' + channel.id, {
                'channel_id': channel.id,
                'label': channel.label,
                'type_id': channel.type_id
            })
                .then(({data}) => {
                    this.fetchChannels();
                    this.modalClose();
                })
                .then(this.statusReset)
                .catch(fetchError);
        });
        Hub.$on('deleteChannel', channel => {
            this.statusStart();

            if (!channel.id) {
                this.statusUpdate(ID_MISSING);
                return;
            }
            if (!confirm('Zeker dat je dit kanaal wil verwijderen?')) {
                this.statusReset();
                return;
            }
            this.$http.delete(API_PREFIX + '/services/' + channel.service_id + '/channels/' + channel.id)
                .then(() => {

                    // remove channel from routeService
                    this.routeService.channels = this.routeService.channels.filter(c => c.id !== channel.id);
                    this.routeService.status = this.serviceStatus(this.routeService);
                    this.routeService.end_date = this.serviceEndDate(this.routeService);
                    this.modalClose();
                })
                .then(this.statusReset)
                .catch(fetchError);
        });
        Hub.$on('createVersion', input => {
            this.statusStart();

            const version = Object.assign(createVersion(), input);
            const originalVersion = input.originalVersion;
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
            this.$http.post(API_PREFIX + '/openinghours', version)
                .then(({data}) => {
                    this.modalClose();

                    this.routeService.has_missing_oh = true;
                    this.routeService.status = this.serviceStatus(this.routeService);
                    this.routeService.end_date = this.serviceEndDate(this.routeService);
                    this.fetchChannels().then(() => {
                        if (!originalVersion) {
                            Hub.$emit('createCalendar', Object.assign(createFirstCalendar(data), {
                                openinghours_id: data.id
                            }), 'calendar');
                        } else {
                            this.toVersion(data.id);
                        }
                    });
                })
                .then(this.statusReset)
                .catch(fetchError)
        });
        Hub.$on('updateVersion', version => {
            this.statusStart();

            if (!version || !version.id) {
                this.statusUpdate(ID_MISSING);
                return;
            }

            this.$http.put(API_PREFIX + '/openinghours/' + version.id, version)
                .then(({data}) => {
                    this.fetchChannels();
                    this.modalClose();
                })
                .then(this.statusReset)
                .catch(fetchError);
        });
        Hub.$on('deleteVersion', version => {
            this.statusStart();

            if (!version || !version.id) {
                this.statusUpdate(ID_MISSING);
                return;
            }
            if (!confirm('Zeker dat je deze versie wil verwijderen?')) {
                this.statusReset();
                return;
            }

            this.$http.delete(API_PREFIX + '/openinghours/' + version.id)
                .then(() => {
                    this.modalClose();
                    this.fetchChannels();
                    this.toChannel(version.channel_id);
                })
                .then(this.statusReset)
                .catch(fetchError);
        });
        Hub.$on('createCalendar', (calendar, done) => {
            this.statusStart();

            if (!calendar.openinghours_id) {
                calendar.openinghours_id = this.route.version;
            }

            if (calendar.id) {
                calendar.published = true;
                this.$http.put(API_PREFIX + '/calendars/' + calendar.id, calendar)
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
                this.$http.post(API_PREFIX + '/calendars', calendar)
                    .then(({data}) => {
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
            this.statusStart();

            if (!calendar.id) {
                this.statusUpdate(ID_MISSING);
                return;
            }
            if (calendar.label !== 'Uitzondering' && !confirm('Zeker dat je deze kalender wil verwijderen?')) {
                this.statusReset();
                return;
            }

            this.$http.delete(API_PREFIX + '/calendars/' + calendar.id)
                .then(() => {
                    this.fetchVersion(true);
                    this.toVersion();
                })
                .then(this.statusReset)
                .catch(fetchError)
        })
    }
}
