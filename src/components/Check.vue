<template>
  <div class="check" :class="status">
    <div class="name" v-text="name"></div>

    <div class="loading" v-if="status === 'loading'"></div>
    <div class="icon" v-else></div>

    <div class="description" v-text="description"></div>
  </div>
</template>

<script>
export default {
  name: 'Check',
  props: {
    name: {
      type: String,
      required: true,
    },
    description: {
      type: String,
      required: true,
    },
    status: {
      type: String,
      default: 'loading',
    },
  },
};
</script>

<style lang="scss" scoped>
.check {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
  flex-shrink: 1;
  margin-right: $layout-spacing-sm;

  width: 25%;

  .name {
    padding: 8px $layout-spacing-sm;

    border-radius: $border-radius;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
    background: $primary-color;
    text-align: center;
    color: $light-color;
    flex-grow: 0;
    flex-shrink: 0;
  }

  .loading,
  .icon {
    border-left: 1px solid $primary-color;
    border-right: 1px solid $primary-color;
    height: 100px;
    flex-grow: 0;
    flex-shrink: 0;
  }

  .loading {
    color: transparent !important;
    min-height: $unit-16;
    pointer-events: none;
    position: relative;

    &::after {
      animation: loading 500ms infinite linear;
      background: transparent;
      border: $border-width-lg solid $primary-color;
      border-radius: 50%;
      border-right-color: transparent;
      border-top-color: transparent;
      content: '';
      display: block;
      height: $unit-16;
      left: 50%;
      margin-left: -$unit-8;
      margin-top: -$unit-6;
      opacity: 1;
      padding: 0;
      position: absolute;
      top: 50%;
      width: $unit-16;
      z-index: $zindex-0;
    }
  }

  .description {
    border: 1px solid $primary-color;
    border-top: none;
    padding: $layout-spacing-sm;
    border-radius: $border-radius;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
    flex-grow: 1;
    flex-shrink: 1;
    text-align: center;
  }

  &.success {
    .name {
      background: $success-color;
    }

    .loading,
    .icon {
      border-color: $success-color;
    }

    .icon {
      position: relative;

      &::after {
        content: '✔';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 70px;
        height: 70px;
        margin-top: -25px;
        margin-left: -35px;

        background: $success-color;
        border-radius: 50%;
        color: $light-color;
        text-align: center;
        line-height: 70px;
        font-weight: 700;
        font-size: 3em;
      }
    }

    .description {
      border-color: $success-color;
    }
  }

  &.error {
    .name {
      background: $error-color;
    }

    .loading,
    .icon {
      border-color: $error-color;
    }

    .icon {
      position: relative;

      &::after {
        content: '✘';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 70px;
        height: 70px;
        margin-top: -25px;
        margin-left: -35px;

        background: $error-color;
        border-radius: 50%;
        color: $light-color;
        text-align: center;
        line-height: 70px;
        font-weight: 700;
        font-size: 3em;
      }
    }

    .description {
      border-color: $error-color;
    }
  }
}

@keyframes loading {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
