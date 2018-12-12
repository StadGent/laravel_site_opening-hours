<template>
  <div>
    <input type="text" :id="id" class="form-control" v-model="parent[prop]" placeholder="Algemeen, na afspraak, tolk aanwezig, ..." list="channel-label">
    <datalist id="channel-label">
      <option>Algemeen</option>
      <option>Chat</option>
      <option>Inschrijvingen</option>
      <option>Jeugd</option>
      <option>Levering/afhaling</option>
      <option>Na afspraak</option>
      <option>Persoonlijke begeleiding</option>
      <option>Receptie</option>
      <option>Skype</option>
      <option>Telefonisch</option>
      <option>Tentoonstelling</option>
      <option>Tolk aanwezig</option>
      <option>Voor scholen en groepen</option>
    </datalist>
  </div>
</template>

<script>
export default {
  props: ['parent', 'prop', 'id']
}
</script>