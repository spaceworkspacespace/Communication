
import Vue from 'vue'
// import $ from 'jquery'
import axios from 'axios'

// axios.defaults.baseURL = "http://127.0.0.1:9510"
// axios.defaults.baseURL = "http://192.168.0.80:1235";
axios.defaults.withCredentials = true;
axios.defaults.params = {
    "_ajax": true,
    // "_origin": "http://192.168.0.87:9510",
};


import { router } from './router/index'
import { store } from './store/index'
// import './mock'
import App from './App.vue'
import './style/init.css'
import './style/common.css'

// Vue.config.productionTip = false
// $(window.document.head).append(`<link rel="stylesheet" href="http://144.34.168.228/layim-v3.8.0/css/layui.css">`);

layui.use(['layim', 'laypage'], function () {
    var layim = layui.layim
        , layer = layui.layer
        , laytpl = layui.laytpl
        , $ = layui.jquery
        , laypage = layui.laypage;
});

new Vue({
    el: '#app',
    router,
    store,
    components: { App },
    template: '<App/>'
});
