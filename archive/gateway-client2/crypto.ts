

import { AES, mode, enc, MD5, pad } from 'crypto-js'


class AESUtils {
    public static encrypt(text: string, key: string): string {
        let words = AES.encrypt(text,
            enc.Utf8.parse(MD5(key).toString()), {
                mode: mode.CTR,
                padding: pad.ZeroPadding,
                iv: enc.Utf8.parse(MD5(key).toString().substring(0, 16))
            });
        // return enc.Base64.stringify(words.ciphertext);
        return words.toString();
    }

    public static decrypt(text: string, key: string): string {
        // let hexStr = enc.Hex.parse(text);
        // let b64Str = enc.Base64.stringify(hexStr);
        let message = AES.decrypt(text,
            enc.Utf8.parse(MD5(key).toString()), {
                mode: mode.CTR,
                padding: pad.ZeroPadding,
                iv: enc.Utf8.parse(MD5(key).toString().substring(0, 16))
            });
        // return enc.Utf8.stringify(words);
        return message.toString(enc.Utf8);
    }
}

export {
    AESUtils
}