"use strict";
exports.__esModule = true;
var axios_1 = require("axios");
var link_1 = require("../conf/link");
var ERROR_MSG = "请求错误, 请稍后重试~";
var MessageService = /** @class */ (function () {
    function MessageService() {
    }
    MessageService.getInstance = function () {
        return this.instance || (this.instance = new this());
    };
    MessageService.prototype.pull = function (userId) {
        return axios_1["default"]({
            method: "POST",
            url: link_1.LINKS.message.pull
        }).then(function (resp) {
            var data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    };
    return MessageService;
}());
exports.MessageService = MessageService;
