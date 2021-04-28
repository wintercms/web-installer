<template>
  <div id="sidebar">
    <div class="logo">
      <img v-asset="'install/assets/img/sidebar-logo.png'" alt="Winter CMS">
    </div>
    <div class="step-links">
      <a
        href="#"
        :class="stepClasses(step)"
        v-for="(step, i) in steps"
        :key="i"
        v-text="step.name"
        @click.prevent="onClick(step)"
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
      const classes = ['step-link'];

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
    onClick(step) {
      if (this.$store.getters['steps/isLocked'](step.id) || this.$store.getters['steps/isActive'](step.id)) {
        return;
      }

      this.$store.dispatch('steps/goTo', {
        id: step.id,
      });
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

  .step-links {
    .step-link {
      display: block;
      position: relative;
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

      &.complete {
        color: $success-color;

        &::after {
          content: '✔';
          position: absolute;
          top: 50%;
          right: $layout-spacing-sm;
          width: 24px;
          height: 24px;
          margin-top: -12px;

          background: $success-color;
          border-radius: 50%;
          color: $light-color;
          text-align: center;
          line-height: 24px;
          font-weight: 700;
          font-size: $font-size-lg;
        }
      }

      &.locked {
        cursor: default;
      }
    }
  }
}
</style>
