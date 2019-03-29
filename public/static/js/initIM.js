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
				init: {
					url: urls.init
				},
				members: {
					url: urls.members
				},
				uploadImage: {
					url: urls.uploadImage
				},
				uploadFile: {
					url: urls.uploadFile
				},
				tool: [{
					alias: 'code',
					title: '代码',
					icon: '&#xe64e;'
				}],
				msgbox: urls.msgbox,
				find: urls.find,
				chatLog: urls.chatLog,
				copyright: true,
			});

			// 监听发送消息
			layim.on('sendMessage', function (data) {
				$.post('send', { str: data }, function (data) {
					// 如果对方离线
					if (data.type == 'friend') {
						if (data.code == 0) {
							layim.setChatStatus('<span style="color:#FF5722;">离线</span>');
							layim.setFriendStatus(data.id, 'offline');
						} else {
							layim.setChatStatus('<span style="color:green;">在线</span>');
							layim.setFriendStatus(data.id, 'online');
						}
					}
				}, 'json');
			});

			layim.on("ready", function (res) {

			});

			//监听查看群员
			layim.on('members', function (data) {

			});

			// 聊天消息
			client.onxmessage = function (data) {
				for (var i = 0; i < data.data.length; i++) {
					var id = document.getElementById('userId').value;
					if (id != data.uid) {
						layim.getMessage({
							username: data.data[i].username
							, avatar: data.data[i].avatar
							, id: data.data[i].id
							, type: data.data[i].type
							, content: data.data[i].content
							, timestamp: new Date().getTime()
							, system: data.data[i].mine
						});
					}
				}
			}

			// 有新的请求信息
			client.onxask = function (data) { layim.msgbox(data.msgCount); }

			client.onxconnected = function (data) {
				// 利用jquery发起ajax请求，将client_id发给后端进行uid绑定
				$.post('bind', { client_id: data.id }, function (data, status, xhr) {
					var ks = JSON.parse(atob(data.data));
					client.setKeys(ks);
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