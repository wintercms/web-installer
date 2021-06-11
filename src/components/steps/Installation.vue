<template>
  <div class="step text-center">
    <div class="step-content">

    </div>
  </div>
</template>

<script>
import StepMixin from '@/mixins/step';

export default {
  name: 'Installation',
  mixins: [
    StepMixin,
  ],
  data() {
    return {
      stepId: 'installation',
      stepName: 'Installation',
    };
  },
  methods: {
    complete() {
      this.$store.dispatch('steps/setStatus', {
        id: 'installation',
        status: 'complete',
      });
      this.$store.dispatch('steps/goTo', {
        id: 'complete',
      });
    },
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
