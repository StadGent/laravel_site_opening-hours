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
            <div v-else>
                <div :hidden="showPresets">
                    <h3>Stel de uitzondering in.</h3>
                    <div class="form-group required"
                         :class="{ 'has-error': cal.label === 'Uitzondering' || !cal.label.trim().length }">
                        <label for="name">Naam uitzondering</label>
                        <input id="name" type="text" class="form-control" v-model="cal.label"
                               required
                               placeholder="Brugdagen, collectieve sluitingsdagen, ..." autofocus>
                        <div class="help-block">Kies een specifieke naam die deze uitzondering beschrijft.</div>
                    </div>
                    <calendar-defaults :cal="cal"
                                       v-on:endTime="setEndTime" :end-time="defaultEndTime"
                                       v-on:startTime="setStartTime" :start-time="defaultStartTime"/>
                    <p>
                        <button type="button" @click="pushEvent" class="btn btn-link" :disabled="$root.isRecreatex">
                            + Voeg nieuwe periode of dag toe
                        </button>
                    </p>
                    <p>
                        <button type="button" @click="showPresets = true" class="btn btn-link"
                                :disabled="$root.isRecreatex">
                            + Voeg voorgedefineerde momenten toe
                        </button>
                    </p>
                    <fieldset v-if="cal.events.length">
                        <legend>Items</legend>
                        <event-editor v-for="(e, i) in cal.events.slice().reverse()"
                                      :parent="cal.events" :prop="cal.events.length - 1 - i"
                                      @add-event="addEvent(cal.events.length - 1 - i, e)"
                                      @rm="rmEvent(cal.events.length - 1 - i)"></event-editor>
                    </fieldset>
                </div>
                <!-- Choose from presets -->
                <preset-selection v-if="showPresets"
                                  @add="addPreset"
                                  @remove="removePreset"
                                  @submit="submitPresets"
                                  :start-time="defaultStartTime"
                                  :end-time="defaultEndTime"
                                  :cal="cal" :presets="presets"
                                  :start-date="currentStartDate" :end-date="versionEndDate">
                </preset-selection>
            </div>
        </div>
        <div class="calendar-editor__buttons" v-if="!showPresets">
            <div class="text-right">
                <button type="button" class="btn btn-default pull-left" @click="rmCalendar()"
                        :disabled="$root.isRecreatex || cal.priority === 0">Verwijder
                </button>
                <button type="button" class="btn btn-default" @click="cancel">Annuleer</button>
                <button type="button" class="btn btn-danger" v-if="disabled" disabled
                        v-text="cal.published ? 'bewaar' : 'publiceer'"></button>
                <button type="button" class="btn btn-primary" @click="save" v-else
                        v-text="cal.published ? 'bewaar' : 'publiceer'">
                </button>
            </div>
            <p class="alert alert-warning" v-if="disabled">{{ disabled }}</p>
        </div>
    </form>
</template>

