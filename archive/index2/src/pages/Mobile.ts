import { UserService } from '../service/UserService'
import { ContactService } from '../service/ContactService'
import layui from 'layui'
import layer from 'layer'
import { GatewayImpl } from '../util/gateway'
import { IMCallBack } from '../service/IMCallBack';

import '@/style/mobile.css'
import { SOCKET } from '../conf/link';
import { LINKS } from '../conf/link';
import Axios from 'axios';

function chatPanelTpl(url: string) {
    return `<iframe src=${url} style="padding:0; margin:0; border-width:0; width:100%; height:100%; display:block; overflow:auto;"></iframe>`;
}

class App {
    private user: RespData.UserMessage;
    private layim: layui.Layim;

    private static instance: App;

    constructor() {
        if (!App.instance) App.instance = this;
    }

    public static getInstance(): App {
        return this.instance || (new App());
    }

    async run() {
        try {
            console.info("开始初始化")
            await this.initUser();
            await this.initIM();
            console.info("聊天已启动");
        } catch (e) {
            this.errMsg(e);
        }
    }
    public getUser(): RespData.UserMessage {
        return this.user;
    }
    // 获取用户信息
    private initUser() {
        return UserService.getInstance()
            .getInfo()
            .then(u => this.user = u);
    }

    // 进行通讯界面 layim 的初始化
    private async initIM() {
        // 初始化 im
        let [layim, friend, group] = await Promise.all([
            new Promise((resolve, reject) => {
                layui.use("mobile", function (mobile) {
                    // console.log(mobile);
                    let { layim, layer } = mobile;
                    // Object.defineProperty(window, "layim", {value: layim});
                    resolve(layim);
                });
            }),
            ContactService.getInstance().getFriendAndGroup(),
            ContactService.getInstance().getGroup(this.user.id)
        ]);
        this.layim = (layim as layui.Layim);
        this.layim.config({
            init: {
                mine: this.user,
                friend,
                group
            },
            uploadFile: {
                // url: "http://192.168.0.80:1235" + LINKS.common.file
                url: LINKS.common.file
            },
            uploadImage: {
                // url: "http://192.168.0.80:1235" + LINKS.common.picture
                url: LINKS.common.picture
            },
            msgbox: LINKS.page.msgBox,
            find: LINKS.page.contactMgr,
        });


        // 初始化 socket
        let socket: GatewayImpl = new GatewayImpl(SOCKET);
        let callback: IMCallBack = new IMCallBack(this.user, this.layim, socket);
        socket.onopen = callback.onOpen;
        socket.onxreconnection = callback.onReconnection;
        socket.onxmessage = callback.onMessage;
        // socket.onxask = callback.onAsk;
        socket.onxadd = callback.onAdd;
        socket.onxfeedback = callback.onFeedback;
        socket.onxconnected = callback.onConnected;
        socket.onsockmessage = callback.onSockMessage;
        socket.onOffline = callback.onOffline;
        
        // 绑定 layim 事件
        this.layim.on("sendMessage", callback.onSendMessage);
        this.layim.on("afterGetMessage", callback.onAfterGetMessage);
        this.layim.on("beforeSendMessage", callback.onBeforeSendMessage);
        // 查看聊天记录
        this.layim.on("chatlog", data => {
            let title: string;
            let type: string;
            if (typeof (data as layui.CallBackDataOfFriend).username != "string") {
                title = data.groupname + "的聊天记录";
                type = "group";
            } else {
                title = (data as layui.CallBackDataOfFriend).username + "的聊天记录";
                type = "friend";
            }
            this.layim.panel({
                title,
                tpl: chatPanelTpl(LINKS.page.chatLog + "?type=" + type + "&id=" + data.id),
            });
        });
        // 新的朋友
        this.layim.on("newFriend", () => {
            this.layim.panel({
                title: "新的朋友",
                tpl: chatPanelTpl(LINKS.page.msgBox)
            });
        });

        // 清除缓存
        // let cache = layui.mobile.layim.cache();
        // if (cache) {
        //     let local = layui.data('layim-mobile')[cache.mine.id];
        //     if (local) {
        //         delete local.chatlog;
        //         layui.data('layim-mobile', {
        //             key: cache.mine.id,
        //             value: local
        //         });
        //     }
        // }
        // let ms: RespData.ChatMessage[] = await ChatService.getInstance()
        //     .getMessage(this.user.id);

        // ChatService.getInstance()
        //     .setLocalMessage(this.user.id, ms.filter(m => m.isread));
        Axios.interceptors.request.use((config) => {
            socket.checkAvailable();
            return config;
        });
    }

    public errMsg(e: Error) {
        console.error(e);
        layer.msg(e.message);
    }
}

export {
    App
}
