
import axios from 'axios'
import { LINKS } from '../conf/link'

const ERROR_MSG: string = "请求错误, 请稍后重试~";

namespace MessageType {
    export const TYPE_GENERAL = 0;
    export const TYPE_FRIEND_ASK = 1;
    export const TYPE_FRIEND_ASK_REFUSE = 12;
    export const TYPE_FRIEND_BE_REMOVED = 16;
    export const TYPE_GROUP_ASK = 2;
    export const TYPE_GROUP_ASK_REFUSE = 13;
    export const TYPE_GROUP_INVITE = 3;
    export const TYPE_GROUP_INVITE_REFUSE = 15;
    export const TYPE_GROUPMEMBER_LEAVE = 23;
    export const TYPE_GROUPMEMBER_REMOVE = 21;
    export const TYPE_GROUPMEMBER_BE_REMOVED = 22;
}

class MessageService {
    private static instance: MessageService;
    public static getInstance(): MessageService {
        return this.instance || (this.instance = new this());
    }

    public pull(userId: number) {
        return axios({
            method: "POST",
            url: LINKS.message.pull,
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    public getMessage(userId: number, params: any) {
        return axios({
            method: "GET",
            url: LINKS.message.index,
            params
        }).then(resp => {
            let payload = resp.data;
            if (payload.code) {
                throw new Error(payload.msg || ERROR_MSG);
            }
            let { data, msg } = payload;
            data.forEach((i: RespData.NoticeMessage) => i.date *= 1000);
            return data || msg;
        });
    }

    public handleMessage(userId: number, params: any): Promise<string> {
        return axios({
            method: "POST",
            // data: params,
            params,
            url: LINKS.message.index,
        }).then(resp => {
            let data = resp.data;
            let message = data.data || data.msg;
            if (data.code) {
                throw new Error(message || ERROR_MSG);
            }
            return message;
        });
    }
}

export {
    MessageService,
    MessageType
}