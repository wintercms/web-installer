export default {
  namespaced: true,
  state: {
    steps: [],
  },
  mutations: {
    add(state, data) {
      state.steps.push(data);
    },
    remove(state, data) {
      state.steps.splice(data.key, 1);
    },
    changeStatus(state, data) {
      state.steps[data.key].status = data.status;
    },
    setActive(state, data) {
      state.steps.forEach((step) => {
        step.active = false;
      });

      state.steps[data.key].active = true;
    },
  },
  getters: {
    getStepById: (state) => (id) => state.steps.findIndex((step) => step.id === id),
    isLocked: (state) => (id) => {
      if (state.steps.length === 0) {
        return false;
      }
      if (!state.steps.find((step) => step.id === id)) {
        return false;
      }

      return (state.steps.find((step) => step.id === id).status === 'locked');
    },
    isActive: (state) => (id) => {
      if (state.steps.length === 0) {
        return false;
      }
      if (!state.steps.find((step) => step.id === id)) {
        return false;
      }

      return state.steps.find((step) => step.id === id).active;
    },
  },
  actions: {
    add({ commit }, data) {
      commit('add', {
        id: data.id,
        name: data.name,
        status: 'locked',
        active: false,
      });
    },
    remove({ getters, commit }, data) {
      if (data.key) {
        commit('remove', {
          key: data.key,
        });
        return;
      }

      commit('remove', {
        key: getters.getStepById(data.id),
      });
    },
    goTo({ getters, commit }, data) {
      if (data.key) {
        if (getters.isLocked(data.key)) {
          commit('changeStatus', {
            key: data.key,
            status: 'pending',
          });
        }

        commit('setActive', {
          key: data.key,
        });
        return;
      }

      if (getters.isLocked(data.id)) {
        commit('changeStatus', {
          key: getters.getStepById(data.id),
          status: 'pending',
        });
      }

      commit('setActive', {
        key: getters.getStepById(data.id),
      });
    },
    setStatus({ getters, commit }, data) {
      if (data.key) {
        commit('changeStatus', {
          key: data.key,
          status: data.status,
        });
        return;
      }

      commit('changeStatus', {
        key: getters.getStepById(data.id),
        status: data.status,
      });
    },
  },
};
