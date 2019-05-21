<template>
    <div id="x-search-contact">
        <ul class="nav nav-tabs">
            <li class="nav-item" v-for="t of tabs" :key="t.key">
                <a
                    :class="['nav-link', activeTab != t.key? '': 'active']"
                    @click="onSwitchTab"
                    :tab="t.key"
                    href="javascript:;"
                >{{t.text}}</a>
            </li>
        </ul>

        <form class="form my-4" @submit.prevent="onSearch">
            <div class="form-group row mx-0">
                <div class="col-8">
                    <input
                        type="text"
                        class="form-control"
                        v-model.trim="keyword"
                        :placeholder="'请输入' + (activeTab != 'groups'? '好友': '群聊') + '名称或 id 进行查找.'"
                    >
                </div>
                <div class="col-4">
                    <button type="submit" class="btn-block btn btn-primary">查找</button>
                </div>
            </div>
        </form>
        <div class="x-fill" style="top: 160px;overflow-scrolling: touch;">
            <div v-if="data === null" class="mx-3 row">请输入关键字或 id 开始搜索.</div>
            <div v-else-if="data.length !== 0" class="mx-0 row">
                <div
                    v-for="(g,i) of data"
                    :key="g.id"
                    class="col-md-6 shadow-sm position-relative py-2 my-2"
                    style="overflow: hidden;"
                >
                    <div class="row" style="width: 320px;">
                        <div class="col-4">
                            <img
                                :src="g.avatar || 'https://i.loli.net/2018/12/10/5c0de4003a282.png'"
                                alt
                                style="width: 100%; height: auto; "
                            >
                        </div>
                        <div v-if="activeTab !== 'groups'" class="col-8">
                            <p
                                :title="g.username"
                                class="my-0 py-1 text-truncate"
                            >({{g.id}}) {{g.username}}</p>
                            <p style="margin: 0; font-size: 12px;">性别: {{g.sex}}</p>
                            <p class="text-truncate" style="margin: 0;">
                                <small>签名: {{g.sign || "木有签名"}}</small>
                            </p>
                        </div>
                        <div v-else class="col-8">
                            <p
                                :title="g.groupname"
                                class="my-0 py-1 text-truncate"
                            >({{g.id}}) {{g.groupname}}</p>
                            <p style="margin: 0; font-size: 12px;">人数: {{g.membercount}}</p>
                            <p class="text-truncate" style="margin: 0;">
                                <small>描述: {{g.description}}</small>
                            </p>
                        </div>
                    </div>
                    <button
                        class="btn btn-primary py-1 px-2"
                        style="font-size: 12px; float: right;"
                        :x-index="i"
                        @click="onClick(g.id)"
                    >{{activeTab !== "groups"? "添加好友":"加入群"}}</button>
                </div>
                <div class="offset-2 col-8 my-4" v-show="!this.endReach">
                    <button @click="pull" type="button" class="btn btn-block btn-primary">加载更多</button>
                </div>
            </div>
            <div v-else class="mx-3 row" style="height: 200px;">
                <p class="py-0 my-0" style="line-height: 200px; text-align: center;">
                    没有找到符合搜索条件的{{activeTab !== "friends"? "群组":"用户"}}.
                    <a
                        v-if="activeTab!='friends'"
                        @click.prevent="onCreate"
                        href="newgroup.html"
                    >建立群组.</a>
                </p>
            </div>
        </div>
        <x-rotate v-show="this.refresh" text="加载中..."/>
    </div>
</template>
<script>

import { ContactService } from '@/service/ContactService'
import Rotate from '@/components/layer/Rotate'

const ERROR_MSG = "请求失败, 请稍后重试~";

