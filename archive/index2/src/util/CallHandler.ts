/// <reference path="../typings.d.ts"/>
import layui from 'layui'
import layer from 'layer'
import * as _ from 'lodash/fp'
import * as Immutable from 'immutable'

import '@/style/desktop.css'
import { ICallHandler, CallState, CallStage } from '../util/ICallHandler';
import { CallService, CallType } from '../service/CallService';

import AdapterJS from 'adapterjs';
import { AbsCallWindow, AbsCallWindowBuilder, ICallWindowEvent } from './AbsCallWindow';
import { fp } from './function';
import { GatewayImpl } from './gateway';

interface ConnectEvent {

}

class Connector {
    private static reservePeerConfig: RTCConfiguration = {
        iceServers: [
            { urls: "stun:stun1.l.google.com:19302" },
            { urls: "stun:stun2.l.google.com:19302" },
            // { urls: "stun:stun3.l.google.com:19302" },
            // { urls: "stun:stun4.l.google.com:19302" },
            // {
            //     urls: "stun:stun.5dx.ink:3479",
            //     username: "rtc",
            //     credential: "rtc123",
            //     credentialType: "password"
            // },
            // {
            //     urls: "turn:turn.5dx.ink:3478",
            //     username: "rtc",
            //     credential: "rtc123",
            //     credentialType: "password"
            // },
            // {
            //     urls: "turn:turn.5dx.ink:5349",
            //     username: "rtc",
            //     credential: "rtc123",
            //     credentialType: "password"
            // }
        ]
    }
    private peerConfig: RTCConfiguration = null;
    private connStage: Map<string, CallStage> = new Map();
    private readonly connStageListener: Map<string, Array<Type.BiConsumer<CallStage, string>>> = new Map();
    private pingMap: Map<string, number> = new Map();

    public constructor(event: ConnectEvent) {
        this.peerMap = new Map();
        this.connStageListener.set("all", []);

        if (this.peerConfig == null) {
            CallService.getInstance()
                .getIceServer()
                .then(c => {
                    this.peerConfig = c;
                })
                .catch(e => {
                    this.peerConfig = Connector.reservePeerConfig;
                    console.error(e);
                    layer.alert("获取服务器信息失败, 视频语音通话可能不可用.")
                });
        }
    }

    // 会话通信的 PeerConnection
    private peerMap: Map<string, RTCPeerConnection>;

    public get(sign: string) {
        return this.peerMap.get(sign);
    }

    public has(sign: string) {
        return this.peerMap.has(sign);
    }

    public delete(sign: string) {
        console.log("删除连接: ", sign);
        try {
            // 清除 ping
            window.clearInterval(this.pingMap.get(sign));
            this.pingMap.delete(sign);
            // 关闭 peer
            this.peerMap.get(sign).close()
        } finally {
            return this.peerMap.delete(sign);
        }
    }
    // public set(sign: string, conn: RTCPeerConnection) {
    //     return this.peerMap.set(sign, conn);
    // }

    // public reconnect(sign: string) {

    // }

    public newConn(sign: string) {
        let conn = new RTCPeerConnection(this.peerConfig);
        this.peerMap.set(sign, conn);
        return conn;
    }

    public setConnectionStage(sign: string, stage: CallStage) {
        this.connStage.set(sign, stage);
        console.log("stage: ", sign, stage);
        // 状态监听的回调
        this.connStageListener.get("all").forEach(r => r(stage, sign));
        if (this.connStageListener.get(sign) == null) this.connStageListener.set(sign, []);
        this.connStageListener.get(sign).forEach(r => r(stage, sign));
    }

    public setPing(sign: string, gateway: GatewayImpl) {
        this.pingMap.set(sign, setInterval(() => gateway.send("c"), 6000));
    }

