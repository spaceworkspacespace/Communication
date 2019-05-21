<template>
    <div id="x-friendmgr">
        <div v-if="error">
            <p style="text-align: center; line-height: 128px; margin-top: 136px;">{{errorText}}</p>
        </div>
        <div v-else-if="!contacts.length">
            <p style="text-align: center; line-height: 128px; margin-top: 136px;">
                <span class="fa fa-spin fa-refresh mr-2"></span>加载中...
            </p>
        </div>
        <div class="row px-0 mx-0" style="height: 100%; width: 100%;" v-else>
            <div class="col-md-5" style="min-height: 75vh;">
                <ul class="list-group">
                    <li class="list-group-item font-weight-bold text-center border-0">分组列表</li>
                </ul>
                <div class="py-2">
                    <span title="添加分组" @click.prevent="addGroup()" class="fa-stack">
                        <span class="fa fa-circle-thin fa-stack-2x"></span>
                        <span class="fa fa-plus fa-stack-1x"></span>
                    </span>
                </div>
                <ul
                    class="nav flex-column nav-pills x-friendmgr-list"
                    style="top: 96px;"
                >
                    <li
                        class="nav-item"
                        @click="switchIndex(i)"
                        v-for="(m, i) of contacts"
                        :key="m.id"
                    >
                        <div
                            :class="['nav-link d-flex border flex-row justify-content-between align-items-center', i !== index? '': 'active']"
                            style="cursor:pointer;"
                            href="javascript:;"
                        >
                            <div>{{m.groupname}}</div>
                            <div style="min-width: 70px;">
                                <span
                                    title="重命名分组"
                                    @click.stop.prevent="renameGroup(i)"
                                    class="fa-stack"
                                >
                                    <span class="fa fa-circle-thin fa-stack-2x"></span>
                                    <span class="fa fa-pencil fa-stack-1x"></span>
                                </span>
                                <span
                                    title="删除分组"
                                    @click.stop.prevent="deleteFriendGroup(i)"
                                    class="fa-stack"
                                >
                                    <span class="fa fa-circle-thin fa-stack-2x"></span>
                                    <span class="fa fa-trash fa-stack-1x"></span>
                                </span>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="col-md-7" style="min-height: 75vh;">
                <ul class="list-group">
                    <li class="list-group-item font-weight-bold text-center border-0">好友列表</li>
                </ul>
                <ul class="list-group x-friendmgr-list" >
                    <li class="list-group-item" v-for="m of contacts[index].list" :key="m.id">
                        <div class="d-flex flex-row justify-content-between align-items-center">
                            <div>{{m.username}}</div>
                            <div>
                                <span
                                    title="别名"
                                    @click.stop.prevent="renameFriend(index, m)"
                                    class="fa-stack"
                                >
                                    <span class="fa fa-circle-thin fa-stack-2x"></span>
                                    <span class="fa fa-pencil fa-stack-1x"></span>
                                </span>
                                <span
                                    title="移动联系人"
                                    @click.stop.prevent="moveFriend(m.username, m.id)"
                                    class="fa-stack"
                                >
                                    <span class="fa fa-circle-thin fa-stack-2x"></span>
                                    <span class="fa fa-exchange fa-stack-1x"></span>
                                </span>
                                <span
                                    title="删除联系人"
                                    @click.stop.prevent="deleteFriend(m.username, m.id)"
                                    class="fa-stack"
                                >
                                    <span class="fa fa-circle-thin fa-stack-2x"></span>
                                    <span class="fa fa-trash fa-stack-1x"></span>
                                </span>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>


<script>
import layer from 'layer'
import { ContactService } from '@/service/ContactService'
const INVALID_INPUT = "无效的输入.";
const GROUP_EXISTED = "分组已存在.";
const REQUEST_ERROR = "请求错误, 请稍后重试.";

