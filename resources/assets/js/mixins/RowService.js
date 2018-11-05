import {
    SERVICE_COMPLETE, SERVICE_COMPLETE_TOOLTIP, SERVICE_INACTIVE_OH, SERVICE_INACTIVE_OH_TOOLTIP, SERVICE_MISSING_OH,
    SERVICE_MISSING_OH_TOOLTIP, SERVICE_NO_CH, SERVICE_NO_CH_TOOLTIP
} from "../constants";

export default {
    props: ['s'],
    computed: {
        url() {
            return window.vesta.source.replace('${identifier}', this.s.identifier)
        },
        old() {
            return this.s.updated_at ? (Date.now() - new Date(this.s.updated_at)) / 1000 / 3600 / 24 : 0
        }
    },
    methods: {
        newRoleFromOverview() {
            this.newRole(this.s);
            this.href('#!service/' + this.s.id);
            this.route.tab2 = 'users'
        },
        getStatusTooltip(statusMessage) {
            switch (statusMessage) {
                case SERVICE_NO_CH:
                    return SERVICE_NO_CH_TOOLTIP;
                case SERVICE_MISSING_OH:
                    return SERVICE_MISSING_OH_TOOLTIP;
                case SERVICE_INACTIVE_OH:
                    return SERVICE_INACTIVE_OH_TOOLTIP;
                case SERVICE_COMPLETE:
                    return SERVICE_COMPLETE_TOOLTIP;
                default: return null;
            }
        },
        getStatusClass(statusMessage) {
            return {
                'text-success': statusMessage === SERVICE_COMPLETE,
                'warning': statusMessage !== SERVICE_COMPLETE
            }
        },
    },
    mounted() {
        $('[data-toggle="tooltip"]').tooltip();
    }
}
