import { CallType } from "../service/CallService";
import { GatewayMessage } from "./gateway";




/**
 * 视屏或语音通话的一些事件
 */
export interface ICallHandler {

    /**
     * 通话请求
     * @param data 对方
     */
    onRequest(data: GatewayMessage.CallAskMessagePayload): void;

    /**
     * 请求描述
     * @param data 对方
     */
    onRequestDescription(data: { sign: string }): void;

    /**
     * 收到对方的描述
     * @param data 
     */
    onResponseDescription(data: { sign: string, description: string }): void;
}