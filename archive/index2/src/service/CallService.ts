import Axios from "axios";
import { LINKS } from "../conf/link";
import { err_retry } from "../util/function";

const ERROR_MSG: string = "请求错误, 请稍后重试~";

export type CallType = "video" | "voice";

class CallService {
    private static _instance: CallService;

    public static getInstance(): CallService {
        return this._instance || (this._instance = new CallService());
    }

    public getCallMembers(userId: number, groupId: number): Promise<RespData.GroupMessage[]> {
        return Axios({
            method: "GET",
            url: LINKS.chat.callMembers,
            params: {
                groupId
            }
        }).then(resp => {
            let data: RespData.GeneralMessage = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    public async getIceServer(): Promise<RTCConfiguration> {
        return err_retry(() => {
            return Axios({
                method: "GET",
                url: LINKS.chat.iceServer
            }).then(resp => {
                let data: RespData.GeneralMessage = resp.data;
                if (data.code) {
                    throw new Error(data.msg || ERROR_MSG);
                }
                return data.data || data.msg;
            });
        });
    }

    /**
     * 请求通话
     * @param userId 当前用户 id
     * @param call 对方
     * @param callType 通话类型
     */
    public requestCall(
        userId: number,
        call: (RespData.UserMessage | RespData.GroupMessage) & Object,
        callType: "video" | "voice"): Promise<MessageData.CommunicationMessageData> {
        let chatType: "group" | "friend";
        // 确定当前进行的聊天类型
        if (call.hasOwnProperty("username")) {
            chatType = "friend";
        } else {
            chatType = "group";
        }

        return Axios({
            method: "POST",
            url: LINKS.chat.call,
            data: {
                type: callType,
                id: call.id,
                chatType
            }
        }).then(resp => {
            let data: RespData.GeneralMessage = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    public requesetReconnect(userId: number, otherId: number, sign: string, connectid: string = '') {
        return Axios({
            url: LINKS.chat.callReconnect,
            method: "POST",
            data: {
                userId: otherId,
                sign,
                connectid
            }
        }).then(resp => {
            let data: RespData.GeneralMessage = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    public requestReply(sign: string, consent: boolean) {
        console.log(sign)
        return Axios({
            method: "POST",
            url: LINKS.chat.call,
            data: {
                stage: "reply",
                sign,
                replay: consent,
                reply: consent
            }
        }).then(resp => {
            let data: RespData.GeneralMessage = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    public pushDescription(userId: number, desc: { [gid: number]: Array<{ userid: number, description: RTCSessionDescriptionInit }> }): Promise<string>
    public pushDescription(userId: number, sign: string, description: RTCSessionDescriptionInit): Promise<string>
    public pushDescription(userId: number, p: any, desc?: RTCSessionDescriptionInit): Promise<string> {
        let data;
        if (desc != null) {
            data = {
                stage: "exchange",
                sign: p,
                description: window.btoa(JSON.stringify(desc))
            };
        } else {
            for (let prop in p) {
                for (let desc of p[prop]) {
                    desc.description = window.btoa(JSON.stringify(desc.description));
                }
            }
            data = {
                stage: "exchange",
                call: p
            };
        }
        return Axios({
            method: "POST",
            url: LINKS.chat.call,
            data
        }).then(resp => {
            let data: RespData.GeneralMessage = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    public pushComplete(userId: number, sign: string, success: boolean) {
        return Axios({
            method: "POST",
            url: LINKS.chat.call,
            data: {
                stage: "complete",
                sign,
                success
            }
        }).then(resp => {
            let data: RespData.GeneralMessage = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    public pushFinish(userId: number, sign: string, errMsg?: string) {
        return Axios({
            method: "POST",
            url: LINKS.chat.call,
            data: {
                stage: "finish",
                sign,
                error: typeof errMsg === "string" ? true : false,
                errmsg: errMsg
            }
        }).then(resp => {
            let data: RespData.GeneralMessage = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    public pushCandidate(userId: number, sign: string, candidate: RTCIceCandidate): Promise<string>
    public pushCandidate(userId: number, ice: { [gid: number]: Array<{ userid: number, ice: RTCIceCandidate }> }): Promise<string>
    public pushCandidate(userId: number, p: any, candidate?: RTCIceCandidate) {
        let data;
        if (candidate != null) {
            data = {
                stage: "exchange-ice",
                sign: p,
                ice: window.btoa(JSON.stringify(candidate))
            };
        } else {
            for (let prop in p) {
                for (let desc of p[prop]) {
                    desc.ice = window.btoa(JSON.stringify(desc.ice));
                }
            }
            data = {
                stage: "exchange-ice",
                call: p
            };
        }

        return Axios({
            method: "POST",
            url: LINKS.chat.call,
            data
        }).then(resp => {
            let data: RespData.GeneralMessage = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }
}

export {
    CallService
}