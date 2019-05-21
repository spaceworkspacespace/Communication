

// import QuickEncrypt from 'quick-encrypt';
// import { encryptPublicLong, decryptPrivateLong } from '@lsqswl/rsaencrypt';
// import JSEncrypt from 'jsencrypt';

import * as jQuery from 'jquery';
// import { RSAUtils } from './crypto';
import { AESUtils } from './crypto'

interface IGateway {
    onclose: (this: GatewayImpl, event: CloseEvent) => any;
    onerror: (this: GatewayImpl, event: Event) => any;
    onmessage: (this: GatewayImpl, event: MessageEvent) => any;
    onopen: (this: GatewayImpl, event: Event) => any;
    // 使用 ajax 发送 http 访问
    ajax(settings: JQuery.AjaxSettings): Promise<JQuery.jqXHR>;
    resetHeartbeat(): void;
    // 发送数据
    send(data: string | ArrayBufferLike | Blob | ArrayBufferView): void;
}

namespace GatewayMessage {
    export type Send = "SEND";
    export type Ask = "ASK";
    export type Update = "UPDATE";
    export type Connected = "CONNECTED";
    export type Add = "ADD";
    export const SEND: Send = "SEND";
    export const ASK: Ask = "ASK";
    export const UPDATE: Update = "UPDATE";
    export const CONNECTED: Connected = "CONNECTED";
    export const ADD: Add = "ADD";
    export const REMOVE_FRIEND = 16;
    export const REMOVE_GROUP = 22;
    export const ONLINE = "ONLINE";
    export const COMMUNICATION_ASK = "COMMUNICATION-ASK";
    export const COMMUNICATION_EXCHANGE = "COMMUNICATION-EXCHANGE";
    export const COMMUNICATION_COMMAND = "COMMUNICATION-COMMAND";
    export const COMMUNICATION_MEMBER = "COMMUNICATION-MEMBER";
    export const COMMUNICATION_FINISH = "COMMUNICATION-FINISH";

    export type MessageType = Send | Ask | Update | Connected | Add;

    export interface MessagePayload {
        id?: string;
        type: MessageType;
        data?: any;
    }

    export interface UpdateMessagePayload extends MessagePayload {
        type: Update;
        data: {
            [key: string]: string // 对应的 keyId: key 的键值对.
        }
    }

    export interface CallAskMessagePayload {
        sign: string, // 通信的标识
        ctype: "video" | "voice", // 通信的类型
        userid: number, // 请求者的 id
        username: string, // 请求者的名称
        useravatar: string, // 请求者的名称
        // 如果是来自群聊的通信, 补充下列信息
        groupid?: number, // 群聊的 id
        groupname?: string, // 群聊的名称
        groupavatar?: string, // 群聊的图像
        // 接收者信息
        ruserid: number, // 接收者的 id,
        rusername: string,
        ruseravatar: string,
    }
}

interface IIM {
    onxmessage: (data: GatewayMessage.MessagePayload) => any;
    onxask: (data: GatewayMessage.MessagePayload) => any;
    onxadd: (data: GatewayMessage.MessagePayload) => any;
    onxconnected: (data: GatewayMessage.MessagePayload) => any;
    // onxupdate: (data: any) => any;
}

interface GatewaySettings {
    url: string, // websocket 连接的 url.
    interval: number, // 心跳间隔, 毫秒
    pingData?: string, // 心跳数据
    $?: JQueryStatic, // jQuery 对象
    keys?: { [k: string]: string }, // 公钥, 设置了将会用其加密消息
}

// 用来加密解密的默认公钥
// 多个 GatewayImpl 对象可能共用这两个对象.
let keys: { [key: string]: { [key: string]: string } } = {};
// 加密解密的对象
// let jsEncrypt = new JSEncrypt();

class GatewayImpl implements IGateway, IIM {
    private _id: string; // 对象的唯一标识.
    private _url: string;
    private _pingData: string;
    private _interval: number;
    private _socket: WebSocket;
    private _jquery: JQueryStatic;
    private _beat: number;
    private _offline: boolean = false;

    public onclose: (this: GatewayImpl, event: CloseEvent) => any;
    public onerror: (this: GatewayImpl, event: Event) => any;
    public onmessage: (this: GatewayImpl, event: MessageEvent) => any;
    public onopen: (this: GatewayImpl, event: Event) => any;
    public onxmessage: (data: any) => any;
    public onxask: (data: any) => any;
    public onxadd: (data: any) => any;
    public onxconnected: (data: GatewayMessage.MessagePayload) => any;
    public onxfeedback: (sign: string) => any;
    public onxreconnection: (this: GatewayImpl) => any;
    public onOffline: () => void;
    /**
     * 懒得起那么多类型的事件属性了, 新的属性都放这里面吧.
     * @param type 消息类型
     * @param data 消息数据
     * @param event 原事件
     */
    public onsockmessage: (type: string | number, data: any, event: MessageEvent) => any;

