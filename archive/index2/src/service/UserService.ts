import axios from 'axios'
import { LINKS } from '../conf/link'

const ERROR_MSG: string = "请求错误, 请稍后重试~";

class UserService {
    private static instance: UserService;
    public static getInstance(): UserService {
        return this.instance || (this.instance = new this());
    }

    public getInfo(userId?: number): Promise<RespData.UserMessage> {
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

    public updateInfo(userId: number, params: any): Promise<RespData.UserMessage> {
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

    public bind(userId: number, clientId: string) {
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

export {
    UserService
}