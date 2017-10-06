export default {
  props: ['s'],
  computed: {
    old() {
      return this.s.updated_at ? (Date.now() - new Date(this.s.updated_at)) / 1000 / 3600 / 24 : 0
    },
    statusClass() {
      return {
        'text-success': this.statusMessage === '✓ Volledig',
        'warning': this.statusMessage !== '✓ Volledig'
      }
    },
    statusMessage() {
        if(this.s.countChannels === 0){
          return 'Geen kanalen'
        }
 
        if (this.s.has_missing_oh === 1) {
            return 'Ontbrekende kalender(s)'
        }

        if(this.s.has_inactive_oh === 1){
            return 'Ontbrekende actieve kalender(s)'
        }

      return '✓ Volledig'
    },

    // TODO: refactor into structured set of messages
    statusTooltip() {
      switch (this.statusMessage) {
        case 'Geen kanalen': return 'Deze dienst heeft geen kanalen.';
        case 'Ontbrekende kalender(s)': return 'Minstens 1 van de kanalen van deze dienst heeft geen versie.';
        case 'Ontbrekende actieve kalender(s)': return 'Alle kanalen hebben een versie maar minstens 1 kanaal heeft geen versie die nu geldt. Een versie geldt niet als deze verlopen is of pas in de toekomst actief wordt.';
        case '✓ Volledig': return 'Alle kanalen hebben minstens een kalenderversie die nu geldig is.';
      }
    }
  },
  methods: {
    newRoleFromOverview () {
      this.newRole(this.s);
      this.href('#!service/' + this.s.id);
      this.route.tab2 = 'users'
    }
  },
  mounted () {
    $('[data-toggle="tooltip"]').tooltip()
  }
}
