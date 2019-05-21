import axios from 'axios'
import { LINKS } from '@/conf/link'

const ERROR_MSG = "请求失败, 请稍后重试~";

class CommonService {
    static _instance = null;

    static getInstance() {
        return CommonService._instance || (CommonService._instance = new CommonService());
    }

    uploadAvatar(file) {
        return this.upload(LINKS.common.avatar, file);
    }

    upload(url, file) {
        let data = new FormData();
        data.append("file", file);
        // data.append("_ajax", true);
        return axios({
            method: "POST",
            url,
            // headers: { "HTTP_X_REQUESTED_WITH": "xmlhttprequest" },
            data
        })
            .then(resp => {
                let data = resp.data;
                if (data.code) {
                    throw new Error(data.msg || ERROR_MSG);
                }
                return data.data || data.msg;
            });
        // return fetch(url, {
        //     headers: { "HTTP_X_REQUESTED_WITH": "xmlhttprequest" },
        //     method: "POST",
        //     body: data
        // })
        //     .then(resp => {
        //         if (resp.ok) {
        //             return resp.json();
        //         }
        //         throw new Error(resp.statusText);
        //     })
        //     .then(data => {
        //         if (data.code) {
        //             throw new Error(data.msg || ERROR_MSG);
        //         }
        //         return data.data || data.msg;
        //     });
    }
}

export {
    CommonService
}