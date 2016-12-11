<template>
  <div class="modal fade" :class="{in: modal.text}" :style="{display: modal.text?'block':'none'}" @click="modalClose" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <button type="button" class="close" @click="modalClose" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">
            <span v-if="modal.text=='requestService'">Vraag toegang tot een dienst</span>
            <span v-else-if="modal.text=='newChannel'">Voeg een kanaal toe</span>
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
            <input type="text" class="form-control" v-model="modal.label">
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
            <button type="button" class="btn btn-primary" @click="createChannel">Toevoegen</button>
            <button type="button" class="btn btn-default" @click="modalClose">Annuleren</button>
          </div>
          <div v-else-if="modal.text=='newRole'">
            <button type="button" class="btn btn-primary" @click="createRole">Uitnodigen</button>
            <button type="button" class="btn btn-default" @click="modalClose">Annuleren</button>
          </div>
          <div v-else>
            <button type="button" class="btn btn-primary" @click="modalClose">OK</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Hub } from '../lib.js'

export default {
  computed: {
    validEmail () {
      return !this.modal.strict || /.+@.+\...+/.test(this.modal.email || '')
    },
    allowedEmail () {
      return (this.modal.email || '').endsWith('@mijngent.be')
    }
  },
  methods: {
    createChannel () {
      Hub.$emit('createChannel', this.modal)
    },
    createRole () {
      this.modal.strict = true
      if (!window.Vue.config.debug && !this.validEmail) {
        return
      }
      Hub.$emit('createRole', this.modal)
    }
  }
}
</script>