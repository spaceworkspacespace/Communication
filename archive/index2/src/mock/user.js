import { mock } from 'mockjs'
import { LINKS } from './conf'

mock(new RegExp(`^${LINKS.user.userInfo}(\\?.+)?$`), "get", {
    "code": 0,
    "msg": "查询成功!",
    "data": {
        "birthday|1535471994-1555471994": 1555471994,
        "account|6-12": "admin",
        "username": "@cword(3,7)",
        "usertype|1": ["admin" | "会员"],
        "id": 1,
        "avatar|1": [
            "https://i.loli.net/2018/12/10/5c0de4003a282.png",
            "https://i.loli.net/2019/04/12/5cafffdaed88f.jpg",
        ],
        "sign": "@paragraph(1,4)",
        "status|1": ["online", "offline"],
        "sex|1": ["保密", "男", "女"],
        "lastlogintime|1535471994-1555471994": 1555471994,
        "createtime|1535471994-1555471994": 1555471994,
        "useremail|6-12": "1@163.com",
        "mobile": /(\+\d{1,3})?\d{11}/, // 手机号码
    }
});

mock(new RegExp(`^${LINKS.user.bind}(\\?.+)?$`), "post", function (options) {
    return {
        code: 0,
        msg: "查询成功!",
        data: {
            "id": "@natural(1,10)",
            ks: window.btoa(JSON.stringify({ "u-01": "abcd" }))
        }
    }
});


mock(new RegExp(`^${LINKS.user.userInfo}(\\?.+)?$`), "put", {
    "code": 0,
    "msg": "更新成功!",
    "data": {
        "birthday|1535471994-1555471994": 1555471994,
        "account|6-12": "admin",
        "username": "@cword(3,7)",
        "usertype|1": ["admin" | "会员"],
        "id": "@natural(1,10)",
        "avatar|1": [
            "https://i.loli.net/2018/12/10/5c0de4003a282.png",
            "https://i.loli.net/2019/04/12/5cafffdaed88f.jpg",
        ],
        "sign": "@paragraph(1,4)",
        "status|1": ["online", "offline"],
        "sex|1": ["保密", "男", "女"],
        "lastlogintime|1535471994-1555471994": 1555471994,
        "createtime|1535471994-1555471994": 1555471994,
        "useremail|6-12": "1@163.com",
        "mobile": /(\+\d{1,3})?\d{11}/, // 手机号码
    }
});