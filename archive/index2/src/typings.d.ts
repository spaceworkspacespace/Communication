declare module "showdown";
declare module "chart.js";
declare module "adapterjs";

declare module 'layer' {
    interface OptionForPageLayer extends BasicOptions {
        type: 1,
        // 文本 html, 或 dom 节点
        content: string | JQuery<HTMLElement> | HTMLElement;
        //  最大最小化
        maxmin?: boolean;
        // 是否允许拉伸
        resize?: boolean;
        // 监听窗口拉伸动作
        resizing?: (layero: any) => void;
    }

    interface OptionForIframeLayer extends BasicOptions {
        type: 2;
        // url 字符串, 后一种写法是避免让 iframe 出现滚动条
        content: string | [string, "no"];
        maxmin?: boolean;
        resize?: boolean;
        resizing?: (layero: any) => void;
    }

    interface OptionForTipsLayer extends BasicOptions {
        type: 4;
        // 第一项是显示的内容, 第二项是元素选择器或 DOM 节点
        content: [string, string | JQuery<HTMLElement> | HTMLElement];
        // 方向和颜色
        // 支持上右下左四个方向，通过1-4进行方向设定
        // [1, '#c00']
        tips?: number | [number, string];
    }
    interface OptionForLoadingLayer extends BasicOptions {
        icon?: 0 | 1 | 2;
        type: 3;
    }

    interface BasicOptions {
        // 弹出动画
        // -1 不显示
        anim?: -1 | 0 | 1 | 2 | 3 | 4 | 5 | 6;
        area?: "auto" | string | [string, string];
        // 设置 0 不显示关闭按钮
        closeBtn?: 0 | 1 | 2;
        content?: any;
        // 即鼠标滚动时，层是否固定在可视区域。如果不想，设置fixed: false即可
        fixed?: boolean;
        icon?: -1 | 0 | 1 | 2 | 3 | 4 | 5 | 6;
        // 用于控制弹层唯一标识
        // 设置该值后，不管是什么类型的层，都只允许同时弹出一个。一般用于页面层和iframe层模式
        id?: string;
        // 关闭动画 
        isOutAnim?: boolean;
        // 最大宽高
        // 只有当高度自适应时才有效
        maxWidth?: number;
        maxHeight?: number;
        offset?: "auto" | string | [string, string] | 't' | 'b' | 'r' | 'l' | "lt" | "lb" | "rt" | "rb";
        // 是否允许浏览器出现滚动条
        scrollbar?: boolean;
        // 遮罩, 字符串表示颜色, 数字表示透明度
        // 0 可以取消遮罩
        shade?: string | [number, string] | number;
        // 是否点击遮罩关闭
        shadeClose?: boolean;
        skin?: "layui-layer-lan" | "layui-layer-molv";
        // 层弹出后的成功回调方法
        // layero: 当前层DOM, 包括 layer 包装的标题栏等, 不包括遮罩
        // index: 当前层索引
        success?: (layero: JQuery<HTMLDivElement>, index: number) => void;
        // 自动关闭所需毫秒
        time?: number;
        // ['文本', 'font-size:18px;'] 自定义标题样式
        title?: false | string | [string, string];
        type?: 0 | 1 | 2 | 3 | 4;
        zIndex?: number;
    }
    type Options = OptionForTipsLayer | OptionForLoadingLayer | OptionForIframeLayer | OptionForPageLayer;

    interface Layer {
        alert(content: string): void;
        alert(content: string, options: BasicOptions): void;
        alert(content: string, yes: (index: number) => void): void;
        alert(content: string, options: BasicOptions, yes: (index: number) => void): void;
        close(index: number): void;
        config(options: BasicOptions): void;
        confirm(content: string, options: BasicOptions, yes: (index: number) => void, cancel: () => void): number;
        msg(msg: string, options?: BasicOptions, onclose?: () => {}): number;
        open(options: Options): number;
    }
    const layer: Layer;
    export default layer;
}

declare module 'layui' {
    namespace layui {
        interface LayimLocalData {
            chatlog: {
                // 命名规则 friend<id> 或 group<id>
                [key: string]: Array<{
                    avatar: string,
                    cid: number,
                    content: string,
                    id: number,
                    mine: boolean,
                    timestamp: number,
                    type: "friend" | "group",
                    username: string,
                    fromid?: number,
                }>
            },
            history: {
                // 命名规则 friend<id> 或 group<id>
                [key: string]: any[]
            }
        }


        type GetMessageType = {
            username: string,
            avatar: string,
            id: number,
            type: "friend" | "group",
            content: string,
            cid: number,
            mine: boolean,
            fromid: number,
            timestamp: number
        } | {
            system: true,
            id: number,
            type: "friend" | "group",
            content: string
        }

