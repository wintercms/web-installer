<template>
  <ValidationObserver slim v-slot="{ invalid: formInvalid }">
    <div class="step">
      <div class="step-content">
        <Tabs @tabchanged="setActiveTab" ref="configTabs">
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
                      tabindex="1"
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
                      tabindex="2"
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
                    <label class="form-label" for="backendUrl">Backend URL Keyword</label>
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
                      tabindex="3"
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
                  <label class="form-label" for="databaseType">Database Type</label>
                  <select
                    class="form-select"
                    name="databaseType"
                    v-model="site.database.type"
                    tabindex="4"
                  >
                    <option value="mysql">MySQL / MariaDB</option>
                    <option value="pgsql">PostgreSQL</option>
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
                    <label class="form-label" for="databaseHost">Server Hostname</label>
                    <input
                      type="text"
                      class="form-input"
                      name="databaseHost"
                      placeholder="Enter the database server hostname"
                      v-model="site.database.host"
                      tabindex="5"
                    >
                    <transition name="fade">
                      <div v-if="dirty && errors.length" class="form-error" v-text="errors[0]">
                      </div>
                    </transition>
                  </div>
                </ValidationProvider>

                <div class="form-group">
                  <label class="form-label" for="databaseUser">Username</label>
                  <input
                    type="text"
                    class="form-input"
                    name="databaseUser"
                    v-model="site.database.user"
                    tabindex="7"
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
                    <label class="form-label" for="databaseName">Database Name</label>
                    <input
                      type="text"
                      class="form-input"
                      name="databaseName"
                      placeholder="Enter the database name"
                      v-model="site.database.name"
                      tabindex="9"
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
                    <label class="form-label" for="databasePort">Server Port</label>
                    <input
                      type="text"
                      class="form-input"
                      name="databasePort"
                      placeholder="Enter the database server port"
                      v-model="site.database.port"
                      tabindex="6"
                    >
                    <transition name="fade">
                      <div v-if="dirty && errors.length" class="form-error" v-text="errors[0]">
                      </div>
                    </transition>
                  </div>
                </ValidationProvider>

                <div class="form-group">
                  <label class="form-label" for="databasePass">Password</label>
                  <input
                    type="password"
                    class="form-input"
                    name="databasePass"
                    v-model="site.database.pass"
                    tabindex="8"
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
                    <label class="form-label" for="databaseName">SQLite Database Path</label>
                    <input
                      type="text"
                      class="form-input"
                      name="databaseName"
                      placeholder="Enter the path to the SQLite database"
                      v-model="site.database.name"
                      tabindex="9"
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
            <div class="columns">
              <div class="column">
                <ValidationProvider
                  name="First Name"
                  mode="eager"
                  rules="required"
                  :immediate="false"
                  v-slot="{ dirty, invalid, errors }"
                  slim
                >
                  <div class="form-group" :class="{ 'has-error': dirty && invalid }">
                    <label class="form-label" for="adminFirstName">First Name</label>
                    <input
                      type="text"
                      class="form-input"
                      name="adminFirstName"
                      placeholder="Enter the admin's first name"
                      v-model="site.admin.firstName"
                      tabindex="10"
                    >
                    <transition name="fade">
                      <div v-if="dirty && errors.length" class="form-error" v-text="errors[0]">
                      </div>
                    </transition>
                  </div>
                </ValidationProvider>

                <ValidationProvider
                  name="Username"
                  mode="eager"
                  rules="required"
                  :immediate="false"
                  v-slot="{ dirty, invalid, errors }"
                  slim
                >
                  <div class="form-group" :class="{ 'has-error': dirty && invalid }">
                    <label class="form-label" for="adminUsername">Username</label>
                    <input
                      type="text"
                      class="form-input"
                      name="adminUsername"
                      placeholder="Enter the admin username"
                      v-model="site.admin.username"
                      tabindex="12"
                    >
                    <transition name="fade">
                      <div v-if="dirty && errors.length" class="form-error" v-text="errors[0]">
                      </div>
                    </transition>
                  </div>
                </ValidationProvider>

                <ValidationProvider
                  name="Username"
                  mode="eager"
                  rules="required|email"
                  :immediate="false"
                  v-slot="{ dirty, invalid, errors }"
                  slim
                >
                  <div class="form-group" :class="{ 'has-error': dirty && invalid }">
                    <label class="form-label" for="adminEmail">Email Address</label>
                    <input
                      type="email"
                      class="form-input"
                      name="adminEmail"
                      placeholder="Enter the admin email address"
                      v-model="site.admin.email"
                      tabindex="14"
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
                  name="Surname"
                  mode="eager"
                  rules="required"
                  :immediate="false"
                  v-slot="{ dirty, invalid, errors }"
                  slim
                >
                  <div class="form-group" :class="{ 'has-error': dirty && invalid }">
                    <label class="form-label" for="adminLastName">Surname</label>
                    <input
                      type="text"
                      class="form-input"
                      name="adminLastName"
                      placeholder="Enter the admin's surname"
                      v-model="site.admin.lastName"
                      tabindex="11"
                    >
                    <transition name="fade">
                      <div v-if="dirty && errors.length" class="form-error" v-text="errors[0]">
                      </div>
                    </transition>
                  </div>
                </ValidationProvider>

                <ValidationProvider
                  name="Password"
                  mode="eager"
                  rules="required"
                  :immediate="false"
                  v-slot="{ dirty, invalid, errors }"
                  slim
                >
                  <div class="form-group" :class="{ 'has-error': dirty && invalid }">
                    <label class="form-label" for="adminPassword">Password</label>
                    <input
                      type="password"
                      class="form-input"
                      name="adminPassword"
                      placeholder="Enter the admin's password"
                      v-model="site.admin.password"
                      tabindex="13"
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
        </Tabs>
      </div>
      <div class="step-actions">
        <Click
          v-if="activeTab === 'Site'"
          label="Enter database configuration"
          flag="primary"
          @press="$refs.configTabs.setActiveTabByName('Database')"
        />
        <Click
          v-else-if="activeTab === 'Database'"
          label="Enter administrator details"
          flag="primary"
          @press="$refs.configTabs.setActiveTabByName('Administrator')"
        />
        <Click
          v-else
          label="Begin final checks"
          flag="primary"
          :disabled="formInvalid"
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
import { required, integer, email } from 'vee-validate/dist/rules';

// Import required validation rules
extend('required', {
  ...required,
  message: '{_field_} is required.',
});
extend('email', {
  ...email,
  message: '{_field_} must be a valid email.',
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
      activeTab: 'Site',
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
    setActiveTab(tab) {
      if (tab) {
        this.activeTab = tab.name;
      }
    },
    complete() {
      this.$store.dispatch('steps/setStatus', {
        id: 'config',
        status: 'complete',
      });
      this.$store.dispatch('steps/goTo', {
        id: 'finalChecks',
      });
    },
  },
};
</script>