export default {
    data: function () {
        return {
            tabs: [
                { text: "查找好友", key: "friends" },
                { text: "查找群聊", key: "groups" }
            ],
            activeTab: "friends",
            data: null,
            no: 1,
            count: 50,
            refresh: false,
            endReach: false,
            keyword: "",
            friendGroup: []
        };
    },
    beforeMount: function () {
        ContactService.getInstance()
            .getFriendAndGroup()
            .then(fg => this.friendGroup = fg)
            .catch(e => {
                console.error(e);
            });
    },
    methods: {
        onSwitchTab: function (event) {
            var element = event.target;
            var tab = element.getAttribute("tab");
            this.activeTab = tab;
            this.data = null;
            console.log(tab)
        },

        onSearch: function () {
            if (this.refresh) return;
            this.endReach = false;
            this.no = 1;
            this.pull();
        },

        pull: async function (event) {
            // 刷新检测
            if (this.endReach || this.refresh) {
                if (this.endReach) this.refresh = false;
                return;
            }
            this.refresh = true;

            var data = {};
            let message = "";

            try {

                let data;
                let user = this.$store.state.user;
                if (this.activeTab !== "friends") {
                    data = await ContactService.getInstance()
                        .getGroup(user.id, {
                            keyword: this.keyword,
                            no: this.no++,
                            count: this.count,
                        });
                } else {
                    data = await ContactService.getInstance()
                        .getFriend(user.id, {
                            no: this.no++,
                            count: this.count,
                            keyword: this.keyword,
                        });
                }

                if (this.no <= 2) {
                    this.data = data;
                } else {
                    this.data = this.data.concat(data);
                }
                // 到底检测
                if (data.length < this.count) this.endReach = true;
            } catch (e) {
                message = e.message;
                console.error(e);
                layer.msg(message);
            } finally {
                // 完成
                this.refresh = false;
            }

        },

        // 新建分组
        onCreate: function (e) {
            if (window.parent) {
                window.parent.postMessage({
                    type: "layer",
                    method: "open",
                    options: {
                        type: 2,
                        area: ['360px', '640px'],
                        content: "http://" + location.host + "/im/index/newgroup"
                    }
                }, "*");
            }
        },

        // 加入群聊或加为好友
        onClick: async function (id) {
            let message = "";

            try {
                let user = this.$store.state.user;
                if (this.activeTab !== "groups") { // 添加好友
                    let fg = this.friendGroup || (
                        this.friendGroup = await ContactService.getInstance()
                            .getFriendAndGroup()
                    );
                    let op = await new Promise((resolve, reject) => {
                        this.$store.commit("layer/show", {
                            title: "添加联系人",
                            type: "contactAdd",
                            list: fg.map(g => ({ text: g.groupname, value: g.id })),
                            onsubmit: op => resolve(op),
                            oncancel: () => resolve(false)
                        });
                    });
                    if (!op) return;
                    message = await ContactService.getInstance()
                        .addFriend(user.id, id, op.option, op.content);
                } else { // 加入群聊
                    let op = await new Promise((resolve, reject) => {
                        this.$store.commit("layer/show", {
                            title: "群聊申请",
                            type: "contactAdd",
                            list: null,
                            onsubmit: op => resolve(op),
                            oncancel: () => resolve(false)
                        });
                    });
                    if (!op) return;
                    message = await ContactService.getInstance()
                        .joinGroup(user.id, id, op.content);
                }

            } catch (e) {
                console.error(e);
                message = e.message;
            }
            layer.msg(message || ERROR_MSG);

            // if (window.parent) {
            //     var index = e.target.getAttribute("x-index");
            //     var contact = this.data[index];
            //     var type, username;
            //     if (this.condition !== "groups") {
            //         type = "friend";
            //         username = contact.username;
            //     } else {
            //         username = contact.groupname;
            //         type = "group";
            //     }
            //     // 给主窗口发送消息, 激活添加窗口
            //     window.parent.postMessage({
            //         type: "layim",
            //         method: "add",
            //         contact: {
            //             id: contact.id,
            //             type: type,
            //             username: username,
            //             avatar: contact.avatar || "https://i.loli.net/2018/12/10/5c0de4003a282.png"
            //         }
            //     }, "*");
        }
    },
    components: {
        "x-rotate": Rotate
    }
}
</script>
<style>
#x-search-contact {
    height: 100%;
    width: 100%;
    max-width: 1024px;
    margin: auto;
    position: relative;
    padding-top: 25px;
}
</style>