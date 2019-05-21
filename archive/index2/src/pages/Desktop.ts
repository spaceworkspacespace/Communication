/// <reference path="../typings.d.ts"/>
import { UserService } from '../service/UserService'
import { ContactService } from '../service/ContactService'
import layui from 'layui'
import layer from 'layer'
import { GatewayImpl, GatewayMessage } from '../util/gateway'
import { IMCallBack } from '../service/IMCallBack';

import '@/style/desktop.css'
import { SOCKET, LINKS } from '../conf/link';
import { ChatService } from '../service/ChatService';
import { ICallHandler } from '../util/ICallHandler';
import { CallService, CallType } from '../service/CallService';

import Disconnect from '../assets/phone.svg'
import Axios from 'axios';

function errMsg(e: Error) {
    console.error(e);
    layer.msg(e.message);
}

// function desktopTemplateTag(strings: TemplateStringsArray, ...args: string[]): string {
//     console.log(strings, args);
//     return strings.join("");
// }

// <iframe class="x_video_call_dis" src="${Disconnect}"></iframe>

/*
<div class="x_video_call_dis">
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
    width="100%" 
    height="100%" 
    class="icon"
    p-id="1998" 
    style="cursor: pointer;" 
    t="1558062165503" 
    version="1.1"
    viewBox="0 0 1120 1120">
    <defs>
        <style type="text/css"/>
    </defs>
    <g transform="translate(60,60)">
        <circle cx="500" cy="500" r="560" fill="red"/>
        <g transform="translate(-5, 30)">
            <path 
                fill="white" 
                d="M914.16 708.576q0 15.424-5.728 40.288t-12 39.136q-12 28.576-69.728 60.576-53.728 29.152-106.272 29.152-15.424 0-30.016-2.016t-32.864-7.136-27.136-8.288-31.712-11.712-28-10.272q-56-20-100-47.424-73.152-45.152-151.136-123.136t-123.136-151.136q-27.424-44-47.424-100-1.728-5.152-10.272-28t-11.712-31.712-8.288-27.136-7.136-32.864-2.016-30.016q0-52.576 29.152-106.272 32-57.728 60.576-69.728 14.272-6.272 39.136-12t40.288-5.728q8 0 12 1.728 10.272 3.424 30.272 43.424 6.272 10.848 17.152 30.848t20 36.288 17.728 30.56q1.728 2.272 10.016 14.272t12.288 20.288 4 16.288q0 11.424-16.288 28.576t-35.424 31.424-35.424 30.272-16.288 26.272q0 5.152 2.848 12.864t4.864 11.712 8 13.728 6.56 10.848q43.424 78.272 99.424 134.272t134.272 99.424q1.152 0.576 10.848 6.56t13.728 8 11.712 4.864 12.864 2.848q10.272 0 26.272-16.288t30.272-35.424 31.424-35.424 28.576-16.288q8 0 16.288 4t20.288 12.288 14.272 10.016q14.272 8.576 30.56 17.728t36.288 20 30.848 17.152q40 20 43.424 30.272 1.728 4 1.728 12z" 
                p-id="1999"
                transform="rotate(135, 500, 500)"/>
        </g>
    </g>
</svg>
</div>
*/

const DESKTOP_VIDEO_CALL_TEMPLATE = `
<div class="x_video_call_container">
    <video class="x_video_call_video"></video>
    <div class="x_video_call_panel">
        <div>连接中</div>
        <div class="x_video_call_dis">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
                width="100%" 
                height="100%" 
                class="icon"
                p-id="1998" 
                style="cursor: pointer;" 
                t="1558062165503" 
                version="1.1"
                viewBox="0 0 1120 1120">
                <defs>
                    <style type="text/css"/>
                </defs>
                <g transform="translate(60,60)">
                    <circle cx="500" cy="500" r="560" fill="red"/>
                    <g transform="translate(-5, 30)">
                        <path 
                            fill="white" 
                            d="M914.16 708.576q0 15.424-5.728 40.288t-12 39.136q-12 28.576-69.728 60.576-53.728 29.152-106.272 29.152-15.424 0-30.016-2.016t-32.864-7.136-27.136-8.288-31.712-11.712-28-10.272q-56-20-100-47.424-73.152-45.152-151.136-123.136t-123.136-151.136q-27.424-44-47.424-100-1.728-5.152-10.272-28t-11.712-31.712-8.288-27.136-7.136-32.864-2.016-30.016q0-52.576 29.152-106.272 32-57.728 60.576-69.728 14.272-6.272 39.136-12t40.288-5.728q8 0 12 1.728 10.272 3.424 30.272 43.424 6.272 10.848 17.152 30.848t20 36.288 17.728 30.56q1.728 2.272 10.016 14.272t12.288 20.288 4 16.288q0 11.424-16.288 28.576t-35.424 31.424-35.424 30.272-16.288 26.272q0 5.152 2.848 12.864t4.864 11.712 8 13.728 6.56 10.848q43.424 78.272 99.424 134.272t134.272 99.424q1.152 0.576 10.848 6.56t13.728 8 11.712 4.864 12.864 2.848q10.272 0 26.272-16.288t30.272-35.424 31.424-35.424 28.576-16.288q8 0 16.288 4t20.288 12.288 14.272 10.016q14.272 8.576 30.56 17.728t36.288 20 30.848 17.152q40 20 43.424 30.272 1.728 4 1.728 12z" 
                            p-id="1999"
                            transform="rotate(135, 500, 500)"/>
                    </g>
                </g>
            </svg>
        </div>
        <div>32:15</div>
    </div>
</div>
`;

