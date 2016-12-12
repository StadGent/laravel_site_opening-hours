<template>
  <div class="calendar"></div>
</template>

<script>
export default {
  props: ['data'],
  mounted () {
    $(this.$el).calendar({
      dataSource: [{
        startDate: '2016-12-15',
        endDate: '2016-12-15',
        name: 'blub'
      }]
    })
  }
}
</script>