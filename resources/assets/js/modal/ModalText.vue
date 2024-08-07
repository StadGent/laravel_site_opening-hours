<template>
  <div class="modal fade" :class="{ in: modal.text }" :style="{ display: modal.text ? 'block' : 'none' }" @click="modalClose" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <form class="modal-content" @click.stop @submit.prevent>
        <div class="modal-header">
          <button type="button" class="close" @click="modalClose" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">
            <span v-if="modal.text == 'requestService'">Vraag toegang tot een dienst</span>
            <span v-else-if="modal.text == 'newChannel'">{{ modal.id ? 'Bewerk dit kanaal' : 'Voeg een kanaal toe' }}</span>
            <span v-else-if="modal.text == 'newVersion'">{{ modal.id ? 'Bewerk deze versie' : 'Voeg een versie toe' }}</span>
            <span v-else-if="modal.text == 'newRoleForUser'">Nodig {{ modal.usr.name }} uit voor een dienst</span>
            <span v-else-if="modal.text == 'newRole'">Nodig iemand uit voor {{ modal.srv.label }}</span>
            <span v-else-if="modal.text == 'newUser'">Nodig iemand uit</span>
            <span v-else>Probleem</span>
          </h4>
        </div>
        <div class="modal-body">
          <div v-if="modal.text == 'requestService'">
            Mail naar <a href="mailto:admin@mijngent.be">admin@mijngent.be</a> om toegang te vragen.
          </div>
          <div v-else-if="modal.text == 'newChannel'" class="form-group">
            <label for="input_channel_name" class="control-label">Naam van het kanaal</label>
            <input-channel :parent="modal" :id="'input_channel_name'" prop="label"></input-channel>
            <select-channel-type prop="type_id" :parent="modal" />
            <div class="help-block">
              Een kanaal is een manier waarop burgers jouw dienst kunnen contacteren.
              <br><br> Dat kunnen algemene openingsuren zijn, maar ook bijvoorbeeld de telefonische beschikbaarheid, afspraak momenten, wanneer er een tolk aanwezig is, of wanneer
              een bepaalde doelgroep een bezoekje kan brengen zoals jongeren.
            </div>
          </div>
          <div v-else-if="modal.text == 'newVersion'">
            <div class="form-group">
              <label for="recipient-name" class="control-label">Naam van de versie</label>
              <input id="recipient-name" type="text" class="form-control" v-model="modal.label" :placeholder="nextVersionLabel">
            </div>
            <div class="row form-group">
              <div class="col-sm-6">
                <label for="start_date" class="control-label">Geldig van</label>
                <pikaday id="start_date" class="form-control" :value="modal.start_date" @input="modal.start_date = $event" :options="pikadayStart" />
              </div>
              <div class="col-sm-6">
                <label for="end_date" class="control-label">Verloopt op</label>
                <pikaday id="end_date" class="form-control" :value="modal.end_date" @input="modal.end_date = $event" :options="pikadayEnd" />
              </div>
            </div>
            <div v-if="modal.id" class="alert alert-warning">
              <strong>Opgelet!</strong> <br>
              Wanneer je de einddatum wijzigt heeft dit geen effect op de einddatum van de bestaande uitzonderingen.
            </div>
            <div v-if="!modal.id" class="form-group">
              <label for="original_version" class="control-label">Kopieer versie (optioneel)</label>
              <select v-model="modal.originalVersion" id="original_version" class="form-control">
                <option></option>
                <template v-for="channel in serviceVersions">
                  <optgroup :label="channel.label"></optgroup>
                  <template v-for="version in channel.versions">
                    <option :value="version.id">{{ version.label }}</option>
                  </template>
                </template>
              </select>
            </div>
          </div>
          <div v-else-if="modal.text == 'newRole' || modal.text == 'newUser'">
            <div class="form-group" :class="{ 'has-error': !validEmail, 'has-success': allowedEmail }">
              <label for="recipient-name" class="control-label">E-mailadres</label>
              <input type="text" class="form-control" v-model="modal.email" placeholder="... @mijngent.be">
            </div>
            <div class="alert alert-warning" v-if="!allowedEmail">
              <b>Pas op!</b> Het is de bedoeling dat je alleen mensen uitnodigt van Mijn Gent.
            </div>
          </div>
          <div v-if="modal.text == 'newUser' || modal.text == 'newRoleForUser'">
            <div class="form-group" :class="{ 'has-error': 0, 'has-success': 0 }">
              <label for="recipient-name" class="control-label">Rol</label>
              <div class="radio">
                <label>
                  <input type="radio" name="modalRole" v-model="modal.role" value="Member"> {{ $root.translateRole("Member") }}
                </label>
              </div>
              <div class="radio">
                <label>
                  <input type="radio" name="modalRole" v-model="modal.role" value="Owner"> {{ $root.translateRole("Owner") }}
                </label>
              </div>
            </div>
            <div class="form-group" :class="{ 'has-error': 0, 'has-success': 0 }">
              <label for="recipient-name" class="control-label">Dienst</label>
              <multiselect v-model="modal.service_id" :options="allowedServices" :multiple="true" :close-on-select="false" :clear-on-select="false" placeholder="Select a service"
                label="label" track-by="id" :preserve-search="true" :allow-empty="false" @input="updateSelected">
              </multiselect>
            </div>
          </div>
          <div v-if="this.modal.error" class="alert alert-danger" v-html="modal.error"></div>
        </div>
        <div class="modal-footer">
          <div v-if="modal.text == 'newChannel'">
            <button type="submit" class="btn btn-primary" @click="createChannel" :disabled="$root.isRecreatex || modal.wait">{{ modal.id ? 'Sla wijzigingen op' : 'Voeg toe'
              }}</button>
            <button type="button" class="btn btn-default" @click="modalClose" :disabled="modal.wait">Annuleer</button>
          </div>
          <div v-else-if="modal.text == 'newVersion'">
            <button type="submit" class="btn btn-primary" @click="createVersion" :disabled="$root.isRecreatex || modal.wait">{{ modal.id ? 'Sla wijzigingen op' : 'Voeg toe'
              }}</button>
            <button type="button" class="btn btn-default" @click="modalClose" :disabled="modal.wait">Annuleer</button>
          </div>
          <div v-else-if="modal.text == 'newRole' || modal.text == 'newUser' || modal.text == 'newRoleForUser'">
            <button type="submit" class="btn btn-primary" @click="createRole" :disabled="modal.wait">Uitnodigen</button>
            <button type="button" class="btn btn-default" @click="modalClose" :disabled="modal.wait">Annuleer</button>
          </div>
          <div v-else>
            <button type="submit" class="btn btn-primary" @click="modalClose" :disabled="modal.wait">OK</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import InputChannel from '../components/InputChannel.vue'
