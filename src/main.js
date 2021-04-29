import Vue from 'vue';
import Installer from './Installer.vue';
import store from './store';
import AssetDirective from './directives/asset';
import ApiPlugin from './plugins/api';

Vue.config.productionTip = false;

// Add global directives
Vue.directive('asset', AssetDirective);

// Add plugins
Vue.use(ApiPlugin);

new Vue({
  store,
  render: (h) => h(Installer),
}).$mount('#app');