        type CallBackDataOfFriend = {
            avatar: string,
            content: string,
            createtime: number,
            groupid: number,
            groupname: string,
            historyTime: number,
            id: number,
            membercount: number,
            name: string,
            priority: number,
            sex: number,
            sign: string,
            status: string,
            type: string,
            username: string,
        }
        type CallBackDataOfGroup = {
            avatar: string,
            cid: string,
            content: string,
            fromid: number,
            groupname: string,
            historyTime: number,
            id: string,
            mine: boolean,
            name: string,
            sign: string,
            timestamp: number,
            type: string,
        };

        export type ThisChat = {
            // 聊天面板消息容器
            elem: JQuery<HTMLUListElement>,
            // 聊天面板的数据
            data: {
                avatar: string,
                createtime: string,
                groupid: string,
                groupname: string,
                id: string,
                membercount: string,
                name: string,
                priority: string,
                sex: string,
                sign: string,
                status: string,
                type: "friend",
                username: string,
            } | {
                admin: string,
                admincount: string,
                avatar: string,
                createtime: string,
                description: string,
                groupname: string,
                id: string,
                membercount: string,
                name: string,
                type: string,
            },
            textarea: JQuery<HTMLTextAreaElement> | JQuery<HTMLInputElement>
        };

        type ModuleName = "layim" | "layim-mobile";
        type EventName = "sendMessage";
        type MobileEventName = EventName | "chatlog" | "detail" | "newFriend";
        type DesktopEventName = EventName | "sign";
        type CustomEventName = "chatMsgDelete" | "beforeSendMessage" | "sendMessage" | "afterGetMessage" | "tool(voiceCall)" | "tool(videoCall)";

        export function use(module: string, callback: (...obj: any) => void): void;
        export function data(m: ModuleName): {
            [key: number]: LayimLocalData
        };
        export function data(m: ModuleName, newData: {
            key: number,
            value: LayimLocalData
        }): void;
        export const layim: Layim;
        export interface Layim {
            config(conf: any): void;
            /*** 基本事件 ***/
            on(event: "sendMessage", callback: (param: { me: any, to: any }) => void): void;
            /*** 桌面版事件 ***/
            on(event: "sign", callback: (sign: string) => void): void;
            /*** 移动端事件 ***/
            on(event: "detail", callback: (data: any) => void): void;
            on(event: "chatlog", callback: (data: CallBackDataOfFriend | CallBackDataOfGroup, ul: HTMLElement) => void): void;
            on(event: "newFriend", callback: () => boolean): void;

            /*** 自定义的消息事件 ***/
            on(event: "afterGetMessage", callback: (li: JQuery<HTMLLIElement>, isActive: boolean, thisChat: ThisChat, data: GetMessageType) => void): void;
            /**
             * 在发送消息之前的回调
             * @param event 
             * @param callback 如果回调的返回值为 false, 表示消息不发送
             *  li 渲染的消息内容对象
             *  thisChat 当前聊天的信息
             */
            on(event: "beforeSendMessage", callback: (li: JQuery<HTMLLIElement>, thisChat: ThisChat) => boolean): void;
            /**
             * 在将消息添加到聊天面板前的回调
             * @param event 
             * @param callback 如果回调的返回值为 false, 表示消息不发送
             *  li 渲染的消息内容对象
             *  isActive 消息窗口当前是否处于活动状态
             *  thisChat 当前聊天的信息
             */
            on(event: "sendMessage", callback: (param: { me: any, to: any }, li: JQuery<HTMLLIElement>) => void): void;

            /**
             * 调用视频聊天
             * @param event 
             * @param callback
             *  insert 往当前输入框插入内容
             *  send 发送
             *  data 当前聊天的数据
             */
            on(event: "tool(videoCall)", callback: (insert: (text: string) => void, send: () => void, data: ThisChat) => void): void;
            /*** --- ***/
            on(event: MobileEventName | DesktopEventName | CustomEventName, callback: (...obj: any) => void): void;
            cache(): any;
            msgbox(count: number): void;
            getMessage(data: GetMessageType): void;
            addList(data: {
                type: 'friend', //列表类型，只支持friend和group两种
                avatar: string, //好友头像
                username: string, //好友昵称
                groupid: number, //所在的分组id
                id: number, //好友id
                sign: string, //好友签名
            }): void;
            addList(data: {
                type: 'group', //列表类型，只支持friend和group两种
                avatar: string, //群组头像
                groupname: string, //群组名称
                id: number, //群组id
            }): void;
            addList(data: any): void;
            // 弹出面板
            // title 是标题
            // tpl 是模板
            // data 是模板中使用的变量
            panel(options: { title: string, tpl: string, data?: { [key: string]: any } }): void;
            removeList(options: {
                type: 'friend' | "group", //或者group
                id: number //好友或者群组ID
            }): void;
        }
        export const mobile: any;
    }
    export default layui;
}

