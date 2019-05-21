<template>
    <mask-container v-if="show" style="z-index: 100;">
        <mask-container
            v-if="layers.groupEdit.show"
            style="background-color: transparent; z-index: 100;"
        >
            <group-edit
                :style="layers.groupEdit.style"
                :auto-close="layers.groupEdit.autoClose"
                :group="layers.groupEdit.group"
                @submit="layers.groupEdit.onsubmit"
            />
        </mask-container>
        <!-- <mask-container
            v-show="layers.groupNew.show"
            style="background-color: transparent; z-index: 100;"
        >
            <group-edit
                :style="layers.groupNew.style"
                :auto-close="layers.groupNew.autoClose"
                :group="layers.groupNew.group"
                @submit="layers.groupNew.onsubmit"
            />
        </mask-container> -->
        <mask-container
            v-if="layers.contactAdd.show"
            style="background-color: transparent; z-index: 110;"
        >
            <contact-add
                :style="layers.contactAdd.style"
                :title="layers.contactAdd.title"
                :list="layers.contactAdd.list"
                @submit="layers.contactAdd.onsubmit"
                @cancel="layers.contactAdd.oncancel"
                :auto-close="layers.contactAdd.autoClose"
            />
        </mask-container>
        <mask-container
            v-if="layers.listSelect.show"
            style="background-color: transparent; z-index: 120;"
        >
            <list-select
                :style="layers.listSelect.style"
                :title="layers.listSelect.title"
                :list="layers.listSelect.list"
                @select="layers.listSelect.onselect"
                :auto-close="layers.listSelect.autoClose"
            />
        </mask-container>
    </mask-container>
</template>

<script>
import MaskContainer from '@/components/MaskContainer'
import ListSelect from '@/components/layer/ListSelect'
import GroupEdit from '@/components/layer/GroupEdit'
import ContactAdd from '@/components/layer/ContactAdd'
// import GroupNew from '@/components/layer/GroupNew'

export default {
    components: {
        ListSelect,
        MaskContainer,
        GroupEdit,
        ContactAdd,
        // GroupNew
    },
    beforeMount: function () {

    },
    watch: {
        show: function (nv, ov) {
            $(document.body).css({ "overflow-y": nv ? "hidden" : "auto" });
        }
    },
    computed: {
        layers: function () {
            // console.log(this.$store.state.layer)
            return this.$store.state.layer;
        },
        show: function () {
            return Object.values(this.layers).some(i => i.show);
        }
    }
}
</script>

<style>
</style>