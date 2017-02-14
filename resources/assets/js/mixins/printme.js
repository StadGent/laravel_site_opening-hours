import { Hub } from '../lib.js'

export default {
  data () {
    return {
      printme: false
    }
  },
  methods: {
    afterprint () {
      window.onafterprint = null
      $(window).off('mousemove', this.afterprint)
      this.$calendar && this.$calendar.remove()
      this.printme = false
      $('#app').show()
    }
  },
  mounted() {
    const self = this
    Hub.$on('printme', newRole => {
      this.printme = true

      this.$calendar = $('.calendar').clone()
      this.$calendar.css('width', '750px')
      $('#app').hide().before(this.$calendar)

      window.print()

      setTimeout(() => {
        window.onafterprint = this.afterprint
        $(window).on('mousemove', this.afterprint)
      }, 100)
    })
  }
}