// Gateway 推送的消息的 data 部分类型
declare namespace MessageData {
    export type SendMessageData = Array<{
        username: string, // 用户名
        avatar: string, // 头像
        id: number, // 用户 id 或群聊 id
        fromid: number,  // 消息发送者 id
        type: "friend" | "group",
        content: string,
        cid: number, // 消息 id
        timestamp: number, // 消息发送时间
    }>

    export type AskMessageData = {
        msgCount: number, // 待处理消息数量
    }

    export type AddMessageData = Array<{
        type: "friend",
        avatar: string,
        username: string,
        groupid: number, // 分组 id
        id: number, // 联系人 id
        sign: string, // 联系人签名
    } | {
        type: "group",
        avatar: string,
        groupname: string,
        id: number,
    }>

    export type CommunicationMessageData = {
        sign: string, // 通信的标识
        ctype: "video" | "voice", // 通信的类型
        communicatetime?: number, // 通话时间
        ice?: string,
        description?: string, // 对方的描述信息
        // 用户信息
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

declare namespace RespData {
    export type ChatMessage = {
        // 消息的信息
        cid: number, // 消息 id
        date: number, // 发送时间
        content: string, // 内容
        isread: boolean, // 是否已读
        issystem: boolean, // 是否为系统消息
        // 消息发送者的信息
        uid: number, // 用户 id
        username: string, // 用户名, im_friends.contact_alias 或 im_groups.user_alias 优先展示.
        avatar: string, // 用户头像,
        // 消息接收者的信息
        tid: number, // 用户 id
        tavatar: string, // 用户头像
        tusername: string, // 用户昵称
        // 消息所在的群聊, 如果有的话
        gid?: number, // 群聊 id
        groupname?: string, // 群组名称
        gavatar?: string, // 群组图像
    }

    export type NoticeMessage = {
        id: number, // 消息 id
        date: number, // 发送的日期, im_msg_receive.send_date
        content: string, // 消息内容
        type: number, // 消息类型
        associated: Array<{
            id?: number, // 发送用户的 id
            username?: string, // 发送用户的名称
            avatar?: string, // 发送用户的头像
        } & {
            id?: number, // 群聊 id
            avatar?: string, // 群聊头像
            groupname?: string, // 群聊名称
        }>, // 相关数据
        issystem: boolean, // 是否为系统消息 
        // 处理信息
        result: "y" | "n", // 处理结果
        treat: boolean,
    }

    export type UserMessage = {
        username: string, // 用户名
        id: number, // id
        avatar: string, // 头像地址
        sign: string, // 签名信息
        status: "online" | "offline", // 是否在线
        sex: "保密" | "男" | "女",
    }

    export type FriendGroupMessage = {
        id: number, // 分组 id
        groupname: string, // 分组名称
        priority: number, // 优先级
        createtime: number, // 创建时间
        membercount: number, // 成员数量
        list?: Array<{ // 分组下联系人信息
            username: string, // 用户名, im_friends.contact_alias 如果有的话
            id: number, // id
            avatar: string, // 头像地址
            sign: string, // 签名信息
            status: "online" | "offline", // 是否在线
            sex: "保密" | "男" | "女",
        }>
    }

    export type GroupMessage = { // 群聊信息
        id: number, // id
        groupname: string, // 名称
        description: string, // 描述
        avatar: string, // 图像
        createtime: number, // 创建时间
        admin: number, // 所有者 id
        admincount: number, // 管理员数量
        membercount: number, // 成员数量
        list?: Array<{ // 如果 include 参数为 true, 附加所有群聊成员的信息
            username: string, // 用户名, im_groups.user_alias 如果有的话
            id: number, // id
            avatar: string, // 头像地址
            sign: string, // 签名信息
            status: "online" | "offline", // 是否在线
            sex: "保密" | "男" | "女",
            isadmin: boolean, // 是否为管理员
        }>
    }

    type GeneralMessage = {
        code: 0 | 1;
        msg: string;
        data: any;
    }
}

declare namespace Beans {
    export type ChatType = "friend" | "group";
}

declare namespace Type {
    export type Runnable = () => void;
    export type Function<T, R> = (arg: T) => R;
    export type Consumer<T> = (arg: T) => void;
    export type BiConsumer<T, U> = (t: T, u: U) => void;
}