<template>
  <div class="step">
    <div class="step-content">
      <h4>We're now running a couple of checks.</h4>

      <p>To ensure that Winter CMS will install and run on your web server, we're just doing
        a couple of checks of your PHP and server configuration.</p>
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

export default {
  name: 'Checks',
  mixins: [
    StepMixin,
  ],
  components: {
    Click,
  },
  data() {
    return {
      stepId: 'checks',
      stepName: 'System Checks',
    };
  },
  methods: {
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