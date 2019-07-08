import * as _ from 'lodash';
import { CallState, ICallHandler } from "./ICallHandler";
import { CallHandler } from "./CallHandler";
import { IllegalStateException } from "../exception/IllegalStateException";

export interface ICallWindowEvent {
    // 通话异常
    onInterrupt(window: AbsCallWindow): void;
    onInterrupt(window: AbsCallWindow, error: Error): void;
    // 正常结束通话
    onComplete(window: AbsCallWindow, state?: CallState): void;
    // 请求事件
    onEvent(window: AbsCallWindow, state: CallState, ...args: any[]): void;
}

export abstract class AbsCallWindowBuilder {
    protected _user: RespData.UserMessage;
    protected _handler: CallHandler;
    protected _other: RespData.UserMessage | RespData.GroupMessage;
    protected _event: ICallWindowEvent;
    protected _zIndex: number;

    handler(h: CallHandler): this {
        this._handler = h;
        return this;
    }

    current(user: RespData.UserMessage): this {
        this._user = user;
        return this;
    }

    other(other: RespData.UserMessage | RespData.GroupMessage): this {
        this._other = other;
        return this;
    }

    event(e: ICallWindowEvent): this {
        this._event = e;
        return this;
    }

    zIndex(z: number): this {
        this._zIndex = z;
        return this;
    }

    abstract build(): AbsCallWindow
}

type SimpeTalkerInfo = { id: number, name: string, avatar: string };

export abstract class AbsCallWindow {
    // 唯一标识
    private _id: string;

    // 聊天类型
    protected type: "friend" | "group";

    // private _detail: MessageData.CommunicationMessageData;
    private _talkerIds: Set<number> = new Set();
    private _chat_detail: Map<string, SimpeTalkerInfo> = new Map();

    constructor(
        protected handler: ICallHandler,
        // 当前用户
        protected user: RespData.UserMessage,
        // 对方
        protected other: RespData.UserMessage | RespData.GroupMessage,
        protected event: ICallWindowEvent,

        protected zIndex?: number) {

        if ((other as Object).hasOwnProperty("username")) {
            this.type = "friend";
        } else {
            this.type = "group";
        }
    }

    get id() {
        return this._id;
    }

    set id(id) {
        this._id = id;
    }

    public getSign(userid: number): string;
    public getSign(d: MessageData.CommunicationMessageData): string;
    public getSign(d: MessageData.CommunicationMessageData & number) {
        console.log(d);

        if (typeof d === "number") {
            return this.type !== "group"
                ? this._id
                : this._id + "-" + d;
        }
        return d.groupid != null
            ? d.sign + "-" + d.userid
            : d.sign;
    }

    public getTalker(sign: string): SimpeTalkerInfo {
        return _.assign({}, this._chat_detail.get(sign));
    }

    public getTalkerIds(): Array<number> {
        if (this.type !== "friend") {
            return [...this._talkerIds.values()];
        }
        return [];
    }

    public setDetail(d: MessageData.CommunicationMessageData) {
        if (this._id != null) {
            if (this._id != d.sign) {
                throw new IllegalStateException("会话 sign 和此窗口 id 不一致.");
            }
        } else {
            this._id = d.sign;
        }

        // 得到对方信息
        let other: SimpeTalkerInfo;
        if (this.user.id !== d.userid) {
            other = { id: d.userid, name: d.username, avatar: d.useravatar };
        } else {
            other = { id: d.ruserid, name: d.rusername, avatar: d.ruseravatar };
        }

        if (other.id != null) {
            // 如果是群聊, 收集收到的连接用户的 id
            if (d.groupid != null) {
                this._talkerIds.add(other.id);
            }

            // 暂存消息详情
            this._chat_detail.set(this.getSign(other.id), other);
        }
    }

    /**
     * 移除群聊中记录的一个用户 id
     * @param ids 
     * @returns boolean 如果全部移除完了, 则返回 true.
     */
    public leaveTalker(...ids: number[]): boolean {
        for (let id of ids) {
            this._talkerIds.delete(id);
        }

        console.log(ids.join(", ") + " 离开了会话 " + this._id + " 离开了, 剩余成员 " + [...this._talkerIds.values()].join(", "));

        return this._talkerIds.size === 0 ? true : false;
    }

    public is(userId: number, otherId: number, type: "group" | "friend"): boolean {
        // console.log(this.user.id, userId, this.other.id, otherId, type, this.type);
        if (this.user.id === userId
            && this.other.id === otherId
            && type === this.type) {
            return true;
        }
        return false;
    }

    /**
     * 连接已经成功
     */
    public connect(): void
    public connect(conn?: RTCPeerConnection): void;
    public connect(conn?: RTCPeerConnection) {

    }

    public getChatType(): Beans.ChatType {
        return this.type;
    }

    public getOtherId(): number {
        return this.other.id;
    }

    // 弹出一个窗口
    public abstract call(): Promise<number>
    public abstract ring(): Promise<number>

    // 更新窗口信息
    public abstract updateState(state: { text?: string, time?: string, onRefuse?: () => any, onAgree?: () => any, track: MediaStreamTrack }): void
}