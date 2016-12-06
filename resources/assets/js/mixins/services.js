export const services = window.initialServices || []

export default {
  data () {
    return {
      services
    }
  },
  methods: {
    fetchServices () {
      return this.$http.get('/api/services.json')
        .then(({ data }) => {
          this.services = data
        })
    }
  },
  mounted () {
    this.fetchServices()
  }
}
