<template>
    <div id="newfriend">
        <header class="mui-bar mui-bar-nav">
            <a href="/im/index/index"
                class="mui-icon mui-icon-left-nav mui-pull-left"></a>
            <router-link to="/add"
                class="mui-pull-right"
                style="height: 44px; line-height: 44px; font-size: 14px;">添加联系人</router-link>

            <h1 class="mui-title">新的朋友</h1>
        </header>
        <div class="mui-content"
            style="position:absolute;top:44px;bottom:0;left:0;right:0;padding:0;overflow-y:auto;"
            ref="content"
            @scroll="onScroll">

            <ul class="mui-table-view">
                <li v-for="(m, i) of msgs"
                    :key="i"
                    class="mui-table-view-cell mui-media">
                    <a href="javascript:;">
                        <img class="mui-media-object mui-pull-left"
                            :src="m.user.avatar || 'https://i.loli.net/2018/12/10/5c0de4003a282.png'">
                        <div class="mui-media-object mui-pull-right btn-group"
                            style="max-width:160px;"
                            v-if="m.agree != 'y' && m.agree != 'n'">
                            <button @click="accept(i, m)"
                                type="button">同意</button>
                            <button @click="refuse(i, m.sender_id)"
                                type="button">拒绝</button>
                        </div>

                        <span v-else
                            class="mui-media-object mui-pull-right"
                            style="font-size:12px;max-width:160px;">
                            {{m.agree!=="y"? "已拒绝":"已接受"}}
                        </span>
                        <div class="mui-media-body">
                            {{m.user.user_nickname}}
                            <p class='mui-ellipsis'>{{m.content}}</p>
                        </div>
                    </a>
                </li>
            </ul>
            <!-- <div style="text-align:center;">
                <span class="mui-icon mui-icon-spinner-cycle mui-spin x-rotate"
                    style="line-height:45px;"></span>
            </div> -->

            <!-- </div> -->
        </div>
    </div>
</template>

<script>
import axios from 'axios'
import mui from 'mui'
import layer from 'layer'
import { ContactService } from '@/service/ContactService'
// import {} from '@/util/functions'


// axios.defaults.transformResponse = [function(data){
//     console.log(data)
//     return data;
// }];

const ERROR_MSG = "请求错误, 请稍后重试~";

export default {
    beforeMount: function () {
        this.pull();
    },
    data: function () {
        return {
            page: 1,
            msgs: [],
            refresh: false,
            endReach: false
        };
    },
    methods: {
        // goBack: function() {
        //     history.back();
        // }
        accept: async function (index, msg) {
            let params = null, url = null;
            if (msg.group_id) {
                url = "/im/MsgBox/agreeGroup";
                params = {
                    sender_id: msg.sender_id,
                    send_ip: msg.send_ip,
                    group_id: msg.group_id,
                    sender_nickname: msg.user.user_nickname
                };
            } else {
                url = "/im/Msgbox/agreeFriends";
                let gid = null;
                // 获取设置的分组名称
                let gName = await new Promise(
                    (resolve, reject) =>
                        layer.prompt({
                            title: "好友分组",
                            value: "我的好友"
                        }, function (val, index) {
                            layer.close(index);
                            resolve(val.trim());
                        }));
                let user = this.$store.state.user;
                // 获取分组 id
                try {
                    gid = await ContactService
                        .getInstance()
                        .determineFriendGroupId(user.id, gName);
                } catch (e) {
                    layer.msg(e.message || ERROR_MSG);
                    return;
                }
                params = {
                    sender_id: msg.sender_id,
                    send_ip: msg.send_ip,
                    group_id_me: gid,
                    group_id_you: msg.sender_friendgroup_id
                };
            }
            // 发送请求
            axios({ url, params }).then(resp => {
                let data = resp.data;
                this.msgs[index].agree = "y";
            }).catch(e => {
                layer.msg(ERROR_MSG);
                console.error(e);
            });
        },
        refuse: function (index, id) {
            axios({
                method: "GET",
                url: "/im/Msgbox/refuse",
                params: {
                    sender_id: id
                }
            }).then(resp => {
                let data = resp.data;
                this.msgs[index].agree = "n";
            }).catch(e => {
                layer.msg(ERROR_MSG);
                console.error(e);
            });
        },
        pull: function () {
            if (this.endReach || this.refresh) {
                if (this.endReach) this.refresh = false;
                return;
            }
            // console.log(this.endReach, this.refresh)
            this.refresh = true;
            axios({
                method: "GET",
                url: "/im/Msgbox/data",
                params: {
                    page: this.page++
                }
            }).then(resp => {
                let data = resp.data;
                if (data.code) {
                    layer.msg(ERROR_MSG);
                    return;
                }
                let users = data.udata;
                let msgs = data.data;
                if (!(msgs instanceof Array)) {
                    msgs = Object.values(msgs);
                }
                // 调整数据格式
                for (let msg of msgs) {
                    for (let user of users) {
                        if (msg.sender_id === user.id) {
                            msg.user = user;
                            break;
                        }
                    }
                    if (!msg.user) {
                        layer.msg(ERROR_MSG);
                        return;
                    }
                    // msg.agree = '';
                }

                if (this.page > 2) {
                    this.msgs = this.msgs.concat(msgs);
                } else {
                    this.msgs = msgs;
                }

                // this.tryFill();
            }).catch(e => {
                layer.msg(ERROR_MSG);
                console.error(e);
            }).finally(() => {
                this.refresh = false;
            });
        },
        tryFill: function () {
            let content = this.$refs.content;
            if (content.scrollHeight <= content.clientHeight) {
                setTimeout(() => this.pull(), 100);
            }
        },
        onScroll: function (event) {
            if (event.target !== this.$refs.content) return;
            let elem = event.target;
            let top = elem.scrollTop, height = elem.clientHeight, scroll = elem.scrollHeight;
            if (top + height >= scroll - 45) {
                // 用于分页
                // this.pull();
            }
        }
    }
}
</script>

<style >
#newfriend {
    width: 100%;
    height: 100%;
}
.btn-group {
    font-size: 12px;
    margin: 0;
}
.btn-group > button {
    margin: 0;
    padding: 0px 12px;
    height: 42px;
}
</style>
