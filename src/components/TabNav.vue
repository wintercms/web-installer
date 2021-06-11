<template>
  <ul class="tab tab-block">
    <li
      class="tab-item"
      v-for="(tabName, tabKey) in tabs"
      :key="tabKey"
      :class="{active: isActiveTab(tabKey)}"
    >
      <a href="#" v-text="tabName" @click.prevent="setTab(tabKey)"></a>
    </li>
  </ul>
</template>

<script>
export default {
  name: 'TabNav',
  props: {
    tabs: {
      type: [Object, Array],
      required: true,
    },
    default: {
      type: String,
    },
  },
  data() {
    return {
      active: null,
    };
  },
  mounted() {
    if (!this.default || this.tabs[this.default] === undefined) {
      if (Array.isArray(this.tabs)) {
        this.active = 'tab-' + 0;
      } else {
        this.active = this.tabs[Object.keys(this.tabs)[0]];
      }
    } else {
      this.active = this.default;
    }
    this.$emit('tabchange', this.active);
  },
  methods: {
    formattedKey(key) {
      if (Array.isArray(this.tabs)) {
        return 'tab-' + key;
      }
      return key;
    },
    isActiveTab(key) {
      return (this.active === this.formattedKey(key));
    },
    setTab(key) {
      this.active = this.formattedKey(key);
      this.$emit('tabchange', this.active);
    },
  },
};
</script>

<style lang="scss">
@import "~spectre.css/src/tabs";

.tab.tab-block {
  display: inline-block;
  margin: 0;
  background: darken($gray-color-light, 2%);
  padding: $unit-2;
  border-radius: $border-radius;

  & > li.tab-item {
    display: inline-block;
    flex: 0;
    margin-bottom: -1px;

    &:first-child {
      padding-left: 0;
    }

    &:last-child {
      padding-right: 0;
    }

    a {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      width: auto;
      flex-grow: 1;
      height: 100%;
      white-space: nowrap;
      outline: none !important;
      box-shadow: none !important;
      border-bottom: none;
      padding: $unit-2 $unit-6;
    }

    &.active a {
      color: $body-font-color;
      background: $secondary-color;
      border-radius: $border-radius;
    }
  }
}
</style>
