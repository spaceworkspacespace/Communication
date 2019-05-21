import axios from 'axios'
import { LINKS } from '../conf/link';
import layui from 'layui';
import { App } from '../App';

const ERROR_MSG: string = "请求错误, 请稍后重试~";

class ChatService {
    private static instance: ChatService;
    // 保存所有本地聊天记录
    private ms: RespData.ChatMessage[];


    public static getInstance(): ChatService {
        return this.instance || (this.instance = new this());
    }

    public deleteLocalMessage(userId: number, msgId: number, localMsgKey: string) {
        // 获取缓存
        let local: layui.LayimLocalData;
        if (App.runClass != "mobile") {
            local = layui.data("layim")[userId];
        } else {
            local = layui.data('layim-mobile')[userId];
        }
        if (!local) {
            local = { chatlog: {}, history: {} };
        }


        if (!local.chatlog[localMsgKey]) local.chatlog[localMsgKey] = [];
        // 更新缓存数据
        // 通过消息 id 删除指定的消息
        for (let i = local.chatlog[localMsgKey].length - 1; i >= 0; i--) {
            let c = local.chatlog[localMsgKey][i];
            // 找到消息, 执行删除操作
            if (c.cid == msgId) {
                local.chatlog[localMsgKey].splice(i, 1);
                break;
            }
        }

        // 设置缓存
        if (App.runClass != "mobile") {
            layui.data("layim", {
                key: userId,
                value: local
            });
        } else {
            layui.data('layim-mobile', {
                key: userId,
                value: local
            });
        }
    }

