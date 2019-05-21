<style>
body .layim-chat-main {
    height: auto;
}
#LAY_view > li:last-of-type {
    margin: 0;
    padding: 0;
    font-size: 14px;
    min-height: auto;
    text-align: center;
}
#x-p-chat-log {
    height: 100%;
    width: 100%;
    display: block;
    overflow: auto;
    position: relative;
    box-sizing: border-box;
}
</style>

<template>
    <div id="x-p-chat-log" class="layim-chat-main">
        <ul id="LAY_view" style="overflow: hidden;">
            <li
                v-for="(record, index) of records"
                :key="record.cid"
                :class="[ record.uid !== $store.state.user.id? '': 'layim-chat-mine']"
            >
                <div class="layim-chat-user">
                    <img :src="record.avatar || 'https://i.loli.net/2018/12/10/5c0de4003a282.png'">
                    <cite>
                        <i>{{ format(record.date) }}</i>
                        {{ record.username }}
                    </cite>
                </div>
                <div class="layim-chat-text">
                    <div class="contextmenuul" style="display: none;"></div>
                    <div
                        v-if="type === 'friend'"
                        style="position:absolute;top:0px;right:5px;cursor:pointer;z-index:1000;"
                        class="x-msg-del"
                        @click="delMsg(index)"
                    >x</div>
                    <div v-html="contentRecognize(record.content)"></div>
                </div>
            </li>
            <li>
                <a @click.prevent="pull(no++)" href="#">点击加载更多</a>
            </li>
        </ul>
    </div>
</template>


<script>
import layer from 'layer'
import { dateFormat, queryStringDeserialize } from '../util/functions'
import { ChatService } from '../service/ChatService.js'

const recognizer = [
    /^(file)\(([^\)]+)\)\[([^\]]+)\]$/,
    /^(img)\[([^\]]+)\]$/
];
const NO_MORE = "没有更多数据了";

export default {
    props: ["chatType", "contactId"],
    data: function () {
        return {
            id: null,
            no: 1,
            count: 150,
            records: [],
            type: null,
            refresh: false,
            endReach: false
        }
    },
    beforeMount: function () {
        // 获取查询字符串
        let query = queryStringDeserialize(window.location.href);
        this.type = this.chatType || query.type;
        this.id = this.contactId || query.id;
        // 拉取数据
        this.pull(this.no++);
    },
    methods: {
        pull: async function (pageNo) {
            if (this.refresh || this.endReach) {
                if (this.endReach) {
                    this.refresh = false;
                    layer.msg(NO_MORE);
                }
                return;
            }
            this.refresh = true;

            if (!Number.isInteger(pageNo)) pageNo = this.no++;
            try {
                let user = this.$store.state.user;
                let data = await ChatService.getInstance()
                    .getMessage(user.id, {
                        type: this.type,
                        id: this.id,
                        no: pageNo,
                        count: this.count
                    });
                if (data.length < this.count) {
                    this.endReach = true;
                }
                if (this.no <= 2) {
                    this.records = data;
                } else {
                    this.records = this.records.concat(data);
                }
            } catch (e) {
                console.error(e);
                layer.msg(e.message);
                this.no--;
            } finally {
                this.refresh = false;
            }
        },

        onScroll: function (event) {
            console.log(event);
        },

        delMsg: function (index) {
            let user = this.$store.state.user;
            ChatService.getInstance()
                .deleteMessage(user.id, {
                    cid: this.records[index].cid,
                    type: this.type
                })
                .then(r => {
                    layer.msg(r);
                    this.records.splice(index, 1);

                })
                .catch(e => {
                    console.error(e);
                    layer.msg(e.message);
                });
        },

        // 识别特殊内容
        contentRecognize: function (content) {
            var match;
            for (var i = recognizer.length - 1; i >= 0; i--) {
                if (match = recognizer[i].exec(content)) {
                    break;
                }
            }
            if (!match) return content;

            switch (match[1]) {
                case "img":
                    if (!match[2]) break;
                    content = '<img class="layui-layim-photos" src="' + match[2] + '">';
                    break;
                case "file":
                    if (!match[2] || !match[3]) break;
                    content = '<a class="layui-layim-file" href="' + match[2]
                        + '" download="" target="_blank"><i class="layui-icon"></i><cite>' + match[3]
                        + '</cite></a>';
                    break;
            }

            return content;
        },

        format: function (timestamp) {
            return dateFormat(new Date(timestamp));
        }

    }
}
</script>
