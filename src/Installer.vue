<template>
  <div id="installer-container" class="container grid-xl">
    <div id="installer">
      <Sidebar />

      <div class="content">
        <transition-group name="fade">
          <Introduction v-show="isStepActive('intro')" :key="'intro'" />
          <Checks v-show="isStepActive('checks')" :key="'checks'" />
          <License v-show="isStepActive('license')" :key="'license'" />
          <SiteDetails v-show="isStepActive('site')" :key="'site'" />
          <Database v-show="isStepActive('database')" :key="'database'" />
        </transition-group>
      </div>
    </div>
  </div>
</template>

<script>
import Sidebar from '@/components/Sidebar.vue';
import Introduction from '@/components/steps/Introduction.vue';
import Checks from '@/components/steps/Checks.vue';
import License from '@/components/steps/License.vue';
import SiteDetails from '@/components/steps/SiteDetails.vue';
import Database from '@/components/steps/Database.vue';

export default {
  name: 'Installer',
  components: {
    Sidebar,
    Introduction,
    Checks,
    License,
    SiteDetails,
    Database,
  },
  mounted() {
    this.$store.dispatch('steps/goTo', {
      id: 'intro',
    });
  },
  methods: {
    isStepActive(id) {
      return this.$store.getters['steps/isActive'](id);
    },
  },
};
</script>

<style lang="scss">
@import "@/assets/scss/base";

html,
body {
  height: 100%;
}

body {
  background-image: url('/install/assets/img/background.jpg');
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center;
}

#installer-container {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 100%;

  #installer {
    display: flex;
    flex-direction: row;
    width: 100%;
    height: 90%;
    max-height: 600px;

    background: $body-bg;
    border-radius: $border-radius;
    box-shadow: 0px 12px 6px rgba(0, 0, 0, 0.18);

    .content {
      position: relative;
      height: 100%;
      width: 100%;
    }
  }
}
</style>