    public deleteMessage(userId: number, params: { cid: number, type: string }): Promise<string> {
        return axios({
            method: "DELETE",
            url: LINKS.chat.message,
            data: params
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    public feedback(userId: number, sign: string): Promise<string> {
        return axios({
            method: "POST",
            url: LINKS.chat.feedback,
            data: { sign }
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    public getMessage(userId: number, params: {
        type?: "friend" | "group",
        id?: number,
        no?: number,
        count?: number
    } = {}): Promise<RespData.ChatMessage[]> {
        return axios({
            method: "GET",
            url: LINKS.chat.message,
            params: params
        }).then(resp => {
            let payload = resp.data;
            if (payload.code) {
                throw new Error(payload.msg || ERROR_MSG);
            }
            let { data, msg } = payload;
            data.forEach((i: RespData.ChatMessage) => i.date *= 1000);
            return data || msg;
        });
    }

    /**
     * 判断本地是否有此消息
     * @param userId 
     * @param message 
     * @returns boolean 消息是否存在, true 存在, false 不存在
     */
    public hasLocalMessage(userId: number, message: RespData.ChatMessage): boolean {
        // console.log(this.ms);
        // this.ms.sort((i, r) => i.date > r.date ? 1 : -1);
        
        for (let i = this.ms.length - 1; i >= 0; i--) {
            let m: RespData.ChatMessage = this.ms[i];
            // console.log(m, message)
            if (m.date < message.date) {
                return false;
            }
            if (m.cid == message.cid) {
                return true;
            }
            // console.log(m, message)
        }
    }

    /**
    * 加入一条记录到本地聊天缓存
    * @param userId 
    * @param m 
    */
    public pushLocalMessage(userId: number, m: RespData.ChatMessage) {
        let local;
        if (App.runClass != "mobile") {
            local = layui.data("layim")[userId];
        } else {
            local = layui.data('layim-mobile')[userId];
        }
        if (!local) local = { chatlog: {}, history: {} };
        // local.chatlog = {};

        this.ms.push(m);

        let index: string;
        let isGroup: boolean;
        if (m.gid != null) {
            index = "group" + m.gid;
            isGroup = true;
        } else {
            if (m.uid == userId) {
                index = "friend" + m.tid;
            } else {
                index = "friend" + m.uid;
            }
        }

        if (!local.chatlog[index]) local.chatlog[index] = [];
        // 插入新的本地记录
        local.chatlog[index].push({
            avatar: m.avatar,
            username: m.username,
            id: m.uid,
            fromid: m.uid,
            mine: m.uid == userId,
            content: m.content,
            cid: m.cid,
            timestamp: m.date,
            type: isGroup ? "group" : "friend"
        });

        if (App.runClass != "mobile") {
            layui.data("layim", {
                key: userId,
                value: local
            });
        } else {
            layui.data('layim-mobile', {
                key: userId,
                value: local
            });
        }
    }

    /**
     * 重新设置本地聊天缓存
     * @param userId 
     * @param message 
     */
    public setLocalMessage(userId: number, message: RespData.ChatMessage[]) {
        let local;
        if (App.runClass != "mobile") {
            local = layui.data("layim")[userId];
        } else {
            local = layui.data('layim-mobile')[userId];
        }
        if (!local) local = { chatlog: {}, history: {} };
        local.chatlog = {};
        message.sort((l, r) => (l.date > r.date ? 1 : -1));
        // 保存
        this.ms = message;
        for (let m of message) {
            let index: string;
            let isGroup: boolean;
            if (m.gid != null) {
                index = "group" + m.gid;
                isGroup = true;
            } else {
                if (m.uid == userId) {
                    index = "friend" + m.tid;
                } else {
                    index = "friend" + m.uid;
                }
            }

            if (!local.chatlog[index]) local.chatlog[index] = [];
            // 插入新的本地记录
            local.chatlog[index].push({
                avatar: m.avatar,
                username: m.username,
                id: m.uid,
                fromid: m.uid,
                mine: m.uid == userId,
                content: m.content,
                cid: m.cid,
                timestamp: m.date,
                type: isGroup ? "group" : "friend"
            });
        }
        if (App.runClass != "mobile") {
            layui.data("layim", {
                key: userId,
                value: local
            });
        } else {
            layui.data('layim-mobile', {
                key: userId,
                value: local
            });
        }
    }

    public sendMessage(userId: number, params: any): Promise<RespData.ChatMessage> {
        return axios({
            method: "POST",
            url: LINKS.chat.message,
            data: params
        }).then(resp => {
            let data = resp.data;
            if (data.code || typeof data == "string") {
                throw new Error(data.msg || ERROR_MSG);
            }
            data.data.date = data.data.date * 1000;

            this.ms.push(data.data);
            return data.data || data.msg;
        });
    }

    public updateLocalMessage(userId: number, m: RespData.ChatMessage) {
        // 获取缓存
        let local;
        if (App.runClass != "mobile") {
            local = layui.data("layim")[userId];
        } else {
            local = layui.data('layim-mobile')[userId];
        }
        if (!local) {
            local = { chatlog: {}, history: {} };
        }

        // 获取索引
        let index: string;
        let isGroup: boolean;
        if (m.gid != null) {
            index = "group" + m.gid;
            isGroup = true;
        } else {
            if (m.uid == userId) {
                index = "friend" + m.tid;
            } else {
                index = "friend" + m.uid;
            }
        }

        if (!local.chatlog[index]) local.chatlog[index] = [];
        // 更新缓存数据
        // 这里通过时间更新 id
        local.chatlog[index].sort((l, r) => l.timestamp > r.timestamp ? 1 : -1)
        for (let c of local.chatlog[index]) {
            // 找到消息, 更新其 id
            if (m.gid == userId // 是自己发送的
                && c.mine
                && !c.cid // 消息没有 id
                && c.content == m.content) { // 内容相等
                c.cid = m.cid;
            }
        }

        // 设置缓存
        if (App.runClass != "mobile") {
            layui.data("layim", {
                key: userId,
                value: local
            });
        } else {
            layui.data('layim-mobile', {
                key: userId,
                value: local
            });
        }
    }
}

export {
    ChatService
}