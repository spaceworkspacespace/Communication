"use strict";
exports.__esModule = true;
/// <reference path="../types/types.d.ts" />
var axios_1 = require("axios");
var link_1 = require("../conf/link");
var ERROR_MSG = "请求错误, 请稍后重试~";
var ChatService = /** @class */ (function () {
    function ChatService() {
    }
    ChatService.getInstance = function () {
        return this.instance || (this.instance = new this());
    };
    ChatService.prototype.getMessage = function (userId, params) {
        return axios_1["default"]({
            method: "GET",
            url: link_1.LINKS.chat.message,
            params: params
        }).then(function (resp) {
            var payload = resp.data;
            if (payload.code) {
                throw new Error(payload.msg || ERROR_MSG);
            }
            var data = payload.data, msg = payload.msg;
            data.forEach(function (i) { return i.date *= 1000; });
            return data || msg;
        });
    };
    ChatService.prototype.sendMessage = function (userId, params) {
        return axios_1["default"]({
            method: "POST",
            url: link_1.LINKS.chat.message,
            data: params
        }).then(function (resp) {
            var data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            data.data.date = data.data.date * 1000;
            return data.data || data.msg;
        });
    };
    ChatService.prototype.deleteMessage = function (userId, params) {
        return axios_1["default"]({
            method: "DELETE",
            url: link_1.LINKS.chat.message,
            data: params
        }).then(function (resp) {
            var data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    };
    ChatService.prototype.feedback = function (userId, sign) {
        return axios_1["default"]({
            method: "POST",
            url: link_1.LINKS.chat.feedback,
            data: { sign: sign }
        }).then(function (resp) {
            var data = resp.data;
            if (data.code) {
                throw new Error(data.msg || ERROR_MSG);
            }
            return data.data || data.msg;
        });
    };
    return ChatService;
}());
exports.ChatService = ChatService;
