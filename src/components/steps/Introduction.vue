<template>
  <div class="step text-center">
    <div class="step-content">
      <div class="circle-logo"></div>

      <h3>Welcome to the Winter CMS Installer.</h3>

      <p>
        Thanks for choosing Winter CMS as your development platform, get ready to enjoy development
        again!
      </p>
      <p>
        This installer will guide you through the process of installing a fresh copy of Winter CMS
        for your project. Please follow the instructions provided and fill in all necessary
        information.
      </p>

      <div class="beta-optin">
        <input
          id="beta"
          name="beta"
          type="checkbox"
          v-model="installation.beta"
        >
        <label for="beta">
          <strong>Install Winter CMS 1.2 beta</strong>
          <span>By ticking this, you will install the Winter CMS 1.2 beta, built on top of
            Laravel 9. This version is still in beta. Leave this unticked to receive the stable
            version of Winter CMS.
          </span>
        </label>
      </div>
    </div>

    <div class="step-actions">
      <Click
        label="Begin compatibility checks"
        flag="primary"
        @press="complete"
      />
    </div>
  </div>
</template>

<script>
import StepMixin from '@/mixins/step';
import Click from '@/components/Click.vue';

export default {
  name: 'Introduction',
  props: {
    installation: {
      type: Object,
      required: true,
    },
  },
  mixins: [
    StepMixin,
  ],
  components: {
    Click,
  },
  data() {
    return {
      stepId: 'intro',
      stepName: 'Introduction',
    };
  },
  methods: {
    complete() {
      this.$store.dispatch('steps/setStatus', {
        id: 'intro',
        status: 'complete',
      });
      this.$store.dispatch('steps/goTo', {
        id: 'checks',
      });
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

  .circle-logo {
    width: 120px;
    height: 120px;
    margin-bottom: $layout-spacing;

    border-radius: 50%;
    background-image: url('../../assets/img/circle-logo.png');
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
  }
}

p {
  margin-bottom: 0.6rem;
}
</style>
