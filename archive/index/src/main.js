// import '@/mock/index'


import Vue from 'vue'
import App from './App'
import { router } from './router'
import { store } from '@/store/index'
import '@/style/init.css'
import '@/style/common.css'



import axios from 'axios'

// axios.defaults.baseURL = "http://192.168.0.80:1235";
// axios.defaults.baseURL = "http://192.168.0.87:8080";
axios.defaults.withCredentials = true;
axios.defaults.params = {
    "_ajax": true,
    // "_origin": "http://192.168.0.87:8080",
};

Vue.config.productionTip = false

/* eslint-disable no-new */
new Vue({
    el: '#app',
    router,
    store,
    components: { App },
    template: '<App/>'
});
