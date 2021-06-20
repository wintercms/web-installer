<template>
  <button
    :type="type"
    :class="buttonClasses"
    role="button"
    :aria-pressed="pressed"
    :aria-label="label"
    :disabled="disabled"
    @mousedown.stop="click"
    @mouseup.stop="unclick"
    ref="button"
  >
    <span
      class="button-label"
      v-text="label"
    ></span>
  </button>
</template>

<script>
export default {
  name: 'Click',
  props: {
    type: {
      type: String,
      default: 'button',
      validator(value) {
        return (['button', 'submit', 'reset'].indexOf(value) !== -1);
      },
    },
    label: {
      type: String,
      required: true,
    },
    flag: {
      type: String,
      default: 'default',
      validator(value) {
        return ([
          'primary',
          'secondary',
          'error',
          'warning',
          'success',
          'default',
          'info',
          'link',
        ].indexOf(value) !== -1);
      },
    },
    size: {
      type: String,
      default: 'md',
      validator(value) {
        return (['xs', 'sm', 'md', 'lg', 'xl'].indexOf(value) !== -1);
      },
    },
    block: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    addClasses: {
      type: String,
    },
  },
  data() {
    return {
      mounted: false,
      hovered: false,
      pressed: false,
    };
  },
  computed: {
    buttonClasses() {
      const classes = [`btn-${this.size}`, `btn-${this.flag}`];

      if (this.disabled) {
        classes.push('btn-disabled');
      }
      if (this.block) {
        classes.push('btn-block');
      }
      if (this.pressed) {
        classes.push('pressed');
      }
      if (this.addClasses) {
        classes.push(this.addClasses);
      }

      return classes.join(' ');
    },
  },
  mounted() {
    this.mounted = true;
  },
  destroyed() {
    this.mounted = false;
  },
  methods: {
    click() {
      if (this.disabled) {
        return;
      }
      if (this.pressed === false) {
        this.pressed = true;
      }
    },
    unclick() {
      if (this.disabled) {
        return;
      }
      if (this.pressed === true) {
        this.pressed = false;
        this.$emit('press');
      }
    },
  },
};
</script>

<style lang="scss" scoped>
@import "~spectre.css/src/mixins/button";
@import "~spectre.css/src/mixins/shadow";

button {
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
  &[disabled],
  &:disabled,
  &.disabled {
    cursor: default;
    opacity: .5;
    pointer-events: none;
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
    &.loading {
      &::after {
        border-bottom-color: $light-color;
        border-left-color: $light-color;
      }
    }
  }

  // Button Colors
  &.btn-success {
    @include button-variant($success-color);
  }

  &.btn-error {
    @include button-variant($error-color);
  }

  // Button Link
  &.btn-link {
    background: transparent;
    border-color: transparent;
    color: $link-color;
    &:focus,
    &:hover,
    &:active,
    &.active {
      color: $link-color-dark;
    }
  }

  // Button Sizes
  &.btn-sm {
    font-size: $font-size-sm;
    height: $control-size-sm;
    padding: $control-padding-y-sm $control-padding-x-sm;
  }

  &.btn-lg {
    font-size: $font-size-lg;
    height: $control-size-lg;
    padding: $control-padding-y-lg $control-padding-x-lg;
  }

  // Button Block
  &.btn-block {
    display: block;
    width: 100%;
  }

  // Button Clear
  &.btn-clear {
    background: transparent;
    border: 0;
    color: currentColor;
    height: $unit-5;
    line-height: $unit-4;
    margin-left: $unit-1;
    margin-right: -2px;
    opacity: 1;
    padding: $unit-h;
    text-decoration: none;
    width: $unit-5;

    &:focus,
    &:hover {
      background: rgba($bg-color, .5);
      opacity: .95;
    }

    &::before {
      content: "\2715";
    }
  }
}
</style>
