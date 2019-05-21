const LINKS = {
    page: {
        contactMgr: "/im/index/index2#/contactmgr", // 好友管理
        msgBox: "/im/index/index3#/msg/box", // 消息盒子
        chatLog: "/im/index/index3#/chat/log", // 聊天记录
        login: "/user/login/index.html",
        logout: "/user/index/logout.html",
        register: "/user/register/index.html",
        im: "/im/index/index1",
        userCenter: "/user/profile/center.html"
        
        // contactMgr: "http://192.168.0.218:1212/im/index/index2#/contactmgr",
        // msgBox: "http://192.168.0.218:1212/im/index/index3#/msg/box",
        // chatLog: "http://192.168.0.218:1212/im/index/index3#/chat/log",
        // login: "http://192.168.0.218:1212/user/login/index.html",
        // logout: "http://192.168.0.218:1212/user/index/logout.html",
        // register: "http://192.168.0.218:1212/user/register/index.html",
        // im: "http://192.168.0.218:1212/im/index/index1",
        // userCenter: "http://192.168.0.218:1212/user/profile/center.html"

        // contactMgr: "http://192.168.0.80:1235/im/index/index2#/contactmgr",
        // msgBox: "http://192.168.0.80:1235/im/index/index3#/msg/box",
        // chatLog: "http://192.168.0.80:1235/im/index/index3#/chat/log",
        // login: "http://192.168.0.80:1235/user/login/index.html",
        // logout: "http://192.168.0.80:1235/user/index/logout.html",
        // register: "http://192.168.0.80:1235/user/register/index.html",
        // im: "http://192.168.0.80:1235/im/index/index1",
        // userCenter: "http://192.168.0.80:1235/user/profile/center.html"
    },
    chat: {
        message: "/im/chat/message",
        feedback: "/im/chat/feedback",
        call: "/im/chat/call"
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
    // socket: "ws://192.168.0.80:8080"
    socket: "ws://39.97.52.10:8080"
};

const SOCKET = {
    url: LINKS.socket,
    interval: 60,
    pingData: "ping",
}

export {
    LINKS,
    SOCKET
}