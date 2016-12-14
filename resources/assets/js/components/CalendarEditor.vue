<template>
  <div class="form-horizontal" @change="sync">

  geldig van tot
  <hr>
    <div class="form-group">
      <label class="col-xs-3 control-label">Regelmaat</label>
      <div class="col-xs-4">
        <select v-model="options.freq" class="form-control">
          <option :value="RRule.YEARLY">Jaarlijks</option>
          <option :value="RRule.MONTHLY">Maandelijks</option>
          <option :value="RRule.WEEKLY">Wekelijks</option>
          <option :value="RRule.DAILY">Dagelijks</option>
        </select>
      </div>
    </div>

    <!-- Yearly -->
    <div v-if="options.freq==RRule.YEARLY">

      <!-- BYMONTHDAY -->
      <div>
        <div class="form-group">
          <label class="col-xs-3 control-label">op</label>
          <div class="col-xs-4">
            <select v-model="options.bymonthday" class="form-control">
              <option :value="1" selected>1</option>
              <option v-for="i in 30" :value="i + 1" v-text="i + 1"></option>
            </select>
           </div>
          <div class="col-xs-5">
            <select v-model="options.bymonth" class="form-control">
              <option :value="1" selected>januari</option>
              <option :value="2">februari</option>
              <option :value="3">maart</option>
            </select>
          </div>
        </div>
      </div>
      <!-- bysetpos -->
      <div>
        <div class="form-group">
          <label class="col-xs-3 control-label">
          <input type="checkbox" name="">
          op de
          </label>
          <div class="col-xs-3">
            <select v-model="options.bysetpos" class="form-control">
              <option :value="1" selected>eerste</option>
              <option :value="2">tweede</option>
              <option :value="3">derde</option>
              <option :value="-2">voorlaatste</option>
              <option :value="-1">laatste</option>
            </select>
          </div>
          <div class="col-xs-3">
            <select v-model="options.byday" class="form-control">
              <option value="1">maandag</option>
              <option value="2">dinsdag</option>
              <option value="3">woesndag</option>
              <option value="4">donderdag</option>
              <option value="5">vrijdag</option>
              <option value="6">zaterdag</option>
              <option value="0">zondag</option>
              <option value="0,1,2,3,4,5,6" selected>dag</option>
              <option value="1,2,3,4,5">weekdag</option>
              <option value="0,6">weekend</option>
            </select>
          </div>
          <div class="col-xs-3">
            <select v-model="options.bymonth" class="form-control">
              <option :value="1" selected>januari</option>
              <option :value="2">februari</option>
              <option :value="3">maart</option>
            </select>
          </div>
        </div>
      </div>
      <!-- byday -->
      <div>
        <div class="form-group">
          <label>&nbsp;</label>
        </div>
      </div>

      <!-- BYMONTH -->
      <div>
        <div class="form-group">
          <label>van</label>
          <select v-model="options.bymonth" class="form-control">
            <option :value="1" selected>januari</option>
            <option :value="2">februari</option>
            <option :value="3">maart</option>
          </select>
        </div>
      </div>
        
    </div>
    <div v-if="options.freq==RRule.MONTHLY">
      
    </div>
    <div v-if="options.freq==RRule.WEEKLY">
      
    </div>
    <div v-if="options.freq==RRule.DAILY">
      
    </div>
    <pre class="cal-render">
      {{ cal }}
    </pre>
    <div class="cal-render">
      <!-- {{ rruleAll }} -->
    </div>
  </div>
</template>

<script>
export default {
  name: 'calendar-editor',
  props: ['cal'],
  data () {
    return {
      // options: {}
    }
  },
  computed: {
    // First event of calendar
    event () {
      if (!this.cal.events) {
        this.$set(this.cal, 'events', [])
      }
      if (!this.cal.events[0]) {
        this.$set(this.cal.events, 0, {
          dtstart: new Date(2016, 1, 1),
          dtend: new Date(2020, 1, 1),
          rrule: 'FREQ=YEARLY'
        })
      }
      console.log(inert(this.cal))
      return this.cal.events[0]
    },
    // Recurring rule of first event
    options: {
      get () {
        var opts = RRule.parseString(this.cal.events[0].rrule) || {}
        if(opts.byweekday) {
          opts.byweekday = opts.byweekday.map(d => d.weekday)
        }
        opts.wkst = null
        return opts
      },
      set (v) {
        console.log('set val', v)
      }
    },
    // RRule object based on options
    rrule () {
      return new RRule(this.options)
    },
    rruleString () {
      return this.rrule.toString()
    },
    rruleAll () {
      return this.rrule.all()
    }
  },
  methods: {
    sync () {
      // console.log('sync', this.options.freq, new RRule(opts).toString())
      this.$set(this.cal.events[0], 'rrule', new RRule(this.options).toString())
    }
  },
  created () {
    this.RRule = RRule || {}
  },
  mounted () {
    console.log('mount editor')

  },
  // watch: {
  //   cal (v) {
  //     console.log('load cal', v)
  //     var options = RRule.parseString(this.rr)
  //     options.dtstart = new Date(2000, 1, 1)
  //     var r = new RRule(options)
  //     this.options = r.options
  //   },
  //   options: {
  //     deep: true,
  //     handler (v, o) {
  //       if (!v || !v.dtstart) {
  //         return
  //       }
  //       var str = new RRule(v).toString()
  //       if (this.cal.events && this.rr != str) {
  //         console.log('saves change to cal', str)
  //         this.cal.events[0].rrule = new RRule(v).toString()
  //       }
  //     }
  //   }
  // }
}
</script>
