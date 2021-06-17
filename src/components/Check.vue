<template>
  <div class="check" :class="status">
    <div class="mobile-icon">
      <div class="loading" v-if="status === 'loading'"></div>
      <div class="icon" v-else></div>
    </div>
    <div class="check-row">
      <div class="name" v-text="name"></div>
        <div class="desktop-icon">
          <div class="loading" v-if="status === 'loading'"></div>
          <div class="icon" v-else></div>
        </div>
      <div class="description" v-text="description"></div>
    </div>
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
  width: 25%;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
  flex-shrink: 1;
  margin-right: $layout-spacing-sm;
  border: 1px solid $primary-color;
  border-top: none;
  border-radius: $border-radius;

  .name {
    padding: 8px $layout-spacing-sm;
    background: $primary-color;
    text-align: center;
    color: $light-color;
    flex-grow: 0;
    flex-shrink: 0;
    border-top: 1px solid $primary-color;
    border-top-left-radius: $border-radius;
    border-top-right-radius: $border-radius;
  }

  .loading,
  .icon {
    height: 100px;
    flex-grow: 0;
    flex-shrink: 0;

    &:after {
      position: absolute;
      top: 25%;
      left: 50%;
      width: 70px;
      height: 70px;
      margin-left: -35px;
      border-radius: 50%;
      color: $light-color;
      text-align: center;
      line-height: 70px;
      font-weight: 700;
      font-size: 3em;
    }
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
      opacity: 1;
      padding: 0;
      position: absolute;
      top: 5%;
      width: $unit-16;
      z-index: $zindex-0;
    }
  }

  .description {
    border-top: none;
    padding: $layout-spacing-sm;
    flex-grow: 1;
    flex-shrink: 1;
    text-align: center;
    word-break: break-word;
  }

  &.success {
    border-color: $success-color;
    .name {
      background: $success-color;
      border-color: $success-color;
    }
    .icon {
      position: relative;
      &::after {
        content: '✔';
        background: $success-color;
        color: $light-color;
      }
    }
  }

  &.error {
    border-color: $error-color;
    .name {
      background: $error-color;
      border-color: $error-color;
    }
    .icon {
      position: relative;
      &::after {
        content: '✘';
        background: $error-color;
        color: $light-color;
      }
    }
  }

  & .mobile-icon {
    display: none;
  }

  @media(screen and max-width: 1200px) {
    & {
      width: 100%;
      flex-direction: row;
      border: none;

      .check-row {
        margin-left: 3em;

        &:not(:last-child) {
          margin-bottom: 2em;
        }
      }

      .desktop-icon {
        display: none;
      }

      .mobile-icon {
        display: block;
      }

      .loading,
      .icon {
        border: none;
        &:after {
          top: 13%;
        }
      }

      .name {
        color: $primary-color;
        padding: 0 $layout-spacing-sm;
        text-align: left;
        flex-grow: 0;
        flex-shrink: 0;
        border: none;
      }

      .description {
        padding: 0 $layout-spacing-sm $layout-spacing-sm;
        flex-grow: 1;
        flex-shrink: 1;
        text-align: left;
        border: none;
      }

      .name {
        padding: 0 $layout-spacing-sm;
        flex-grow: 1;
        flex-shrink: 1;
        text-align: left;
        background: transparent;
      }

      &.success, &.error {
        .name {
          background: transparent;
        }
      }

      &.success {
        .name {
          color: $success-color;
        }
      }

      &.error {
        .name {
          color: $error-color;
        }
      }
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
