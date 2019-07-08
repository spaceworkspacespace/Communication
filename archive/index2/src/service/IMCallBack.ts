
import { UserService } from './UserService'
import { ContactService } from './ContactService'
import layui from 'layui'
import layer from 'layer'
import { GatewayImpl, GatewayMessage } from '../util/gateway';
import { ChatService } from './ChatService';
import { MessageService } from './MessageService';
import { App } from '../App';
import { ICallHandler } from '../util/ICallHandler';
import { CallService } from './CallService';

// 消息发送的间隔时间
const MESSAGE_SEND_INTERVAL = 1500;

function errMsg(e: Error): void {
    console.error(e);
    layer.msg(e.message, {
        zIndex: 21000000
    });
}

function layerMsg(msg: string, options?: any) {
    layer.msg(msg, { ...options, zIndex: 21000000 });
}

class IMCallBack {
    private user: RespData.UserMessage;
    private layim: layui.Layim;
    private socket: GatewayImpl;
    // 最后发送的消息的时间
    private lastSendTime: number;

    // 用来处理通话事件的对象
    private callHandler: ICallHandler;

    private zIndex: 21000000;

    constructor(u: RespData.UserMessage, layim: layui.Layim, socket: GatewayImpl) {
        this.user = u;
        this.layim = layim;
        this.socket = socket;
        this.lastSendTime = Date.now();

        // 绑定 this
        for (let prop in this) {
            if (typeof this[prop] === "function"
                || this[prop] instanceof Function) {
                let fun: any = this[prop];
                this[prop] = fun.bind(this);
            }
        }
    }

    public setCallHandler(callHandler: ICallHandler) {
        this.callHandler = callHandler;
    }

    /**
     * 删除一条消息
     * @param msgId 消息的 id
     * @param localMsgKey 本地消息的 Key
     * @param msg 消息对象
     */
    public deleteChatMessage(msgId: number, localMsgKey: string, msgElem: JQuery<HTMLLIElement>): void {
        // console.log(localMsgKey, /^group/.test(localMsgKey))
        let type: "friend" | "group" = /^group/.test(localMsgKey) ? "group" : "friend";
        ChatService.getInstance()
            .deleteMessage(this.user.id,
                { cid: msgId, type })
            .then(m => {
                layer.msg(m, { zIndex: this.zIndex });
                // 从 DOM 中移除
                msgElem.remove();
                // 更新到缓存
                ChatService.getInstance()
                    .deleteLocalMessage(this.user.id, msgId, localMsgKey);
            })
            .catch(errMsg);
    }

