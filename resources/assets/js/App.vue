<template>
  <div id="app" :class="{ printme: $root.printme }">
    <top-nav></top-nav>
    <top-breadcrumb></top-breadcrumb>

    <page-home v-if="route.page=='home'" :services="$root.services" :users="$root.users"></page-home>
    <page-service v-if="route.page=='service'" :users="$root.users"></page-service>
    <page-channel v-if="route.page=='channel'"></page-channel>
    <page-user v-if="route.page=='user'"></page-user>
    <page-version v-if="route.page=='version'||route.page=='calendar'"></page-version>

    <modal-text></modal-text>
    <div class="modal-backdrop fade in" v-show="modalActive"></div>

    <div class="container" style="padding-top:10em">
      <h3>Debug info</h3>
      <label>
        <input type="checkbox" v-model="$root.user.admin"> User is admin: {{ isAdmin ? 'yes' : 'no' }}
      </label>
      <br>
      Service role is: {{ isOwner ? 'owner' : 'member' }}
      <!-- <pre v-text="route"></pre> -->
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

export default {
  name: 'app',
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
