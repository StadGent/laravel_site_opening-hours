import './bootstrap.js'
import App from './App.vue'

import { rootAuthMixin } from './mixins/auth.js'
import { rootRouterMixin } from './mixins/router.js'
import servicesMixin from './mixins/services.js'
import usersMixin from './mixins/users.js'

const app = new Vue({
  el: '#app',
  render: h => h(App),
  mixins: [rootAuthMixin, rootRouterMixin, servicesMixin, usersMixin]
})
