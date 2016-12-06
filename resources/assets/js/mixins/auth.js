export const user = window.initialUser || {
  id: 1,
  name: 'Voornaam Naam',
  groups: []
}

export default {
  data () {
    return {
      user
    }
  },
  computed () {

  }
}
