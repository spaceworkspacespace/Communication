import { mock } from 'mockjs'
import { LINKS } from './conf'
import { queryStringDeserialize } from '@/util/functions'

mock(new RegExp(`^${LINKS.contact.friend}(\\?.+)?$`), "get", {
    "code": 0,
    "msg": "查询成功!",
    "data|50": [
        {
            "username": "@cword(3,7)",
            "id|+1": 1,
            "avatar|1": [
                "https://i.loli.net/2018/12/10/5c0de4003a282.png",
                "https://i.loli.net/2019/04/12/5cafffdaed88f.jpg",
            ],
            "sign": "@paragraph(1,4)",
            "status|1": ["online", "offline"],
            "sex|1": ["保密", "男", "女"],
        }
    ]
});

mock(new RegExp(`^${LINKS.contact.friend}(\\?.+)?$`), "post", {
    "msg": "@cword(3,7)",
    "code|1": [0, 1],
});


mock(new RegExp(`^${LINKS.contact.friendGroup}(\\?.+)?$`), "get", {
    "code": 0,
    "msg": "查询成功!",
    "data|6-24": [
        {
            "id|+1": 1,
            "groupname": "@cword(3,7)",
            "priority|+16": 16,
            "createtime|+10": 1555471994,
            "membercount|1-10": 1,
            "list|6-24": [
                {
                    "username": "@cword(3,7)",
                    "id|+1": 1,
                    "avatar|1": [
                        "https://i.loli.net/2018/12/10/5c0de4003a282.png",
                        "https://i.loli.net/2019/04/12/5cafffdaed88f.jpg",
                    ],
                    "sign|6-12": "@paragraph(1)",
                    "status|1": ["online", "offline"],
                    "sex|1": ["保密", "男", "女"]
                }
            ]
        }
    ]
});



mock(new RegExp(`^${LINKS.contact.group}(\\?.+)?$`), "get", {
    "code": 0,
    "msg": "查询成功!",
    "data|6-12": [
        {
            "id|+1": 1,
            "groupname": "@cword(3,7)",
            "description": "@paragraph(1,4)",
            "avatar|1": [
                "https://i.loli.net/2018/12/10/5c0de4003a282.png",
                "https://i.loli.net/2019/04/12/5cafffdaed88f.jpg",

            ],
            "createtime|1535471994-1555471994": 1555471994,
            "admin|+1": "@natural(1,10)",
            "admincount": "@natural(10,1000)",
            "membercount": "@natural(10,1000)",
            "list|8-21": [
                {
                    "username": "@cword(3,7)",
                    "id|+1": 1,
                    "avatar|1": [
                        "https://i.loli.net/2018/12/10/5c0de4003a282.png",
                        "https://i.loli.net/2019/04/12/5cafffdaed88f.jpg",
                    ],
                    "sign": "@paragraph(1,4)",
                    "status|1": ["online", "offline"],
                    "sex|1": ["保密", "男", "女"],
                    "isadmin": "@boolean"
                }
            ]
        }
    ]
});

mock(new RegExp(`^${LINKS.contact.group}(\\?.+)?$`), "post", {
    "msg": "@cword(3,7)",
    "code|1": [0, 1],
});


mock(new RegExp(`^${LINKS.contact.group}(\\?.+)?$`), "put", {
    "code": 0,
    "msg": "修改成功!",
    "data": {}
});

mock(new RegExp(`^${LINKS.contact.group}(\\?.+)?$`), "delete", {
    "msg": "@cword(3,7)",
    "code|1": [0, 1],
});

mock(new RegExp(`^${LINKS.contact.groupMember}(\\?.+)?$`), "put", function (options) {
    let query = queryStringDeserialize(options.url);
    let data = {};
    if (query.hasOwnProperty("alias")) {
        data.username = query.alias;
    }
    if (query.hasOwnProperty("admin")) {
        data.isadmin = query.admin !== "false" ? true : false;
    }
    return {
        code: 0,
        msg: "修改成功",
        data
    }
});

mock(new RegExp(`^${LINKS.contact.groupMember}(\\?.+)?$`), "delete", {
    "msg|1": "@cword(3,7)",
    "code|1": [0, 1]
});

mock(new RegExp(`^${LINKS.contact.mygroup}(\\?.+)?$`), "post", {
    "msg": "@cword(3,7)",
    "code|1": [0, 1],
});