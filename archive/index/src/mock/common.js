

import { mock } from 'mockjs'
import { LINKS } from './conf'
// Object.values(LINKS.common).map(i =>
//     new RegExp(`^${i}(\\?.+)?$`)),
mock(new RegExp(`^${LINKS.common.avatar}(\\?.+)?$`), "post", {
    "msg": "@cword(3,7)",
    "code|1": [0, 1],
    "data": {
        "src|1": [
            "https://i.loli.net/2019/04/12/5cb04b95be706.png"

        ],
        "size": "@natural(999,9999999)",
        "name": "cword(3,7)",
    }
});