    public onAfterGetMessage(li: JQuery<HTMLLIElement>, isActive: boolean, thisChat: layui.ThisChat, data: layui.GetMessageType) {
        // console.log(li, isActive, thisChat, data);
        switch (App.runClass) {
            case "desktop":
                // console.log(li)
                // 如果收到消息的窗口不是当前打开的窗口, 添加自定义样式显示“未读信息”样式
                if (!isActive) {
                    // 窗口列表
                    let elem = $('.layim-chatlist-' + data.type + data.id);
                    // 是否已经监听
                    let isMonitor = elem.attr("x-on-new-message");
                    if (!isMonitor) {
                        elem.on("click", function () {
                            elem.removeClass("x-new-message");
                        });
                        elem.attr("x-on-new-message", "true");
                    }
                    // 添加样式
                    if (elem.find(".x-new-message-hints").length == 0) {
                        elem.append('<span class="x-new-message-hints" style="display: none;"></span>');
                    }
                    elem.addClass("x-new-message");
                }
                li.find(".x-msg-del").on("click", event => {
                    let cid = li.attr("data-cid");
                    if (!cid) {
                        layer.msg("消息无法删除");
                        return;
                    }
                    this.deleteChatMessage(Number.parseInt(cid), thisChat.data.type + thisChat.data.id, li);
                });
                break;
            case "mobile":
                let chatInfoContent: JQuery<HTMLElement> = li.find(".layim-chat-text");
                let mytips = chatInfoContent.find(".mytips");
                let timeOutEvent = 0;
                // 长按显示删除tips;
                chatInfoContent.on({
                    touchstart: function (e) {
                        timeOutEvent = window.setTimeout(function () {
                            // 此处为长按事件-----在此显示删除按钮
                            // console.log("长按了。。。");
                            mytips.show();
                        }, 1000);
                    },
                    touchmove: function (e) {
                        clearTimeout(timeOutEvent);
                        timeOutEvent = 0;
                        e.preventDefault();
                    },
                    touchend: function (e) {
                        clearTimeout(timeOutEvent);
                        // if (timeOutEvent != 0) { //点击
                        //   //此处为点击事件
                        // }
                        return false;
                    }
                })
                // 删除按钮
                mytips.on({
                    touchstart: e => {
                    },
                    touchmove: e => {
                        clearTimeout(timeOutEvent);
                        e.preventDefault();
                    },
                    touchend: e => {
                        clearTimeout(timeOutEvent);
                        if (timeOutEvent != 0) { //点击
                            //点击事件处理
                            console.log("删除");
                            let cid = li.attr("data-cid");
                            if (!cid) {
                                layer.msg("消息无法删除");
                                return;
                            }
                            this.deleteChatMessage(Number.parseInt(cid), thisChat.data.type + thisChat.data.id, li);

                        }
                        return false;
                    }
                });
                // 点击其他地方可取消删除按钮
                $(document).on('click', (e: JQuery.ClickEvent) => {
                    if (mytips.get(0) !== e.target) {
                        mytips.hide();
                    }
                });
                break;
        }
    }

    /**
     * 在消息发送前执行的回调
     * @param li 
     */
    public onBeforeSendMessage(li: JQuery<HTMLLIElement>, thisChat: layui.ThisChat): boolean {
        // 判断发送频率是否过快
        if (this.lastSendTime + MESSAGE_SEND_INTERVAL > Date.now()) {
            layerMsg("您发送的太快了,请稍等一下.");
            return false;
        }
        this.lastSendTime = Date.now();

        switch (App.runClass) {
            case "desktop":
                // 点击删除按钮, 删除消息
                li.find(".x-msg-del").on("click", event => {
                    let cid = li.attr("data-cid");
                    if (!cid) {
                        layerMsg("消息无法删除");
                        return;
                    }
                    this.deleteChatMessage(Number.parseInt(cid), thisChat.data.type + thisChat.data.id, li);
                });
                // if (call.chatMsgClick instanceof Array) {
                //   for (var i=call.chatMsgClick.length-1; i>=0; i--) {
                //     chatInfoContent.on("mousedown", call.chatMsgClick[i]);
                //     chatInfoContent.on("contextmenu", _preventDefaultRightClick);
                //   }
                // }
                break;
            case "mobile":
                let chatInfoContent: JQuery<HTMLElement> = li.find(".layim-chat-text");
                let mytips = chatInfoContent.find(".mytips");
                let timeOutEvent = 0;
                // 长按显示删除tips;
                chatInfoContent.on({
                    touchstart: function (e) {
                        timeOutEvent = window.setTimeout(function () {
                            // 此处为长按事件-----在此显示删除按钮
                            // console.log("长按了。。。");
                            mytips.show();
                        }, 1000);
                    },
                    touchmove: function (e) {
                        clearTimeout(timeOutEvent);
                        timeOutEvent = 0;
                        e.preventDefault();
                    },
                    touchend: function (e) {
                        clearTimeout(timeOutEvent);
                        // if (timeOutEvent != 0) { //点击
                        //   //此处为点击事件
                        // }
                        return false;
                    }
                })
                // 删除按钮
                mytips.on({
                    touchstart: e => {
                    },
                    touchmove: e => {
                        clearTimeout(timeOutEvent);
                        e.preventDefault();
                    },
                    touchend: e => {
                        clearTimeout(timeOutEvent);
                        if (timeOutEvent != 0) { //点击
                            //点击事件处理
                            // console.log("删除");
                            let cid = li.attr("data-cid");
                            if (!cid) {
                                layer.msg("消息无法删除");
                                return;
                            }
                            this.deleteChatMessage(Number.parseInt(cid), thisChat.data.type + thisChat.data.id, li);

                        }
                        return false;
                    }
                });
                // 点击其他地方可取消删除按钮
                $(document).on('click', (e: JQuery.ClickEvent) => {
                    if (mytips.get(0) !== e.target) {
                        mytips.hide();
                    }
                });
                break;

        }
        return true;
    }

