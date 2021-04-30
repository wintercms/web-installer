import Vue from 'vue';
import Installer from './Installer.vue';
import store from './store';
import ApiPlugin from './plugins/api';

Vue.config.productionTip = false;

// Add plugins
Vue.use(ApiPlugin);

new Vue({
  store,
  render: (h) => h(Installer),
}).$mount('#app');
