<template>
  <div id="app">
    <top-nav></top-nav>
    <top-breadcrumb></top-breadcrumb>

    <page-home v-if="route.page=='home'" :services="services" :users="users"></page-home>
    <page-service v-if="route.page=='service'" :users="users"></page-service>
    <page-channel v-if="route.page=='channel'"></page-channel>
    <page-user v-if="route.page=='user'"></page-user>
    <page-version v-if="route.page=='version'||route.page=='calendar'"></page-version>

    <modal-text></modal-text>
    <div class="modal-backdrop fade in" v-show="modalActive"></div>

    <div class="container" style="padding-top:10em">
      <h3>Debug info</h3>
      <label>
        <input type="checkbox" v-model="user.admin"> User is admin: {{ user.admin ? 'yes' : 'no' }}
      </label>
      <label>
        <input type="checkbox" disabled> User is owner: {{ isOwner ? 'yes' : 'no' }}
      </label>
      <label>
        <input type="checkbox" v-model="user.basic"> User is basic: {{ user.basic ? 'yes' : 'no' }}
      </label>
      <pre v-text="route"></pre>
    </div>
  </div>
</template>

<script>
import TopBreadcrumb from './components/TopBreadcrumb.vue'
import TopNav from './components/TopNav.vue'

import PageChannel from './page/PageChannel.vue'
import PageHome from './page/PageHome.vue'
import PageService from './page/PageService.vue'
import PageUser from './page/PageUser.vue'
import PageVersion from './page/PageVersion.vue'

import ModalText from './modal/ModalText.vue'

import { addListener } from './mixins/router.js'
import servicesMixin from './mixins/services.js'
import usersMixin from './mixins/users.js'

export default {
  name: 'app',
  mixins: [addListener, servicesMixin, usersMixin],
  components: {
    ModalText,
    PageChannel,
    PageHome,
    PageService,
    PageUser,
    PageVersion,
    TopBreadcrumb,
    TopNav
  }
}
</script>
