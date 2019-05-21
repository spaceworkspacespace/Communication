"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : new P(function (resolve) { resolve(result.value); }).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
exports.__esModule = true;
/// <reference path="../types/layui.d.ts"/>
/// <reference path="../types/layer.d.ts"/>
var UserService_1 = require("./UserService");
var layer_1 = require("layer");
var ChatService_1 = require("./ChatService");
var MessageService_1 = require("./MessageService");
function errMsg(e) {
    console.error(e);
    layer_1["default"].msg(e.message, {
        zIndex: 19991116
    });
}
var IMCallBack = /** @class */ (function () {
    function IMCallBack(u, layim, socket) {
        this.user = u;
        this.layim = layim;
        this.socket = socket;
        // 绑定 this
        for (var prop in this) {
            if (typeof this[prop] === "function"
                || this[prop] instanceof Function) {
                var fun = this[prop];
                this[prop] = fun.bind(this);
            }
        }
    }
    IMCallBack.prototype.onSign = function (sign) {
        var _this = this;
        return UserService_1.UserService.getInstance()
            .updateInfo(this.user.id, { sign: sign })
            .then(function (u) {
            _this.user = u;
            layer_1["default"].msg("更新成功");
        })["catch"](errMsg);
    };
    IMCallBack.prototype.onSendMessage = function (data, passCid) {
        return ChatService_1.ChatService.getInstance()
            .sendMessage(this.user.id, {
            id: data.to.id,
            type: data.to.type,
            content: data.mine.content
        })
            .then(function (m) { return passCid(m.cid); })["catch"](function (e) {
            errMsg(e);
            passCid(null);
        });
    };
    IMCallBack.prototype.onChatMsgDelete = function (cid, type, del) {
        return ChatService_1.ChatService.getInstance()
            .deleteMessage(this.user.id, { cid: cid, type: type })
            .then(function (m) {
            layer_1["default"].msg(m);
            del();
        })["catch"](errMsg);
    };
    IMCallBack.prototype.onOpen = function (event) {
        // 给服务器确认用户 id
        // this.socket.send(JSON.stringify({
        //     data: {
        //         uid: this.user.id
        //     },
        //     type: GatewayMessage.ONLINE
        // }));
        layer_1["default"].msg("连接成功");
    };
    IMCallBack.prototype.onReconnection = function () {
        layer_1["default"].msg("连接已断开, 重连中...");
    };
    IMCallBack.prototype.onMessage = function (data) {
    };
    IMCallBack.prototype.onAsk = function (data) {
        this.layim.msgbox(data.msgCount);
    };
    IMCallBack.prototype.onAdd = function (data) {
    };
    IMCallBack.prototype.onFeedback = function (sign) {
        return ChatService_1.ChatService.getInstance()
            .feedback(this.user.id, sign)["catch"](errMsg);
    };
    IMCallBack.prototype.onConnected = function (data) {
        return __awaiter(this, void 0, void 0, function () {
            var bind, ks, e_1;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        _a.trys.push([0, 3, , 4]);
                        return [4 /*yield*/, UserService_1.UserService.getInstance()
                                .bind(this.user.id, data.id)];
                    case 1:
                        bind = _a.sent();
                        ks = JSON.parse(window.atob(bind.ks));
                        this.socket.setKeys(ks);
                        return [4 /*yield*/, MessageService_1.MessageService.getInstance()
                                .pull(this.user.id)];
                    case 2:
                        _a.sent();
                        return [3 /*break*/, 4];
                    case 3:
                        e_1 = _a.sent();
                        errMsg(e_1);
                        return [3 /*break*/, 4];
                    case 4: return [2 /*return*/];
                }
            });
        });
    };
    return IMCallBack;
}());
exports.IMCallBack = IMCallBack;
