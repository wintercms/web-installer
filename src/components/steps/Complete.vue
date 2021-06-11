<template>
  <div class="step text-center">
    <div class="step-content">

    </div>
  </div>
</template>

<script>
import StepMixin from '@/mixins/step';

export default {
  name: 'Complete',
  mixins: [
    StepMixin,
  ],
  data() {
    return {
      stepId: 'complete',
      stepName: 'Complete',
    };
  },
  methods: {
  },
};
</script>

<style lang="scss" scoped>
.step-content {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
}

p {
  margin-bottom: 0.6rem;
}
</style>
