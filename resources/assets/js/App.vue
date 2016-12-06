<template>
  <div id="app">
    <top-nav></top-nav>
    <top-breadcrumb></top-breadcrumb>
    <page-home v-if="route.page=='home'" :services="services"></page-home>
    <page-service v-if="route.page=='service'"></page-service>
    <page-channel v-if="route.page=='channel'"></page-channel>
    <page-calendar v-if="route.page=='calendar'"></page-calendar>
    <div class="container" style="padding-top:10em">
      <h3>Debug info</h3>
      <pre v-text="route"></pre>
    </div>
  </div>
</template>

<script>
import PageCalendar from './page/PageCalendar.vue'
import PageChannel from './page/PageChannel.vue'
import PageHome from './page/PageHome.vue'
import PageService from './page/PageService.vue'
import TopBreadcrumb from './components/TopBreadcrumb.vue'
import TopNav from './components/TopNav.vue'

import { addListener } from './mixins/router.js'
import servicesMixin from './mixins/services.js'

export default {
  name: 'app',
  mixins: [addListener, servicesMixin],
  components: {
    PageCalendar,
    PageChannel,
    PageHome,
    PageService,
    TopBreadcrumb,
    TopNav
  }
}
</script>
