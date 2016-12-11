import './bootstrap.js'
import App from './App.vue'

import { rootAuthMixin } from './mixins/auth.js'

const app = new Vue({
  el: '#app',
  render: h => h(App),
  mixins: [rootAuthMixin]
})