    public onOffline() {
        layer.msg("网络连接不可用, 您已离线");
    }

    public onSign(sign: string) {
        return UserService.getInstance()
            .updateInfo(this.user.id, {
                id: this.user.id,
                sign
            })
            .then(u => {
                Object.assign(this.user, u);
                layer.msg("更新成功");
            })
            .catch(errMsg);
    }

    public onSendMessage(data: any, li: JQuery<HTMLLIElement>) {
        ChatService.getInstance()
            .sendMessage(this.user.id, {
                id: data.to.id,
                type: data.to.type,
                content: data.mine.content
            })
            .then(m => {
                // 消息发送成功
                // 更新到元素
                li.attr("data-cid", m.cid);
                // 更新到缓存
                ChatService.getInstance()
                    .updateLocalMessage(this.user.id, m);
            })
            .catch(e => {
                errMsg(e);
                // 消息发送失败, 设置样式
                li.find(".messagefail_img")
                    .css({ "display": "inline-block" });
            });
    }

    public onChatMsgDelete(cid: number, type: string, del: () => void) {
        return ChatService.getInstance()
            .deleteMessage(this.user.id,
                { cid, type })
            .then(m => {
                layer.msg(m);
                del();
            })
            .catch(errMsg);
    }

    public async onOpen(event: Event): Promise<any> {
        // 给服务器确认用户 id

        // this.socket.send(JSON.stringify({
        //     data: {
        //         uid: this.user.id
        //     },
        //     type: GatewayMessage.ONLINE
        // }));

        try {
            // 设置近期聊天数据
            // friend.forEach()
            let ms: RespData.ChatMessage[] = await ChatService.getInstance()
                .getMessage(this.user.id);

            ChatService.getInstance()
                .setLocalMessage(this.user.id, ms.filter(m => m.isread));

            await MessageService.getInstance()
                .pull(this.user.id);
            layer.msg("连接成功");
            console.log("连接成功")
        } catch (e) {
            // errMsg(e);
            layer.alert(e.message, {
                shadeClose: false,
                closeBtn: 0
            }, index => {
                window.location.href = "/";
            });
        }
    }

    public onReconnection() {
        layer.msg("连接已断开, 重连中...");
    }

    public onMessage(data: RespData.ChatMessage[]) {
        data.sort(function (l, r) {
            return l.date > r.date ? 1 : -1;
        });
        data.forEach(m => m.date *= 1000);
        // console.log(data)
        setTimeout(() => {
            for (let m of data) {
                // console.log(ChatService.getInstance().hasLocalMessage(this.user.id, m))
                // console.log(2)
                // 检查本地是否已有此消息, 如果消息是自己发送的话
                if (this.user.id != m.uid
                    || !ChatService.getInstance()
                        .hasLocalMessage(this.user.id, m)) {
                    // 确定会话的类型
                    let type: "friend" | "group";
                    if (m.gid != null) {
                        type = "group";
                    } else {
                        type = "friend";
                    }
                    // 系统消息
                    if (m.issystem) {
                        this.layim.getMessage({
                            system: true,
                            id: type != "friend" ? m.gid : m.uid,
                            type: type,
                            content: m.content
                        });
                    } else {
                        let isMine: boolean = false;
                        if (this.user.id == m.uid) isMine = true;
                        // console.log("组装好的消息: ", {
                        //     id: type != "friend" ? m.gid : (isMine ? m.tid : m.uid),
                        //     cid: m.cid,
                        //     content: m.content,
                        //     fromid: m.uid,
                        //     avatar: m.avatar,
                        //     username: m.username,
                        //     type: type,
                        //     mine: isMine,
                        //     timestamp: m.date
                        // });
                        this.layim.getMessage({
                            id: type != "friend" ? m.gid : (isMine ? m.tid : m.uid),
                            cid: m.cid,
                            content: m.content,
                            fromid: m.uid,
                            avatar: m.avatar,
                            username: m.username,
                            type: type,
                            mine: isMine,
                            timestamp: m.date
                        });
                    }
                }
            }
        }, 500);
    }

