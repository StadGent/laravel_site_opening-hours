<template>
  <div class="form-group">
    <label for="select_channel_type">Type</label>
      <select name="type" id="select_channel_type" class="form-control" v-model="parent[prop]">
        <option value="null">Geen type</option>
        <option v-for="type in types" v-bind:value="type.id">{{type.name}}</option>
      </select>
  </div>
</template>

<script>
export default {
  props: ['parent', 'prop', 'id'],
    computed: {
      types() {
          return this.$root.types;
      }
    }
}
</script>