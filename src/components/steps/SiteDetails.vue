<template>
  <div class="step">
    <div class="step-content">

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
  name: 'SiteDetails',
  mixins: [
    StepMixin,
  ],
  components: {
    Click,
  },
  data() {
    return {
      stepId: 'site',
      stepName: 'Site Details',
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
