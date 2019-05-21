import { mock } from 'mockjs'
import { LINKS } from './conf'

mock(new RegExp(`^${LINKS.chat.message}(\\?.+)?$`), "get", {
    "msg": "@cword(3,7)",
    "code|1": [0, 1],
    "data|50": [
        {
            "id|1": [0, 1], // 用户 id
            "cid|+1": 1, // 消息 id
            "username": "@cword(4)", // 用户名, im_friends.contact_alias 或 im_groups.user_alias 优先展示.
            "avatar|1": [
                "https://i.loli.net/2018/12/10/5c0de4003a282.png",
                "https://i.loli.net/2019/04/12/5cafffdaed88f.jpg",
            ], // 用户头像,
            "date|1535471-1555471": 1555471994, // 发送时间
            "content": "@cword(3,7)", // 内容 
        }
    ]
});

mock(new RegExp(`^${LINKS.chat.message}(\\?.+)?$`), "post", {
    "msg": "@cword(3,7)",
    "code|1": [0, 1],
    "data": {
        "id": "@natural(1,10)", // 用户 id
        "cid": "@natural(1,10)", // 消息 id
        "username": "@cword(4)", // 用户名, im_friends.contact_alias 或 im_groups.user_alias 优先展示.
        "avatar|1": [
            "https://i.loli.net/2018/12/10/5c0de4003a282.png",
            "https://i.loli.net/2019/04/12/5cafffdaed88f.jpg",
        ], // 用户头像,
        "date|1535471-1555471": 1555471994, // 发送时间
        "content": "@cword(3,7)", // 内容
    }
});

mock(new RegExp(`^${LINKS.chat.message}(\\?.+)?$`), "delete", {
    "msg": "@cword(3,7)",
    "code|1": [0, 1],
});