class DesktopCallWindow {
    // 窗口创建时间
    private createTime: number;
    // 聊天开始时间
    private startTime: number;
    // 当前用户
    // private user: RespData.UserMessage & RespData.GroupMessage;
    // 对方
    // private other: RespData.UserMessage & RespData.GroupMessage;
    // private handler: DesktopCallHandler;

    constructor(
        private handler: DesktopCallHandler,
        private user: any,
        private other: any,
        private event: {
            // 通话异常
            onInterrupt: (window: DesktopCallWindow) => void;
            // 正常结束通话
            onComplete: (window: DesktopCallWindow) => void;
        }) {

    }

    public init(element: JQuery<HTMLElement>) {
        // element.css();
        // 挂断
        console.log(element)
        console.log(element.find(".x_video_call_dis"))
        element.find(".x_video_call_dis").on("click", () => {
            console.log("关闭")
            this.event.onComplete(this);
            console.log("关闭")
        });
    }
}

export class DesktopCallHandler implements ICallHandler {
    // private user: RespData.UserMessage;
    // private layim: layui.Layim;

    // 储存窗口和它的 layer 索引值
    private windowMap: Map<DesktopCallWindow, number>;
    private zIndex: number = 20000000;

    constructor(private user: RespData.UserMessage,
        private layim: layui.Layim) {
        this.windowMap = new Map();
    }

    async call(other: RespData.UserMessage | RespData.GroupMessage, callType: CallType) {
        let isGroupChat: boolean = false;
        let group: RespData.GroupMessage;
        let friend: RespData.UserMessage;
        if ((other as Object).hasOwnProperty("groupname")) {
            isGroupChat = true;
            group = other as RespData.GroupMessage;
        } else {
            friend = other as RespData.UserMessage;
        }

        let window = new DesktopCallWindow(this, this.user, other, {
            onComplete: window => {
                layer.close(this.windowMap.get(window));
            },
            onInterrupt: window => {

            }
        });
        layer.open({
            title: isGroupChat ? group.groupname : friend.username,
            type: 1,
            content: DESKTOP_VIDEO_CALL_TEMPLATE,
            resize: true,
            closeBtn: 0,
            shade: 0,
            zIndex: this.zIndex,
            success: (layero, index) => {
                this.windowMap.set(window, index);
                window.init(layero.find(".x_video_call_container"))
            }
        });
        return;
        try {
            let result = await CallService.getInstance()
                .requestCall(this.user.id, other, callType);
        } catch (e) {
            errMsg(e);
        }
    }

    // 语音/视频通话相关的操作
    onRequest(data: GatewayMessage.CallAskMessagePayload): void {
        throw new Error("Method not implemented.");
    }

    onRequestDescription(data: { sign: string; }): void {
        throw new Error("Method not implemented.");
    }

    onResponseDescription(data: { sign: string; description: string; }): void {
        throw new Error("Method not implemented.");
    }
}

class App {
    private user: RespData.UserMessage;
    private layim: layui.Layim;
    private callHandler: DesktopCallHandler;

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
            this.callHandler.call(this.user, "voice");
        } catch (e) {
            errMsg(e);
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
                layui.use("layim", function (layim) {
                    // console.log(arguments);
                    // Object.defineProperty(window, "layim", {value: layim});
                    resolve(layim)
                });
                //  (layim: layui.Layim) => resolve(layim));
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
            chatLog: LINKS.page.chatLog,
            tool: [{
                alias: "voiceCall",
                title: "语音通话",
                icon: "&#xe645"
            }, {
                alias: "videoCall",
                title: "视频通话",
                icon: "&#xe6ed"
            }]
        });

        // 视频聊天
        this.callHandler = new DesktopCallHandler(this.user, this.layim);

        // 初始化 socket
        let socket: GatewayImpl = new GatewayImpl(SOCKET);
        let callback: IMCallBack = new IMCallBack(this.user, this.layim, socket);
        callback.setCallHandler(this.callHandler);
        socket.onopen = callback.onOpen;
        socket.onxreconnection = callback.onReconnection;
        socket.onxmessage = callback.onMessage;
        socket.onxask = callback.onAsk;
        socket.onxadd = callback.onAdd;
        socket.onxfeedback = callback.onFeedback;
        socket.onxconnected = callback.onConnected;
        socket.onsockmessage = callback.onSockMessage;
        socket.onOffline = callback.onOffline;

        // 绑定 layim 事件
        this.layim.on("sign", callback.onSign);
        this.layim.on("sendMessage", callback.onSendMessage);
        // this.layim.on("chatMsgDelete", callback.onChatMsgDelete);
        this.layim.on("afterGetMessage", callback.onAfterGetMessage);
        this.layim.on("beforeSendMessage", callback.onBeforeSendMessage);
        this.layim.on("tool(videoCall)", (insert, send, data) => {
            this.callHandler.call((data.data as any), "video");
        });

        // 在每次网络活动前进行 socket 连接检测
        Axios.interceptors.request.use((config) => {
            socket.checkAvailable();
            return config;
        });
    }
}

export {
    App
}
