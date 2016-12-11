import { Hub } from '../lib.js'

export const users = (window.initialUsers || []).map(expandUser)

export default {
  data() {
    return {
      // WARNING: all user data must be passed through expandUser()
      users
    }
  },
  methods: {
    fetchUsers() {
      return this.$http.get('/api/users.json')
        .then(({ data }) => {
          this.users = (data || []).map(expandUser)
        })
    }
  },
  mounted() {
    this.fetchUsers()
    Hub.$on('createRole', modal => {
      setTimeout(() => {
        if (!modal.email) {
          return console.error('createRole: email is missing')
        }
        if (!modal.srv || !modal.srv.id) {
          return console.error('createRole: service is missing')
        }
        const service = modal.srv.id
        const role = modal.owner ? 'owner' : 'basic'
        const index = this.users.findIndex(s => s.email === modal.email)

        // Create or update user
        if (index === -1) {
          this.users.push(expandUser({
              id: Math.floor(Math.random() * 1000),
              name: 'Temp',
              email: modal.email,
              roles: [{ service, role }]
            }))
            // Send invite
        } else {
          const user = this.users[index]
          user.roles.push({ service, role })
          this.$set(this.users, index, expandUser(user))
        }
        this.modalClose()
      }, 100)
    })
  }
}

export function expandUser (u) {
  u.roles = u.roles || []
  u.services = u.roles.map(r => r.service)

  u.role = {}
  for (var i = 0; i < u.roles.length - 1; i++) {
    u.role[u.roles[i].service] = u.roles[i].role
  }

  return u
}
