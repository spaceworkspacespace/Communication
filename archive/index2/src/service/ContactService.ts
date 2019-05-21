import axios from 'axios'
import { LINKS } from '../conf/link'

const ERROR_MSG = "请求失败, 请稍后重试~";

class ContactService {
    static _instance: ContactService = null;

    static getInstance() {
        return ContactService._instance || (ContactService._instance = new ContactService());
    }

    /**
     * 通过分组名获取分组 id, 如果不存在分组会尝试创建
     * @param {number} userId 
     * @param {string} fgName 
     * @returns {number} 分组 id
     */
    async determineFriendGroupId(userId: number, fgName: string) {
        // 获取用户的所有分组
        let fg = await axios({
            method: "GET",
            url: LINKS.contact.friendGroup,
        }).then(resp => {
            if (resp.data.code) {
                throw new Error(resp.data.msg || ERROR_MSG);
            }
            return resp.data.data;
        });

        // 获取好友分组
        let gid = null;
        for (let g of fg) {
            if (g.groupname === fgName) {
                gid = g.id;
                break;
            }
        }

        // 创建新的分组
        if (!gid) {
            let group = await this.createFriendGroup(userId, fgName);
            gid = group.id;
        }
        return gid;
    }



    async getFriendAndGroup(): Promise<RespData.FriendGroupMessage[]> {
        let result: any = await axios({
            method: "GET",
            url: LINKS.contact.friendGroup,
            params: {
                include: true
            }
        });
        result = result.data;

        // console.log(result)
        // 请求错误
        if (result.code || !result.data) {
            throw new Error(result.msg || ERROR_MSG);
        }
        return result.data;
    }

    getFriendGroup(userId: number, include: boolean): Promise<RespData.FriendGroupMessage[] | string> {
        return axios({
            method: "GET",
            url: LINKS.contact.friendGroup,
            params: {
                include
            }
        }).then(resp => {
            let payload = resp.data;
            if (payload.code) {
                throw new Error(payload.msg || ERROR_MSG);
            }
            let { data, msg }: { data: RespData.FriendGroupMessage[], msg: string } = payload;
            data.forEach(i => i.createtime *= 1000);
            return data || msg;
        });
    }

    getFriend(userId: number, params: { keyword?: number, id?: number, no?: number, count?: number }): Promise<RespData.UserMessage> {
        return axios({
            method: "GET",
            params,
            url: LINKS.contact.friend
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    addFriend(userId: number, uId: number, fgId: number, info: string) {
        return axios({
            method: "POST",
            data: {
                id: uId,
                fgId,
                content: info
            },
            url: LINKS.contact.friend
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    updateFriend(userId: number, contact: any) {
        return axios({
            method: "PUT",
            params: contact,
            url: LINKS.contact.friend
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    deleteFriend(userId: number, contactId: number) {
        return axios({
            method: "DELETE",
            params: {
                id: contactId
            },
            url: LINKS.contact.friend
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    createFriendGroup(userId: number, fgName: string): Promise<RespData.FriendGroupMessage> {
        return axios({
            method: "POST",
            url: LINKS.contact.friendGroup,
            params: {
                groupname: fgName
            }
        }).then(resp => {
            if (resp.data.code) {
                throw new Error(resp.data.msg || ERROR_MSG);
            }
            return resp.data.data;
        });
    }

    updateFriendGroup(userId: number, group: any) {
        return axios({
            method: "PUT",
            params: group,
            url: LINKS.contact.friendGroup
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    deleteFriendGroup(userId: number, params: any) {
        return axios({
            method: "DELETE",
            params,
            url: LINKS.contact.friendGroup
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    leaveGroup(userId: number, gid: number): Promise<string> {
        return axios({
            url: LINKS.contact.mygroup,
            method: "DELETE",
            params: {
                gid
            }
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    joinGroup(userId: number, gid: number, info: string) {
        return axios({
            method: "POST",
            data: {
                gid,
                content: info
            },
            url: LINKS.contact.mygroup
        }).then(resp => {
            let data = resp.data;
            let msg = data.data || data.msg;
            if (data.code) {
                throw new Error(msg || ERROR_MSG);
            }
            return msg;
        });
    }

    createGroup(userId: number, group: any) {
        return axios({
            method: "POST",
            data: group,
            url: LINKS.contact.group
        }).then(resp => {
            let data = resp.data;
            let msg = data.data || data.msg;
            if (data.code) {
                throw new Error(msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    getGroup(userId: number, params: any = {}) {
        return axios({
            method: "GET",
            params: params,
            url: LINKS.contact.group
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    pudateGroup(userId: number, params: any) {
        return axios({
            method: "PUT",
            data: params,
            url: LINKS.contact.group
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    deleteGroup(userId: number, group: any) {
        return axios({
            method: "DELETE",
            params: { gid: group.id },
            url: LINKS.contact.group
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    getGroupMember(userId: number, groupId: number) {
        return axios({
            method: "GET",
            url: LINKS.contact.groupMember,
            params: {
                id: groupId
            }
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data.list || data.msg;
        });
    }

    deleteGroupMember(userId: number, group: any, member: any) {
        return axios({
            method: "DELETE",
            url: LINKS.contact.groupMember,
            params: {
                gid: group.id,
                uid: member ? member.id : userId
            }
        }).then(resp => {
            let data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    }

    updateGroupMember(userId: number, params: any) {
        return axios({
            method: "PUT",
            url: LINKS.contact.groupMember,
            params,
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
    ContactService
}