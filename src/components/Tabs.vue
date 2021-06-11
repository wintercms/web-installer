<template>
  <div class="tabs-container">
    <TabNav
      :tabs="tabs"
      @tabchange="setActiveTab"
      ref="tabNav"
    >
    </TabNav>

    <div class="tab-panes">
      <slot></slot>
    </div>
  </div>
</template>

<script>
import Vue from 'vue';
import TabNav from './TabNav.vue';

const tabCollection = new Vue({
  data() {
    return {
      tabs: [],
      activeTab: null,
      tabCount: 0,
    };
  },
  methods: {
    addTab(tab) {
      this.tabs.push(tab);
      this.tabCount += 1;
      if (tab.isActive === true) {
        this.setActiveTab(tab);
      }
    },
    findTab(tab) {
      return this.tabs.findIndex((item) => (item === tab));
    },
    setActiveTab(tab) {
      const key = this.findTab(tab);
      if (key !== -1) {
        this.activeTab = key;
      }
      this.refreshTabActiveStates();
    },
    setActiveTabByKey(key) {
      this.activeTab = key;
      this.refreshTabActiveStates();
    },
    removeTab(tab) {
      const key = this.findTab(tab);
      if (key !== -1) {
        this.tabs.splice(key, 1);
        this.tabCount -= 1;
      }
    },
    refreshTabActiveStates() {
      this.tabs.forEach((tab, index) => {
        tab.isActive = false;
        if (this.activeTab === index) {
          tab.isActive = true;
        }
      });
    },
  },
});
export default {
  name: 'Tabs',
  provide: {
    $tabs: tabCollection,
  },
  components: {
    TabNav,
  },
  computed: {
    tabs() {
      return tabCollection.tabs.map((item) => item.name);
    },
  },
  methods: {
    setActiveTab(tab) {
      tabCollection.setActiveTabByKey(Number(tab.replace('tab-', '')));
    },
  },
};
</script>

<style lang="scss">
.tab-panes {
  margin-top: $layout-spacing-sm;
}
</style>
