<template>
  <transition name="slideup">
    <div class="pagination-wrapper container" v-if="lastPage">
        <ul class="pagination pull-right">
          <li :class="{disabled:!currentPage}"><a href="#" @click.prevent="fetch(0)">Eerste</a></li>
          <li :class="{disabled:!currentPage}"><a href="#" @click.prevent="fetch(currentPage - 1)">Vorige</a></li>
          <li v-if="currentPage - 2 > 0 && lastPage > 4"><a href="#"  class="disabled">...</a></li>

          <li v-if="currentPage - 4 >= 0 && currentPage == lastPage"><a href="#" @click.prevent="fetch(currentPage - 4)">{{ currentPage - 3}}</a></li>
          <li v-if="currentPage - 3 >= 0 && currentPage >= lastPage - 1"><a href="#" @click.prevent="fetch(currentPage - 3)">{{ currentPage - 2}}</a></li>
          <li v-if="currentPage - 2 >= 0"><a href="#" @click.prevent="fetch(currentPage - 2)">{{ currentPage - 1}}</a></li>
          <li v-if="currentPage - 1  >= 0"><a href="#" @click.prevent="fetch(currentPage - 1)">{{ currentPage}}</a></li>
          <li class="active"><a href="#">{{ currentPage +  1}}</a></li>
          <li v-if="currentPage + 1 <= lastPage"><a href="#" @click.prevent="fetch(currentPage + 1)">{{ currentPage +  2}}</a></li>
          <li v-if="currentPage + 2 <= lastPage"><a href="#" @click.prevent="fetch(currentPage + 2)">{{ currentPage +  3}}</a></li>
          <li v-if="currentPage + 3 <= lastPage && currentPage <= 1"><a href="#" @click.prevent="fetch(currentPage + 3)">{{ currentPage +  4}}</a></li>
          <li v-if="currentPage + 4 <= lastPage && currentPage == 0"><a href="#" @click.prevent="fetch(currentPage + 4)">{{ currentPage +  5}}</a></li>

          <li v-if="currentPage + 2 < lastPage && lastPage > 4"><a href="#" >...</a></li>
          <li :class="{disabled:currentPage == lastPage}"><a href="#" @click.prevent="fetch(currentPage + 1)">Volgende</a></li>
          <li :class="{disabled:currentPage == lastPage}"><a href="#" @click.prevent="fetch(lastPage)">Laatste</a></li>
        </ul>
      </div>
  </transition>
</template>

<script>
export const pageSize = 20;

export default {
  name: 'pagination',
  props: ['total'],
  computed: {
    currentPage () {
      return Math.round((this.route.offset || 0) / pageSize)
    },
    lastPage () {
      return this.total && Math.ceil((this.total || 0) / pageSize) - 1
    }
  },
  methods: {
    fetch(p) {
      if (p < 0 || p > this.lastPage) {
        return
      }
      this.route.offset = p * pageSize
    }
  }
}
</script>