<template>
    <div @change="sync" class="form-horizontal event-editor">
        <div class="form-group" :class="{ 'has-error text-danger': !isUntilValid }"
             v-if="event.rrule && $parent.cal.layer">
            <div class="col-xs-5">
                <label class="control-label">Geldig vanaf</label>
                <pikaday class="form-control inp-startDate" v-model="eventStartDate" :options="pikadayStart"/>
            </div>
            <div class="col-xs-5">
                <label class="control-label">tot en met</label>
                <pikaday class="form-control inp-until" v-model="eventUntil" :options="pikadayUntil"/>
            </div>
            <div class="col-xs-2">
                <div class="close close--col" style="padding-top: 30px;" @click="$emit('rm')">&times;</div>
            </div>
        </div>
        <div v-if="event.rrule">
            <!-- Choose the period -->
            <div class="form-group" v-if="!prevEventSameLabel && $parent.cal.layer">
                <div class="col-xs-5">
                    <label>Regelmaat</label>
                    <select v-model="optionFreq" class="form-control" aria-label="Regelmaat">
                        <option :value="RRule.YEARLY">Jaarlijks</option>
                        <option :value="RRule.MONTHLY">Maandelijks</option>
                        <option :value="RRule.WEEKLY">Wekelijks</option>
                        <option :value="RRule.DAILY">Dagelijks</option>
                    </select>
                </div>
                <div class="col-xs-5" v-if="options.freq==RRule.MONTHLY">
                    <label>Frequentie</label>
                    <select v-model="optionInterval" class="form-control" aria-label="Frequentie">
                        <option :value="null">elke maand</option>
                        <option :value="2">tweemaandelijks</option>
                        <option :value="3">elk kwartaal</option>
                        <option :value="4">viermaandelijks</option>
                    </select>
                </div>
                <div class="col-xs-5" v-else-if="options.freq==RRule.WEEKLY">
                    <label>Frequentie</label>
                    <select v-model="optionInterval" class="form-control" aria-label="Frequentie">
                        <option :value="null">elke week</option>
                        <option :value="2">tweewekelijks</option>
                        <option :value="3">driewekelijks</option>
                        <option :value="4">vierwekelijks</option>
                    </select>
                </div>
                <div class="col-xs-5" v-else-if="options.freq==RRule.DAILY">
                    <label>Frequentie</label>
                    <select v-model="optionInterval" class="form-control" aria-label="Frequentie">
                        <option :value="null">elke dag</option>
                        <option :value="2">om de dag</option>
                        <option :value="3">om de drie dagen</option>
                        <option :value="4">om de vier dagen</option>
                        <option :value="5">om de vijf dagen</option>
                        <option :value="6">om de zes dagen</option>
                    </select>
                </div>
            </div>
            <!-- Yearly -->
            <div v-if="options.freq==RRule.YEARLY" class="form-group">
                <div class="col-xs-12">
                    <strong>Op</strong>
                </div>
                <!-- bymonthday + bymonth -->
                <div class="col-xs-12" @click="weekOrMonth = 'monthday'">
                    <label class="control-label">
                        <input type="radio" :name="event.start_date" v-model="weekOrMonth" value="monthday">
                        &nbsp;{{ eventStartDayMonth }}
                    </label>
                </div>
                <!-- bysetpos + byweekday + bymonth -->
                <div @click="weekOrMonth = 'weekday'">
                    <div class="col-xs-3">
                        <label class="control-label">
                            <input type="radio" :name="event.start_date" v-model="weekOrMonth" value="weekday">
                            &nbsp;de
                        </label>
                    </div>
                    <div class="col-xs-3">
                        <select v-model="optionBysetpos" class="form-control">
                            <option value="-" disabled></option>
                            <option value="1">eerste</option>
                            <option value="2">tweede</option>
                            <option value="3">derde</option>
                            <option value="4">vierde</option>
                            <option value="-2">voorlaatste</option>
                            <option value="-1">laatste</option>
                        </select>
                    </div>
                    <div class="col-xs-3">
                        <select v-model="optionByweekday" class="form-control">
                            <option value="0">maandag</option>
                            <option value="1">dinsdag</option>
                            <option value="2">woensdag</option>
                            <option value="3">donderdag</option>
                            <option value="4">vrijdag</option>
                            <option value="5">zaterdag</option>
                            <option value="6">zondag</option>
                            <option value="0,1,2,3,4,5,6">dag</option>
                            <option value="0,1,2,3,4">weekdag</option>
                            <option value="5,6">weekenddag</option>
                        </select>
                    </div>
                    <div class="col-xs-3" @click="weekOrMonth = 'weekday'">
                        <select v-model="options.bymonth" class="form-control">
                            <option v-for="(month, index) in fullMonths" :value="index + 1">{{ month }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <!-- Monthly -->
            <div v-else-if="options.freq==RRule.MONTHLY" class="form-group">
                <div class="col-xs-12">
                    <strong>Op</strong>
                </div>
                <!-- bymonthday + bymonth -->
                <div class="col-xs-12" @click="weekOrMonth = 'monthday'">
                    <label class="control-label">
                        <input type="radio" :name="event.start_date" v-model="weekOrMonth" value="monthday">
                        &nbsp;dag&nbsp;{{ eventStartDayMonth.slice(0, 2) }}
                    </label>
                </div>
                <!-- bysetpos + byweekday + bymonth -->
                <div @click="weekOrMonth = 'weekday'">
                    <div class="col-xs-3">
                        <label class="control-label">
                            <input type="radio" :name="event.start_date" v-model="weekOrMonth" value="weekday">
                            &nbsp;de&nbsp;
                        </label>
                    </div>
                    <div class="col-xs-3" @click="weekOrMonth = 'weekday'">
                        <select v-model="optionBysetpos" class="form-control">
                            <option value="-" disabled></option>
                            <option value="1">eerste</option>
                            <option value="2">tweede</option>
                            <option value="3">derde</option>
                            <option value="4">vierde</option>
                            <option value="-2">voorlaatste</option>
                            <option value="-1">laatste</option>
                        </select>
                    </div>
                    <div class="col-xs-3" @click="weekOrMonth = 'weekday'">
                        <select v-model="optionByweekday" class="form-control">
                            <option value="0">maandag</option>
                            <option value="1">dinsdag</option>
                            <option value="2">woensdag</option>
                            <option value="3">donderdag</option>
                            <option value="4">vrijdag</option>
                            <option value="5">zaterdag</option>
                            <option value="6">zondag</option>
                            <option value="0,1,2,3,4,5,6">dag</option>
                            <option value="0,1,2,3,4">weekdag</option>
                            <option value="5,6">weekend</option>
                        </select>
                    </div>
                </div>
            </div>
            <!-- Weekly -->
            <div v-else-if="options.freq==RRule.WEEKLY" class="form-group">
                <div :class="{'col-xs-12' : $parent.cal.layer, 'col-xs-10': !$parent.cal.layer}">
                    <strong>Op</strong>
                </div>
                <div v-if="!$parent.cal.layer" class="col-xs-2">
                    <div class="close close--col" style="padding-top: 0px;" @click="$emit('rm')">&times;</div>
                </div>
                <div class="col-xs-5" :class="{ 'has-error text-danger': eventStartTime > eventEndTime }">
                    <multi-day-select :options="fullDays" v-model="optionByweekday"></multi-day-select>
                </div>
            </div>

            <!-- Openinghours -->
            <div v-if="!closinghours" class="form-group"
                 :class="{ 'has-error text-danger': eventStartTime > eventEndTime }">

                <div class="col-xs-3">
                    <label>Van</label>
                    <input type="text" class="form-control control-time inp-startTime"
                           aria-label="Van"
                           v-model.lazy="eventStartTime"
                           placeholder="_ _ : _ _">
                </div>
                <div class="col-xs-3">
                    <label >tot</label>
                    <input type="text" class="form-control control-time inp-endTime"
                           aria-label="tot"
                           v-model.lazy="eventEndTime"
                           placeholder="_ _ : _ _">
                </div>
            </div>
        </div>

        <div v-if="!$parent.cal.layer">
            <button type="button" class="btn btn-link" @click="$emit('add-event', prop, event)"
                    :disabled="$root.isRecreatex"><b>+</b> Voeg meer dagen toe
            </button>
        </div>
        <hr>
    </div>
</template>


<script>
    import MultiDaySelect from '../components/MultiDaySelect.vue'
    import Pikaday from '../components/Pikaday.vue'

    import {cleanEmpty, toTime, toDatetime, dateAfter} from '../lib.js'
    import {stringToHM} from '../util/stringToHM'

    const fullDays = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];
    const fullMonths = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];

    // Returns 10 character ISO string if date is valid
    // or empty string if not
    function toDateString(date, otherwise) {
        return !date ? toDateString(otherwise, new Date()) : typeof date === 'string' ? date.slice(0, 10) :
            date.toJSON ? date.toJSON().slice(0, 10) : toDateString(otherwise, new Date());
    }

    export default {
        name: 'event-editor',
        props: ['parent', 'prop'],
        data() {
            return {
                // options: {}
                days: ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'],
                show: {
                    endDate: 0
                },
                fullDays,
                fullMonths
            }
        },
        computed: {
            closinghours() {
                return this.$parent.cal.closinghours
            },
            // If the label of the previous event is the same, you can not choose the period
            prevEventSameLabel() {
                return this.event.label && (this.parent[this.prop - 1] || {}).label == this.event.label;
            },
            // The current event being edited
            event() {
                const event = this.parent[this.prop] || {};

                if (!event.until) {
                    this.$set(event, 'until', this.versionEndDate);
                }

                return event;
            },
            eventStartDayMonth() {
                return toDatetime(this.event.start_date).getDate() + ' ' + fullMonths[toDatetime(this.event.start_date).getMonth()];
            },
            eventStartDate: {
                get() {
                    return (this.event.start_date || '').slice(0, 10)
                },
                set(v) {
                    const endDate = toDatetime(this.event.end_date);
                    const startDate = toDatetime(this.event.start_date);
                    const duration = endDate - startDate;
                    // console.debug('duration', duration)

                    // Keep duration the same if it's shorter than 2 days
                    if (!v) {
                        return console.warn('did not select date');
                    }
                    this.event.start_date = v + ((this.event.start_date || '').slice(10, 19) || 'T00:00:00');
                    if (duration < 36e5 * 48) {
                        // Force end_date to be on same date as start_date
                        this.event.end_date = this.event.start_date.slice(0, 11) + this.event.end_date.slice(11, 19);
                    }

                    if (this.options.bymonthday) {
                        this.options.bymonthday = toDatetime(this.event.start_date).getDate();
                        this.options.bymonth = toDatetime(this.event.start_date).getMonth() + 1;
                    }
                    if (this.event.start_date.slice(0, 10) > this.event.until.slice(0, 10)) {
                        this.warnTime('.inp-startDate', 'Dit tijdstip moet voor het eindtijdstip liggen.');
                    }
                }
            },
            eventEndDate: {
                get() {
                    return (this.event.end_date || '').slice(0, 10);
                },
                set() {
                    // Force end_date to be on same date as start_date
                    this.event.end_date = v + ((this.event.start_date || '').slice(10, 19) || 'T00:00:00');
                }
            },
            eventStartTime: {
                get() {
                    return toTime(this.event.start_date);
                },
                set(v) {
                    v = stringToHM(v);
                    if (!/\d\d:\d\d/.test(v)) {
                        return;
                    }
                    if (this.eventEndTime === '00:00') {
                        this.eventEndTime = '23:59';
                    }
                    if (this.eventEndTime < v) {
                        this.warnTime('.inp-startTime');
                    }
                    this.event.start_date = this.event.start_date.slice(0, 11) + v + ':00';
                }
            },
            eventEndTime: {
                get() {
                    return toTime(this.event.end_date);
                },
                set(v) {
                    v = stringToHM(v);
                    if (!/\d\d:\d\d/.test(v)) {
                        return;
                    }
                    if (v === '00:00') {
                        v = '23:59';
                    }
                    if (this.eventStartTime > v) {
                        this.warnTime('.inp-endTime');
                    }
                    // Force end_date to be on same date as start_date
                    this.event.end_date = this.event.start_date.slice(0, 11) + v + ':00';
                }
            },
            eventUntilSet() {
                return this.eventUntil !== this.versionEndDate;
            },
            eventUntil: {
                get() {
                    return toDatetime(this.event.until || this.versionEndDate).toJSON().slice(0, 10);
                },
                set(v) {
                    this.event.until = new Date(Date.parse(v)).toJSON().slice(0, 10);
                    if (this.event.start_date.slice(0, 10) > this.event.until.slice(0, 10)) {
                        this.warnTime('.inp-until', 'Dit tijdstip moet na het start tijdstip liggen.');
                    } else if (this.$root.routeVersion.end_date < this.event.until) {
                        this.warnTime('.inp-until', 'Dit tijdstip moet voor het einde van de kalender liggen.');
                    }
                }
            },
            pikadayStart() {
                return {};
            },
            pikadayUntil() {
                return {};
            },
            hasDates() {
                return typeof this.options.freq === 'number';
            },
            // Parsed recurring rule of first event
            options() {
                if (!this.event.rrule) {
                    return {};
                }
                if (this.event.rrule.includes('BYDAY=;')) {
                    this.event.rrule = this.event.rrule.replace('BYDAY=;', '');
                }
                return Object.assign({
                    wkst: null,
                    bysetpos: null,
                    byweekday: null
                }, RRule.parseString(this.event.rrule) || {})
            },
            optionBysetpos: {
                get() {
                    return this.options.bysetpos ? this.options.bysetpos.toString() : '-';
                },
                set(v) {
                    this.options.bysetpos = parseInt(v) || 1;
                }
            },
            optionByweekday: {
                get() {
                    return this.options.byweekday ? this.options.byweekday.map(wd => wd.weekday || 0).join(',') : '0';
                },
                set(v) {
                    if (v) {
                        this.options.byweekday = v.split(',').map(wd => ({weekday: parseInt(wd)}));
                        this.sync();
                    }
                }
            },
            optionFreq: {
                get() {
                    return this.options.freq;
                },
                set(v) {
                    this.options.freq = v;
                    this.cleanOptions();
                }
            },
            optionInterval: {
                get() {
                    return this.options.interval > 0 ? this.options.interval : null;
                },
                set(v) {
                    this.options.interval = v;
                }
            },
            weekOrMonth: {
                get() {
                    return this.options.bymonthday ? 'monthday' : 'weekday';
                },
                set(v) {
                    // console.debug('weekOrMonth', v)
                    if (v === 'weekday') {
                        if (!this.options.byweekday) {
                            this.optionByweekday = '0';
                        }
                        if (!this.options.bysetpos || this.options.bysetpos > 4) {
                            this.options.bysetpos = 1;
                        }
                        delete this.options.bymonthday
                    } else if (v === 'monthday') {
                        this.options.bymonthday = parseInt(this.event.start_date.slice(8, 10), 10);
                        this.options.bymonth = this.options.freq < 1 ? parseInt(this.event.start_date.slice(5, 7), 10) : null;
                        this.options.byweekday = null;
                        delete this.options.bysetpos;
                    }
                }
            },
            versionEndDate() {
                return toDateString(this.$parent.$parent.version.end_date);
            },
            // RRule object based on options
            rrule() {
                this.cleanOptions();
                return new RRule(this.options);
            },
            rruleString() {
                return this.rrule.toString();
            },
            rruleAll() {
                return this.rrule.all();
            },
            isUntilValid() {
                return this.event.start_date.slice(0, 10) <= this.event.until.slice(0, 10)
                    && this.$root.routeVersion.end_date >= this.event.until;
            }
        },
        methods: {
            warnTime(selector, message) {
                const elem = $(this.$el).find(selector);
                elem.tooltip({
                    title: message || 'Het begintijdstip moet vroeger vallen dan het eindtijdstip.',
                    toggle: 'manual'
                });
                setTimeout(() => {
                    elem.tooltip('show');
                }, 300);
                setTimeout(() => {
                    elem.tooltip('hide').tooltip('destroy');
                }, 3000);
            },
            setFreq() {
                if (this.options.freq === RRule.MONTHLY) {
                    this.byMonthDay();
                }
            },
            byMonthDay() {
                // console.debug('monthday')
                delete this.options.byweekday;
                this.options.bymonthday = toDatetime(this.event.start_date).getDate();
            },
            cleanOptions() {
                delete this.options.byhour;
                delete this.options.bysecond;
                delete this.options.byminute;
                if (this.options.interval < 2) {
                    delete this.options.interval;
                }

                if (this.options.bymonthday) {
                    delete this.options.byweekday;
                }
                if (this.options.freq > 2) {
                    delete this.options.byweekday;
                } else {
                    if (!this.options.bymonthday && !this.options.byweekday) {
                        this.options.byweekday = [{weekday: 0}];
                        this.options.bysetpos = 1;
                    }
                }
                if (this.options.freq > 1) {
                    delete this.options.bysetpos;
                    delete this.options.bymonthday;
                }
                if (this.options.freq > 0) {
                    delete this.options.bymonth;
                }

                if (this.options.freq === 0) {
                    // Month must be set
                    if (!this.options.bymonth) {
                        this.options.bymonth = 1;
                    }
                    if (!this.options.byweekday && !this.options.bymonthday) {
                        this.optionsByweekday = '0';
                    }
                }
            },
            sync() {
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    this.cleanOptions();

                    // Clone object because RRule will mutate it
                    const opts = inert(this.options);

                    // Fix weekday issue
                    if (Array.isArray(opts.byweekday)) {
                        opts.byweekday = opts.byweekday.map(b => parseInt(b.weekday || 0));
                    }

                    opts.until = opts.dtstart = new Date(Date.UTC(2016, 0, 1));
                    let rule = new RRule(opts).toString()
                        .replace(';DTSTART=20160101T000000Z', '')
                        .replace(';UNTIL=20160101T000000Z', '');
                    this.$set(this.event, 'rrule', rule);
                }, 100)
            }
        },
        created() {
            this.RRule = RRule || {};
        },
        components: {
            MultiDaySelect,
            Pikaday
        }
    }
</script>
