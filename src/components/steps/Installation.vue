<template>
  <div class="step text-center">
    <div class="step-content">
      <div class="circle-logo"></div>

      <h3>Installing Winter CMS</h3>

      <p>This process may take a minute or two. Please do not close the window.</p>

      <div class="bar bar-lg">
        <div
          class="bar-item"
          role="progressbar"
          :style="{ width: progress }"
        ></div>
      </div>

      <div class="install-steps">
        <transition-group name="install-step" tag="ul">
          <li
            v-for="action in actioned"
            :key="action"
            class="install-step-item"
            v-text="action"
          ></li>
        </transition-group>
      </div>
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

      // Disable ESLint rule checks for these lines, as the AirBnb standard doesn't seem to handle
      // async work very well.

      // eslint-disable-next-line
      for (const key of Object.keys(this.apiSteps)) {
        console.log(key, this.apiSteps[key]);
        this.actioned.unshift(this.apiSteps[key]);
        // eslint-disable-next-line
        await this.installStep(key);
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
      transition: all 500ms ease;
      display: block;
      text-align: center;
      font-size: $font-size-lg;
      margin-bottom: $unit-2;
      font-weight: bold;
    }

    .install-step-enter, .install-step-leave-to {
      opacity: 0;
    }
  }
}

p {
  margin-bottom: 0.6rem;
}
</style>
