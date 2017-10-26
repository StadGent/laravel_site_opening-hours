<template>
    <div v-if="active" class="status" v-bind:class="{'status--error' : error}">
        <div v-if="statusText" class="status__message">
            {{ statusText }}
        </div>
        <div class="status__progress">
            <span></span>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'status',
        computed : {
            statusText() {
                if(this.error) {
                    return this.error;
                }
                else{
                    return this.$root.status.text || '';
                }
            },
            error () {
                return this.$root.status.error;
            },
            active () {
                return this.$root.isStatusActive;
            }
        }
    }
</script>
