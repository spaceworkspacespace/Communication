import Vue from 'vue'
import Router from 'vue-router'
import axios from 'axios'
import { LINKS } from '../conf/link'
import { store } from '../store'

import ChatLog from '../pages/ChatLog.vue'
import MsgBox from '../pages/MsgBox.vue'

Vue.use(Router);

const router = new Router({
    routes: [
        { path: "/chat/log", component: ChatLog },
        { path: "/msg/box", component: MsgBox },
        { path: "/", redirect: "/chat/log" }
    ]
});


// 获取用户的登录信息
router.beforeEach((to, from, next) => {
    if (store.state.user) {
        next();
    } else {
        axios({
            method: "GET",
            url: LINKS.user.userInfo
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                store.commit("setUser", null);
                window.location.href = "/";
            } else {
                store.commit("setUser", data.data);
                next();
            }
        }).catch(e => {
            next(false);
        });
    }
});

export {
    router
}