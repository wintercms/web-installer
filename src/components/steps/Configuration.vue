<template>
  <ValidationObserver slim>
    <div class="step">
      <div class="step-content">
        <Tabs>
          <Tab name="Site" :active="true">
            <div class="columns">
              <div class="column">

                <ValidationProvider
                  name="Site name"
                  mode="eager"
                  rules="required"
                  :immediate="false"
                  v-slot="{ dirty, invalid, errors }"
                  slim
                >
                  <div class="form-group" :class="{ 'has-error': dirty && invalid }">
                    <label class="form-label label-lg" for="siteName">Site name</label>
                    <small class="help">
                      Enter the name of your next awesome project.
                    </small>
                    <input
                      type="text"
                      class="form-input input-lg"
                      name="siteName"
                      placeholder="Enter your site name"
                      v-model="site.name"
                      ref="firstField"
                    >
                    <transition name="fade">
                      <div v-if="dirty && errors.length" class="form-error" v-text="errors[0]">
                      </div>
                    </transition>
                  </div>
                </ValidationProvider>

                <ValidationProvider
                  name="Site URL"
                  mode="eager"
                  rules="required|url"
                  :immediate="false"
                  v-slot="{ dirty, invalid, errors }"
                  slim
                >
                  <div class="form-group" :class="{ 'has-error': dirty && invalid }">
                    <label class="form-label" for="siteUrl">Site URL</label>
                    <small class="help">
                      Please provide a publicly-available address to your site. Make sure to include
                      <strong>https://</strong> or <strong>http://</strong> at the beginning of your URL.
                    </small>
                    <input
                      type="text"
                      class="form-input"
                      name="siteUrl"
                      placeholder="Enter your site URL"
                      v-model="site.url"
                    >
                    <transition name="fade">
                      <div v-if="dirty && errors.length" class="form-error" v-text="errors[0]">
                      </div>
                    </transition>
                  </div>
                </ValidationProvider>

                <ValidationProvider
                  name="Backend URL Keyword"
                  mode="eager"
                  rules="backendUrl"
                  :immediate="false"
                  v-slot="{ dirty, invalid, errors }"
                  slim
                >
                  <div class="form-group" :class="{ 'has-error': dirty && invalid }">
                    <label class="form-label" for="siteUrl">Backend URL Keyword</label>
                    <small class="help">
                      Provide the keyword that will be used for the URL to the Backend. By default,
                      this is <strong>backend</strong>.
                    </small>
                    <input
                      type="text"
                      class="form-input"
                      name="backendUrl"
                      placeholder="Enter your backend URL keyword"
                      v-model="site.backendUrl"
                    >
                    <transition name="fade">
                      <div v-if="dirty && errors.length" class="form-error" v-text="errors[0]">
                      </div>
                    </transition>
                  </div>
                </ValidationProvider>

              </div>
            </div>
          </Tab>
          <Tab name="Database">
            <div class="columns">
              <div class="column">
                <div class="form-group">
                  <label class="form-label" for="siteName">Database Type</label>
                  <select
                    class="form-select"
                    name="siteName"
                    v-model="site.database.type"
                    ref="firstField"
                  >
                    <option value="mysql">MySQL / MariaDB</option>
                    <option value="postgres">PostgreSQL</option>
                    <option value="sqlsrv">SQL Server</option>
                    <option value="sqlite">SQLite</option>
                  </select>
                </div>
              </div>
              <div class="column"></div>
            </div>

            <div class="columns" v-if="site.database.type !== 'sqlite'">
              <div class="column">
                <ValidationProvider
                  name="Server Hostname"
                  mode="eager"
                  rules="required"
                  :immediate="false"
                  v-slot="{ dirty, invalid, errors }"
                  slim
                >
                  <div class="form-group" :class="{ 'has-error': dirty && invalid }">
                    <label class="form-label" for="siteUrl">Server Hostname</label>
                    <input
                      type="text"
                      class="form-input"
                      name="databaseHost"
                      placeholder="Enter the database server hostname"
                      v-model="site.database.host"
                    >
                    <transition name="fade">
                      <div v-if="dirty && errors.length" class="form-error" v-text="errors[0]">
                      </div>
                    </transition>
                  </div>
                </ValidationProvider>

                <div class="form-group">
                  <label class="form-label" for="siteUrl">Username</label>
                  <input
                    type="text"
                    class="form-input"
                    name="databaseUser"
                    v-model="site.database.user"
                  >
                </div>

                <ValidationProvider
                  name="Database Name"
                  mode="eager"
                  rules="required"
                  :immediate="false"
                  v-slot="{ dirty, invalid, errors }"
                  slim
                >
                  <div class="form-group" :class="{ 'has-error': dirty && invalid }">
                    <label class="form-label" for="siteUrl">Database Name</label>
                    <input
                      type="text"
                      class="form-input"
                      name="databaseName"
                      placeholder="Enter the database name"
                      v-model="site.database.name"
                    >
                    <transition name="fade">
                      <div v-if="dirty && errors.length" class="form-error" v-text="errors[0]">
                      </div>
                    </transition>
                  </div>
                </ValidationProvider>
              </div>
              <div class="column">
                <ValidationProvider
                  name="Server Port"
                  mode="eager"
                  rules="required|integer"
                  :immediate="false"
                  v-slot="{ dirty, invalid, errors }"
                  slim
                >
                  <div class="form-group" :class="{ 'has-error': dirty && invalid }">
                    <label class="form-label" for="siteUrl">Server Port</label>
                    <input
                      type="text"
                      class="form-input"
                      name="databaseHost"
                      placeholder="Enter the database server port"
                      v-model="site.database.port"
                    >
                    <transition name="fade">
                      <div v-if="dirty && errors.length" class="form-error" v-text="errors[0]">
                      </div>
                    </transition>
                  </div>
                </ValidationProvider>

                <div class="form-group">
                  <label class="form-label" for="siteUrl">Password</label>
                  <input
                    type="password"
                    class="form-input"
                    name="databasePass"
                    v-model="site.database.pass"
                  >
                </div>
              </div>
            </div>

            <div class="columns" v-else>
              <div class="column">
                <ValidationProvider
                  name="SQLite Database Path"
                  mode="eager"
                  rules="required"
                  :immediate="false"
                  v-slot="{ dirty, invalid, errors }"
                  slim
                >
                  <div class="form-group" :class="{ 'has-error': dirty && invalid }">
                    <label class="form-label" for="siteUrl">SQLite Database Path</label>
                    <input
                      type="text"
                      class="form-input"
                      name="databaseName"
                      placeholder="Enter the path to the SQLite database"
                      v-model="site.database.name"
                    >
                    <transition name="fade">
                      <div v-if="dirty && errors.length" class="form-error" v-text="errors[0]">
                      </div>
                    </transition>
                  </div>
                </ValidationProvider>
              </div>
            </div>
          </Tab>
          <Tab name="Administrator">
            Admin
          </Tab>
        </Tabs>
      </div>
      <div class="step-actions">
        <Click
          label="Continue installation"
          flag="primary"
          @press="complete"
        />
      </div>
    </div>
  </ValidationObserver>
