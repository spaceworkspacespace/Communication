

import Vue from 'vue'
import App from './App'
import router from './router'
import { store } from '@/store/index'
import '@/style/init.css'
// import './assets/mui-3.7.1/dist/css/mui.min.css'
import '@/style/common.css'
// import './assets/layer/layer'

import axios from 'axios'

// axios.defaults.baseURL = "http://192.168.0.80:1235";
// axios.defaults.withCredentials = true;
// axios.defaults.headers = {
    // Cookie: "PHPSESSID=dqaoi4j46bmou8eg0o70lnm37c; thinkphp_show_page_trace=0|0",
// };
axios.defaults.params = {
    "_ajax": true,
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