    constructor(config: GatewaySettings) {
        if (this && this.constructor !== GatewayImpl) return new GatewayImpl(config);
        else if (!new.target) return new GatewayImpl(config);

        this._pingData = config.pingData || "ping";
        this._interval = config.interval * 1000;
        this._url = config.url;
        this._jquery = config.$ || jQuery;
        this._socket = new WebSocket(config.url);
        this._id = "GatewayImpl_" + Math.random().toString().slice(-10);
        keys[this._id] = config.keys;

        this.resetHeartbeat();
        this._initSocket();

        // document.addEventListener("visibilitychange", e => {
        //     if (document.visibilityState !== "visible" || document.hidden) {
        //         this._isEnd = true;
        //         clearInterval(this._beat);
        //         this._socket.close();
        //         this._beat = null;
        //     } else {
        //         window.location.href = window.location.href;
        //     }
        // console.log(document.hidden)
        // if (document.visibilityState !== "visible" || document.hidden) {
        // if (document.hidden || document.visibilityState === "hidden") {
        // console.log("关闭 ws")
        // document.write("<p>" + document.hidden + "</p>")
        // 关闭 ws
        // this._socket.close();
        // 清除心跳发送
        // clearInterval(this._beat);
        // this._beat = null;
        // } else {
        // console.log("重连 ws")
        // 尝试重新连接
        // setTimeout(() => this._tryReConnection().then(() => this.resetHeartbeat()), 1500);
        // }
        // });

        // 网络连接判断, 保证及时重连
        window.addEventListener("online", (e: Event) => {
            this._offline = false;
            this._tryReConnection();
            this.resetHeartbeat();
        });
        document.addEventListener("visibilitychange", (e: Event) => {
            if (document.visibilityState === "visible" || !document.hidden) {
                this._tryReConnection();
            }
        });
        window.addEventListener("offline", () => {
            this._offline = true;
            window.clearInterval(this._beat);
            this._beat = null;
        });
    }

    /**
     * 尝试重新连接 (如果断开)
     * @param maxTime 最大重连时间 0 为不限制, 单位为毫秒
     */
    private async _tryReConnection(maxTime: number = 0): Promise<null> {
        if (this._offline) {
            this.onOffline();
        }
        // 连接不可用
        if (this._socket.readyState !== 1) {
            let startTime = Date.now();
            // 尝试重连
            this._socket = await new Promise<WebSocket>((resolve, reject) => {
                const _connection = async () => {
                    // 重连的事件调用
                    if (this.onxreconnection) {
                        this.onxreconnection();
                    }

                    // 最大时间限制
                    if (maxTime > 0 && startTime + maxTime <= Date.now()) {
                        reject(new Error("WebSocket 重连超时."));
                    }

                    try {
                        let socket = new WebSocket(this._url);

                        // 等到连接可用
                        await new Promise<void>((resolve, reject) => {
                            socket.onopen = () => resolve();
                            socket.onclose = () => reject("连接已关闭.");
                            socket.onerror = () => reject("连接错误.");
                        });
                        resolve(socket);
                    } catch (e) {
                        console.error(e);
                        window.setTimeout(() => _connection(), 3000);
                    }
                }

                _connection();
            });
            this._initSocket();
            this.onopen(new CustomEvent("open"));
        }
        return null;
    }

