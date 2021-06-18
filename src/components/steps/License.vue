<template>
  <div class="step">
    <div class="step-content">
      <h4>Winter CMS License.</h4>

      <p>Let's get the boring stuff out of the way. Please read the following license and
        indicate that you accept its terms.</p>
    </div>

    <pre class="license">
MIT License

Copyright (c) 2013-2021.03.01 October CMS
Copyright (c) 2021 Winter CMS

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
    </pre>

    <div class="step-actions">
      <Click
        label="I accept, let's do this!"
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
  name: 'License',
  mixins: [
    StepMixin,
  ],
  components: {
    Click,
  },
  data() {
    return {
      stepId: 'license',
      stepName: 'License',
    };
  },
  methods: {
    complete() {
      this.$store.dispatch('steps/setStatus', {
        id: 'license',
        status: 'complete',
      });
      this.$store.dispatch('steps/goTo', {
        id: 'config',
      });
    },
  },
};
</script>

<style lang="scss" scoped>
p {
  margin-bottom: 0;
}

.license {
  flex-grow: 2;
  flex-shrink: 1;
  margin: 0 $layout-spacing .6rem;
  padding: $layout-spacing;
  overflow: auto;
  max-height: 380px;
  border: 1px solid $gray-color;
  background: $gray-color-light;
  font-size: $font-size-sm;
  @media (screen and max-width: 576px) {
    max-height: initial;
  }
}
</style>