<script>
    import CalendarDefaults from "../components/CalendarDefaults.vue";
    import EventEditor from '../components/EventEditor.vue'
    import PresetSelection from "../components/PresetSelection.vue";
    import {createEvent, createFirstEvent} from '../defaults.js'
    import {Hub, toDatetime, nextDateString} from '../lib.js'
    import Services from '../mixins/services.js'
    import {
        EVENT_INVALID_RANGE,
        IS_RECREATEX,
        NAME_CANNOT_BE_EXCEPTION, NAME_REQUIRED,
        NO_EVENTS,
        START_AFTER_END,
        START_AFTER_UNTIL
    } from "../constants";

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
                calLabel: '',
                days: ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'],
                fullDays,
                presetSelection: [],
                showPresets: false,
                showUnique: false,
                storedPresets: null,
                defaultStartTime: '09:00',
                defaultEndTime: '17:00',
                showDefaults: false
            }
        },
        computed: {
            presets() {
                if (!this.storedPresets) {
                    Services.methods.fetchPresets(moment.utc(this.currentStartDate).format('YYYY-MM-DD'), this.$root.routeVersion.end_date,
                        data => {
                            data.collection = [];
                            if (data.recurring) {
                                data.recurring.sort((a, b) => a.start_date > b.start_date ? 1 : -1);
                            }
                            if (data.unique) {
                                Object.keys(data.unique).forEach(key => {
                                    data.unique[key]
                                        .sort((a, b) => a.start_date > b.start_date ? 1 : -1)
                                        .forEach(preset => {
                                            if (data.collection.indexOf(preset.label) === -1) {
                                                data.collection.push(preset.label);
                                            }
                                        });
                                })
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

                // Start after end
                if (this.events.filter(e => e.start_date > e.end_date).length) {
                    return START_AFTER_END
                }

                // Start after until
                if (this.events.filter(e => e.start_date.slice(0, 10) > e.until.slice(0, 10)).length) {
                    return START_AFTER_UNTIL
                }

                // Start before versionStart or end after versionEnd
                if (this.events.filter(e => e.start_date.slice(0, 10) < this.versionStartDate || e.until.slice(0, 10) > this.versionEndDate).length) {
                    return EVENT_INVALID_RANGE
                }

                // Name cannot be empty
                if (!this.cal.label || !this.cal.label.trim().length) {
                    return NAME_REQUIRED
                }

                // Name cannot be 'Uitzondering'
                if (this.cal.label === 'Uitzondering') {
                    return NAME_CANNOT_BE_EXCEPTION
                }

                // Cannot save a calendar with no events
                if (!this.showPresets && !this.showDefaults && this.events.length === 0) {
                    return NO_EVENTS;
                }

                return false;
            },
            versionStartDate() {
                return toDateString(this.$parent.version.start_date)
            },
            currentStartDate() {
                const today = moment.utc();
                const versionStartDate = moment.utc(this.$parent.version.start_date);
                const versionEndDate = moment.utc(this.$parent.version.end_date);
                return today.isBetween(versionStartDate, versionEndDate) ? today.toDate(): versionStartDate.toDate();
            },
            versionEndDate() {
                return toDateString(this.$parent.version.end_date)
            },
            sortedEvents() {
                return this.cal.events.sort((a, b) => {
                        if (a.start_date > b.start_date) {
                            return 1
                        }
                        if (a.start_date < b.start_date) {
                            return -1
                        }
                        return 0
                    }
                )
            }
        },
        methods: {
            addPreset(event) {
                this.cal.events.push(event);
            },
            removePreset(event) {
                this.cal.events.splice(this.cal.events.indexOf(event), 1)
            },
            submitPresets() {
                this.showPresets = false;
            },
            setEndTime(time) {
                this.defaultEndTime = time;
            },
            setStartTime(time) {
                this.defaultStartTime = time;
            },
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
                const versionStart = toDatetime(this.$parent.version.start_date);
                start.setFullYear(versionStart.getFullYear());
                if (start < versionStart) {
                    start.setFullYear(start.getFullYear() + 1)
                }
                const event = createEvent({
                    label: label,
                    start_date: start,
                    until: toDatetime(this.$parent.version.end_date),
                    rrule: rrule + (rrule === 'FREQ=YEARLY' ? ';BYMONTH=' + (start.getMonth() + 1) + ';BYMONTHDAY=' + start.getDate() : '')
                });
                if (checked) {
                    this.cal.events.push(event)
                } else {
                    this.rmEvent(this.cal.events.indexOf(event))
                }
            },
            toggleUnique(checked, {start_date, label, ended}) {
                const event = createEvent({
                    label: label,
                    start_date: toDatetime(start_date),
                    until: toDatetime(ended),
                    rrule: 'FREQ=DAILY'
                });
                if (checked) {
                    this.cal.events.push(event)
                } else {
                    this.cal.events = this.cal.events.filter(e =>
                        e.label !== event.label
                        || e.start_date !== event.start_date
                        || e.end_date !== event.end_date
                        || e.until !== event.until
                        || e.rrule !== event.rrule
                    );
                }
                console.log(inert(this.cal.events))
            },
            toggleClosing() {
                this.$set(this.cal, 'closinghours', !this.cal.closinghours)
            },
            pushEvent() {
                const start_date = this.currentStartDate;
                const until = toDatetime(this.$parent.version.end_date);
                this.cal.events.push(createEvent({
                    start_date,
                    until,
                    label: this.cal.events.length + 1,
                    startTime: this.defaultStartTime,
                    endTime: this.defaultEndTime
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
                if (this.disabled) {
                    return console.warn('Expected valid calendar')
                }
                Hub.$emit('createCalendar', this.cal, true)
            },
            saveLabel() {
                this.showDefaults = false;
                if (!this.cal.label || this.cal.label === 'Uitzondering') {
                    return console.warn('Expected calendar name')
                }
                if (this.cal.events && this.cal.events.length) {
                    this.cal.events.forEach(event => {
                        event.start_date = event.start_date.slice(0, 11) + this.defaultStartTime + ':00';
                        if (this.defaultStartTime >= this.defaultEndTime) {
                            event.end_date = nextDateString(event.start_date.slice(0, 11) + this.defaultEndTime + ':00');
                        } else {
                            // Force end_date to be on same date as start_date
                            event.end_date = event.start_date.slice(0, 11) + this.defaultEndTime + ':00';
                        }
                    })
                }
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
            this.$set(this.cal, 'closinghours', !!this.cal.closinghours);
            if (!this.cal.events) {
                this.$set(this.cal, 'events', []);
            }
            if (!this.cal.events.length) {
                this.showDefaults = true;
            }
        },
        components: {
            PresetSelection,
            CalendarDefaults,
            EventEditor
        }
    }
</script>