    private _initSocket() {
        this._socket.onmessage = (event: MessageEvent) => {
            try {
                // console.log(event.data)
                if (!event.data) return;

                let frame: any = JSON.parse(event.data);
                // console.log(frame, keys[this._id]);
                let msg: GatewayMessage.MessagePayload;
                // 判断是否为加密数据
                if (typeof frame.payload !== "string") { // 不是加密数据, 没有加密负载.
                    msg = frame as GatewayMessage.MessagePayload;
                } else { // 是加密数据, 进行解密操作
                    let key = keys[this._id][frame.id];
                    // console.log(keys, key, frame.payload);
                    if (!key) return;
                    msg = JSON.parse(this.decrypt(frame.payload, keys[this._id][frame.id])) as GatewayMessage.MessagePayload;
                }
                // console.log(msg.type, msg.data)
                // 根据不同的消息类型分别处理.
                switch (msg.type) {
                    case GatewayMessage.SEND:
                        if (this.onxmessage) this.onxmessage(msg.data);
                        break;
                    case GatewayMessage.ASK:
                        if (this.onxask) this.onxask(msg.data);
                        break;
                    case GatewayMessage.UPDATE:
                        this.setKeys((msg as GatewayMessage.UpdateMessagePayload).data);
                        break;
                    case GatewayMessage.CONNECTED:
                        if (this.onxconnected) this.onxconnected(msg);
                        break;
                    case GatewayMessage.ADD:
                        if (this.onxadd) this.onxadd(msg.data);
                        break;
                    default:
                        if (this.onsockmessage) this.onsockmessage(msg.type, msg.data, event);
                        break;
                }
                // 如果有 sign 字段, 调用回调
                if (frame.sign && this.onxfeedback) this.onxfeedback(frame.sign);
            } catch (e) {
                console.error(e);
            } finally {
                // 调用原 message 事件
                return this.onmessage && this.onmessage(event);
            }
        }
        this._socket.onopen = (event: Event) => {
            return this.onopen && this.onopen(event);
        }

        this._socket.onclose = (event: CloseEvent) => {
            console.log(event);
            if (this.onclose) {
                this.onclose(event);
            }

            // else {
            //     this._tryReConnection();
            // }
        }

        // this._socket.onerror = function (event) {
        //     console.error(event);
        //     return this.onerror && this.onerror(event);
        // }
    }

    // 发送心跳
    private _sendHeartbeat(): Promise<void> {
        try {
            return this._tryReConnection().then(() => this._socket.send(this._pingData));
        } catch (e) {
            // this._socket = new WebSocket(this._url);
            console.error(e);
            // this._tryReConnection();
        }
    }

    /**
     * 检查连接是否可用, 并在不可用时重连
     */
    public checkAvailable(): Promise<null> {
        return this._tryReConnection();
    }

    // 重置心跳
    public resetHeartbeat(): void {
        this._beat && clearInterval(this._beat);
        this._beat = window.setInterval(() => this._sendHeartbeat(), this._interval);
    }

    /**
     * 发送 ajax 请求
     * @param settings 请求参数
     * @param crypto 是否加密 body.
     */
    public async ajax(settings: JQuery.AjaxSettings, crypto: boolean = true): Promise<JQuery.jqXHR> {
        await this._tryReConnection();
        // 如果加密选项开启, 就将 body 加密并添加 "x-gateway-encrypt" 头
        if (crypto && keys[this._id]["default"]) {
            if (!settings.headers) settings.headers = {};
            settings.headers["x-gateway-encrypt"] = "true";
            if (typeof settings.data !== "string") {
                settings.headers["Content-Type"] = "application/x-www-form-urlencoded";
                settings.data = jQuery.param(settings.data);
            }
            settings.data = this.encrypt(settings.data, keys[this._id]["default"]);
        }
        return this._jquery.ajax(settings);
    }

    public send(data: string | ArrayBufferLike | Blob | ArrayBufferView): void {
        this.resetHeartbeat();
        this._tryReConnection().then(() => this._socket.send(data));
    }

    public setKey(id: string, pk: string): void {
        if (!keys[this._id]) keys[this._id] = {};
        keys[this._id][id] = pk;
    }

    public setKeys(argKeys: { [k: string]: string }) {
        keys[this._id] = argKeys;
    }

    /**
     * 解密密文
     * @param text 密文, 为 base64 编码
     * @param pk public key, 如果不提供将会使用先前设置的值
     * @returns string 普通字符串.
     */
    public decrypt(text: string, key: string): string {
        // jsEncrypt.setPublicKey(pk || publicKeys[this._id]);
        // let decrypted = [];

        // return jsEncrypt.decrypt(text);
        // return encryptPublicLong(text, pk);
        // return RSAUtils.decrypt(text, pk || publicKeys[this._id]);
        // text = atob(text);
        // console.log(text, key);
        return AESUtils.decrypt(atob(text), key);
    }

    /**
     * 加密明文
     * @param text 明文, 为普通字符串
     * @param pk public key, 如果不提供将会使用先前设置的值
     * @returns string base64 编码的密文.
     */
    public encrypt(text: string, key: string): string {
        // jsEncrypt.setPublicKey(pk || publicKeys[this._id]);

        // return jsEncrypt.encrypt(text);
        // return QuickEncrypt.decrypt(text, pk);
        // return RSAUtils.encrypt(text, pk || publicKeys[this._id]);
        return btoa(AESUtils.encrypt(text, key));
    }
}

export {
    GatewayImpl,
    GatewayMessage
}