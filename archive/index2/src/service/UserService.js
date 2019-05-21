import axios from 'axios';
import { LINKS } from '../conf/link';
const ERROR_MSG = "请求错误, 请稍后重试~";
class UserService {
    static getInstance() {
        return this.instance || (this.instance = new this());
    }
    getInfo(userId) {
        return axios({
            method: "GET",
            url: LINKS.user.userInfo
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }
    updateInfo(userId, params) {
        return axios({
            method: "PUT",
            url: LINKS.user.userInfo,
            data: params,
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }
    bind(userId, clientId) {
        return axios({
            method: "POST",
            url: LINKS.user.bind,
            data: {
                clientId
            },
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }
}
export { UserService };
