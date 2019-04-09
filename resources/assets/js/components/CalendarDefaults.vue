<template>
    <div>
        <h3>Stel de standaardwaarden in</h3>
        <div class="form-group">
            <fieldset class="btn-toggle">
                <input type="radio" id="closed_true" name="closinghours" class="visuallyhidden"
                       @change="toggleClosing"
                       :checked="cal.closinghours"><label for="closed_true">Gesloten</label>
                <input type="radio" id="closed_false" name="closinghours" class="visuallyhidden"
                       @change="toggleClosing"
                       :checked="!cal.closinghours"><label for="closed_false">Open</label>
            </fieldset>
        </div>
        <div v-if="!cal.closinghours" class="form-group row" :class="{ 'has-error': !isValid }">
            <div class="col-xs-3">
                <label>Van</label>
                <input type="time" class="form-control control-time inp-startTime"
                       aria-label="Van"
                       v-model="defaultStartTime">
            </div>
            <div class="col-xs-3">
                <label :aria-describedby="`next_day_${this._uid}`">tot</label>
                <input type="time" class="form-control control-time inp-endTime"
                       aria-label="tot"
                       v-model="defaultEndTime">
            </div>
            <span class="col-xs-9  col-sm-offset-3 text-danger" :id="`next_day_${this._uid}`"
                  v-if="startTime >= endTime">volgende dag</span>
        </div>
        <hr>
    </div>
</template>

<script>
    export default {
        name: 'calendar-defaults',
        props: ['cal', 'startTime', 'endTime'],
        data() {
            return {
                isValid: true
            }
        },
        computed: {
            defaultStartTime: {
                get() {
                    return this.startTime;
                },
                set(v) {
                    if (!/\d\d:\d\d/.test(v)) {
                        this.isValid = false;
                        return;
                    }
                    this.isValid = true;
                    this.$emit('startTime', v);
                    this.startTime = v;
                }
            },
            defaultEndTime: {
                get() {
                    return this.endTime;
                },
                set(v) {
                    if (!/\d\d:\d\d/.test(v)) {
                        this.isValid = false;
                        return;
                    }
                    this.isValid = true;
                    this.$emit('endTime', v);
                    this.endTime = v;
                }
            }
        },
        methods: {
            toggleClosing() {
                this.$set(this.cal, 'closinghours', !this.cal.closinghours)
            }
        }
    }
</script>