    public listenConnectionStageChange(fn: Type.BiConsumer<CallStage, string>, sign: string = "all"): Type.Runnable {
        let listener = this.connStageListener.get(sign);
        if (listener == null) {
            listener = [];
            this.connStageListener.set(sign, listener);
        }
        let index = listener.push(fn);
        return () => {
            console.log("解除订阅", sign);
            this.connStageListener.get(sign).splice(index - 1, 1);
        };
    }

    public waitForStage(sign: string, ...stage: CallStage[]): Promise<void> {
        console.log("等待 ", stage);

        if (stage.indexOf(this.connStage.get(sign)) >= 0) {
            return Promise.resolve();
        }
        return new Promise((resolve, reject) => {
            let unsubscribe: Type.Runnable = this.listenConnectionStageChange(s => {
                // console.log("新的 stage: ", stage, s);
                if (stage.some(i => i <= s)) {
                    // 取消订阅并结束
                    unsubscribe();
                    resolve();
                }

            }, sign);
        });
    }

}

export class CallHandler implements ICallHandler, ICallWindowEvent {


    // 储存窗口和它的 layer 索引值
    private windowMap: Map<AbsCallWindow, number>;
    private connector: Connector;
    private zIndex: number = 20000000;

    private resource: MediaStream;

    public isSupported: (ctype: CallType) => Promise<boolean>

    // 会话标识和消息标识的对应
    constructor(private user: RespData.UserMessage,
        private layim: layui.Layim,
        private provider: { getCallWindowBuilder: () => AbsCallWindowBuilder, getGateway: () => GatewayImpl }) {
        this.windowMap = new Map();

        this.onComplete = this.onComplete.bind(this);
        this.onInterrupt = this.onInterrupt.bind(this);
        this.onEvent = this.onEvent.bind(this);

        this.isSupported = (() => {

            let supported: Promise<any> = new Promise((resolve, reject) =>
                AdapterJS.webRTCReady((isUsingPlugin: any) =>
                    resolve(isUsingPlugin))
            );

            return (ctype: CallType) => supported.then(async (supported) => {
                console.log("应用 adapterjs: ", supported);
                // 获得媒体流
                if (this.resource == null) {
                    let options = { audio: true, video: true };
                    if (ctype !== "video") options.video = false;
                    try {
                        this.resource = await window.navigator
                            .mediaDevices
                            .getUserMedia(options);
                    } catch (e) {
                        console.error(e);
                        // layer.msg(e.message);
                    }
                }
                return this.resource != null;
            });
        })();
        // 负责维护所有 PeerConnection 的对象
        this.connector = new Connector({});
    }

    public getWindowById(id: string): AbsCallWindow {
        for (let window of this.windowMap.keys()) {
            if (window.id === id) {
                return window;
            }
        }
    }

    public getWindowByBothId(id: number, id2: number, type: "group" | "friend") {
        for (let window of this.windowMap.keys()) {
            if (window.is(id, id2, type)) {
                return window;
            }
        }
        return null;
    }

    public getWindowByChat(data: MessageData.CommunicationMessageData) {
        let w = this.getWindowById(data.sign);
        if (w != null) return w;

        // 获取聊天窗口对象
        let type: Beans.ChatType;
        let otherId: number;
        if (/^\d+$/.test(data.groupid + '')) {
            type = "group";
            otherId = data.groupid;
        } else {
            type = "friend"
            otherId = data.ruserid;
        }
        return this.getWindowByBothId(this.user.id, otherId, type);
    }


