import Vue from 'vue'
import Vuex from 'vuex'

import { actions } from './actions'
import { mutations } from './mutations'
import { layer } from './modules/layer'

Vue.use(Vuex);

const store = new Vuex.Store({
    actions,
    mutations,
    state: {
        user: null
    },
    modules: {
        layer
    }
});

export {
    store
};