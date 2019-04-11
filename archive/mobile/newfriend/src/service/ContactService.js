import axios from 'axios'

class ContactService {
    static _instance = null;

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
            url: "/im/contact/friendgroup",
        }).then(resp => {
            if (resp.data.code) {
                throw new Error(resp.data.msg || ERROR_MSG);
            }
            return resp.data.data;
        });

        // 获取好友分组
        let gid = null;
        for (let g of fg) {
            if (g.group_name === fgName) {
                gid = g.id;
                break;
            }
        }

        // 创建新的分组
        if (!gid) {
            let group = await axios({
                method: "POST",
                url: "/im/contact/friendgroup",
                params: {
                    groupname: fgName
                }
            }).then(resp => {
                if (resp.data.code) {
                    throw new Error(resp.data.msg || ERROR_MSG);
                }
                return resp.data.data;
            });
            gid = group.id;
        }
        return gid;
    }
}

export {
    ContactService
}