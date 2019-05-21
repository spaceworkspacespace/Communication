<style>
#x-group-new {
    max-width: 1024px;
}
</style>

<template>
    <div id="x-group-new" class="container py-3">
        <p class="font-weight-normal text-md-center" style="font-size: 18px;">新建群聊</p>
        <div class="row">
            <div class="col-12 offset-md-2 col-md-8">
                <form @submit.prevent.stop="submit" method="POST">
                    <div class="form-group">
                        <label for="groupname">群聊名称:</label>
                        <input
                            type="text"
                            v-model.trim="name"
                            class="form-control"
                            name="groupname"
                        >
                    </div>
                    <div class="form-row">
                        <div class="col-md-2">
                            <label class="btn btn-primary" @click="$refs.avatar.click()">选取图像</label>
                        </div>
                        <div class="col-md-10">
                            <img
                                @click="$refs.avatar.click()"
                                :src="avatar"
                                alt="请先选择头像"
                                style="max-width: 250px; width: 100%; margin: auto;"
                            >
                        </div>
                        <input
                            ref="avatar"
                            type="file"
                            accept="image/png, image/jpeg"
                            @change="uploadFile"
                            class="d-none"
                        >
                    </div>
                    <div class="form-group">
                        <label for="description">群聊描述:</label>
                        <textarea name="description" v-model="desc" class="form-control" rows="3"></textarea>
                    </div>
                    <!-- <button type="submit" class="btn btn-primary">提交申请</button> -->
                    <div class="form-row">
                        <input
                            type="submit"
                            value="提交申请"
                            class="btn btn-primary btn-block offset-md-2 col-md-8"
                        >
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import layer from 'layer'
import { ContactService } from '@/service/ContactService'
import { CommonService } from '@/service/CommonService'

export default {
    data: function () {
        return {
            avatar: "https://i.loli.net/2019/04/12/5cafffdaed88f.jpg",
            name: "",
            desc: "",
        }
    },
    methods: {
        submit: async function (event) {
            if (!this.avatar || !this.name || !this.desc) {
                layer.msg("信息不能为空.");
                return;
            }
            let message = "";
            try {
                let user = this.$store.state.user;
                message = await ContactService.getInstance()
                    .createGroup(user.id, {
                        groupname: this.name,
                        description: this.desc,
                        avatar: this.avatar
                    });
            } catch (e) {
                message = e.message;
                console.error(e);
            }
            layer.msg(message);

            //获取数据
            // var groupname = $("#groupname").val();
            // var avatar = $("#display-avatar").attr("src");
            // var description = $("#description").val();

            // var data = $(this).serialize();


            // $.ajax({
            //     url: "/im/contact/Group",
            //     method: "post",
            //     data: {
            //         groupname: groupname,
            //         avatar: avatar,
            //         description: description
            //     },
            //     dataType: 'json',
            //     success: function (res) {
            //         if (res.code != 1) {
            //             layer.msg(res.data, {
            //                 icon: 1,
            //                 time: 2000 //2秒关闭（如果不配置，默认是3
            //             }, function () {
            //                 //关闭当前页面层
            //                 var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
            //                 parent.layer.close(index);
            //             });

            //             return;
            //         }
            //         layer.msg(res.data, {
            //             icon: 2,
            //             time: 2000
            //         });
            //     }
            // });

        },

        uploadFile: function (event) {
            let avatar = this.$refs.avatar;
            if (!avatar.value) return;
            let file = avatar.files[0];
            if (!file) return;
            CommonService.getInstance()
                .uploadAvatar(file)
                .then(d => this.avatar = d.src)
                .catch(e => layer.msg(e.message))
                .finally(() => avatar.value = "");
            // var form = new FormData();
            // form.append("file", this.files[0]);
            // form.append("_ajax", true);
            // fetch("/im/comm/avatar", {
            //     method: "POST",
            //     body: form,
            //     headers: { "HTTP_X_REQUESTED_WITH": "xmlhttprequest" }
            // }).then(function (response) {
            //     return response.json();
            // }).then(function (res) {
            //     if (!res.code) {
            //         $("#display-avatar").attr({ src: res.data.src });
            //         $("input[name='avatar']").attr({ value: res.data.src });
            //     } else {
            //         layer.msg(res.msg);
            //     }
            // }).catch(function (e) {
            //     layer.msg(e.message);
            // });
        }
    }
}
</script>
