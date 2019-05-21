const common = {
    type: "",
    show: false,
    style: {},
    autoClose: true
};

const defaultState = {
    listSelect: {
        ...common,
        title: "选项列表",
        list: ["选项1", "选项2", "..."],
        onselect: index => { }, // 回调返回选择的索引
    },
    groupEdit: {
        ...common,
        group: {},
        onsubmit: group => { }, // 回调返回编辑后的群聊
    },
    contactAdd: {
        ...common,
        title: "添加联系人",
        hint: "请填写验证信息",
        list: [{ text: "选项1", value: "one" }, { text: "选项2", value: "two" }, "..."],
        onsubmit: ({ option, content }) => { }, // 回调返回编辑后的选项和内容, option 为选项的 value 值
        oncancel: () => { }, // 取消
    }
};

const state = { ...defaultState };

const actions = {
    show: function () {

    },
    hide: function () {

    }
};

const mutations = {
    show: function (state, payload) {
        state[payload.type] = Object.assign({}, defaultState[payload.type], payload, { show: true });
    },
    hide: function (state, payload) {
        // 关闭所有
        if (!payload) {
            for (let prop in state) {
                if (state[prop].show) {
                    state[prop].show = false;
                }
            }
        } else { // 关闭单个
            state[payload.type] = { show: false };
        }
    }
};

const layer = {
    namespaced: true,
    state,
    actions,
    mutations
};

export {
    layer
}