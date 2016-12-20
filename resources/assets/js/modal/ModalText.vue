<template>
  <div class="modal fade" :class="{in: modal.text}" :style="{display: modal.text?'block':'none'}" @click="modalClose" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <form class="modal-content" @click.stop @submit.prevent>
        <div class="modal-header">
          <button type="button" class="close" @click="modalClose" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">
            <span v-if="modal.text=='requestService'">Vraag toegang tot een dienst</span>
            <span v-else-if="modal.text=='newChannel'">Voeg een kanaal toe</span>
            <span v-else-if="modal.text=='newVersion'">Voeg een versie toe</span>
            <span v-else-if="modal.text=='newRole'">Nodig iemand uit voor {{ modal.srv.label }}</span>
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
                <label for="recipient-name" class="control-label">Geldig van</label>
                <select class="form-control" v-model="modal.start_date">
                  <option v-for="i in 10" v-text="(i + 2015)+'-01-01'"></option>
                </select>
              </div>
              <div class="col-sm-6">
                
                <label for="recipient-name" class="control-label">Verloopt op</label>
                <select class="form-control" v-model="modal.end_date">
                  <option v-for="i in 10" v-text="(i + parseInt(modal.start_date.slice(0,4), 10))+'-01-01'"></option>
                </select>
              </div>
            </div>
          </div>
          <div v-else-if="modal.text=='newRole'">
            <div class="form-group" :class="{'has-error':!validEmail, 'has-success':allowedEmail}">
              <label for="recipient-name" class="control-label">E-mailadres</label>
              <input type="text" class="form-control" v-model="modal.email" placeholder="... @mijngent.be">
            </div>
            <div class="alert alert-warning" v-if="!allowedEmail">
              <b>Pas op!</b> Het is de bedoeling dat je alleen mensen uitnodigt van Mijn Gent.
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div v-if="modal.text=='newChannel'">
            <button type="submit" class="btn btn-primary" @click="createChannel">Voeg toe</button>
            <button type="button" class="btn btn-default" @click="modalClose">Annuleer</button>
          </div>
          <div v-else-if="modal.text=='newVersion'">
            <button type="submit" class="btn btn-primary" @click="createVersion">Toevoegen</button>
            <button type="button" class="btn btn-default" @click="modalClose">Annuleren</button>
          </div>
          <div v-else-if="modal.text=='newRole'">
            <button type="submit" class="btn btn-primary" @click="createRole">Uitnodigen</button>
            <button type="button" class="btn btn-default" @click="modalClose">Annuleren</button>
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

import { Hub } from '../lib.js'

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
      return this.modal.start_date.slice(0, 4) + ' t.e.m. ' + (parseInt(this.modal.end_date.slice(0, 4), 10) - 1)
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
      Hub.$emit('createVersion', this.modal)
    },
    createRole () {
      this.modal.strict = true
      if (!window.Vue.config.debug && !this.validEmail) {
        return
      }
      Hub.$emit('createRole', this.modal)
    }
  },
  updated () {
    const inp = this.$el.querySelector('input')
    inp && inp.focus()
  },
  components: {
    InputChannel
  }
}
</script>