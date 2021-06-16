<template>
  <div class="step text-center">
    <div class="step-content">
      <div class="success-logo"></div>

      <h3>Winter CMS is now installed!</h3>

      <p>Your new installation is ready to go. We hope you enjoy using Winter CMS.</p>
    </div>
    <div class="step-actions">
      <Click
        label="View your install"
        flag="primary"
        @press="viewSite"
      />

      <Click
        label="View your Backend"
        flag="primary"
        @press="viewBackend"
      />
    </div>
  </div>
</template>

<script>
import StepMixin from '@/mixins/step';
import Click from '@/components/Click.vue';

export default {
  name: 'Complete',
  mixins: [
    StepMixin,
  ],
  components: {
    Click,
  },
  props: {
    site: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      stepId: 'complete',
      stepName: 'Complete',
    };
  },
  methods: {
    viewSite() {
      window.location.replace(this.site.url);
    },
    viewBackend() {
      window.location.replace(this.site.url + '/' + this.site.backendUrl);
    },
  },
};
</script>

<style lang="scss" scoped>
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

.step-actions button:first-child {
  margin-right: $layout-spacing-sm;
}

p {
  margin-bottom: 0.6rem;
}

@keyframes showTick {
  0% {
    top: 120%;
    opacity: 0;
  }

  100% {
    top: 50%;
    opacity: 1;
  }
}
</style>