    async call(other: RespData.UserMessage | RespData.GroupMessage, callType: CallType) {
        let supported = await this.isSupported(callType);
        if (!supported) {
            layer.alert("您的浏览器尚不支持此操作 !");
            return;
        }
        // 判断客户端是否有通话
        if (this.windowMap.size > 0) {
            layer.msg("您有正在进行的通话");
            return;
        }

        // 初始化窗口
        let window = this.provider
            .getCallWindowBuilder()
            .current(this.user)
            .other(other)
            .event(this)
            .handler(this)
            .zIndex(this.zIndex)
            .build();

        // 发出通话请求
        CallService.getInstance()
            .requestCall(this.user.id, other, callType)
            .then(d => {
                if (d.groupid != null) {
                    window.setDetail(d);
                    // let members = await CallService.getInstance()
                    //     .getCallMembers(this.user.id, d.groupid);
                    // _.chain(members)
                    //     .map(group => group.list)
                    //     .flatten()
                    //     .map(member =>
                    //         _.assign({
                    //             ruserid: member.id,
                    //             rusername: member.username,
                    //             ruseravatar: member.avatar
                    //         }, d))
                    //     .forEach(item => window.setDetail(item));
                } else {
                    window.setDetail(d);
                }
            })
            .catch(e => {
                console.error(e);
                layer.msg(e.message);
                this.onInterrupt(window, e);
            });

        // 呼叫
        window.call()
            .then(i => this.windowMap.set(window, i));
    }

    /**
     * 释放一个连接
     */
    protected finish(w: AbsCallWindow, other: { id: number, name: string }) {
        if (w.id != null) {
            let connector = this.connector;
            if (w.getChatType() !== "friend") {
                // 群聊逐个移除
                let peerSign = w.getSign(other.id);
                connector.setConnectionStage(peerSign, CallStage.FINISH);
                // connector.delete(peerSign);
                // 如果群聊中还有用户, 就不执行下面的关闭窗口代码
                if (!w.leaveTalker(other.id)) {
                    return;
                }
                // 没有成员在聊天了, 自己也退出
                if (other.id != this.user.id) {
                    CallService.getInstance()
                        .pushFinish(this.user.id, w.id);
                }
            } else {
                connector.setConnectionStage(w.id, CallStage.FINISH)
                // connector.delete(w.id);
            }
        }

        // 关闭窗口
        layer.close(this.windowMap.get(w));
        this.windowMap.delete(w);

        // 弹出提示
        layer.alert("与 " + other.name + " 的通话已结束");

        // 关闭资源流
        if (this.resource != null) {
            for (let track of this.resource.getTracks()) {
                track.stop();
            }
            this.resource = null;
        }
    }

    /**
     * 收到连接完成的指示
     * @param data 
     */
    onFinish(data: MessageData.CommunicationMessageData) {
        let w = this.getWindowById(data.sign);
        if (!w) return;
        let otherName: string;
        let senderId: number;

        senderId = data.userid;
        if (w.getChatType() !== "group") {
            otherName = data.username;
        } else {
            otherName = data.groupname;
        }

        this.finish(w, { id: senderId, name: otherName });
    }

