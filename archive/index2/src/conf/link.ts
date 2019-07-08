const DOMAIN_NAME = "im.5dx.ink";
const PORT = '443';

let links = {
    page: {
        contactMgr: "/im/index/index2#/contactmgr", // 好友管理
        msgBox: "/im/index/index3#/msg/box", // 消息盒子
        chatLog: "/im/index/index3#/chat/log", // 聊天记录
        login: "/im/login/index.html",
        logout: "/im/index/logout.html",
        register: "/im/register/index.html",
        im: "/im/index/index1",
        userCenter: "/im/profile/center.html"
    },
    chat: {
        message: "/im/chat/message",
        feedback: "/im/chat/feedback",
        call: "/im/chat/call",
        callMembers: "/im/chat/callmembers",
        callReconnect: "/im/chat/callReconnect",
        iceServer: "/im/chat/iceserver",
    },
    user: {
        userInfo: "/im/user/info",
        bind: "/im/user/bind",
    },
    message: {
        index: "/im/message/index",
        feedback: "/im/message/feedback",
        friend: "/im/message/friend",
        group: "/im/message/group",
        pull: "/im/message/pull",
    },
    common: {
        avatar: "/im/comm/avatar",
        picture: "/im/comm/picture",
        file: "/im/comm/file"
    },
    contact: {
        mygroup: "/im/contact/mygroup",
        friend: "/im/contact/friend",
        friendAndGroup: "/im/contact/friendandgroup",
        friendGroup: "/im/contact/friendgroup",
        group: "/im/contact/group",
        groupMember: "/im/contact/groupmember",
    },
    socket: ":8080"
};

// 便利的方式改变域名协议
for (let prop in links) {
    switch (prop) {
        case "common":
        case "page":
            for (let p in links[prop]) {
                (links as any)[prop][p] = "https://" + DOMAIN_NAME + ":" + PORT + (links as any)[prop][p];
            }
            break;
        case "socket":
            links[prop] = "wss://" + DOMAIN_NAME + links[prop];
            break;
    }
}

const LINKS = links;
const SOCKET = {
    url: LINKS.socket,
    interval: 60,
    pingData: "ping",
}
export {
    LINKS,
    SOCKET
}