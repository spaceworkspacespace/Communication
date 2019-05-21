

export function queryStringDeserialize(url) {
    url = decodeURIComponent(url);
    let query = /\?([^#]+)(#.*)?$/.exec(url);
    if (!query) return {};
    query = query[1];
    let obj = {};
    for (let pair of query.split("&")) {
        let kv = pair.split("=");
        obj[kv[0]] = kv[1];
    }
    return obj;
}

/**
* 将 Date 格式化
* @param {Date} date 日期
* @param {string} format 格式 
*/
export function dateFormat(date, format = "yyyy-MM-dd  HH:mm:ss") {
    // 匹配时间的正则
    let reg = /([a-zA-Z])\1+/g;
    let result = format;

    for (let match = reg.exec(format); match !== null; match = reg.exec(format)) {
        // console.log("匹配到值: ", match[0], format, result)
        let value = null;
        let length = match[0].length;

        // 得到这次匹配置换的值, 如果没有对应的值, 进行下一次匹配.
        switch (match[1]) {
            case "y": value = date.getFullYear(); break;
            case "M": value = date.getMonth() + 1; break;
            case "d": value = date.getDate(); break;
            case "H": value = date.getHours(); break;
            case "m": value = date.getMinutes(); break;
            case "s": value = date.getSeconds(); break;
            // .... 其他匹配项
            default: continue;
        }
        value = value.toString();

        // 补齐长度并替换
        if (value.length > length) value = value.substring(0, length);
        if (value.length < length) value = value.padStart(length, "0");
        result = result.replace(match[0], value);
    }

    return result;
}