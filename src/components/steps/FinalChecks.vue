<template>
  <div class="step">
    <div class="step-content">
      <h4>Last checks.</h4>

      <p>To ensure that Winter CMS can install and run with your configuration, we're doing some
        final checks.</p>
    </div>
    <div class="checks">
      <Check
        name="Database connection"
        :description="checkDescription('database')"
        :status="checkStatus('database')"
      />

      <Check
        name="Directory is writable"
        :description="checkDescription('writable')"
        :status="checkStatus('writable')"
      />
    </div>
    <div class="step-actions">
      <Click
        :label="buttonLabel"
        :size="(completedChecks && checksSuccessful) ? 'lg' : 'md'"
        :flag="(completedChecks && !checksSuccessful) ? 'error' : 'success'"
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
  name: 'FinalChecks',
  mixins: [
    StepMixin,
  ],
  components: {
    Click,
    Check,
  },
  props: {
    site: {
      type: Object,
      required: true,
    },
  },
  computed: {
    buttonLabel() {
      if (!this.completedChecks) {
        return 'Running checks...';
      }
      if (!this.checksSuccessful) {
        return 'Re-run checks';
      }
      return 'Begin the installation';
    },
    isActive() {
      return this.$store.getters['steps/isActive'](this.stepId);
    },
  },
  data() {
    return {
      stepId: 'finalChecks',
      stepName: 'Final Checks',
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
        this.$api('POST', 'checkDatabase', { site: this.site }),
        this.$api('GET', 'checkWriteAccess'),
      ]).then(
        (responses) => {
          this.completedChecks = true;
          this.checksSuccessful = responses.every((response) => response.success === true);

          // Set check responses
          this.checks.database.status = responses[0].success;
          this.checks.writable.status = responses[1].success;

          if (responses[0].success) {
            this.checks.database.description = 'We were successfully able to connect to the database.';
          } else {
            this.checks.database.description = 'We could not connect to the database. Please check your database settings.';
          }

          if (responses[1].success) {
            this.checks.writable.description = 'The folder you are installing into is writable.';
          } else {
            this.checks.writable.description = 'The folder you are installing into is not writable. Please check the permissions and ownership of the folder.';
          }
        },
        () => {
          this.completedChecks = true;
          Object.keys(this.checks).forEach((key) => {
            this.checks[key].status = false;
            this.checks[key].description = 'An error occurred querying the installer\'s PHP API. We cannot complete this check. Please double-check that your server has PHP installed and is web-accessible.';
          });
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
        database: {
          status: null,
          description: 'Check that we can connect to the database using the provided configuration.',
        },
        writable: {
          status: null,
          description: 'Check that your current project folder is writable.',
        },
      };
    },
    complete() {
      this.$store.dispatch('steps/setStatus', {
        id: 'finalChecks',
        status: 'complete',
      });
      this.$store.dispatch('steps/goTo', {
        id: 'installation',
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
