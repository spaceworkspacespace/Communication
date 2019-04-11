<template>
    <div id="addcontact">
        <header class="mui-bar mui-bar-nav">
            <a @click="$router.back()"
                class="mui-icon mui-icon-left-nav mui-pull-left"></a>
            <h1 class="mui-title">添加好友</h1>
        </header>
        <div class="mui-content"
            style="position:absolute;top:44px;bottom:0;left:0;right:0;padding:0;overflow-y:auto;">
            <form @submit.prevent="onRefresh"
                class="mui-input-group">
                <div class="mui-input-row">
                    <input type="text"
                        style="width:75%;"
                        v-model="keyword"
                        class="mui-input-clear"
                        placeholder="请输入关键字搜索">
                    <button type="submit"
                        style="width:25%;"
                        class="mui-btn mui-btn-primary">确认</button>
                </div>
            </form>
            <ul class="mui-table-view">
                <li :key="i"
                    v-for="(c, i) of contacts"
                    class="mui-table-view-cell mui-media">
                    <a href="javascript:;"
                        v-if="c._type !== 'group'">
                        <img class="mui-media-object mui-pull-left"
                            :src="c.avatar">
                        <div class="mui-media-object mui-pull-right"
                            style="max-width: 160px">
                            <button @click="onAsk(c.id, c._type)">申请好友</button>
                        </div>
                        <div class="mui-media-body">
                            {{c.username}}
                            <p class='mui-ellipsis'>{{c.sign}}</p>
                        </div>
                    </a>
                    <a href="javascript:;"
                        v-else>
                        <img class="mui-media-object mui-pull-left"
                            :src="c.avatar">
                        <div class="mui-media-object mui-pull-right"
                            style="max-width: 160px">
                            <button @click="onAsk(c.id, c._type)">申请入群</button>
                        </div>
                        <div class="mui-media-body">
                            {{c.groupname}}
                            <p class='mui-ellipsis'>{{c.description}}</p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

    </div>
</template>

<script>
import axios from 'axios'

const ERROR_MSG = "请求错误, 请稍后重试~";

export default {
    beforeMount: function () {

    },
    data: function () {
        return {
            keyword: '',
            page: 1,
            refresh: false,
            endReach: false,
            contacts: [],
            friendGroup: [],
        }
    },
    methods: {
        onAsk: async function (id, type) {
            let groupName = "", gid = null;
            if (type !== "group") {
                try {
                    let [value, group] = await Promise.all([
                        new Promise(
                            (resolve, reject) =>
                                layer.prompt({
                                    title: "好友分组",
                                    value: "我的好友"
                                }, function (val, index) {
                                    layer.close(index);
                                    resolve(val.trim());
                                })),
                        axios({
                            method: "GET",
                            url: "/im/contact/friendgroup",

                        }).then(resp => {
                            if (resp.data.code) {
                                throw new Error(resp.data.msg || ERROR_MSG);
                            }
                            return resp.data.data;
                        })
                    ]);
                    groupName = value;
                    // 获取好友分组
                    for (let g of group) {
                        if (g.group_name === value) {
                            gid = g.id;
                            break;
                        }
                    }
                    // 创建新的分组
                    if (!gid) {
                        let group = await axios({
                            method: "POST",
                            url: "/im/contact/friendgroup",
                            params: {
                                groupname: value
                            }
                        }).then(resp => {
                            if (resp.data.code) {
                                throw new Error(resp.data.msg || ERROR_MSG);
                            }
                            return resp.data.data;
                        });
                        gid = group.id;
                    }
                } catch (e) {
                    layer.msg(e.message || ERROR_MSG);
                    return;
                }
            }

            try {
                let user = this.$store.state.user;
                if (!user) {
                    this.$store.dispatch("getUser");
                    throw new Error("当前登录信息无效, 请稍后重试.");
                }
                let info = await new Promise(
                    (resolve, reject) => layer.prompt({
                        title: "验证信息",
                        value: "我是" + user.user_nickname
                    }, function (val, index) {
                        layer.close(index);
                        resolve(val.trim());
                    }));

                // 提交
                await axios({
                    url: "/im/contact/"+(type !== "friend"? "linkgroup": "linkfriend"),
                    method: "POST",
                    data: {
                        id: id,
                        content: info,
                        friendGroupId: gid
                    }
                }).then(resp=>{
                    if (resp.data.code) {
                        throw new Error(resp.data.msg || ERROR_MSG);
                    }
                    layer.msg(resp.data.msg || "添加成功, 请等待对方确认.");
                });
            } catch (e) {
                layer.msg(e.message || ERROR_MSG);
                return;
            }
        },
        ask: function (type, args) {

        },
        onRefresh: function () {
            this.page = 1;
            this.onPull();
        },
        onPull: function () {
            if (this.endReach || this.refresh) {
                if (this.endReach) this.refresh = false;
                return;
            }
            // console.log(this.endReach, this.refresh)
            this.refresh = true;

            let params = {
                keyword: this.keyword,
                page: this.page++
            };

            axios.all([
                axios({
                    method: "GET",
                    url: "/im/contact/group",
                    params: params
                }),
                axios({
                    method: "GET",
                    url: "/im/contact/user",
                    params: params
                })
            ]).then(resps => {
                for (let resp of resps) {
                    if (resp.data.code) {
                        layer.msg(resp.data.msg || ERROR_MSG);
                        return;
                    }
                }
                let group = resps[0].data.data;
                let friend = resps[1].data.data;

                // 合并
                let minLen = group.length > friend.length ? friend.length : group.length;
                let contacts = [];
                for (let i = 0; i < minLen; i++) {
                    contacts.push((friend[i]._type = "friend", friend[i]));
                    contacts.push((group[i]._type = "group", group[i]));
                }
                // console.log(contacts, friend, group)
                if (group.length > friend.length) {
                    contacts = contacts.concat(group.slice(minLen).map(i => (i._type = "group", i)));
                } else {
                    contacts = contacts.concat(friend.slice(minLen).map(i => (i._type = "friend", i)));
                }

                // 更新
                if (this.page > 2) {
                    this.contacts = this.contacts.concat(contacts);
                } else {
                    this.contacts = contacts;
                }
                console.log(this.contacts)
            }).catch(e => {
                layer.msg(ERROR_MSG);
                console.error(e);
            }).finally(() => {
                this.refresh = false;
            });
        },
    }
}
</script>


<style>
#addcontact {
    width: 100%;
    height: 100%;
}
</style>
