<template>
  <div class="container">
    <h1>{{ usr.name || 'Naamloos' }}</h1>
    <form v-if="isAdmin && !hasRoleAdmin" style="max-width:25em;margin:2em 0;padding: 1em;border:1px solid #ddd;" @submit.prevent="updateRole">
      <fieldset>
        <legend>Globale rol</legend>
        <div class="radio">
          <label><input ref="noRole" name="role" :checked="!hasRoleEditor && !hasRoleAdmin" :value="null"  type="radio">Geen globale rol</label>
        </div>
        <div class="radio">
          <label><input ref="editor" name="role" :checked="hasRoleEditor" :value="'Editor'" type="radio">Redacteur</label>
        </div>
      </fieldset>
      <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Opslaan</button>
      <p style="margin-top: 1rem;" class="text-info" v-if="message" v-html="message"></p>
    </form>

    <h2>Diensten</h2>
    <div v-if="hasRoleAdmin">
      Admins hebben toegang tot alle diensten.
    </div>
    <div v-else>
      <button class="btn btn-default" @click="newRoleForUser(usr)">Nodig uit voor een dienst</button>
    </div>

    <!-- Services -->
    <div v-if="!userServices.length && !hasRoleAdmin" class="table-message">
      <h3 class="text-muted">Deze gebruiker heeft nog geen diensten</h3>
      <p>
        <button class="btn btn-lg btn-default" @click="newRoleForUser(usr)">Nodig uit voor een dienst</button>
      </p>
    </div>
    <div v-if="userServices.length">
      <div v-if="isAdmin">
        <table class="table table-hover table-service-admin">
          <thead>
            <tr>
              <th-sort by="service_id">Dienst</th-sort>
              <th>Rol</th>
              <th class="text-right">Beheer gebruikers</th>
            </tr>
          </thead>
          <tbody is="row-user-service-admin" v-for="s in sortedServices" :s="s"></tbody>
        </table>
      </div>
      <div v-else>
        <table class="table table-hover table-service">
          <thead>
            <tr>
              <th-sort by="label">Dienst</th-sort>
              <th>Rol</th>
            </tr>
          </thead>
          <tbody is="row-user-service" v-for="s in sortedServices" :s="s"></tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import RowUserService from '../components/RowUserService.vue'
import RowUserServiceAdmin from '../components/RowUserServiceAdmin.vue'
import ThSort from '../components/ThSort.vue'
import { Hub, orderBy } from '../lib.js'

export default {
  name: 'page-user',
  data () {
    return {
      fetchedUser: null,
      order: 'name',
      query: '',
      globalRole: null,
      message: null
    }
  },
  computed: {

    // User
    users () {
      return this.$root.users || []
    },
    usr () {
      return (this.$root.users && this.$root.users.find(u => u.id == this.route.id)) || {}
    },
    hasRoleAdmin() {
      return this.usr.globalRoles && this.usr.globalRoles.indexOf('Admin') !== -1;
    },
    hasRoleEditor() {
      return this.usr.globalRoles && this.usr.globalRoles.indexOf('Editor') !== -1;
    },
    // Services
    services () {
      return this.$root.services || []
    },
    userServices () {

        return this.usr.roles || {};
//      return this.services.filter(s => s.users.find(u => u.id == this.route.id))
    },
    filteredServices () {

        return this.userServices ;
      //return this.query ? this.userServices.filter(s => (s.label || '').indexOf(this.query) !== -1) : this.userServices
    },
    sortedServices () {
      const services = this.order ? this.filteredServices.slice().sort(orderBy(this.order)) : this.filteredServices;

      services.forEach(s => {
         s.label = this.$root.serviceById(s.service_id).label
      });

      return services
    }
  },
  methods: {
    updateRole() {
      this.usr.role = this.$refs.editor.checked ? 'Editor' : null;
      Hub.$emit('patchRole', this.usr, (role) => {
        if (!role) {
          this.message = 'De gebruiker heeft geen globale rol meer';
          return;
        }
        this.message = `De gebruiker heeft nu globale rol <strong>${role}</strong>`;
      });
    }
  },
  components: {
    RowUserService,
    RowUserServiceAdmin,
    ThSort
  }
}
</script>

