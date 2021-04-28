<template>
  <div id="sidebar">
    <div class="logo">
      <img v-asset="'install/assets/img/sidebar-logo.png'" alt="Winter CMS">
    </div>
    <div class="steps">
      <a
        href="#"
        :class="stepClasses(step)"
        v-for="(step, i) in steps"
        :key="i"
        v-text="step.name"
      ></a>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex';

export default {
  name: 'Sidebar',
  computed: mapState({
    steps: (state) => state.steps.steps,
  }),
  methods: {
    stepClasses(step) {
      const classes = ['step'];

      if (step.active) {
        classes.push('active');
      }
      if (step.status === 'locked') {
        classes.push('locked');
      }
      if (step.status === 'failed') {
        classes.push('failed');
      }
      if (step.status === 'complete') {
        classes.push('complete');
      }

      return classes.join(' ');
    },
  },
};
</script>

<style lang="scss">
#sidebar {
  width: 250px;
  flex-shrink: 0;

  background: $gray-color-light;
  box-shadow: -5px 0 20px rgba(0, 0, 0, 0.08) inset;
  overflow: hidden;
  border-top-left-radius: $border-radius;
  border-bottom-left-radius: $border-radius;

  .logo {
    padding: $layout-spacing 20px;
  }

  .steps {
    .step {
      display: block;
      padding: $layout-spacing-sm 20px;

      font-family: $heading-font-family;
      font-weight: 500;
      color: $darker-color;
      text-decoration: none;
      opacity: 1;

      &.active {
        margin-right: -1px;

        background: $body-bg;
        box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.08);
        font-weight: 800;
      }

      &.locked {
        cursor: default;
      }
    }
  }
}
</style>
