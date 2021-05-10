<template>
  <div
    class="tab-pane"
    :class="{attached}"
    v-show="isActive"
  >
    <slot></slot>
  </div>
</template>

<script>
export default {
  name: 'Tab',
  inject: {
    $tabs: {
      from: '$tabs',
      default: null,
    },
  },
  props: {
    name: {
      type: String,
      required: true,
    },
    active: {
      type: Boolean,
      default: false,
    },
    attached: {
      type: Boolean,
      default: false,
    },
  },
  watch: {
    active(val) {
      this.isActive = val;
    },
  },
  data() {
    return {
      isActive: this.active,
    };
  },
  mounted() {
    if (this.$tabs) {
      this.$tabs.addTab(this);
    }
  },
  destroyed() {
    if (this.$tabs) {
      this.$tabs.removeTab(this);
    }
  },
};
</script>
