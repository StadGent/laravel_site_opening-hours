import './bootstrap.js'
import App from './App.vue'

import { rootAuthMixin } from './mixins/auth.js'
import { rootRouterMixin } from './mixins/router.js'
import printmeMixin from './mixins/printme.js'
import servicesMixin from './mixins/services.js'
import usersMixin from './mixins/users.js'
/*
Vue.component(
    'passport-clients',
    require('./components/passport/Clients.vue')
);

Vue.component(
    'passport-authorized-clients',
    require('./components/passport/AuthorizedClients.vue')
);

Vue.component(
    'passport-personal-access-tokens',
    require('./components/passport/PersonalAccessTokens.vue')
);
 */
const app = new Vue({
  el: '#app',
  render: h => h(App),
  mixins: [rootAuthMixin, rootRouterMixin, printmeMixin, servicesMixin, usersMixin]
})
