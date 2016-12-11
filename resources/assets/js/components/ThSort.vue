<template>
  <th class="th-sortable"
    @click="setOrder(by)"
    :class="{up:$parent.order==by,'down':$parent.order=='-'+by}"
    :width="width"
    >
    <slot></slot>
  </th>
</template>

<script>
export default {
  props: ['by', 'width'],
  methods: {
    setOrder (value) {
      var p = this.$parent
      if (p.order === value) {
        p.order = '-' + value
      } else if (p.order === '-' + value) {
        p.order = null
      } else {
        p.order = value
      }
      p.offset = null
    }
  }
}
</script>