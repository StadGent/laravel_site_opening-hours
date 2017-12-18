<template>
    <form class="calendar-editor" @submit.prevent>
        <div class="calendar-editor__fields">
            <div class="cal-img top-right" :class="'layer-'+cal.layer"></div>

            <!-- First calendar is always weekly -->
            <div v-if="!cal.layer">
                <h3>Stel de openingsuren in voor kanaal <strong>{{ $root.routeChannel.label}}</strong>
                    van dienst <strong>{{ $root.routeService.label }}</strong>.</h3>
                <p>Op welke dagen is dit kanaal normaal open?</p>
                <p class="text-muted">Uitzonderingen kan je later instellen.</p>
                <event-editor v-for="(e, i) in cal.events" :parent="cal.events" :prop="i" @add-event="addEvent(i, e)"
                              @rm="rmEvent(i)"></event-editor>
                <p v-if="!cal.events.length">
                    <button type="button" @click="pushFirstEvent" class="btn btn-link" :disabled="$root.isRecreatex">
                        + Voeg weekschema toe
                    </button>
                </p>
            </div>

            <!-- Exception calendars must be renamed -->
            <!-- Choose from presets -->
            <div v-else-if="cal.label == 'Uitzondering'">
                <h3>Stel de uitzondering in.</h3>
                <div class="form-group required">
                    <label>Naam uitzondering</label>
                    <input type="text" class="form-control" v-model="calLabel"
                           placeholder="Brugdagen, collectieve sluitingsdagen, ..." autofocus>
                    <div class="help-block">Kies een specifieke naam die deze uitzondering beschrijft.</div>
                </div>
                <br>
                <transition name="slideup">
                    <div v-if="showPresets">
                        <h3>Voeg voorgedefineerde momenten toe</h3>
                        <p class="text-muted">
                            Klik op
                            <em>Bewaar</em>
                            om ook andere momenten toe te voegen
                        </p>
                        <div class="form-group">
                            <h4>Herhalende vakantiedagen</h4>
                            <div class="checkbox checkbox--preset" v-for="(preset, index) in presets">
                                <hr v-if="preset.group"/>
                                <p class="text-muted" v-if="preset.group">
                                    Geldig voor jaar {{preset.group}}
                                </p>
                                <label>
                                    <div class="text-muted pull-right">{{ preset | dayMonth }}</div>
                                    <input type="checkbox" name="preset" :value="index" v-model="presetSelection">
                                    {{ preset.label }}
                                </label>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- Other calendars have more options -->
            <div v-else>
                <h3>{{ cal.label }}</h3>
                <fieldset class="btn-toggle">
                    <input type="radio" id="closinghours_true" name="closinghours" class="visuallyhidden"
                           @change="toggleClosing"
                           :checked="cal.closinghours"><label for="closinghours_true">Gesloten</label>
                    <input type="radio" id="closinghours_false" name="closinghours" class="visuallyhidden"
                           @change="toggleClosing"
                           :checked="!cal.closinghours"><label for="closinghours_false">Open</label>
                </fieldset>
                <hr>
                <event-editor v-for="(e, i) in cal.events" :parent="cal.events" :prop="i" @add-event="addEvent(i, e)"
                              @rm="rmEvent(i)"></event-editor>
                <p>
                    <button type="button" @click="pushEvent" class="btn btn-link" :disabled="$root.isRecreatex">
                        + Voeg nieuwe periode of dag toe
                    </button>
                </p>
            </div>
        </div>
        <div class="calendar-editor__buttons">
            <div class="text-right">
                <button type="button" class="btn btn-default pull-left" @click="rmCalendar()"
                        :disabled="$root.isRecreatex || cal.priority === 0">Verwijder
                </button>
                <button type="button" class="btn btn-default" @click="cancel">Annuleer</button>
                <button type="submit" class="btn btn-primary" @click.prevent="showPresets = true"
                        v-if="cal.label == 'Uitzondering' && !showPresets">Volgende
                </button>
                <button type="button" class="btn btn-danger" v-else-if="disabled" disabled>Bewaar</button>
                <button type="submit" class="btn btn-primary" @click="saveLabel"
                        v-else-if="cal.label == 'Uitzondering'">Bewaar
                </button>
                <button type="button" class="btn btn-primary" @click="save" v-else>Bewaar</button>
            </div>
        </div>
    </form>
</template>

