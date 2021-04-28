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
  name: 'Database',
  mixins: [
    StepMixin,
  ],
  components: {
    Click,
  },
  data() {
    return {
      stepId: 'database',
      stepName: 'Database',
    };
  },
  methods: {
    complete() {
      this.$store.dispatch('steps/setStatus', {
        id: 'database',
        status: 'complete',
      });
      this.$store.dispatch('steps/goTo', {
        id: 'plugins',
      });
    },
  },
};
</script>
