<style>
#x-groupmgr {
    height: 100%;
    width: 100%;
    max-width: 1024px;
    margin: auto;
    position: relative;
}
#x-groupmgr .fa {
    cursor: pointer;
}
#x-groupmgr .fa-stack:hover {
    color: #17a2b8;
}
#x-groupmgr .fa-stack.member {
    color: var(--gray);
}
#x-groupmgr .fa-stack.admin {
    color: var(--primary);
}
#x-groupmgr .fa-stack.owner {
    color: var(--danger);
}
#x-groupmgr .fa-stack.me {
    color: var(--success);
}

.x-groupmgr-list {
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

<template>
    <div id="x-groupmgr">
        <div v-if="error">
            <p style="text-align: center; line-height: 128px; margin-top: 136px;">{{errorText}}</p>
        </div>
        <div v-else-if="contacts === null">
            <p style="text-align: center; line-height: 128px; margin-top: 136px;">
                <span class="fa fa-spin fa-refresh mr-2"></span>加载中...
            </p>
        </div>
        <div v-else-if="!contacts.length">
            <p style="text-align: center; line-height: 128px;">还没有加入任何群聊.</p>
        </div>
        <div class="px-0 mx-0 row" style="height: 100%; width: 100%;" v-else>
            <div class="col-md-5 position-relative" style="min-height: 75vh;">
                <ul class="list-group">
                    <li class="list-group-item font-weight-bold text-center border-0">群聊列表</li>
                </ul>

                <ul class="nav flex-column nav-pills x-groupmgr-list">
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
                            <div style>
                                <span
                                    v-if="m._operate.edit"
                                    title="编辑群聊信息"
                                    @click.stop.prevent="editGroup(i)"
                                    class="fa-stack"
                                >
                                    <span class="fa fa-circle-thin fa-stack-2x"></span>
                                    <span class="fa fa-pencil fa-stack-1x"></span>
                                </span>
                                <span
                                    v-if="m._operate.dismiss"
                                    title="解散群聊"
                                    @click.stop.prevent="dismissGroup(i)"
                                    class="fa-stack"
                                >
                                    <span class="fa fa-circle-thin fa-stack-2x"></span>
                                    <span class="fa fa-trash fa-stack-1x"></span>
                                </span>
                                <span
                                    v-if="$store.state.user.id!==m.admin"
                                    title="退出群聊"
                                    @click.stop.prevent="leaveGroup(m)"
                                    class="fa-stack"
                                >
                                    <span class="fa fa-circle-thin fa-stack-2x"></span>
                                    <span class="fa fa-sign-out fa-stack-1x"></span>
                                </span>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="col-md-7 position-relative" style="min-height: 75vh;">
                <ul class="list-group">
                    <li class="list-group-item font-weight-bold text-center border-0">成员列表</li>
                </ul>
                <ul class="list-group x-groupmgr-list">
                    <li class="list-group-item" v-for="m of contacts[index].list" :key="m.id">
                        <div class="d-flex flex-row justify-content-between align-items-center">
                            <div>{{m.username}}</div>
                            <div>
                                <span
                                    v-if="m._operate.edit"
                                    title="别名"
                                    @click.stop.prevent="renameMember(contacts[index].id, m)"
                                    class="fa-stack"
                                >
                                    <span class="fa fa-circle-thin fa-stack-2x"></span>
                                    <span class="fa fa-pencil fa-stack-1x"></span>
                                </span>
                                <span :title="m._style.statusTitle" :class="m._style.statusClass">
                                    <span class="fa fa-circle-thin fa-stack-2x"></span>
                                    <span class="fa fa-user fa-stack-1x"></span>
                                </span>
                                <span
                                    v-if="m._operate.privilege"
                                    :title="m.isadmin? '变更成员':'提升管理'"
                                    @click.stop.prevent="privilegeMember(contacts[index], m)"
                                    class="fa-stack"
                                >
                                    <span class="fa fa-circle-thin fa-stack-2x"></span>
                                    <span :class="m._style.privilegeClass"></span>
                                </span>
                                <span
                                    v-if="m._operate.out"
                                    title="强制退出"
                                    @click.stop.prevent="removeMember(contacts[index], m)"
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

const INVALID_INPUT = "输入无效数据.";
const OPERATE_SUCCESS = "修改成功";

export default {
    data: function () {
        return {
            index: 0,
            contacts: null, // 所有的群组列表
            error: false,
            errorText: "",
        }
    },
    beforeMount: function () {
        this.pull();
        // .then(() => this.$store.commit("layer/show", {
        //     type: "groupEdit",
        //     group: this.contacts[0]
        // }));

    },

    watch: {
        index: function (val, oldValue) {
            this.orderMembers(this.contacts[val]);
            this.calculStyle(this.contacts[val]);
        }
    },

    methods: {
        pull: async function () {
            try {
                let user = this.$store.state.user;

                let groups = await ContactService.getInstance()
                    .getGroup(user.id, { include: true });

                this.contacts = groups;
                if (this.contacts.length === 0) {
                    return;
                }
                this.orderMembers(this.contacts[this.index]);
                for (let m of this.contacts) {
                    this.calculStyle(m);
                }
            } catch (e) {
                this.error = true;
                this.errorText = e.message || REQUEST_ERROR;
                layer.msg(this.errorText);
                console.error(e);
            }
        },

        switchIndex: function (index) {
            this.index = index;
        },

        editGroup: function (index) {
            this.$store.commit("layer/show", {
                type: "groupEdit",
                group: this.contacts[index],
                onsubmit: group => {
                    this.contacts.splice(index, 1, { ...group });
                }
            });
        },

        dismissGroup: async function (index) {
            let group = this.contacts[index];
            let message = "";
            try {
                let confirm = await new Promise((resolve, reject) => {
                    layer.confirm("是否解散群聊“" + group.groupname + "”?",
                        index => {
                            layer.close(index);
                            resolve(true);
                        },
                        () => resolve(false));
                });
                if (!confirm) {
                    return;
                }
                let user = this.$store.state.user;
                message = await ContactService.getInstance()
                    .deleteGroup(user.id, group);
                // layer.alert("申请提交成功！您可以在几天内前往“消息中心”取消申请。");
                layer.alert(message);
            } catch (e) {
                console.error(e);
                message = e.message;
                layer.msg(message);
            }
        },

        leaveGroup: async function (group) {
            let confirm = await new Promise((resolve, reject) => {
                layer.confirm("是否退出群聊“" + group.groupname + "”?",
                    index => {
                        layer.close(index);
                        resolve(true);
                    }, () => resolve(false));
            });
            if (!confirm) {
                layer.msg("取消操作");
                return;
            }
            let message = "";
            try {
                let user = this.$store.state.user;
                message = await ContactService.getInstance()
                    .leaveGroup(user.id, group.id);
                if (!this.contacts) {
                    return;
                }
                let index = this.contacts.indexOf(group);
                if (index >= 0) {
                    this.contacts.splice(index, 1);
                }
            } catch (e) {
                message = e.message;
                console.error(e);
            }
            layer.msg(message);
        },

        renameMember: async function (gid, member) {
            let name = await new Promise((resolve, reject) => {
                layer.prompt({
                    title: "输入成员的昵称",
                    value: member.username,
                }, (name, index) => {
                    layer.close(index);
                    resolve(name.trim());
                });
            });
            let message = "";
            try {
                if (!name || name === member.username) {
                    layer.msg(INVALID_INPUT);
                    return;
                }
                let data = await ContactService.getInstance()
                    .updateGroupMember(this.$store.state.user.id, {
                        gid,
                        uid: member.id,
                        alias: name
                    });
                Object.assign(member, data);
                message = OPERATE_SUCCESS;
            } catch (e) {
                console.error(e)
                message = e.message;
            }
            layer.msg(message);
        },

        privilegeMember: async function (group, member) {
            let gid = group.id;
            let confirm = await new Promise((resolve, reject) => {
                layer.confirm(member.username + "将被设为" + (member.isadmin ? "成员" : "管理员"), index => {
                    layer.close(index);
                    resolve(true);
                }, () => {
                    resolve(false);
                });
            });
            if (!confirm) {
                layer.msg("取消操作");
                return;
            }
            let message = "";
            try {
                let data = await ContactService.getInstance()
                    .updateGroupMember(this.$store.state.user.id, {
                        gid,
                        uid: member.id,
                        admin: !member.isadmin
                    });

                // 更新成员
                Object.assign(member, data);
                this.calculStyle(group);
                this.orderMembers(group);
                message = OPERATE_SUCCESS;
                console.log(group)
                console.log(member)
            } catch (e) {
                console.error(e);
                message = e.message;
            }
            layer.msg(message);
        },

        removeMember: async function (group, member) {
            let confirm = await new Promise((resolve, reject) => {
                layer.confirm("是否将“" + member.username + "”退出群聊“" + group.groupname + "”?",
                    index => {
                        layer.close(index);
                        resolve(true);
                    }, () => resolve(false));
            });
            if (!confirm) {
                layer.msg("取消操作");
                return;
            }
            let message = "";
            try {
                let user = this.$store.state.user;
                message = await ContactService.getInstance()
                    .deleteGroupMember(user.id, group, member);
                let index = group.list.indexOf(member);
                if (index >= 0)
                    group.list.splice(index, 1);
            } catch (e) {
                message = e.message;
                console.error(e);
            }
            layer.msg(message);
        },

        calculStyle: function (group) {
            let user = this.$store.state.user;
            group._calculStyle = true;

            group._operate = {
                edit: this.isAdmin(group) || user.id === group.admin,
                dismiss: user.id === group.admin
            };
            for (let m of group.list) {
                m._operate = {
                    edit: group.admin !== m.id && (this.isAdmin(group) && !m.isadmin || m.id === user.id || user.id === group.admin),
                    privilege: user.id === group.admin && user.id != m.id,
                    out: group.admin !== m.id && m.id !== user.id && (this.isAdmin(group) && !m.isadmin || user.id === group.admin)
                };
                m._style = {
                    statusTitle: group.admin !== m.id ? (m.isadmin ? '管理员' : '成员') : '群主',
                    statusClass: [
                        'fa-stack', 'member',
                        m.isadmin ? 'admin' : '',
                        group.admin !== m.id ? '' : 'owner',
                        m.id !== user.id ? '' : 'me'
                    ],
                    privilegeClass: ['fa', 'fa-level-' + (m.isadmin ? 'down' : 'up'), 'fa-stack-1x'],
                };
            }
        },

        /**
         * 排序群组中的成员, 所有者排第一, 然后是管理者再是普通成员. 当前用户在所在分级中排第一.
         * @param group 群聊数据
         */
        orderMembers: function (group) {
            let user = this.$store.state.user;
            if (!(group instanceof Array) 
                || !(group.list instanceof Array)) {
                return;
            }
            group.list.sort((l, r) => {
                if (l.id === group.admin || r.id === group.admin) { // 所有者
                    return l.id !== group.admin ? 1 : -1;
                } else if (l.isadmin != r.isadmin) { // 管理员
                    return l.isadmin ? -1 : 1;
                } else if (l.id === user.id || r.id === user.id) { // 当前用户
                    return l.id !== user.id ? 1 : -1;
                } else { // 名称
                    let ln = l.username;
                    let rn = r.username;
                    let minLen = ln.length > rn.length ? rn.length : ln.length;
                    for (let i = 0; i < minLen; i++) {
                        if (ln.charAt(i) != rn.charAt(i)) {
                            return ln.charAt(i) > rn.charAt(i) ? 1 : -1;
                        }
                    }
                    return minLen !== ln.length ? 1 : -1;
                }
            });
        },

        /**
         * 判断当前登录用户在群组中是否为管理员
         * @param group 群聊
         */
        isAdmin: function (group) {
            let user = this.$store.state.user;
            for (let m of group.list) {
                if (user.id === m.id) {
                    return m.isadmin;
                }
            }
            return false;
        }
    }
}
</script>