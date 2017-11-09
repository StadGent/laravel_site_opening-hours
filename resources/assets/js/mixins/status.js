import {UNKNOWN_ERROR, VAGUE_ERROR} from "../constants";

export default {

    data() {
        return {
            status: {
                text: '',
                error: '',
                active: false,
            }
        };
    },

    computed: {
        isStatusActive() {
            return this.status.text || this.status.error || this.status.active;
        }
    },
    created: function () {
    },
    methods: {
        statusUpdate: function (err, data) {
            if (err) {
                if(err.body && err.body.error) {
                    this.status.error = 'Error'
                        + err.status
                        + ' - '
                        + VAGUE_ERROR;
                }
                else if (err.body && err.body.message) {
                    this.status.error = err.body.message;
                }
                else if (typeof err === 'string') {
                    this.status.error = err;
                }
            }
            else if (data) {
                this.status.text = data.text || '';
                this.status.active = true;
            }
            else {
                this.status.text = UNKNOWN_ERROR;
            }
        },
        statusReset: function () {
            this.$set(this.status, 'active', false);
            this.$set(this.status, 'text', null);
            this.$set(this.status, 'error', null);
        },
        statusStart: function () {
            this.status.active = true;
        }
    }
}

