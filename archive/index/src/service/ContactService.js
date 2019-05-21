import axios from 'axios';
import { LINKS } from '../conf/link';
const ERROR_MSG = "请求失败, 请稍后重试~";
class ContactService {
    static getInstance() {
        return ContactService._instance || (ContactService._instance = new ContactService());
    }
    /**
     * 通过分组名获取分组 id, 如果不存在分组会尝试创建
     * @param {number} userId
     * @param {string} fgName
     * @returns {number} 分组 id
     */
    async determineFriendGroupId(userId, fgName) {
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
    async getFriendAndGroup() {
        let result = await axios({
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
    getFriendGroup(userId, include) {
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
            let { data, msg } = payload;
            data.forEach(i => i.createtime *= 1000);
            return data || msg;
        });
    }
    getFriend(userId, params) {
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
    addFriend(userId, uId, fgId, info) {
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
    updateFriend(userId, contact) {
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
    deleteFriend(userId, contactId) {
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
    createFriendGroup(userId, fgName) {
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
    updateFriendGroup(userId, group) {
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
    deleteFriendGroup(userId, params) {
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
    leaveGroup(userId, gid) {
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
    joinGroup(userId, gid, info) {
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
    createGroup(userId, group) {
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
    getGroup(userId, params = {}) {
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
    pudateGroup(userId, params) {
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
    deleteGroup(userId, group) {
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
    getGroupMember(userId, groupId) {
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
    deleteGroupMember(userId, group, member) {
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
    updateGroupMember(userId, params) {
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
ContactService._instance = null;
export { ContactService };
