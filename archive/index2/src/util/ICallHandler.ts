import { CallType } from "../service/CallService";
import { GatewayMessage } from "./gateway";
import { namespace } from "d3";

// 通话中的一些时间
export enum CallState {
    // 对方拒绝
    IS_REFUSED = 1,
    // 拒绝对方
    REFUSE = 2,
    // 对方同意
    IS_AGREED = 3,
    // 同意对方
    AGREE = 4,
}
// 通话中的步骤
export enum CallStage {
    CALL, // 呼叫
    REPLY, // 回复
    JOIN, // 加入
    OFFER, // offer
    ANSWER, // answer
    CANDIDATE, // ...
    CONNECTION, // 连接成功
    COMPLETION, // 连接完成
    FINISH // 通话完成/结束
}


/**
 * 视屏或语音通话的一些事件
 */
export interface ICallHandler {
    /**
     * 发起一个双人通话
     * @param other 
     * @param callType 
     */
    call(other: RespData.UserMessage | RespData.GroupMessage, callType: CallType): void;

    /**
     * 加入一个多人通话
     * @param other 
     */
    // join(other: RespData.UserMessage | RespData.GroupMessage, callType: CallType): void;

    /**
     * 通话请求
     * @param data 对方
     * @return Promise<boolean> 是否同意通话请求
     */
    onRequest(data: MessageData.CommunicationMessageData): Promise<boolean>;

    /**
     * 请求描述
     * @param data 对方
     */
    onRequestDescription(data: { sign: string }): void;

    /**
     * 收到对方的描述
     * @param data 
     */
    onResponseDescription(data: { sign: string, description: RTCSessionDescriptionInit } & MessageData.CommunicationMessageData): Promise<void>;

    onResponseCandidate(data: { candidate: RTCIceCandidateInit | RTCIceCandidate } & MessageData.CommunicationMessageData): any;

    onFinish(data: { sign: string }): any;
}