    // 语音/视频通话相关的操作
    onRequest(data: MessageData.CommunicationMessageData): Promise<boolean> {
        // console.log(data);
        // 窗口已经存在
        let w = this.getWindowByBothId(this.user.id, data.groupid, "group");
        if (data.groupid != null && w != null) {
            if (w.id == null) {
                w.id = data.sign;
            }
            return;
        }
        // 弹窗
        return new Promise((resolve, reject) => {
            let type: Beans.ChatType = "friend";
            let other: RespData.UserMessage | RespData.GroupMessage;
            if (data.groupid != null) {
                other = {
                    groupname: data.groupname,
                    id: data.groupid,
                    avatar: data.groupavatar,
                    description: '',
                    createtime: null,
                    admin: null,
                    admincount: null,
                    membercount: null,
                };
            } else {
                other = {
                    username: data.username,
                    id: data.userid,
                    avatar: data.useravatar,
                    sign: '',
                    sex: "保密",
                    status: "online"
                };
            }

            let window = this.provider.getCallWindowBuilder()
                .current(this.user)
                .handler(this)
                .other(other)
                .zIndex(this.zIndex)
                .event({
                    onComplete: (window, state) => {
                        // 拒绝会话
                        switch (state) {
                            case CallState.REFUSE:
                                resolve(false);
                                // 关闭窗口
                                layer.close(this.windowMap.get(window));
                                this.windowMap.delete(window);

                                CallService.getInstance()
                                    .requestReply(window.id, false);
                                break;
                            default:
                                // 其他情况
                                this.onComplete(window, state);
                                break;
                        }
                    },
                    onInterrupt: this.onInterrupt,
                    onEvent: async (window, state) => {
                        if (state === CallState.AGREE) {
                            let supported = await this.isSupported(data.ctype);
                            if (!supported) {
                                layer.alert("您的浏览器尚不支持此操作 !");
                                this.onComplete(window);
                                return;
                            }

                            // 接受通话
                            if (data.groupid != null) {
                                // 群聊
                                CallService.getInstance()
                                    .requestCall(this.user.id, other, data.ctype)
                                    .catch(e => {
                                        console.log(e);
                                        layer.msg(e.message);
                                    });
                            } else {
                                // 双人
                                CallService.getInstance()
                                    .requestReply(data.sign, true)
                                    .catch(e => {
                                        console.log(e);
                                        layer.msg(e.message);
                                    });
                            }

                            // 关闭其他窗口
                            let closeWindow = (e: ICallWindowEvent, w: AbsCallWindow,
                                wMap: Immutable.Map<AbsCallWindow, number>) =>
                                wMap.forEach((v, k) => k !== w && e.onComplete(k));
                            // 删除其他窗口对象
                            let removeWindow = (w: AbsCallWindow,
                                wMap: Immutable.Map<AbsCallWindow, number>) =>
                                wMap.filter((v, k) => k === w);
                            // 关闭并移除对象
                            let removeAndCloseWindow = fp.fork(
                                v => v,
                                _.partial(removeWindow, [window]),
                                _.partial(closeWindow, [this, window]));
                            // 调用并更新
                            this.windowMap = new Map(
                                removeAndCloseWindow(
                                    Immutable.Map(this.windowMap.entries()))
                                    .entries());
                            console.log(this.windowMap)
                        }
                        this.onEvent(window, state);
                    }
                })
                .build();
            window.setDetail(data);

            // 通知
            window.ring().then(i => this.windowMap.set(window, i));
        });
    }

    async onRequestDescription(data: MessageData.CommunicationMessageData): Promise<void> {
        // 获得聊天窗口对象
        let w = this.getWindowById(data.sign);
        if (w == null) {
            console.error("不存在的会话.", data.sign);
            return;
        }
        w.setDetail(data);

        // 构建 PeerConnection 对象
        let peerSign: string = w.getSign(data);
        let connector = this.connector;
        if (connector.has(peerSign)) {
            console.error("重复请求 offer ! ", peerSign);
            return;
        }
        let conn = this.initPeerConnection(peerSign, w);

        // 构建 offer 并传递
        let offer = await conn.createOffer();
        conn.setLocalDescription(offer);
        console.log("发送 desc", offer);

        // 加入重试机制
        let count = 9;
        // 响应完成
        let retry = async () => {
            let connector = this.connector;

            // 发送描述到远程
            try {
                if (w.getChatType() !== "group") {
                    await CallService.getInstance()
                        .pushDescription(this.user.id, data.sign, offer);
                } else {
                    let otherId: number = w.getOtherId();
                    await CallService.getInstance()
                        .pushDescription(this.user.id, { [otherId]: [{ userid: data.userid, description: offer }] });
                }
                connector.setConnectionStage(peerSign, CallStage.OFFER);
            } catch (e) {
                console.error(e);
                layer.msg(e.message);
                console.debug("请求失败, 尝试重试: ", count);
                if (count-- >= 0) window.setTimeout(retry, 1500);
            }
        }
        retry();
    }

