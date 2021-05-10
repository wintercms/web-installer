<template>
  <div class="step">
    <div class="step-content">
      <Tabs>
        <Tab name="Site" :active="true">
          <div class="columns">
            <div class="column">

              <div class="form-group">
                <label class="form-label label-lg" for="siteName">Site name</label>
                <small class="text-grey">Please enter the name of your site.</small>
                <input type="text" class="form-input input-lg" name="siteName" placeholder="Enter your site name">
              </div>

              <div class="form-group">
                <label class="form-label" for="siteName">Site URL</label>
                <small class="text-grey">Please enter the publicly-accessible address to your site.</small>
                <input type="text" class="form-input" name="siteName" placeholder="Enter your site URL">
              </div>

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
</template>

<script>
import StepMixin from '@/mixins/step';
import Click from '@/components/Click.vue';
import Tabs from '@/components/Tabs.vue';
import Tab from '@/components/Tab.vue';

export default {
  name: 'Configuration',
  mixins: [
    StepMixin,
  ],
  components: {
    Click,
    Tabs,
    Tab,
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
