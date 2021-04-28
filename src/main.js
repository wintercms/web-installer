import Vue from 'vue';
import Installer from './Installer.vue';
import store from './store';
import AssetDirective from './directives/asset';

Vue.config.productionTip = false;

// Add global directives
Vue.directive('asset', AssetDirective);

new Vue({
  store,
  render: (h) => h(Installer),
}).$mount('#app');
