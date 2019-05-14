
;
var initIM = (function ($, _) {
	function initIM(config) {
		if (!layui) {
			console.error("IM 初始化失败, 请确认正确载入依赖 !");
			return;
		}

		layui.use("layim", function (layim) {
			// layim = layui.mobile.layim;
			if (!layim) {
				console.error("IM 初始化失败, 请确认正确载入依赖 !");
				return;
			}
			// 配置避免为空
			config = config || {};
			var urls = config.urls || {};

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
				init: { url: urls.init },
				members: { url: urls.members },
				uploadImage: { url: urls.uploadImage },
				uploadFile: { url: urls.uploadFile },
				// tool: [{
				// 	alias: 'code',
				// 	title: '代码',
				// 	icon: '&#xe64e;'
				// }],
				msgbox: urls.msgbox,
				find: urls.find,
				chatLog: urls.chatLog,
				copyright: true,
			});

			// 监听发送消息
			layim.on('sendMessage', function (data, passCid) {
				// console.log(data)
				$.ajax({
					url: "/im/chat/message",
					method: "POST",
					data: { id: data.to.id, type: data.to.type, content: data.mine.content },
					success: function (data) {
						if (data.code) {
							layer.msg(data.msg);
							passCid(null);
							layim.getMessage({
								system: true,
								id: data.to.id,
								type: data.to.type,
								content: '消息发送失败.'
							});
							return;
						}
						if (data.data) passCid(data.data.cid);
					},
					error: function (xhr, status, error) {
						layer.msg("消息发送失败, 请检查网络设置~");
						passCid(null);
						layim.getMessage({
							system: true,
							id: data.to.id,
							type: data.to.type,
							content: '消息发送失败.'
						});
					}
				});
				// $.post('/im/chat/message',
				// 	{ id: data.to.id, type: data.to.type, content: data.mine.content },
				// 	function (data) {
				// 		if (data.code) layer.msg(data.msg);
				// 		if (data.data) passCid(data.data.cid);
				// 	}, 'json');
				// 如果对方离线
				// if (data.type == 'friend') {
				// 	if (data.code == 0) {
				// 		layim.setChatStatus('<span style="color:#FF5722;">离线</span>');
				// 		layim.setFriendStatus(data.id, 'offline');
				// 	} else {
				// 		layim.setChatStatus('<span style="color:green;">在线</span>');
				// 		layim.setFriendStatus(data.id, 'online');
				// 	}
				// }

			});

			// 监听鼠标点击发言内容
			layim.on("chatMsgClick", function (event) {
				// var othercontextmenuul = $(".contextmenuul");
				// othercontextmenuul.hide();
				console.log(event);
				// // 聊天面板的宽高
				// var chatmain = $(".layim-chat-main");
				// var chatmainWidth = chatmain.width();
				// var chatmainHeight = chatmain.height();
				// //获取鼠标点击位置坐标
				// var mouseX = event.pageX;
				// var mouseY = event.pageY;
				// 拿到显示的menu
				var contextmenuul = $(this).find(".contextmenuul");
				// var contextmenuul =$(".contextmenuul");
				console.log("contextmenuul", contextmenuul);
				contextmenuul.show();
				var menu = $("#x-chat-del-menu");
				// 首次加入
				if (!menu.length) {

				}
			});
			$(document).on('click', function (e) {
				var target = e.target;
				var contextmenuul = $(".contextmenuul");
				if (contextmenuul !== target) {
					contextmenuul.hide();
				}
			})

			// 删除消息
			layim.on("chatMsgDelete", function (cid, type, del) {
				$.ajax({
					url: "/im/chat/message",
					method: "DELETE",
					data: { cid: cid, type: type },
					success: function (data) {
						layer.msg(data.msg);
						if (!data.code) del();
					},
					error: function (xhr, satatus) {
						layer.msg("请求错误, 请稍后重试~");
					}
				});
			});

			layim.on("ready", function (res) {
				// 删除本地数据
				try {
					var cache = layui.layim.cache();
					if (cache) {
						var local = layui.data('layim')[cache.mine.id];
						delete local.chatlog;
						layui.data('layim', {
							key: cache.mine.id,
							value: local
						});
					}
				} catch (e) {
					console.error(e);
				}
			});

			//监听查看群员
			layim.on('members', function (data) {

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

			// var $id = {:cmf_get_current_user_id()};
			client.onopen = function (event) {
				layer.msg("连接可用");
			}

			client.onxreconnection = function (event) {
				layer.msg("连接已断开, 重连中...");
			}

			// 聊天消息
			client.onxmessage = function (data) {
				// console.log(data);
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
			client.onxask = function (data) { layim.msgbox(data.msgCount); }

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

					// 发送启动完成的请求
					sendFinish();
				}, 'json');
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
					// default:
					// 	console.error("未知消息! ");
					// 	console.error(event);
					// 	break;
				}
			}
			function sendFinish() {
				// 确保在 bind 之后
				if (!client.clientId) {
					setTimeout(sendFinish, 500);
					return;
				}
				$.ajax({
					url: "/im/index/finish",
					success: function (data, status, xhr) { layer.msg("登录成功"); },
					error: function (xhr, status) { setTimeout(sendFinish, 1500); }
				});
			}


			window.layim = layim;
		});
	}


	return initIM;
})($, _);