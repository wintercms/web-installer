<template>
  <div class="step" v-show="isActive">
    Checks
  </div>
</template>

<script>
import StepMixin from '@/mixins/step';

export default {
  name: 'Checks',
  mixins: [
    StepMixin,
  ],
  data() {
    return {
      stepId: 'checks',
      stepName: 'System Checks',
    };
  },
};
</script>
