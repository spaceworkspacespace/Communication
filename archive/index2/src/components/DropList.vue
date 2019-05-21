<template>
    <div class="x-droplist">
        <button @click.stop="toggle" class="btn btn-block btn-default border">{{text}}</button>
        <div v-show="show" class="position-relative">
            <ul class="list-group x-list">
                <li
                    @click.stop="select(i);"
                    class="list-group-item"
                    v-for="(m, i) of list"
                    :key="i"
                >{{m}}</li>
            </ul>
        </div>
    </div>
</template>

<script>
export default {
    name: "DropList",
    props: ["list", "defaultText"],
    data: function () {
        return {
            text: "",
            show: false
        };
    },
    beforeMount: function () {
        this.text = this.defaultText || "请选择";
    },
    methods: {
        select: function (index) {
            this.$emit('xchange', index);
            this.text = this.list[index];
            this.show = !this.show;
        },
        toggle: function () {
            this.show = !this.show;
        }
    }
}
</script>

<style scoped>
.x-droplist {
    position: relative;
}
.x-list {
    position: absolute;
    left: 0;
    top: 0;
    right: 0;
    max-height: 320px;
    overflow-y: auto;
}
.x-list li {
    cursor: pointer;
}
.x-list li:hover {
    background-color: var(--light);
}
.x-list li:focus {
    background-color: var(--secondary);
}
</style>