</template>

<script>
import StepMixin from '@/mixins/step';
import Click from '@/components/Click.vue';
import Tabs from '@/components/Tabs.vue';
import Tab from '@/components/Tab.vue';
import { ValidationProvider, ValidationObserver, extend } from 'vee-validate';
import { required, integer } from 'vee-validate/dist/rules';

// Import required validation rules
extend('required', {
  ...required,
  message: '{_field_} is required.',
});
extend('integer', {
  ...integer,
  message: '{_field_} must be a number.',
});

// Custom validation rules
extend('backendUrl', (value) => {
  const regex = /^[-a-zA-Z0-9()@:%_+.~#=]*$/;
  if (regex.test(value)) {
    return true;
  }

  return 'Invalid backend URL keyword.';
});
extend('url', (value) => {
  const regex = /https?:\/\/(www\.)?[-a-zA-Z0-9@:%._+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_+.~#?&//=]*)/;
  if (regex.test(value)) {
    return true;
  }

  return 'Invalid URL provided';
});

export default {
  name: 'Configuration',
  mixins: [
    StepMixin,
  ],
  components: {
    Click,
    Tabs,
    Tab,
    ValidationProvider,
    ValidationObserver,
  },
  props: {
    site: {
      type: Object,
      required: true,
    },
  },
  computed: {
    isActive() {
      return this.$store.getters['steps/isActive'](this.stepId);
    },
  },
  data() {
    return {
      stepId: 'config',
      stepName: 'Configuration',
      firstView: true,
    };
  },
  watch: {
    isActive(val) {
      if (val && this.firstView) {
        this.firstView = false;
        this.$refs.firstField.focus();
      }
    },
  },
  methods: {
    complete() {
      this.$store.dispatch('steps/setStatus', {
        id: 'site',
        status: 'complete',
      });
      this.$store.dispatch('steps/goTo', {
        id: 'database',
      });
    },
  },
};
</script>
