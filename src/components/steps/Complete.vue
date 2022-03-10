<template>
  <div class="step text-center">
    <div class="step-content">
      <div class="success-logo"></div>

      <h3>Winter CMS is now installed!</h3>

      <p>Your new installation is ready to go. We hope you enjoy using Winter CMS!</p>
    </div>
    <div class="step-actions">
      <a
        :href="siteUrl"
        target="wintercms"
        class="btn btn-primary"
      >
        View your install
      </a>

      <a
        :href="backendUrl"
        target="backend"
        class="btn btn-primary"
      >
        View your Backend
      </a>
    </div>
  </div>
</template>

<script>
import StepMixin from '@/mixins/step';

export default {
  name: 'Complete',
  mixins: [
    StepMixin,
  ],
  props: {
    site: {
      type: Object,
      required: true,
    },
  },
  computed: {
    siteUrl() {
      return this.site.url;
    },
    backendUrl() {
      return this.site.url + '/' + this.site.backendUrl;
    },
  },
  data() {
    return {
      stepId: 'complete',
      stepName: 'Complete',
    };
  },
};
</script>

<style lang="scss" scoped>
@import "spectre.css/src/mixins/shadow";

.step-content {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;

  .success-logo {
    position: relative;
    width: 120px;
    height: 120px;
    margin-bottom: $layout-spacing;

    border-radius: 50%;
    background-color: $success-color;
    overflow: hidden;

    &:after {
      content: '✔';
      position: absolute;
      top: 100%;
      left: 50%;
      width: 70px;
      height: 70px;
      margin-top: -35px;
      margin-left: -40px;
      animation: showTick 1s cubic-bezier(0, 1.5, 1, 1) 500ms both;

      color: $light-color;
      text-align: center;
      line-height: 70px;
      font-weight: 700;
      font-size: 6em;
    }
  }
}

.step-actions .btn:first-child {
  margin-right: $layout-spacing-sm;
}

p {
  margin-bottom: 0.6rem;
}

.btn {
  appearance: none;
  background: $bg-color-light;
  border: none;
  border-radius: $border-radius;
  color: $primary-color;
  cursor: pointer;
  display: inline-block;
  font-size: $font-size;
  height: $control-size;
  line-height: $line-height;
  outline: none;
  padding: $control-padding-y $control-padding-x;
  text-align: center;
  text-decoration: none;
  transition: background .2s, border .2s, box-shadow .2s, color .2s;
  user-select: none;
  vertical-align: middle;
  white-space: nowrap;

  &:focus {
    @include control-shadow();
  }
  &:focus,
  &:hover {
    background: $secondary-color;
    border-color: $primary-color-dark;
    text-decoration: none;
  }
  &:active,
  &.active {
    background: $primary-color-dark;
    border-color: darken($primary-color-dark, 5%);
    color: $light-color;
    text-decoration: none;
    &.loading {
      &::after {
        border-bottom-color: $light-color;
        border-left-color: $light-color;
      }
    }
  }

  // Button Primary
  &.btn-primary {
    background: $primary-color;
    border-color: $primary-color-dark;
    color: $light-color;
    &:focus,
    &:hover {
      background: darken($primary-color-dark, 2%);
      border-color: darken($primary-color-dark, 5%);
      color: $light-color;
    }
    &:active,
    &.active {
      background: darken($primary-color-dark, 4%);
      border-color: darken($primary-color-dark, 7%);
      color: $light-color;
    }
  }
}
</style>
