/**
 * 似乎是做不到用 public key 解密.
 * 参考: https://github.com/travist/jsencrypt/issues/115
 */

import JSEncrypt from 'jsencrypt';

var b64map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
var b64pad = '=';

var BI_RM = '0123456789abcdefghijklmnopqrstuvwxyz';


function hex2b64(h: string) {
    var i;
    var c;
    var ret = '';
    for (i = 0; i + 3 <= h.length; i += 3) {
        c = parseInt(h.substring(i, i + 3), 16);
        ret += b64map.charAt(c >> 6) + b64map.charAt(c & 63);
    }
    if (i + 1 === h.length) {
        c = parseInt(h.substring(i, i + 1), 16);
        ret += b64map.charAt(c << 2);
    }
    else if (i + 2 === h.length) {
        c = parseInt(h.substring(i, i + 2), 16);
        ret += b64map.charAt(c >> 2) + b64map.charAt((c & 3) << 4);
    }
    while ((ret.length & 3) > 0) {
        ret += b64pad;
    }
    return ret;
}
function int2char(n: number) {
    return BI_RM.charAt(n);
}

function b64tohex(s: string) {
    var ret = '';
    var i;
    var k = 0; // b64 state, 0-3
    var slop = 0;
    for (i = 0; i < s.length; ++i) {
        if (s.charAt(i) === b64pad) {
            break;
        }
        var v = b64map.indexOf(s.charAt(i));
        if (v < 0) {
            continue;
        }
        if (k === 0) {
            ret += int2char(v >> 2);
            slop = v & 3;
            k = 1;
        }
        else if (k === 1) {
            ret += int2char((slop << 2) | (v >> 4));
            slop = v & 0xf;
            k = 2;
        }
        else if (k === 2) {
            ret += int2char(slop);
            ret += int2char(v >> 2);
            slop = v & 3;
            k = 3;
        }
        else {
            ret += int2char((slop << 2) | (v >> 4));
            ret += int2char(v & 0xf);
            k = 0;
        }
    }
    if (k === 1) {
        ret += int2char(slop << 2);
    }
    return ret;
}


// 加密解密的对象
let jsEncrypt = new JSEncrypt();

class RSAUtils {
    /**
    * 加密明文
    * @param text 明文, 为普通字符串
    * @param pk public key, 如果不提供将会使用先前设置的值
    * @returns string base64 编码的密文.
    */
    public static encrypt(text: string, keyStr: string, blockSize: number = 110): string {
        jsEncrypt.setPublicKey(keyStr);
        let key = jsEncrypt.getKey();
        // 储存字符分隔点
        let marker = [0];

        let strLen = text.length;
        // 已计算的字符大小.
        let size = 0;
        // 解析字符大小, 确定分隔点
        for (let i = 0; i < strLen; i++) {
            let charCode = text.charCodeAt(i);
            if (charCode >= 0x010000 && charCode <= 0x10FFFF) { // 特殊字符，如Ř，Ţ
                size += 4;
            }
            else if (charCode >= 0x000800 && charCode <= 0x00FFFF) { // 中文以及标点符号
                size += 3;
            }
            else if (charCode >= 0x000080 && charCode <= 0x0007FF) { // 特殊字符，如È，Ò
                size += 2;
            }
            else { // 英文以及标点符号
                size += 1;
            }
            // 容量是否足够
            if (size >= blockSize) {
                // 保存该点索引, 重新计算大小
                marker.push(i);
                size = 0;
            }
        }
        // 最后一次循环不会把最后一段数据加入, 这里的分隔点确定在两点间是用 [start, end) 确定,
        // 即使最后一个字符被确定为分隔点, 在分段加密中也不会将其包括, 这里用字符串的长度做最后的结束点.
        // 保存最后一个分隔点
        marker.push(strLen);

        // 取得所有分隔点, 进行分段加密
        let aryLen = marker.length;
        // 存放所有加密成功的片段
        let encrypted = [];
        for (let i=0; i<aryLen-1; i++) {
            let partial = text.substring(marker[i], marker[i+1]);
            partial = key.encrypt(partial);
            if (!partial) { // 加密失败了.
                console.error(partial);
                return "";
            }
            console.log(partial, partial.length);
            encrypted.push(partial);
        }
        // 返回值
        let hexStr = encrypted.join("");
        console.log(hexStr, hexStr.length, encrypted);
        return hex2b64(hexStr);
    }

    /**
     * 解密密文
     * @param text 密文, 为 base64 编码
     * @param key public key, 如果不提供将会使用先前设置的值
     * @returns string 普通字符串.
     */
    public static decrypt(text: string, keyStr: string, blockSize: number = 128): string {
        jsEncrypt.setPublicKey(keyStr);
        let key = jsEncrypt.getKey();
        let decrypted = [];
        
        text = b64tohex(text);
        console.log(text, text.length);
        let range = new RegExp(`.{1,${blockSize}}`, "g");
        // try {
            // 将完整的字符串分段
            let match = text.match(range);
            if (match) {
                // 将每一段分开解密
                for (let partial of match) {
                    let partialStr = key.decrypt(partial);
                    decrypted.push(partialStr);
                }
            }
            
        // } catch(e) {
        //     console.error(e);
        //     return "";
        // }

        return decrypted.join();
    }
}

export {
    RSAUtils
}