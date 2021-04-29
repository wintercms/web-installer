<template>
  <div class="step">
    <div class="step-content">
      <h4>We're now running a couple of checks.</h4>

      <p>To ensure that Winter CMS will install and run on your web server, we're just doing
        a couple of checks of your PHP and server configuration.</p>
    </div>
    <div class="checks">
      <Check
        name="API Connection"
        :description="checkDescription('api')"
        :status="checkStatus('api')"
      />

      <Check
        name="PHP Version"
        :description="checkDescription('phpVersion')"
        :status="checkStatus('phpVersion')"
      />

      <Check
        name="PHP Extensions"
        :description="checkDescription('phpExtensions')"
        :status="checkStatus('phpExtensions')"
      />
    </div>
    <div class="step-actions">
      <Click
        :label="buttonLabel"
        :flag="(completedChecks && !checksSuccessful) ? 'error' : 'primary'"
        :disabled="!completedChecks"
        @press="onPress"
      />
    </div>
  </div>
</template>

<script>
import StepMixin from '@/mixins/step';
import Click from '@/components/Click.vue';
import Check from '@/components/Check.vue';

export default {
  name: 'Checks',
  mixins: [
    StepMixin,
  ],
  components: {
    Click,
    Check,
  },
  computed: {
    buttonLabel() {
      if (!this.completedChecks) {
        return 'Running checks...';
      }
      if (!this.checksSuccessful) {
        return 'Re-run checks';
      }
      return 'Continue installation';
    },
    isActive() {
      return this.$store.getters['steps/isActive'](this.stepId);
    },
  },
  data() {
    return {
      stepId: 'checks',
      stepName: 'System Checks',
      ranChecks: false,
      completedChecks: false,
      checksSuccessful: false,

      checks: {},
    };
  },
  mounted() {
    this.resetChecks();
  },
  watch: {
    isActive(val) {
      if (val && !this.ranChecks) {
        this.runChecks();
      }
    },
  },
  methods: {
    checkDescription(check) {
      if (!this.checks[check]) {
        return '';
      }
      return this.checks[check].description;
    },
    checkStatus(check) {
      if (!this.checks[check] || this.checks[check].status === null) {
        return 'loading';
      }
      if (this.checks[check].status === false) {
        return 'error';
      }
      return 'success';
    },
    runChecks() {
      this.ranChecks = true;

      Promise.all([
        this.$api('GET', 'checkApi'),
        this.$api('GET', 'checkPhpVersion'),
        this.$api('GET', 'checkPhpExtensions'),
      ]).then(
        (responses) => {
          this.completedChecks = true;
          this.checksSuccessful = responses.every((response) => response.success === true);

          // Set check responses
          this.checks.api.status = responses[0].success;
          this.checks.phpVersion.status = responses[1].success;
          this.checks.phpExtensions.status = responses[2].success;

          if (responses[0].success) {
            this.checks.api.description = 'We were successfully able to connect to the Winter CMS Marketplace API.';
          } else {
            this.checks.api.description = 'We could not connect to the Winter CMS Marketplace API.';
          }

          if (responses[1].success) {
            this.checks.phpVersion.description = `You are running PHP version ${responses[1].data.detected}, which is compatible with Winter CMS.`;
          } else {
            this.checks.phpVersion.description = `You are running PHP version ${responses[1].data.detected}, which is incompatible with Winter CMS.`;
          }

          if (responses[2].success) {
            this.checks.phpExtensions.description = 'All necessary PHP extensions required to run Winter CMS are installed on your server.';
          } else {
            this.checks.phpExtensions.description = `You are missing the "${responses[2].data.extension}", which is required in order to run Winter CMS. Please install it on your server and re-run the tests.`;
          }
        },
        () => {
          this.completedChecks = true;
        },
      );
    },
    onPress() {
      if (this.checksSuccessful) {
        this.complete();
        return;
      }

      this.rerunChecks();
    },
    rerunChecks() {
      this.ranChecks = false;
      this.completedChecks = false;
      this.checksSuccessful = false;

      this.resetChecks();
      this.runChecks();
    },
    resetChecks() {
      this.checks = {
        api: {
          status: null,
          description: 'Check that we can connect to the Winter CMS Marketplace API to retrieve the installation files.',
        },
        phpVersion: {
          status: null,
          description: 'Check that your server is running at least PHP version 7.2.9.',
        },
        phpExtensions: {
          status: null,
          description: 'Check that all necessary PHP extensions are installed on your server.',
        },
      };
    },
    complete() {
      this.$store.dispatch('steps/setStatus', {
        id: 'checks',
        status: 'complete',
      });
      this.$store.dispatch('steps/goTo', {
        id: 'license',
      });
    },
  },
};
</script>

<style lang="scss" scoped>
p {
  margin-bottom: 0;
}

.checks {
  display: flex;
  flex-direction: row;
  flex-grow: 20;
  flex-shrink: 1;
  margin: 0 $layout-spacing-lg;
}
</style>
