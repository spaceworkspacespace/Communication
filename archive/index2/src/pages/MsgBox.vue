<style>
#x-p-msg-box {
    height: 100%;
    width: 100%;
    display: block;
    overflow: auto;
    position: relative;
    box-sizing: border-box;
}
#x-p-msg-box a {
    color: blue !important;
    text-decoration-style: solid;
    text-decoration-color: blue;
    text-decoration-line: underline;
}
.layim-msgbox {
    margin: 15px;
}
.layim-msgbox li {
    position: relative;
    margin-bottom: 10px;
    padding: 0 130px 10px 60px;
    padding-bottom: 10px;
    line-height: 22px;
    border-bottom: 1px dotted #e2e2e2;
}
.layim-msgbox .layim-msgbox-tips {
    margin: 0;
    padding: 10px 0;
    border: none;
    text-align: center;
    color: #999;
}
.layim-msgbox .layim-msgbox-system {
    padding: 0 10px 10px 10px;
}
.layim-msgbox li p span {
    padding-left: 5px;
    color: #999;
}
.layim-msgbox li p em {
    font-style: normal;
    color: #ff5722;
}

.layim-msgbox-avatar {
    position: absolute;
    left: 0;
    top: 0;
    width: 50px;
    height: 50px;
}
.layim-msgbox-user {
    padding-top: 5px;
}
.layim-msgbox-content {
    margin-top: 3px;
}
.layim-msgbox .layui-btn-small {
    padding: 0 15px;
    margin-left: 5px;
}
.layim-msgbox-btn {
    position: absolute;
    right: 0;
    top: 12px;
    color: #999;
}
#LAY_view > li:last-of-type {
    margin: 0;
    padding: 0;
    font-size: 14px;
    min-height: auto;
    text-align: center;
}
</style>
<template>
    <div id="x-p-msg-box">
        <ul class="layim-msgbox" id="LAY_view">
            <li
                v-for="(item) of ms"
                :key="item.id"
                data-from-group="item.from_group"
                :class="{'layim-msgbox-system': item.issystem}"
            >
                <div v-if="item.issystem">
                    <p>
                        <em>系统：</em>
                        <span>{{ format(item.date) }}</span>
                        
                    </p>
                    <p style="padding-left:55px;" v-html="getHint(item) || item.content"></p>
                </div>
                <div v-else>
                    <a href="javascript:;" target="_blank">
                        <img :src="item.avatar" class="layui-circle layim-msgbox-avatar">
                    </a>
                    <p class="layim-msgbox-user">
                        <a href="javascript:;" target="_blank">{{ item.associated[0].username || '' }}</a>
                        <span>{{ format(item.date) }}</span>
                    </p>
                    <p class="layim-msgbox-content">
                        <span v-html="getHint(item)" style="color: black;"></span>
                        <span>{{ item.content ? '附言: ' + item.content : '' }}</span>
                    </p>
                    <p class="layim-msgbox-btn" v-if="!item.result">
                        <button
                            class="layui-btn layui-btn-small"
                            data-type="agree"
                            @click="agree(item)"
                        >同意</button>
                        <button
                            class="layui-btn layui-btn-small layui-btn-primary"
                            @click="refuse(item)"
                            data-type="refuse"
                        >拒绝</button>
                    </p>
                    <p class="layim-msgbox-btn" v-else-if="!item.treat">
                        <span v-if="item.type === 2">已处理</span>
                    </p>
                    <p class="layim-msgbox-btn" v-else>已{{item.result !== 'n'? '同意':'拒绝'}}</p>
                </div>
                
            </li>
            <li>
                <a @click.prevent="pull(no++)" href="#">点击加载更多</a>
            </li>
        </ul>
    </div>
</template>

<script lang="ts">
/// <reference path="../typings.d.ts"/>
import Vue from "vue";
import layer from "layer";
import { ContactService } from "../service/ContactService";
import { MessageService, MessageType } from "../service/MessageService";
import { dateFormat, queryStringDeserialize } from "../util/functions";
const NO_MORE = "没有更多数据了";

type DataType = {
    no: number;
    count: number;
    ms: RespData.NoticeMessage[];
    refresh: boolean;
    endReach: boolean;
};