    public onAsk(data: MessageData.AskMessageData) {
        this.layim.msgbox(data.msgCount);
    }

    public onAdd(data: MessageData.AddMessageData) {
        // console.log("添加好友", data);
        for (var i = data.length - 1; i >= 0; i--) {
            this.layim.addList(data[i]);
        }
    }

    public onFeedback(sign: string) {
        return ChatService.getInstance()
            .feedback(this.user.id, sign)
            .catch(errMsg);
    }

    public async onConnected(data: any) {
        let bind = await UserService.getInstance()
            .bind(this.user.id, data.id);
        let ks = JSON.parse(window.atob(bind.ks));
        this.socket.setKeys(ks);
    }

    public onSockMessage(type: string | number, data: any, event: MessageEvent) {
        console.log("新的命令: ", type, data)
        switch (type) {
            case GatewayMessage.COMMUNICATION_ASK:
                // let rawData = JSON.parse(event.data);
                this.callHandler.onRequest(data);
                // .then(r => {
                //     CallService.getInstance()
                //     .requestCall(null, )
                //     CallService.getInstance()
                //         .requestReply(data.sign, r)
                //         .catch(e => {
                //             console.log(e);
                //             layer.msg(e.message);
                //         });
                // });
                break;
            case GatewayMessage.COMMUNICATION_EXCHANGE:
                // 群聊的 sign 特殊处理一下, 保证唯一性
                // if (data.groupid != null) {
                //     data.sign = data.sign + "_for_" + data.userid;
                // }
                this.callHandler.onRequestDescription(data);
                break;
            case GatewayMessage.COMMUNICATION_COMMAND:
                try {
                    // 群聊的 sign 特殊处理一下, 保证唯一性
                    // if (data.groupid != null) {
                    //     data.sign = data.sign + "_for_" + data.userid;
                    // }
                    data.description = JSON.parse(window.atob(data.description));
                    this.callHandler.onResponseDescription(data);
                } catch (e) {
                    console.error(e);
                    layer.msg(e.message);
                }
                break;
            case GatewayMessage.COMMUNICATION_EXCHANGE_ICE:
                try {
                    // 群聊的 sign 特殊处理一下, 保证唯一性
                    // if (data.groupid != null) {
                    //     data.sign = data.sign + "_for_" + data.userid;
                    // }
                    data.candidate = JSON.parse(window.atob(data.ice));
                    this.callHandler.onResponseCandidate(data);
                } catch (e) {
                    console.error(e);
                    layer.msg(e.message);
                }
                break;
            case GatewayMessage.COMMUNICATION_RECONNECT:
                break;
            case GatewayMessage.COMMUNICATION_FINISH:
                this.callHandler.onFinish(data);
                break;
            case GatewayMessage.REMOVE_FRIEND:
                // console.log("删除好友", data);
                for (let friend of (data as RespData.UserMessage[])) {
                    this.layim.removeList({
                        id: friend.id,
                        type: "friend"
                    });
                }
                break;
            case GatewayMessage.REMOVE_GROUP:
                // console.log("删除群聊", data)
                for (let group of (data as RespData.GroupMessage[])) {
                    this.layim.removeList({
                        type: "group",
                        id: group.id
                    });
                }
                break;

            default:
                console.warn("未识别推送消息: ", type);
                break;
        }
    }

}

export { IMCallBack }