    async onResponseDescription(data: { description: RTCSessionDescriptionInit } & MessageData.CommunicationMessageData): Promise<void> {
        let success: boolean = false;
        // 获取聊天窗口对象
        let w = this.getWindowById(data.sign);
        if (w == null) {
            console.error("不存在的会话.", data.sign);
            console.log("已有会话", [...this.windowMap.keys()].map(m => m.id));
            return;
        }
        w.setDetail(data);

        // 获取聊天对等连接 id
        let peerSign: string = w.getSign(data);
        let connector = this.connector;
        try {
            // 获取对等连接对象
            let isNew = false;
            let conn: RTCPeerConnection = null;
            conn = connector.get(peerSign) || (isNew = true, this.initPeerConnection(peerSign, w));

            if (isNew) {
                // 设置远程后发送答复信息
                conn.setRemoteDescription(data.description);
                let answer: RTCSessionDescriptionInit = await conn.createAnswer();
                conn.setLocalDescription(answer);
                console.log("回复 desc", answer);

                try {
                    if (w.getChatType() !== "group") {
                        await CallService.getInstance()
                            .pushDescription(this.user.id, data.sign, answer);
                    } else {
                        await CallService.getInstance()
                            .pushDescription(this.user.id, { [w.getOtherId()]: [{ userid: data.userid, description: answer }] });
                    }
                    connector.setConnectionStage(peerSign, CallStage.ANSWER);
                } catch (e) {
                    console.error(e);
                    layer.msg(e.message);
                }
            } else {
                console.log("接收 desc", data.description);
                conn.setRemoteDescription(data.description);
                connector.setConnectionStage(peerSign, CallStage.ANSWER);
            }

            w.connect(conn);
            success = true;
        } catch (e) {
            layer.msg("连接失败");
            console.error(e);
        }

        CallService.getInstance()
            .pushComplete(this.user.id, data.sign, success)
            .then(() => this.connector.setConnectionStage(peerSign, CallStage.COMPLETION))
            .catch(e => {
                layer.msg(e.message);
                console.error(e);
            });
    }

    async onResponseCandidate(data: { candidate: RTCIceCandidateInit | RTCIceCandidate } & MessageData.CommunicationMessageData) {
        // console.log("等待 offer/anwser");
        // 获取聊天窗口对象
        let w = this.getWindowById(data.sign);
        if (w == null) {
            console.info("会话 " + data.sign + " 已结束");
            return;
        }
        w.setDetail(data);

        let peerSign = w.getSign(data);
        await this.connector.waitForStage(peerSign, CallStage.ANSWER);
        // console.log("收到 offer/anwser");

        // 获取聊天对等连接对象
        let conn: RTCPeerConnection = this.connector.get(peerSign);
        if (conn == null || w == null) {
            // layer.msg("不存在的会话");
            console.error("不存在的会话: " + data.sign);
            return;
        }
        try {
            conn.addIceCandidate(data.candidate);
        } catch (e) {
            layer.msg(e.message);
            console.error(e);
        }
    }

    onReconnect(data: { connectid: string } & MessageData.CommunicationMessageData) {
        let w = this.getWindowById(data.sign);
        if (w == null) {
            console.info("会话不存在 " + data.sign);
            return;
        }
        let otherId = data.userid !== this.user.id ? data.userid : data.ruserid;
        let peerSign = w.getSign(otherId);

        // 重置连接对象
        this.connector.delete(peerSign);
        this.initPeerConnection(peerSign, w);
        this.connector.setConnectionStage(peerSign, CallStage.CALL);

        // 发送重新连接请求
        CallService.getInstance()
            .requesetReconnect(this.user.id, otherId, data.sign, data.connectid);
    }

