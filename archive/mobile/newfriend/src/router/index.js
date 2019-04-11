import Vue from 'vue'
import Router from 'vue-router'
// import Editor from '@/components/Editor'
import NewFriend from '@/pages/NewFriend'
import AddContact from '@/pages/AddContact'

Vue.use(Router)

export default new Router({
  routes: [
    {
      path: "/new",
      name: "NewFriend",
      component: NewFriend
    },
    {
      path: "/add",
      name: "AddContact",
      component: AddContact
    },
    {
      path: "/",
      redirect: "/new"
    }
  ]
})
