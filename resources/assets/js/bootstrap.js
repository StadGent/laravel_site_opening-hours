import './mixins/filters.js'

import authMixin from './mixins/auth.js'
import modalMixin from './mixins/modal.js'
import routerMixin from './mixins/router.js'

// Make this.route globally available
Vue.mixin(authMixin)
Vue.mixin(modalMixin)
Vue.mixin(routerMixin)

Vue.http.interceptors.push((request, next) => {
  request.headers.set('X-CSRF-TOKEN', Laravel.csrfToken);
  next();
});
