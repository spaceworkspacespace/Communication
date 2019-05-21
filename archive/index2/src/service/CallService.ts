import Axios from "axios";

const ERROR_MSG: string = "请求错误, 请稍后重试~";

export type CallType = "video" | "voice";

class CallService {
    private static _instance: CallService;

    public static getInstance(): CallService {
        return this._instance;
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
        callType: "video" | "voice"): Promise<string> {
        let chatType: "group" | "friend";
        // 确定当前进行的聊天类型
        if (call.hasOwnProperty("groupname")) {
            chatType = "group";
        } else {
            chatType = "friend";
        }

        return Axios({
            method: "POST",
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
} 

export {
    CallService
}