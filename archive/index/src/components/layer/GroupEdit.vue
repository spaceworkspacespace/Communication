<template>
    <div class="x-group-edit container">
        <p class="bg-light border h3 mx-0 px-2 my-2 " style="line-height: 55px;position: relative; left: 0; right: 0; ">
            群组编辑
        </p>
        <form class="px-3">
            <div class="row my-3">
                <div class="col-sm-4 col-md-3 x-colon font-weight-bold">
                    图片
                    <small>(点击图像进行切换)</small>
                </div>
                <div class="col-sm-8 col-md-9 offset-sm-0 offset-1">
                    <img
                        @click="selectPicture"
                        :src="g.avatar"
                        style="width: 96px; height: 96px; cursor: pointer;"
                    >
                    <input
                        ref="avatar"
                        accept="image/png, image/jpeg"
                        type="file"
                        style="display: none;"
                        @change="submitPicture"
                    >
                </div>
            </div>
            <hr>
            <div class="row my-3">
                <div class="col-sm-4 col-md-3 x-colon font-weight-bold">群名称</div>
                <div class="col-sm-8 col-md-9 offset-sm-0 offset-1">
                    <input type="text" class="form-control" v-model="g.groupname">
                </div>
            </div>
            <hr>
            <div class="row my-3">
                <div class="col-sm-4 col-md-3 x-colon font-weight-bold">创建时间</div>
                <div class="col-sm-8 col-md-9 offset-sm-0 offset-1">
                    <p>{{format(new Date(g.createtime*1000))}}</p>
                </div>
            </div>
            <hr>
            <div class="row my-3">
                <div class="col-sm-4 col-md-3 x-colon font-weight-bold">群主</div>
                <div class="col-sm-8 col-md-9 offset-sm-0 offset-1">
                    <p>
                        {{adminName}}
                        <a
                            v-if="isAdmin"
                            @click.prevent="transfer"
                            class="text-danger pl-2"
                            href="javascript:;"
                        >转让</a>
                    </p>
                </div>
            </div>
            <hr>
            <div class="row my-3">
                <div class="col-sm-4 col-md-3 x-colon font-weight-bold">成员数量</div>
                <div class="col-sm-8 col-md-9 offset-sm-0 offset-1">
                    <p>{{g.membercount}}</p>
                </div>
            </div>
            <hr>
            <div class="row my-3">
                <div class="col-sm-4 col-md-3 x-colon font-weight-bold">管理员列表</div>
                <div class="col-sm-8 col-md-9 offset-sm-0 offset-1">
                    <ul class="list-group" style="max-height: 384px; overflow-y: auto;">
                        <li v-for="m of adminList" :key="m.id" class="list-group-item">
                            <div class="row">
                                <div class="col-3 col-md-2">
                                    <img :src="m.avatar" style="width: 32px; height: 32px;">
                                </div>
                                <div class="col-9 col-md-10">
                                    <p>{{m.username}}</p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="row my-3">
                <div class="col-sm-4 col-md-3 x-colon font-weight-bold">描述</div>
                <div class="col-sm-8 col-md-9 offset-sm-0 offset-1">
                    <textarea
                        class="form-control"
                        style="min-height: 194px;"
                        row
                        my-3="5"
                        v-model="g.description"
                    ></textarea>
                </div>
            </div>
            <hr>
            <div class="row my-3">
                <div class="offset-sm-2 col-sm-4">
                    <button @click="submit" type="button" class="btn btn-primary btn-block">保存</button>
                </div>
                <div class="col-sm-4 my-3 my-sm-0">
                    <button @click="close" type="button" class="btn btn-secondary btn-block">关闭</button>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
import layer from 'layer'
import { CommonService } from '@/service/CommonService'
import { ContactService } from '@/service/ContactService'
import { dateFormat } from '@/util/functions'

const INVALID_FILE = "无效文件";
const ERROR_MSG = "操作失败, 请稍后重试~";
export default {
    props: ["group"],
    data: function () {
        return {
            g: {},
            edited: false, // 是否有更改
        };
    },
    beforeMount: function () {
        Object.assign(this.g, this.group);
    },
    computed: {
        adminName: function () {
            for (let m of this.g.list) {
                if (m.id === this.g.admin) {
                    return m.username;
                }
            }
            return this.g.admin;
        },
        adminList: function () {
            return this.g.list.filter(i => i.isadmin);
        },
        isAdmin: function () {
            return this.g.id === this.$store.state.user.id;
        }
    },
    methods: {
        format: function (date) {
            return dateFormat(date, "yyyy-MM-dd");
        },

        selectPicture: function () {
            this.$refs.avatar.click();
        },

        submitPicture: async function () {
            if (!this.$refs.avatar.value) return;
            let file = this.$refs.avatar.files[0];
            if (!file) {
                layer.msg(INVALID_FILE);
                return;
            }
            try {
                let result = await CommonService.getInstance()
                    .uploadAvatar(file);
                this.g = Object.assign({}, this.g, { avatar: result.src });
            } catch (e) {
                console.error(e);
                layer.msg(e.message || ERROR_MSG);
            }
            this.$refs.avatar.value = "";
        },

        transfer: async function () {
            let confirm = await new Promise((resolve, reject) => {
                layer.confirm("转移之后, 您将会变更为成员, 是否确认?", {
                    title: "群聊转移"
                },
                    index => {
                        layer.close(index);
                        resolve(true);
                    },
                    () => resolve(false))
            });
            if (!confirm) {
                return;
            }
            let member = await new Promise((resolve, reject) => {
                this.$store.commit("layer/show", {
                    title: "选择转移成员",
                    type: "listSelect",
                    list: this.g.list.map(i => i.username + " (" + i.id + ")"),
                    onselect: index => {
                        resolve(this.g.list[index]);
                    }
                });
            });
            this.g.admin = member.id;
            this.g = Object.assign({}, this.g, { admin: member.id });
        },

        close: function () {
            this.$store.commit("layer/hide", {
                type: "groupEdit"
            });
            if (this.edited) {
                this.$emit("submit", this.g);
            }
        },

        submit: async function () {
            let confirm = await new Promise((resolve, reject) => {
                layer.confirm("提交后将不可回退 !",
                    index => {
                        layer.close(index);
                        resolve(true);
                    },
                    () => resolve(false));
            });
            if (!confirm) {
                layer.msg("取消提交");
                return;
            }
            let user = this.$store.state.user;
            let message = "修改成功";
            try {
                let result = await ContactService.getInstance()
                    .pudateGroup(user.id, {
                        gid: this.g.id,
                        name: this.g.groupname,
                        desc: this.g.description,
                        avatar: this.g.avatar,
                        admin: this.g.admin
                    });
                this.g = Object.assign({}, this.g, result);
                this.edited = true;
            } catch (e) {
                console.error(e);
                message = e.message;
            }
            layer.msg(message);
        }
    }
}
</script>

<style>
.x-group-edit {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: white;
    z-index: 200;
    overflow-y: auto;
}
</style>