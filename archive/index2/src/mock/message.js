import { mock } from 'mockjs'
import { LINKS } from './conf'

mock(new RegExp(`^${LINKS.message.pull}(\\?.+)?$`), "post", {
    "code": 0,
    "msg": "查询成功!",
});

mock(new RegExp(`^${LINKS.message.index}(\\?.+)?$`), "get", {
    "code": 0,
    "msg": "查询成功!",
    "data|50": [{
        "result|1": ["y", "n", null], // 处理结果
        "id|+1": 1, // 消息 id
        "sender": "@natural(1,10)", // 发送用户的 id
        "username": "@cword(3,7)", // 发送用户的名称
        "avatar|1": [
            "https://i.loli.net/2018/12/10/5c0de4003a282.png",
            "https://i.loli.net/2019/04/12/5cafffdaed88f.jpg",
        ],
        "date|1535471994-1555471994": 1555471994,
        "content": "@paragraph(1,3)", // 消息内容
        "type": "@natural(1,3)", // 消息类型
        "corrid": "@natural(1,10)",
        "corrid2": "@natural(1,10)",
        "corrstr": "@cword(3,7)",
        "corrstr2": "@cword(3,7)",
        "treat|1": [true, false],
    }]
});

mock(new RegExp(`^${LINKS.message.index}(\\?.+)?$`), "post", {
    "msg": "@cword(3,7)",
    "code|1": [0, 1],
});