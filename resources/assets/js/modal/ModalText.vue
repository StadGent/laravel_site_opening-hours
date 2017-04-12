<template>
  <div class="modal fade" :class="{in: modal.text}" :style="{display: modal.text?'block':'none'}" @click="modalClose" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <form class="modal-content" @click.stop @submit.prevent>
        <div class="modal-header">
          <button type="button" class="close" @click="modalClose" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">
            <span v-if="modal.text=='requestService'">Vraag toegang tot een dienst</span>
            <span v-else-if="modal.text=='newChannel'">{{ modal.id ? 'Bewerk dit kanaal' : 'Voeg een kanaal toe' }}</span>
            <span v-else-if="modal.text=='newVersion'">{{ modal.id ? 'Bewerk deze versie' : 'Voeg een versie toe' }}</span>
            <span v-else-if="modal.text=='newRoleForUser'">Nodig {{ modal.usr.name }} uit voor een dienst</span>
            <span v-else-if="modal.text=='newRole'">Nodig iemand uit voor {{ modal.srv.label }}</span>
            <span v-else-if="modal.text=='newUser'">Nodig iemand uit</span>
            <span v-else>Probleem</span>
          </h4>
        </div>
        <div class="modal-body">
          <div v-if="modal.text=='requestService'">
            Mail naar <a href="mailto:admin@mijngent.be">admin@mijngent.be</a> om toegang te vragen.
          </div>
          <div v-else-if="modal.text=='newChannel'" class="form-group">
            <label for="recipient-name" class="control-label">Naam van het kanaal</label>
            <input-channel :parent="modal" prop="label"></input-channel>
            <div class="help-block">
              Een kanaal is een manier waarop burgers jouw dienst kunnen contacteren.
              <br><br> Dat kunnen algemene openingsuren zijn, maar ook bijvoorbeeld de telefonische beschikbaarheid, afspraak momenten, wanneer er een tolk aanwezig is, of wanneer een bepaalde doelgroep een bezoekje kan brengen zoals jongeren.
            </div>
          </div>
          <div v-else-if="modal.text=='newVersion'">
            <div class="form-group">
              <label for="recipient-name" class="control-label">Naam van de versie</label>
              <input type="text" class="form-control" v-model="modal.label" :placeholder="nextVersionLabel">
            </div>

            <div class="row form-group">
              <div class="col-sm-6">
                <label for="start_date" class="control-label">Geldig van</label>
                <pikaday id="start_date" class="form-control" v-model="modal.start_date" :options="pikadayStart" />
              </div>
              <div class="col-sm-6">
                <label for="end_date" class="control-label">Verloopt op</label>
                <pikaday id="end_date" class="form-control" v-model="modal.end_date" :options="pikadayEnd" />
              </div>
            </div>
          </div>
          <div v-else-if="modal.text == 'newRole' || modal.text == 'newUser'">
            <div class="form-group" :class="{'has-error':!validEmail, 'has-success':allowedEmail}">
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
                  <input type="radio" name="modalRole" v-model="modal.role" value="Member"> Lid
                </label>
              </div>
              <div class="radio">
                <label>
                  <input type="radio" name="modalRole" v-model="modal.role" value="Owner"> Beheerder
                </label>
              </div>
            </div>
            <div class="form-group" :class="{ 'has-error': 0, 'has-success': 0 }">
              <label for="recipient-name" class="control-label">Dienst</label>
              <select v-model="modal.service_id" class="form-control">
                <option v-for="service in allowedServices" :value="service.id" v-text="service.label"></option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div v-if="modal.text=='newChannel'">
            <button type="submit" class="btn btn-primary" @click="createChannel">Voeg toe</button>
            <button type="button" class="btn btn-default" @click="modalClose">Annuleer</button>
          </div>
          <div v-else-if="modal.text=='newVersion'">
            <button type="submit" class="btn btn-primary" @click="createVersion">{{ modal.id ? 'Sla wijzigingen op' : 'Voeg toe' }}</button>
            <button type="button" class="btn btn-default" @click="modalClose">Annuleer</button>
          </div>
          <div v-else-if="modal.text == 'newRole' || modal.text == 'newUser' || modal.text == 'newRoleForUser'">
            <button type="submit" class="btn btn-primary" @click="createRole">Uitnodigen</button>
            <button type="button" class="btn btn-default" @click="modalClose">Annuleer</button>
          </div>
          <div v-else>
            <button type="submit" class="btn btn-primary" @click="modalClose">OK</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import InputChannel from '../components/InputChannel.vue'
import Pikaday from '../components/Pikaday.vue'

import { Hub, toDatetime } from '../lib.js'

export default {
  computed: {
    validEmail () {
      return !this.modal.strict || /.+@.+\...+/.test(this.modal.email || '')
    },
    allowedEmail () {
      return (this.modal.email || '').endsWith('@mijngent.be')
    },
    nextVersionLabel () {
      if (!this.modal.start_date || !this.modal.end_date) {
        return 'Nieuwe versie'
      }
      return 'Openingsuren ' + this.modal.start_date.slice(0, 4) + ' tot en met ' + (parseInt(this.modal.end_date.slice(0, 4), 10) - 1)
    },

    allowedServices () {
      return this.$root.services.filter(s => !s.draft)
    },

    // Pikaday options
    pikadayStart () {
      return {
        maxDate: toDatetime(this.modal.end_date)
      }
    },
    pikadayEnd () {
      return {
        minDate: toDatetime(this.modal.start_date)
      }
    }
  },
  methods: {
    createChannel () {
      if (!this.modal.label) {
        this.modal.label = 'Algemeen'
      }
      Hub.$emit('createChannel', this.modal)
    },
    createVersion () {
      if (!this.modal.label) {
        this.modal.label = this.nextVersionLabel
      }

      // Align events with start_date and end_date
      if (this.modal.id && this.modal.calendars) {
        const version = this.modal

        // Look for events that pose problems
        let invalid = false
        this.modal.calendars.forEach(cal => {
          cal.events.forEach(event => {
            if (cal.layer && event.start_date < version.start_date) {
              invalid = true
            }
          })
        })
        if (invalid) {
          return alert('Er mogen geen events beginnen voor de start van de versie.\n\nDe wijziging werd niet doorgevoerd.')
        }

        // Update the event until date
        this.modal.calendars.forEach(cal => {
          let changed = false
          cal.events.forEach(event => {
            if (!cal.layer) {
              event.start_date = version.start_date + event.start_date.slice(10)
              event.end_date = version.start_date + event.end_date.slice(10)
              changed = true
            }
            if (!cal.layer || event.until > version.end_date) {
              event.until = version.end_date
              changed = true
            }
          })
          if (changed) {
            Hub.$emit('createCalendar', cal)
          }
        })
      }
      Hub.$emit(this.modal.id ? 'updateVersion' : 'createVersion', this.modal)
    },
    createRole () {
      this.modal.strict = true
      if (this.modal.usr) {
        this.modal.user_id = this.modal.usr.id
      }
      if (!this.modal.user_id && !window.Vue.config.debug && !this.validEmail) {
        return
      }
      if (!this.modal.service_id && !this.modal.srv) {
        return alert('Kies een dienst')
      }
      Hub.$emit('createRole', this.modal)
    }
  },
  updated () {
    const inp = this.$el.querySelector('input')
    inp && inp.focus()
  },
  components: {
    InputChannel,
    Pikaday
  }
}
</script>