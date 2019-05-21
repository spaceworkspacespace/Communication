<template>
    <div class="rounded bg-white border shadow x-list-select">
        <p class="border bg-light font-weight-bold text-center py-2 my-0">{{title}}</p>
        <ul class="list-group" style="max-height: 384px; overflow-y: auto;">
            <li
                @click="onSelect(i)"
                class="list-group-item x-friend-group-select-item"
                v-for="(m, i) of list"
                :key="i"
            >{{m}}</li>
        </ul>
    </div>
</template>
<script>
export default {
    props: ["list", "title", "auto-close"],
    methods: {
        onSelect: function (index) {
            this.$emit("select", index);

            if (this.autoClose) {
                this.$store.commit("layer/hide", { type: "listSelect" });
            }
        }
    }
}
</script>
<style>
.x-list-select {
    position: absolute;
    transform: translate(-50%, -50%);
    left: 50vw;
    top: 50vh;
    /* min-width: 340px; */
    max-width: 100vw;
    max-height: 65vh;
    z-index: 500;
}
.x-friend-group-select-item {
    cursor: pointer;
}
.x-friend-group-select-item:hover {
    background-color: rgba(3, 3, 3, 0.2);
    color: white;
}

/* 无 bootstarp 使用样式 */
.x-list-select {
    background-color: white;
    border-radius: .5em;
    box-shadow: 1px 1.5px 2px gary;
    display: flex;
    flex-shrink: 0;
    box-sizing: border-box;
    padding: 15px 25px;
    margin: 0;
    flex-direction: column;
    align-items: stretch;
}
.x-list-select p {
    font-size: 16px;
    font-weight: 400;
    padding-bottom: 15px;
}
.x-list-select ul {
    list-style: none;
    margin: 0;
    padding: 0;
}
.x-list-select li {
    margin: 0;
    padding: 5px 10px;
}
</style>