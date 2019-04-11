
;
var initIM = (function ($, _) {
	function initIM(config) {
		if (!layui) {
			console.error("IM 初始化失败, 请确认正确载入依赖 !");
			return;
		}

		// 配置避免为空
		config = config || {};
		var urls = config.urls = config.urls || {};

		function sendInit() {
			$.ajax({
				url: urls.init,
				success: function (data, status, xhr) {
					config.init = data.data;
					_init(config)
				},
				error: function (xhr, status, error) { sendInit(); }
			})
		}
		sendInit();
	}

	function _init(config) {
		layui.use("mobile", function () {
			var mobile = layui.mobile,
				layim = mobile.layim;

			// layim = layui.mobile.layim;
			if (!layim) {
				console.error("IM 初始化失败, 请确认正确载入依赖 !");
				return;
			}

			var urls = config.urls;

			// 连接 socket 服务器
			if (!urls.socket) {
				console.error("socket 地址未配置 !");
				return;
			}

			var client = new Gateway({
				url: urls.socket,
				interval: 60,
				pingData: "ping",
				$: $
			});

			// 初始化 LayIM
			layim.config({
				// brief: true
				title: config.title || "IM",
				notice: true,
				init: config.init,
				members: { url: urls.members },
				uploadImage: { url: urls.uploadImage },
				uploadFile: { url: urls.uploadFile },
				// moreList: [{
				// 	alias: "find",
				// 	title: "发现",
				// 	iconUnicode: '&#xe628;', //图标字体的unicode，可不填
				// 	iconClass: '' //图标字体的class类名
				// }],
				copyright: true,
			});

			// 删除消息
			layim.on("chatMsgDelete", function (cid, type, del) {
				$.ajax({
					url: "/im/chat/message",
					method: "DELETE",
					data: { cid: cid, type: type },
					success: function (data) {
						if (!data.code) del();
						layer.msg(data.msg);
					},
					error: function (xhr, satatus) {
						layer.msg("请求错误, 请稍后重试~");
					}
				});
			});

			layim.on("newFriend", function () {
				// $.ajax({
				// 	url: "/im/index/msgbox",
				// 	method: "GET",
				// 	success: function(data, status, xhr) {
				// 		console.log(data)
				// 		layim.panel({
				// 			title: "新的朋友",
				// 			tpl: data,
				// 			data: {}
				// 		});
				// 	}
				// });
				location.href = "/im/index/find";
			});

			// 监听发送消息
			layim.on('sendMessage', function (data, passCid) {
				// console.log(data)
				$.post('/im/chat/message',
					{ id: data.to.id, type: data.to.type, content: data.mine.content },
					function (data) {
						if (data.code) layer.msg(data.msg);
						if (data.data) passCid(data.data.cid);
					}, 'json');
			});

			layim.on("sign", function (data) {
				$.ajax({
					url: "/im/user/info",
					method: "POST",
					data: { sign: data },
					success: function (data, status, xhr) {
						layer.msg(data.msg);
					},
					error: function (xhr, status) {
						layer.msg("网络错误 ! 请重试.");
					}
				});
			});

			layim.on("moreList", function (ex) {
				switch (ex.alias) {
					case "find":
						location.href = "/im/index/find";
						break;
				}
			});

			// var $id = {:cmf_get_current_user_id()};
			client.onopen = function (event) {
				layer.msg("连接可用");
			}

			client.onxreconnection = function (event) {
				layer.msg("连接已断开, 重连中...");
			}

			// var $id = {:cmf_get_current_user_id()};
			// 聊天消息
			client.onxmessage = function (data) {
				console.log(data);
				var msgLen = data.length;
				data.sort(function (l, r) {
					return l.timestamp > r.timestamp ? 1 : -1;
				});
				for (var i = 0; i < msgLen; i++) {
					if (!data[i].require && client.userId == data[i].fromid) continue;
					// data[i].avatar || (data[i].avatar = "https://i.loli.net/2018/12/10/5c0de4003a282.png");
					layim.getMessage(data[i]);
				}
			}

			// 有新的请求信息
			client.onxask = function (data) { layim.showNew('Friend', true); }

			// 有新的添加命令
			client.onxadd = function (data) {
				// console.log(data);
				for (var i = data.length - 1; i >= 0; i--) {
					layim.addList(data[i]);
				}
			}
			// 消息反馈功能
			client.onxfeedback = function (sign) {
				// console.log(sign)
				$.ajax({
					url: "/im/chat/messagefeedback",
					method: "POST",
					data: { sign: sign },
				});
			}

			client.onxconnected = function (data) {
				// 利用jquery发起ajax请求，将client_id发给后端进行uid绑定
				var clientId = data.id;
				$.post('bind', { client_id: data.id }, function (data, status, xhr) {
					client.userId = data.data.id;
					client.clientId = clientId;
					var ks = JSON.parse(atob(data.data.ks));
					// console.log(ks);
					client.setKeys(ks);

					// 删除本地数据
					try {
						var cache = layui.mobile.layim.cache();
						if (cache) {
							var local = layui.data('layim-mobile')[cache.mine.id];
							delete local.chatlog;

							layui.data('layim-mobile', {
								key: cache.mine.id,
								value: local
							});
						}
					} catch (e) {
						console.error(e);
					}

					// 发送启动完成的请求
					sendFinish();


				}, 'json');

				function sendFinish() {
					$.ajax({
						url: "/im/index/finish",
						success: function (data, status, xhr) { layer.msg("登录成功"); },
						error: function (xhr, status) { setTimeout(sendFinish, 1500); }
					});
				}
			}

			window.onmessage = function (event) {
				var data = event.data;
				switch (data.type) {
					case "layer":
						layer[data.method](data.options);
						break;
					case "layim":
						var options = data.contact;
						if (data.method === "add") { // 申请好友和分组
							options.submit = function (group, remark, index) {
								var url, data = {
									id: options.id,
									content: remark,
								};
								if (options.type !== "friend") { // 添加群聊
									url = "/im/contact/linkGroup";
								} else { // 添加好友
									url = "/im/contact/linkFriend";
									data.friendGroupId = group;
								}
								client.ajax({
									url: url,
									method: "POST",
									data: data,
									success: function (data, status, xhr) {
										if (data.code) {
											layer.msg(data.msg || "添加失败, 请稍后重试~");
										} else {
											layer.msg(data.msg || "添加成功, 请等待回复~");
										}
									}
								});
								layer.close(index);
							}
							layim.add(options);
						}
						break;
					default:
						console.error("未知消息! ");
						console.error(event);
						break;
				}
			}


			window.layim = layim;
		});
	}
	return initIM;
})($, _);