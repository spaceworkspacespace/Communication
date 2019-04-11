import axios from 'axios'
import layer from 'layer'

const actions = {
    // 获取用户信息
    getUser: function({ commit }) {
        axios({
            method: "GET",
            url: "/im/user/info"
        }).then(resp => {
            if (resp.data.code) {
                layer.msg(resp.data.msg || "获取用户信息失败.");
                commit("setUser", null);
            } else {
                commit("setUser", resp.data.data);
            }
        });
    }
};

export {
    actions
}