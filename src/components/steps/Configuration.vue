<template>
  <ValidationObserver slim>
    <div class="step">
      <div class="step-content">
        <Tabs>
          <Tab name="Site" :active="true">
            <div class="columns">
              <div class="column">

                <ValidationProvider
                  mode="lazy"
                  tag="div"
                  rules="required"
                  v-slot="{ invalid }"
                >
                  <div class="form-group" :class="{ 'has-error': invalid }">
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
                    >
                  </div>
                </ValidationProvider>

                <ValidationProvider
                  mode="lazy"
                  tag="div"
                  rules="required|url"
                  v-slot="{ invalid }"
                >
                  <div class="form-group" :class="{ 'has-error': invalid }">
                    <label class="form-label" for="siteUrl">Site URL</label>
                    <small class="help">
                      Please provide a publicly-available address to your site.
                    </small>
                    <input
                      type="text"
                      class="form-input"
                      name="siteUrl"
                      placeholder="Enter your site URL"
                      v-model="site.url"
                    >
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
import { required } from 'vee-validate/dist/rules';

// Import required validation rules
extend('required', required);
extend('url', (value) => {
  const regex = /https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)/;
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
  data() {
    return {
      stepId: 'config',
      stepName: 'Configuration',
    };
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
