import Vue from 'vue'
import Router from 'vue-router'
import axios from 'axios'

import { store } from '@/store/index'

import ContactMgr from '@/pages/ContactMgr'
import WebVideoCall from '@/pages/WebVideoCall'
import FriendMgr from '@/pages/contactmgr/FriendMgr'
import GroupMgr from '@/pages/contactmgr/GroupMgr'
import SeachContact from '@/pages/contactmgr/SeachContact'
import GroupNew from '@/pages/contactmgr/GroupNew'
import Index from '@/pages/Index'
import { LINKS } from '@/conf/link'


Vue.use(Router)

const router = new Router({
  routes: [
    { // 首页
      path: "/index",
      name: "Index",
      component: Index,
      meta: { requiresUInfo: true }
    },
    { // 视频聊天
      path: "/videocall",
      name: "WebVideoCall",
      component: WebVideoCall,
      meta: { requiresAuth: true }
    },
    { // 联系人管理
      path: "/contactmgr",
      name: "ContactMgr",
      component: ContactMgr,
      meta: { requiresAuth: true },
      children: [
        {
          path: "friend",
          component: FriendMgr
        },
        {
          path: "group",
          component: GroupMgr
        },
        {
          path: "find",
          component: SeachContact
        },
        {
          path: 'groupnew',
          component: GroupNew
        }
      ]
    },

    {
      path: "/",
      redirect: "/index"
    }
  ]
});

// 获取用户的登录信息
router.beforeEach((to, from, next) => {
  // 如果已经登录或不需要登录, 则可进入路由
  if (store.state.user
    || !to.matched.some(m => m.meta.requiresAuth || m.meta.requiresUInfo)) {
    next();
  } else {
    axios({
      method: "GET",
      url: LINKS.user.userInfo
    }).then(resp => {
      let data = resp.data;
      if (data.code) { // 用户信息无效
        store.commit("setUser", null);
        // 必须需要登录, 跳转首页
        if (to.matched.some(m => m.meta.requiresAuth)) {
          next({ path: "/index" });
          return;
        }
        // 不是必须要登录
        next();
        return;
      }
      // 用户信息有效
      store.commit("setUser", data.data);
      next();
    }).catch(e => {
      // 发生异常
      console.log(e);
      next({ path: "/index" });
    });
  }
});

export {
  router
};