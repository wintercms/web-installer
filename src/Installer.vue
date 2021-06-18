<template>
  <div id="installer-container" class="container grid-xl">
    <div id="installer">
      <Sidebar />

      <div class="content">
        <transition-group name="fade">
          <Introduction
            v-show="isStepActive('intro')"
            :key="'intro'"
          />
          <Checks
            v-show="isStepActive('checks')"
            :key="'checks'"
            :installation="installation"
          />
          <License
            v-show="isStepActive('license')"
            :key="'license'"
          />
          <Configuration
            v-show="isStepActive('config')"
            :key="'config'"
            :site="site"
            :installation="installation"
          />
          <FinalChecks
            v-show="isStepActive('finalChecks')"
            :key="'finalChecks'"
            :site="site"
          />
          <Installation
            v-show="isStepActive('installation')"
            :key="'installation'"
            :site="site"
            :installation="installation"
          />
          <Complete
            v-show="isStepActive('complete')"
            :key="'complete'"
            :site="site"
          />
        </transition-group>
      </div>
    </div>
  </div>
</template>

<script>
import Sidebar from '@/components/Sidebar.vue';
import Introduction from '@/components/steps/Introduction.vue';
import Checks from '@/components/steps/Checks.vue';
import License from '@/components/steps/License.vue';
import Configuration from '@/components/steps/Configuration.vue';
import FinalChecks from '@/components/steps/FinalChecks.vue';
import Installation from '@/components/steps/Installation.vue';
import Complete from '@/components/steps/Complete.vue';

export default {
  name: 'Installer',
  components: {
    Sidebar,
    Introduction,
    Checks,
    License,
    Configuration,
    FinalChecks,
    Installation,
    Complete,
  },
  data() {
    return {
      installation: {
        installing: false,
        installPath: null,
      },
      site: {
        name: '',
        url: this.defaultUrl(),
        backendUrl: 'backend',
        database: {
          type: 'mysql',
          host: 'localhost',
          port: 3306,
          username: '',
          password: '',
          name: '',
        },
        admin: {
          firstName: '',
          lastName: '',
          email: '',
          username: '',
          password: '',
        },
      },
    };
  },
  mounted() {
    this.$store.dispatch('steps/goTo', {
      id: 'intro',
    });
  },
  watch: {
    'site.database.type': {
      handler(newVal, oldVal) {
        const defaultPorts = {
          mysql: 3306,
          pgsql: 5432,
          sqlsrv: 1433,
        };

        if (Number(this.site.database.port) === defaultPorts[oldVal] || oldVal === 'sqlite') {
          this.site.database.port = defaultPorts[newVal];
        }
      },
    },
  },
  methods: {
    isStepActive(id) {
      return this.$store.getters['steps/isActive'](id);
    },
    defaultUrl() {
      return window.location.protocol
        + '//'
        + window.location.host
        + (window.location.pathname.replace('/install.html', ''));
    },
  },
};
</script>

<style lang="scss">
@import "@/assets/scss/base";

html,
body {
  height: 100%;
}

body {
  background: url('./assets/img/background.jpg') no-repeat center center fixed;
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center;
  &::-webkit-scrollbar {
    display: none;
  }
}

#installer-container {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 100%;

  #installer {
    display: flex;
    flex-direction: row;
    width: 100%;
    background: $body-bg;
    border-radius: $border-radius;
    box-shadow: 0px 12px 6px rgba(0, 0, 0, 0.18);
    margin: 2em auto;

    .content {
      position: relative;
      height: 100%;
      width: calc(100vw - 250px /* sidebar width */ - (1.6rem /* container l+r padding */ * 2));
      max-width: calc(
        1344px /* max container width */
        - 250px /* sidebar width */
        - (1.6rem /* container l+r padding */ * 2)
      );
    }
  }
}

@media (screen and max-width: 768px) {
  #installer-container {
    #installer {
      flex-direction: column;
      #sidebar {
        width: 100%;
        flex-direction: row;
        border-top-right-radius: $border-radius;
        border-bottom-left-radius: 0;
        .logo {
          margin: 0 auto;
          width: 300px;
          padding: 1.6rem 20px 0;
        }
        .step-links {
          flex-direction: row;
          display: flex;
          flex-wrap: nowrap;
          align-content: center;
          align-items: center;
          overflow-x: scroll;
          padding-top: 1rem;
          scroll-behavior: smooth;
          .step-link {
            white-space: nowrap;
            &.complete {
              padding-right: 2.4rem;
            }
            &.active {
              box-shadow: 0px -5px 15px 0px rgb(0 0 0 / 8%);
            }
          }
        }
      }
      .content {
        width: auto;
      }
    }
  }
}
@media(screen and max-width: 576px) {
  #installer-container.container {
    padding-left: 0.6rem;
    padding-right: 0.6rem;
    #installer {
      margin: .7em auto;
      .step {
        .step-content {
          padding: 1.6rem 1rem;
        }
        .step-actions {
          height: initial;
          align-items: stretch;
          line-height: initial;
          justify-content: center;
          text-align: center;
          display: flex;
          flex-direction: column;
          a.btn, button {
            margin: 0 $layout-spacing $layout-spacing;
          }
        }
      }
    }
  }
}
</style>
