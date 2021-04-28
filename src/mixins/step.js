export default {
  computed: {
    isActive() {
      return this.$store.getters['steps/isActive'](this.stepId);
    },
  },
  data() {
    return {
      stepId: 'step',
      stepName: 'Step',
    };
  },
  created() {
    this.$store.dispatch('steps/add', {
      id: this.stepId,
      name: this.stepName,
    });
  },
  destroyed() {
    this.$store.dispatch('steps/remove', {
      id: this.stepId,
    });
  },
};
