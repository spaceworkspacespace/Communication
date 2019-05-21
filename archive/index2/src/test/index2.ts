// import './styles/reset.css'
// import './styles/common.css'
// import './chart'
// import './d3'
// import { GatewayImpl } from './gateway'
import { AESUtils } from '../util/crypto'
// import './md'

// import Hammer = require('hammerjs')
// import './hammer'
// import * as Tween from '@tweenjs/tween.js';

// import { Parabola } from './parabola'

// export function hello(): string {
//     console.log("hell1o")
//     return "hello";
// }

// hello();

Object.defineProperty(window, "AESUtils", {
    enumerable: true,
    writable: false,
    value: AESUtils,
    configurable: false,
});
// Object.defineProperty(window, "Gateway", {
//     enumerable: true,
//     writable: false,
//     value: GatewayImpl,
//     configurable: false,
// });

var text = '快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好 友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.';
var key = "123456";
Object.defineProperty(window, "text", {
    enumerable: true,
    writable: false,
    value: text,
    configurable: false,
});
Object.defineProperty(window, "key", {
    enumerable: true,
    writable: false,
    value: key,
    configurable: false,
});

let encrypted = AESUtils.encrypt(text, key);
console.log(encrypted);
let decrypted = AESUtils.decrypt(encrypted, key);
console.log(decrypted);