import SelectChannelType from '../components/SelectChannelType.vue'
import Pikaday from '../components/Pikaday.vue'
import Status from '../components/Status.vue'
import Multiselect from 'vue-multiselect'

import { Hub, toDatetime } from '../lib.js'
import { CHOOSE_SERVICE, NO_VALID_EMAIL, OH_INVALID_RANGE } from "../constants";

export default {
  computed: {
    validEmail() {
      return !this.modal.strict || /.+@.+\...+/.test(this.modal.email || '')
    },
    allowedEmail() {
      return (this.modal.email || '').endsWith('@mijngent.be')
    },
    nextVersionLabel() {
      if (!this.modal.start_date || !this.modal.end_date) {
        return 'Nieuwe versie'
      }
      return 'Openingsuren ' + this.modal.start_date.slice(0, 4) + ' tot en met ' + this.modal.end_date.slice(0, 4)
    },

    allowedServices() {
      // Step 1: Filter out draft services and sort them
      let services = this.$root.services.filter(s => !s.draft)
        .sort((a, b) => (a.label.toLowerCase() <= b.label.toLowerCase()) ? -1 : 1);

      if (this.modal.text === 'newUser') {
        return services;
      }

      // Step 2: Filter out userServices from the sorted and filtered services
      let filteredServices = services.filter(allowedService =>
        !this.userServices.some(userService => userService.service_id === allowedService.id)
      );

      return filteredServices;
    },

    // Pikaday options
    pikadayStart() {
      return {
      }
    },
    pikadayEnd() {
      return {
        minDate: toDatetime(this.modal.start_date)
      }
    },

    serviceVersions() {
      return this.$root.routeService.channels.reduce((sum, c) => {
        if (c.openinghours && c.openinghours.length > 0) {
          sum.push({
            // Use channels as optgroup.
            "label": c.label,
            "versions": c.openinghours.map(o => {
              // For each channel, add all versions.
              return {
                "label": o.label,
                "id": o.id
              }
            })
          })
        }
        return sum;
      }, [])
    },

    usr() {
      return (this.$root.users && this.$root.users.find(u => u.id == this.route.id)) || {}
    },

    userServices() {
      return this.usr.roles || {};
    },

  },
  methods: {
    createChannel() {

      this.modalWait();

      if (!this.modal.label) {
        this.modal.label = 'Algemeen'
      }

      if (this.modal.type_id === '') {
        this.modal.type_id = null
      }

      Hub.$emit(this.modal.id ? 'updateChannel' : 'createChannel', this.modal)
    },
    createVersion() {

      this.modalWait();

      if (!this.modal.label) {
        this.modal.label = this.nextVersionLabel
      }

      if (this.modal.originalVersion) {
        this.modal.calendars = this.$root.routeService.channels.find(c => {
          if (!c.openinghours) {
            return false
          }
          c.openinghours.find(o => {
            if (o.id === this.modal.originalVersion)
              return o.calendars
          })
        })
      }

      // Align events with start_date and end_date
      if (this.modal.id && this.modal.calendars) {
        const version = this.modal;

        // Look for events that pose problems
        let invalid = false;
        this.modal.calendars.forEach(cal => {
          cal.events.forEach(event => {
            if (cal.layer && (event.start_date < version.start_date || event.until > version.end_date)) {
              invalid = true
            }
          })
        });

        if (invalid) {
          this.modalResume();
          this.modalError(OH_INVALID_RANGE);
          return;
        }

        // Update the event until date
        this.modal.calendars.forEach(cal => {
          let changed = false;
          cal.events.forEach(event => {
            if (!cal.layer) {
              // calculate difference to include openinghours past midnight
              let start = moment(event.start_date);
              let end = moment(event.end_date);
              let diff = end.startOf('day').diff(start.startOf('day'), 'days');

              event.start_date = moment(version.start_date).format('YYYY-MM-DD') + event.start_date.slice(10);
              event.end_date = moment(version.start_date).add(diff, 'days').format('YYYY-MM-DD') + event.end_date.slice(10);
              event.until = version.end_date;
              changed = true
            }
          });
          if (changed) {
            Hub.$emit('createCalendar', cal)
          }
        })
      }
      Hub.$emit(this.modal.id ? 'updateVersion' : 'createVersion', this.modal)
    },
    createRole() {

      this.modalWait();

      this.modal.strict = true;

      if (!this.modal.usr && !this.validEmail) {
        this.modalResume();
        this.modalError(NO_VALID_EMAIL);
        return;
      }

      if (this.modal.usr) {
        this.modal.user_id = this.modal.usr.id;
        this.modal.email = this.modal.usr.email;
      }

      if (!this.modal.user_id && !window.Vue.config.debug && !this.validEmail) {
        this.modalResume();
        return;
      }

      if (!this.modal.service_id && !this.modal.srv) {
        this.modalResume();
        this.modalError(CHOOSE_SERVICE);
        return;
      }

      Hub.$emit('inviteUser', this.modal)
    },
    updateSelected(options) {
      this.modal.service_id = options.map(option => option.id);
    }
  },
  updated() {
    const inp = this.$el.querySelector('input');
    inp && inp.focus()
  },
  components: {
    InputChannel,
    SelectChannelType,
    Pikaday,
    Status,
    Multiselect
  }
}
</script>
