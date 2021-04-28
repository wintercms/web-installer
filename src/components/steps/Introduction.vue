<template>
  <div class="step" v-show="isActive">
    Introduction
  </div>
</template>

<script>
import StepMixin from '@/mixins/step';

export default {
  name: 'Introduction',
  mixins: [
    StepMixin,
  ],
  data() {
    return {
      stepId: 'intro',
      stepName: 'Introduction',
    };
  },
};
</script>
