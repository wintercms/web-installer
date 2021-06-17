<template>
  <div class="step text-center">
    <div class="step-content">
      <transition name="fade" mode="out-in">
        <div class="circle-logo" v-if="!errored"></div>
        <div class="danger-logo" v-else></div>
      </transition>

      <h3>Installing Winter CMS</h3>

      <p>This process may take a minute or two. Please do not close the window.</p>

      <div class="bar bar-lg" :class="{ 'bar-error': errored }">
        <div
          class="bar-item"
          role="progressbar"
          :style="{ width: progress }"
        ></div>
      </div>

      <transition name="fade" mode="out-in">
        <div class="install-steps" v-if="!errored">
          <transition-group name="install-step" tag="ul">
            <li
              v-for="action in actioned"
              :key="action"
              class="install-step-item"
              v-text="action"
            ></li>
          </transition-group>
        </div>
        <div class="error" v-else>
          <p>
            Sorry, but an error has occurred while trying to install Winter CMS.
          </p>
          <p><strong v-text="error"></strong></p>
        </div>
      </transition>
    </div>
  </div>
</template>

<script>
import StepMixin from '@/mixins/step';

export default {
  name: 'Installation',
  mixins: [
    StepMixin,
  ],
  props: {
    site: {
      type: Object,
      required: true,
    },
    installing: {
      type: Boolean,
    },
  },
  computed: {
    isActive() {
      return this.$store.getters['steps/isActive'](this.stepId);
    },
    progress() {
      return Math.ceil((this.actioned.length / Object.keys(this.apiSteps).length) * 100) + '%';
    },
  },
  data() {
    return {
      stepId: 'installation',
      stepName: 'Installation',
      begun: false,
      errored: false,
      error: null,
      apiSteps: {
        downloadWinter: 'Downloading Winter CMS',
        extractWinter: 'Extracting Winter CMS',
        lockDependencies: 'Determining dependencies',
        installDependencies: 'Installing dependencies',
        setupConfig: 'Configuring site',
        runMigrations: 'Running database migrations',
        createAdmin: 'Create administrator account',
        cleanUp: 'Cleaning up',
      },
      actioned: [],
    };
  },
  watch: {
    isActive(val) {
      if (val && !this.begun) {
        this.install();
      }
    },
  },
  methods: {
    async install() {
      this.begun = true;
      this.install = true;

      // Disable ESLint rule checks for these lines, as the AirBnb standard doesn't seem to handle
      // async work very well.

      // eslint-disable-next-line
      for (const key of Object.keys(this.apiSteps)) {
        this.actioned.unshift(this.apiSteps[key]);
        try {
          // eslint-disable-next-line
          await this.installStep(key);
        } catch (e) {
          this.setError(e);
          return;
        }
      }

      this.complete();
    },
    installStep(endpoint) {
      return new Promise((resolve, reject) => {
        this.$api('POST', endpoint, { site: this.site }).then(
          (response) => {
            if (response.success) {
              resolve();
            }
            reject(response.error);
          },
          (error) => {
            reject(error);
          },
        );
      });
    },
    setError(message) {
      this.errored = true;
      this.error = message;
    },
    complete() {
      this.$store.dispatch('steps/setStatus', {
        id: 'installation',
        status: 'complete',
      });
      this.$store.dispatch('steps/goTo', {
        id: 'complete',
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

  h3 {
    margin-bottom: 0;
  }

  .bar {
    height: $unit-8;
  }

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

  .danger-logo {
    position: relative;
    width: 120px;
    height: 120px;
    margin-bottom: $layout-spacing;

    border-radius: 50%;
    background-color: $error-color;
    overflow: hidden;

    &:after {
      content: '✗';
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

  .install-steps {
    position: relative;
    height: 170px;
    width: 100%;
    overflow: hidden;

    ul {
      margin: 0;
      padding: 0;
      list-style: none;
    }

    &::after {
      content: '';
      position: absolute;
      left: 0;
      bottom: 0;
      height: 80%;
      width: 100%;
      background: linear-gradient(
        rgba(255, 255, 255, 0) 0%,
        rgba(255, 255, 255, 0.5) 15%,
        rgba(255, 255, 255, 1) 100%
      );
    }

    .install-step-item {
      display: block;
      text-align: center;
      font-size: $font-size-lg;
      margin-bottom: $unit-2;
      font-weight: bold;
    }
  }

  .error {
    background: lighten($error-color, 10%);
    color: $light-color;
    border-radius: $border-radius;

    width: 100%;
    padding: $layout-spacing-sm $layout-spacing;

    p:last-child {
      margin-bottom: 0;
    }
  }
}

p {
  margin-bottom: 0.6rem;
}
</style>
