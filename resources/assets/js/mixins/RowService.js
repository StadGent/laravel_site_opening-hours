import {hasActiveOh, hasOh} from "../lib";
import {
    SERVICE_COMPLETE, SERVICE_COMPLETE_TOOLTIP, SERVICE_INACTIVE_OH, SERVICE_INACTIVE_OH_TOOLTIP, SERVICE_MISSING_OH,
    SERVICE_MISSING_OH_TOOLTIP, SERVICE_NO_CH, SERVICE_NO_CH_TOOLTIP} from "../constants";

export default {
    props: ['s'],
    computed: {
        old() {
            return this.s.updated_at ? (Date.now() - new Date(this.s.updated_at)) / 1000 / 3600 / 24 : 0
        },
        statusClass() {
            return {
                'text-success': this.statusMessage === SERVICE_COMPLETE,
                'warning': this.statusMessage !== SERVICE_COMPLETE
            }
        },
        statusMessage: function () {

            if (!this.s.channels) {
                if (this.s.countChannels === 0) {
                    return SERVICE_NO_CH;
                }

                if (this.s.has_missing_oh === 1 || this.s.has_missing_oh === true) {
                    return SERVICE_MISSING_OH;
                }

                if (this.s.has_inactive_oh === 1 || this.s.has_inactive_oh === true) {
                    return SERVICE_INACTIVE_OH;
                }
            }
            else {

                if (this.s.channels.length === 0) {
                    return SERVICE_NO_CH;
                }

                // Not every channel of the service has at least 1 version
                if (this.s.channels.filter(ch => !hasOh(ch).length).length) {
                    return SERVICE_MISSING_OH;
                }

                // Not every channel of the service has at least 1 active version
                if (this.s.channels.filter(ch => !hasActiveOh(ch).length).length) {
                    return SERVICE_INACTIVE_OH;
                }
            }

            return SERVICE_COMPLETE;
        },

        // TODO: refactor into structured set of messages
        statusTooltip() {
            switch (this.statusMessage) {
                case SERVICE_NO_CH:
                    return SERVICE_NO_CH_TOOLTIP;
                case SERVICE_MISSING_OH:
                    return SERVICE_MISSING_OH_TOOLTIP;
                case SERVICE_INACTIVE_OH:
                    return SERVICE_INACTIVE_OH_TOOLTIP;
                case SERVICE_COMPLETE:
                    return SERVICE_COMPLETE_TOOLTIP;
            }
        }
    },
    methods: {
        newRoleFromOverview() {
            this.newRole(this.s);
            this.href('#!service/' + this.s.id);
            this.route.tab2 = 'users'
        }
    },
    mounted() {
        $('[data-toggle="tooltip"]').tooltip();
    }
}