<script>
    import EventEditor from '../components/EventEditor.vue'
    import {createEvent, createFirstEvent} from '../defaults.js'
    import {cleanEmpty, Hub, toDatetime} from '../lib.js'
    import {MONTHS} from '../mixins/filters.js'
    import {rruleToStarts, keepRuleWithin} from '../util/rrule-helpers.js'
    import Services from '../mixins/services.js'
    import {EVENT_INVALID_RANGE, IS_RECREATEX} from "../constants";

    const fullDays = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];


    // Returns 10 character ISO string if date is valid
    // or empty string if not
    function toDateString(date, otherwise) {
        return !date ? toDateString(otherwise, new Date()) : typeof date === 'string' ? date.slice(0, 10) :
            date.toJSON ? date.toJSON().slice(0, 10) : toDateString(otherwise, new Date())
    }

    export default {
        name: 'calendar-editor',
        props: ['cal', 'layer'],
        data() {
            return {
                // options: {}
                calLabel: '',
                days: ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'],
                fullDays,
                presetSelection: [],
                showPresets: false,
                storedPresets: null,
            }
        },
        computed: {
            presets() {
                if (!this.storedPresets) {
                    Services.methods.fetchPresets(this.$root.routeVersion.start_date, this.$root.routeVersion.end_date,
                        data => {
                            data.sort((a, b) => a.start_date > b.start_date ? 1 : -1);

                            for (let i = data.length - 2; i >= 0; i--) {
                                const year = (data[i + 1].start_date || '').slice(0, 4);
                                if (year !== (data[i].start_date || '').slice(0, 4)) {
                                    data[i + 1].group = year;
                                }
                            }

                            this.storedPresets = data;
                        })
                }
                return this.storedPresets;
            },
            events() {
                return this.cal.events
            },
            disabled() {
                if (this.$root.isRecreatex) {
                    return IS_RECREATEX
                }

                // Start before end
                if (this.events.filter(e => e.start_date > e.end_date).length) {
                    return true
                }

                // Start before until
                if (this.events.filter(e => e.start_date.slice(0, 10) > e.until.slice(0, 10)).length) {
                    return true
                }

                // Start before versionStart or end after versionEnd
                if (this.events.filter(e => e.start_date.slice(0, 10) < this.versionStartDate || e.until.slice(0, 10) > this.versionEndDate).length) {
                    return EVENT_INVALID_RANGE
                }

                // Name cannot be 'Uitzondering'
                if (this.cal.label === 'Uitzondering' && (!this.calLabel || this.calLabel === 'Uitzondering')) {
                    return true
                }

                // Cannot save a calendar with no events
                if (!this.showPresets && this.events.length === 0) {
                    return true;
                }

                return false;
            },
            versionStartDate() {
                return toDateString(this.$parent.version.start_date)
            },
            versionEndDate() {
                return toDateString(this.$parent.version.end_date)
            }
        },
        methods: {
            toggleClosing() {
                this.$set(this.cal, 'closinghours', !this.cal.closinghours)
            },
            pushEvent() {
                const start_date = toDatetime(this.$parent.version.start_date);
                this.cal.events.push(createEvent({
                    start_date,
                    label: this.cal.events.length + 1
                }))
            },
            pushFirstEvent() {
                this.cal.events.push(createFirstEvent(this.$parent.version))
            },
            addEvent(index, event) {
                event = Object.assign({}, event, {id: null});
                this.cal.events.splice(index, 0, event)
            },
            rmEvent(index) {
                this.cal.events.splice(index, 1)
            },
            cancel() {
                if (this.cal.label === 'Uitzondering') {
                    return this.rmCalendar()
                }
                this.toVersion();
                this.$root.fetchVersion(true)
            },
            save() {
                // Set start_date to first occurrence of rrule
                this.cal.events.forEach(e => {
                    const limitedRule = keepRuleWithin(e);
                    const date = rruleToStarts(limitedRule + ';COUNT=1')[0];
                    if (date) {
                        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
                        if (e.start_date.slice(0, 10) !== date.toJSON().slice(0, 10)) {
                            e.start_date = date.toJSON().slice(0, 10) + e.start_date.slice(10);
                            e.end_date = date.toJSON().slice(0, 10) + e.end_date.slice(10)
                        }
                    }
                });

                if (this.disabled) {
                    return console.warn('Expected valid calendar')
                }

                Hub.$emit('createCalendar', this.cal, true)
            },
            saveLabel() {
                if (!this.calLabel || this.calLabel === 'Uitzondering') {
                    return console.warn('Expected calendar name')
                }
                this.showPresets = false;
                this.cal.label = this.calLabel;
                Hub.$emit('createCalendar', this.cal)
            },
            rmCalendar() {
                Hub.$emit('deleteCalendar', this.cal)
            }
        },
        created() {
            this.RRule = RRule || {};
        },
        mounted() {
            this.$set(this.cal, 'closinghours', !!this.cal.closinghours)
            if (!this.cal.events) {
                this.$set(this.cal, 'events', [])
            }
        },
        watch: {
            presetSelection(selection) {
                this.cal.events = [];

                selection
                    .map(s => this.presets[s])
                    .forEach(({start_date, rrule, until, ended}) => {
                        // Repeating events
                        if (rrule) {
                            const start = toDatetime(start_date);
                            const versionStart = toDatetime(this.$parent.version.start_date);
                            start.setFullYear(versionStart.getFullYear());
                            if (start < versionStart) {
                                start.setFullYear(start.getFullYear() + 1)
                            }
//
                            let event = createEvent({
                                start_date: start,
                                until: toDatetime(this.$parent.version.end_date),
                                rrule: rrule + (rrule === 'FREQ=YEARLY' ? ';BYMONTH=' + (start.getMonth() + 1) + ';BYMONTHDAY=' + start.getDate() : '')
                            });

                            this.cal.events.push(event)
                        }

                        // Specific events
                        if (ended) {
                            this.cal.events.push(createEvent({
                                start_date: toDatetime(start_date),
                                until: toDatetime(ended),
                                rrule: 'FREQ=DAILY'
                            }))
                        }
                    })
            }
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
        },
        components: {
            EventEditor
        }
    }
</script>