    onInterrupt(window: AbsCallWindow, e?: string): void;
    onInterrupt(window: AbsCallWindow, e?: Error): void;
    onInterrupt(window: AbsCallWindow, e?: Error | string): void {
        let errMsg: string = typeof e !== "string"
            ? e.message
            : e;

        layer.msg(errMsg);
        // 关闭窗口
        layer.close(this.windowMap.get(window));
        this.windowMap.delete(window);
        // 推送失败请求
        CallService.getInstance()
            .pushFinish(this.user.id, window.id, errMsg)
            .catch(e => {
                layer.msg(e.message);
                console.error(e);
            });

        // 关闭传输流
        // 将所有的 id 设置为完成
        if (window.id != null) {
            if (window.getChatType() !== "friend") {
                window.getTalkerIds()
                    .map(m => window.getSign(m))
                    .forEach(m => this.connector.setConnectionStage(m, CallStage.FINISH));
            } else {
                this.connector.setConnectionStage(window.id, CallStage.FINISH)
            }
        }

        // 关闭资源流
        if (this.resource != null) {
            for (let track of this.resource.getTracks()) {
                track.stop();
            }
            this.resource = null;
        }
    }

    onComplete(w: AbsCallWindow, state?: CallState): void {
        layer.close(this.windowMap.get(w));
        this.windowMap.delete(w);

        CallService.getInstance()
            .pushFinish(this.user.id, w.id)
            .then(() => {
                if (w.id != null) {
                    if (w.getChatType() !== "friend") {
                        w.getTalkerIds()
                            .map(m => w.getSign(m))
                            .forEach(m => this.connector.setConnectionStage(m, CallStage.FINISH));
                    } else {
                        this.connector.setConnectionStage(w.id, CallStage.FINISH)
                    }
                }
                layer.alert("通话结束");
            })
            .catch(e => {
                layer.msg(e.message);
                console.error(e);
            });
        // 关闭资源流
        if (this.resource != null) {
            for (let track of this.resource.getTracks()) {
                track.stop();
            }
            this.resource = null;
        }
    }

    onEvent(window: AbsCallWindow, state: CallState, ...args: any[]): void {

    }

    private initPeerConnection(sign: string, w: AbsCallWindow): RTCPeerConnection {
        let conn: RTCPeerConnection = this.connector.newConn(sign);
        conn.onconnectionstatechange = (e: Event) => {
            console.log("连接状态改变: ", conn.connectionState);
            switch (conn.connectionState) {
                case "failed":
                    let talker = w.getTalker(sign);
                    // 重连请求
                    CallService.getInstance()
                        .requesetReconnect(this.user.id, talker.id, w.id)
                        .then(() => console.log(`重连 ${w.id} 中 ${talker.id}`));
                    break;
                case "connected":
                    console.log("通话连接成功");
                    break;
            }
        };

        conn.onicecandidate = (e: RTCPeerConnectionIceEvent) => {
            if (e.candidate != null) {
                if (w.getChatType() !== "friend") {
                    let match = /-(\d+)$/.exec(sign);
                    if (match == null || match.length < 2) {
                        // layer.msg("")
                        console.error("错误的群聊 sign: ", sign);
                        return;
                    }

                    CallService.getInstance()
                        .pushCandidate(this.user.id, {
                            [w.getOtherId()]: [{
                                userid: Number.parseInt(match[1]),
                                ice: e.candidate
                            }]
                        });
                } else {
                    CallService.getInstance()
                        .pushCandidate(this.user.id, w.id, e.candidate);
                }
            }
        };

        conn.ontrack = e => w.updateState({ track: e.track });

        // 完成时关闭 peer
        let unsubscribe = this.connector.listenConnectionStageChange((s, sign) => {

            if (s >= CallStage.FINISH) {
                // 关闭和移除 peerConn
                this.connector.delete(sign);
                // 取消订阅
                unsubscribe();
            } else if (s === CallStage.COMPLETION) { // 连接完成时的 ping
                this.connector.setPing(sign, this.provider.getGateway());
            }
        }, sign);

        // 添加媒体流
        for (let track of this.resource.getTracks()) {
            conn.addTrack(track);
        }
        return conn;
    }
}
