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
                  rules="required|alphaNum"
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
            Database
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
import { required, alpha_num as alphaNum } from 'vee-validate/dist/rules';

// Import required validation rules
extend('required', {
  ...required,
  message: '{_field_} is required.',
});
extend('alphaNum', {
  ...alphaNum,
  message: '{_field_} must be alphanumeric.'
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