export default {
    data: function () {
        return {
            index: 0,
            contacts: [],
            error: false,
            errorText: "",
        }
    },
    computed: {
        groupNames: function () {
            return this.contacts.map(i => i.groupname);
        }
    },
    components: {
        // "drop-list": DropList,
        // FriendGroupSelect,
        // MaskContainer
    },
    beforeMount: function () {
        this.pull();
    },

    methods: {
        pull: function () {
            return ContactService.getInstance()
                .getFriendAndGroup()
                .then(g => this.contacts = g)
                .catch(e => {
                    this.error = true;
                    this.errorText = e.message || REQUEST_ERROR;
                    layer.msg(this.errorText);
                });

        },

        deleteFriend: async function (contactName, contactId) {
            let message = REQUEST_ERROR;
            let user = this.$store.state.user;

            try {
                let confirm = await new Promise((resolve, reject) => {
                    layer.confirm(`确认删除'${contactName}'?`,
                        { title: "删除联系人" },
                        index => {
                            layer.close(index);
                            resolve(true);
                        },
                        () => {
                            resolve(false);
                        }
                    );
                });
                if (!confirm) return;

                message = await ContactService.getInstance()
                    .deleteFriend(user.id, contactId);
                this.pull();
            } catch (e) {
                console.error(e)
                message = e.message;
            }
            layer.msg(message);
        },

        deleteFriendGroup: async function (index) {
            let message = REQUEST_ERROR;
            let user = this.$store.state.user;

            try {
                let deleteId = this.contacts[index].id;
                // 默认分组
                let transfer = await ContactService.getInstance()
                    .determineFriendGroupId(user.id, "我的好友");
                if (deleteId === transfer) {
                    throw new Error("默认分组无法删除.");
                }
                // 拉取最新的联系人信息
                await this.pull();
                // 分组中存在联系人, 选择联系人转移的分组
                if (this.contacts[index].list.length) {
                    let index = await new Promise((resolve, reject) => {
                        this.$store.commit("layer/show", {
                            type: "listSelect",
                            title: "将分组中的联系人转移到",
                            list: this.groupNames,
                            style: { width: "340px" },
                            onselect: index => resolve(index),
                        });
                    });
                    if (this.contacts[index] && typeof this.contacts[index].id === "number") {
                        let gid = this.contacts[index].id;
                        transfer = gid;
                    }
                }
                // 更新
                message = await ContactService.getInstance()
                    .deleteFriendGroup(user.id, {
                        id: deleteId,
                        into: transfer,
                    });
                await this.pull();
                // 移动当前选中的分组
                for (let g, i = this.contacts.length - 1; i >= 0; i--) {
                    g = this.contacts[i];
                    if (g.id === transfer) {
                        this.index = i;
                        return;
                    }
                }
            } catch (e) {
                console.error(e)
                message = e.message;
            }
            layer.msg(message);
        },
        // 将好友移动到其他分组
        moveFriend: async function (contactName, contactId) {
            let message = REQUEST_ERROR;
            let user = this.$store.state.user;
            try {
                let index = await new Promise((resolve, reject) => {
                    this.$store.commit("layer/show", {
                        type: "listSelect",
                        title: "将好友移动到",
                        list: this.groupNames,
                        style: { width: "340px" },
                        onselect: index => resolve(index),
                    });
                });
                let groupId = this.contacts[index].id;
                message = await ContactService.getInstance()
                    .updateFriend(user.id, {
                        contact: contactId,
                        group: groupId,
                    });
                await this.pull();
                // 移动当前选中的分组
                for (let g, i = this.contacts.length - 1; i >= 0; i--) {
                    g = this.contacts[i];
                    if (g.id === groupId) {
                        this.index = i;
                        return;
                    }
                }
            } catch (e) {
                console.error(e)
                message = e.message;
            }
            layer.msg(message);
        },

        renameFriend: async function(index, m) {
            let message = "";
            try {
                let nName = await new Promise(
                (resolve, reject) =>
                    layer.prompt({
                        title: "好友别名",
                        value: m.username
                    }, function (val, index) {
                        layer.close(index);
                        resolve(val.trim());
                    }));
                // 名称相同不做处理
                if (nName === m.username) {
                    return;
                }          
                let user = this.$store.state.user;          
                let data = await ContactService
                    .getInstance()
                    .updateFriend(user.id, { contact: m.id, alias: nName});
                // await this.pull();
                m.username = data.alias;
                // let newInfo = this.contacts[index];
                // console.log(index, newInfo, data, data.alias)
                // this.contacts.splice(index, 1, newInfo);
                // console.log(this.contacts)
            } catch(e) {
                message = e.message;
                layer.msg(message);
                console.error(e);
            }
        },

        groupNameCheck: function (name) {
            if (!name) {
                layer.msg(INVALID_INPUT);
                return false;
            }
            for (let g of this.contacts) {
                if (g.groupname === name) {
                    layer.msg(GROUP_EXISTED);
                    return false;
                }
            }
            return true;
        },
        // 切换索引
        switchIndex: function (index) {
            this.index = index;
        },

        /**
         * 重命名分组
         */
        renameGroup: async function (index) {
            let name = await new Promise(
                (resolve, reject) =>
                    layer.prompt({
                        title: "分组名称",
                        value: "在此输入新的分组名称"
                    }, function (val, index) {
                        layer.close(index);
                        resolve(val.trim());
                    }));
            if (!this.groupNameCheck(name)) return;

            let user = this.$store.state.user;
            let result = '';
            try {
                result = await ContactService.getInstance()
                    .updateFriendGroup(user.id, {
                        id: this.contacts[index].id,
                        name
                    });
                let group = this.contacts[index]
                group.groupname = name;
                this.contacts.splice(index, 1, group);
            } catch (e) {
                result = e.message;
            }
            layer.msg(result);
        },

        addGroup: async function (name) {
            if (!name) {
                name = await new Promise(
                    (resolve, reject) =>
                        layer.prompt({
                            title: "分组名称",
                            value: "在此输入新的分组名称"
                        }, function (val, index) {
                            layer.close(index);
                            resolve(val.trim());
                        }));
                if (!name) {
                    layer.msg(INVALID_INPUT);
                    return;
                }
                for (let g of this.contacts) {
                    if (g.groupname === name) {
                        layer.msg(GROUP_EXISTED);
                        return;
                    }
                }
            }
            let user = this.$store.state.user;
            try {
                await ContactService.getInstance()
                    .createFriendGroup(user.id, name);
            } catch (e) {
                layer.msg(e.message || REQUEST_ERROR);
            } finally {
                this.pull();
            }
        }
    }

}


</script>

<style>
#x-friendmgr {
    height: 100%;
    width: 100%;
    max-width: 1024px;
    margin: auto;
    position: relative;
}
#x-friendmgr .fa {
    cursor: pointer;
}
#x-friendmgr .fa-stack:hover {
    color: #17a2b8;
}

.x-friendmgr-list {
    overflow-y: auto;
    flex-wrap: nowrap;
    top: 48px;
    position: absolute;
    bottom: 25px;
    left: 0;
    right: 0;
    padding: 0 15px;
}
</style>