export default Vue.extend({
    data: function(): DataType {
        return {
            no: 1,
            count: 50,
            ms: [],
            refresh: false,
            endReach: false
        };
    },
    beforeMount: function() {
        this.pull();
    },
    methods: {
        pull: async function(pageNo: number) {
            if (this.refresh || this.endReach) {
                if (this.endReach ) {
                    if (this.refresh)
                        this.refresh = false;
                    layer.msg(NO_MORE);
                }
                return;
            }
            this.refresh = true;

            if (!Number.isInteger(pageNo)) pageNo = this.no++;
            try {
                let user = this.$store.state.user;
                let data = await MessageService.getInstance().getMessage(
                    user.id,
                    {
                        no: pageNo,
                        count: this.count
                    }
                );
                if (data.length < this.count) {
                    this.endReach = true;
                }
                if (this.no <= 2) {
                    this.ms = data;
                } else {
                    this.ms = this.ms.concat(data);
                }
            } catch (e) {
                console.error(e);
                layer.msg(e.message);
                this.no--;
            } finally {
                this.refresh = false;
            }
        },
        format: function(timestamp: number) {
            return dateFormat(new Date(timestamp));
        },
        getHint: function(m: RespData.NoticeMessage): string {
            let hint = "";
            const wrapLink = (text: string, url?: string)=>`<a href="${url || 'javascript:;'}">${text}</a>`
            switch (m.type) {
                case MessageType.TYPE_FRIEND_ASK:
                    hint = wrapLink(m.associated[0].username) 
                        + "申请添加您为好友";
                    break;
                case MessageType.TYPE_FRIEND_ASK_REFUSE:
                    hint = wrapLink(m.associated[1].username)
                        + "拒绝了您的好友申请";
                    break;
                case MessageType.TYPE_GROUP_ASK:
                    hint = wrapLink(m.associated[0].username) 
                        + "申请加入群聊" 
                        + wrapLink(m.associated[1].groupname);
                    break;
                case MessageType.TYPE_GROUP_ASK_REFUSE:
                    hint = "加入群聊"
                        + wrapLink(m.associated[1].groupname)
                        + "被拒绝";
                    break;
                case MessageType.TYPE_GROUP_INVITE:
                    hint = wrapLink(m.associated[0].username) 
                        + "邀请您加入群聊" 
                        + wrapLink(m.associated[2].groupname);
                    break;
                case MessageType.TYPE_GROUP_INVITE_REFUSE:
                    hint = wrapLink(m.associated[1].username) 
                        + "拒绝加入群聊" 
                        + wrapLink(m.associated[2].groupname);
                    break;
                case MessageType.TYPE_GROUPMEMBER_REMOVE:
                    hint = wrapLink(m.associated[1].username)
                        + "将"
                        + wrapLink(m.associated[2].username)
                        + "移除了群聊"
                        + wrapLink(m.associated[3].groupname);
                    break;
                case MessageType.TYPE_GROUPMEMBER_BE_REMOVED:
                    hint = "您已被移出群聊"
                        + wrapLink(m.associated[1].groupname);
                    break;
                case MessageType.TYPE_FRIEND_BE_REMOVED:
                    hint = wrapLink(m.associated[1].username) 
                        + "解除了与您的好友关系";
                    break;
                case MessageType.TYPE_GROUPMEMBER_LEAVE:
                    hint = wrapLink(m.associated[1].username)
                        + "退出了群聊"
                        + wrapLink(m.associated[2].groupname);
                    break;
            }
            return hint;
        },

        agree: async function(m: RespData.NoticeMessage) {
            let message = "";
            try {
                let user = this.$store.state.user;
                let data = {
                    id: m.id,
                    id2: '',
                    _action: 1
                };
                if (m.type == MessageType.TYPE_FRIEND_ASK) {
                    let fg: RespData.FriendGroupMessage[] = await ContactService.getInstance()
                        .getFriendGroup(
                            user.id,
                            false
                        ) as RespData.FriendGroupMessage[];
                    let index: any = await new Promise((resolve, reject) => {
                        this.$store.commit("layer/show", {
                            type: "listSelect",
                            title: "选择分组",
                            list: fg.map((i: any) => i.groupname),
                            style: { width: "340px" },
                            onselect: (index: number) => resolve(index)
                        });
                    });
                    data.id2 = fg[index as number].id + '';
                } else {

                }
                message = await MessageService.getInstance()
                    .handleMessage(
                        user.id,
                        data
                    );
                m.result = "y";
            } catch (e) {
                console.error(e);
                message = e.message;
            }
            layer.msg(message);
        },
        refuse: async function(m: RespData.NoticeMessage) {
            let message = "";
            try {
                let user = this.$store.state.user;

                message = await MessageService.getInstance().handleMessage(
                    user.id,
                    {
                        id: m.id,
                        _action: 0
                    }
                );
                m.result = "n";
            } catch (e) {
                message = e.message;
                console.log(e);
            }
            layer.msg(message);
        }
    }
});
</script>
