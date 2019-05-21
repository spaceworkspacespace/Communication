<style>
#x-layer-contact-add {
    position: absolute;
    background-color: white;
    z-index: 201;
    overflow-y: auto;
    max-width: 640px;
    min-width: 320px;
    width: 80vw;
    padding-bottom: 25px;
}
</style>
<template>
    <div id="x-layer-contact-add" class="container x-absolute-center rounded shadow border">
        <p class="bg-light border h3 mx-0 px-2 my-2 " style="line-height: 55px;position: relative; left: 0; right: 0; ">
            {{title}}
        </p>
        <form>
            <div class="form-group" v-if="list">
                <label>选择好友的分组</label>
                <select class="form-control" v-model="option">
                    <option disabled value>请选择</option>
                    <option v-for="m of list" v-bind:key="m.value" :value="m.value">{{m.text}}</option>
                </select>
                <small>在好友添加成功后, 将会到你选择的分组下.</small>
            </div>
            <div class="form-group">
                <label>验证信息</label>
                <textarea class="form-control" v-model="content"></textarea>
            </div>
            <div class="form-row">
                <button
                    @click="submit"
                    type="button"
                    class="offset-1 col-4 btn btn-block btn-primary"
                >发送</button>
                <button
                    @click="cancel"
                    type="button"
                    class="offset-2 col-4 btn btn-block btn-secondary mt-0"
                >取消</button>
            </div>
        </form>
    </div>
</template>
<script>
import DropList from '@/components/DropList'
export default {
    props: ["list", "title", "hint", "autoClose"],
    components: { DropList },
    data: function () {
        return {
            option: "",
            content: "",
        };
    },
    methods: {
        cancel: function (event) {
            this.$store.commit("layer/hide", { type: "contactAdd" });
            this.$emit("cancel");
        },
        submit: function (event) {
            if (this.list && !this.option || !this.content) {
                layer.msg("信息不能为空!");
                return;
            }
            this.$emit('submit', { option: this.option, content: this.content });
            if (this.autoClose) {
                this.$store.commit("layer/hide", { type: "contactAdd" });
            }
        }
    }
}
</script>
