<template>
    <div>
        <h3>Voeg voorgedefineerde momenten toe</h3>
        <p class="text-muted">Klik op <em>Bewaar</em> om ook andere momenten toe te voegen</p>
        <div class="alert alert-info">
            <p>
                Op de geselecteerde ogenblikken zal het kanaal <strong v-text="cal.closinghours ? 'gesloten' : 'open'"></strong> zijn.
            </p>
            <p v-if="!cal.closinghours">
                De voorgedefinieerde openingsuren zijn van
                <em v-text="startTime"></em> tot <em v-text="endTime"></em>.
            </p>
            <p>
                <strong>Keer terug om dit te wijzigen.</strong>
            </p>
        </div>
        <div class="form-group">
            <div v-if="presets && presets.recurring">
                <h4>Herhalende vakantiedagen</h4>
                <div class="checkbox checkbox--preset" v-for="preset in presets.recurring">
                    <label>
                        <div class="text-muted pull-right">{{ preset | dayMonth }}</div>
                        <input type="checkbox" name="preset"
                               :value="preset" v-model="presetSelection"
                               @change="toggleRepeating($event.target.checked, preset)"
                        >
                        {{ preset.label }}
                    </label>
                </div>
            </div>
            <div v-if="presets && presets.unique">
                <h4>Unieke vakantiedagen</h4>
                <div class="checkbox checkbox--preset"
                     v-if="presets.collection && presets.collection.length">
                    <h5 class="text-muted">Selecteer voor elk jaar</h5>
                    <label v-for="label in presets.collection">
                        <input type="checkbox" name="preset" :value="{label, multiple: true}"
                               @change="recurringClicked($event.target.checked, {label, multiple: true})">
                        {{ label }}
                    </label>
                </div>
                <div class="calendar-editor__buttons" style="margin-top: 1rem" v-if="!showUnique">
                    <button type="button" class="btn btn-default btn-sm btn-block"
                            @click="showUnique = true">Toon alle jaren
                    </button>
                    <hr>
                </div>
                <div v-if="showUnique">
                    <div class="checkbox checkbox--preset" v-for="(year,key) in presets.unique">
                        <hr>
                        <h5 class="text-muted">
                            Geldig voor jaar {{ key }}
                        </h5>
                        <label v-for="preset in year">
                            <div class="text-muted pull-right">{{ preset | dayMonth }}</div>
                            <input type="checkbox" name="preset" :value="preset" v-model="presetSelection"
                                   @change="toggleUnique($event.target.checked, preset)">
                            {{ preset.label }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class="calendar-editor__buttons">
            <div class="text-right">
                <button type="button" class="btn btn-default" @click="$emit('submit')">Terug naar overzicht</button>
            </div>
        </div>
    </div>
</template>

<script>
    import {toDatetime} from '../lib.js'
    import {MONTHS} from '../mixins/filters.js'
    import {createEvent} from '../defaults.js'

    export default {
        props: ['cal', 'presets', 'startDate', 'endDate', 'startTime', 'endTime'],
        data() {
            return {
                showUnique: false,
                presetSelection: []
            }
        },
        computed: {},
        methods: {
            recurringClicked(checked, value) {
                Object.values(this.presets.unique).forEach(
                    presets => {
                        presets.filter(preset => preset.label === value.label).forEach(preset => {
                            const isInSelection = this.presetSelection.indexOf(preset);
                            if (checked && isInSelection === -1) {
                                this.toggleUnique(true, preset);
                                this.presetSelection.push(preset);
                            }
                            if (!checked && isInSelection !== -1) {
                                this.toggleUnique(false, preset);
                                this.presetSelection.splice(isInSelection, 1);
                            }
                        })
                    }
                );

            },
            toggleRepeating(checked, {start_date, rrule, label}) {
                const start = toDatetime(start_date);
                const versionStart = toDatetime(this.startDate);
                start.setFullYear(versionStart.getFullYear());
                if (start < versionStart) {
                    start.setFullYear(start.getFullYear() + 1)
                }
                const event = createEvent({
                    startTime: this.startTime,
                    endTime: this.endTime,
                    label: label,
                    start_date: start,
                    until: toDatetime(this.endDate),
                    rrule: rrule + (rrule === 'FREQ=YEARLY' ? ';BYMONTH=' + (start.getMonth() + 1) + ';BYMONTHDAY=' + start.getDate() : '')
                });
                if (checked) {
                    this.$emit('add', event);
                } else {
                    this.$emit('remove', event);
                }
            },
            toggleUnique(checked, {start_date, label, ended}) {
                const event = createEvent({
                    startTime: this.startTime,
                    endTime: this.endTime,
                    label: label,
                    start_date: toDatetime(start_date),
                    until: toDatetime(ended),
                    rrule: 'FREQ=DAILY'
                });
                if (checked) {
                    this.$emit('add', event);
                } else {
                    this.$emit('remove', this.cal.events.find(e =>
                        e.label === event.label
                        && e.start_date === event.start_date
                        && e.end_date === event.end_date
                        && e.until === event.until
                        && e.rrule === event.rrule
                    ));
                }
            },
        },
        filters: {
            dayMonth(d) {
                const start = toDatetime(d.start_date);
                const until = d.ended ? toDatetime(d.ended) : start;
                if (start.getMonth() === until.getMonth()) {
                    if (start.getDate() === until.getDate()) {
                        return start.getDate() + ' ' + MONTHS[start.getMonth()]
                    }
                    return start.getDate() + ' - ' + until.getDate() + ' ' + MONTHS[start.getMonth()]
                }
                return start.getDate() + ' ' + MONTHS[start.getMonth()] + ' - ' + until.getDate() + ' ' + MONTHS[until.getMonth()]
            }
        }
    }
</script>
