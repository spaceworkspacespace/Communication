;
var initIM = (function ($, _) {
	function initIM(config) {
		if (!layui) {
			console.error("IM 初始化失败, 请确认正确载入依赖 !");
			return;
		}
		layui.use("layim", function (layim) {
			if (!layim) {
				console.error("IM 初始化失败, 请确认正确载入依赖 !");
				return;
			}
			// 配置避免为空
			config = config || {};
			urls = config.urls || {};

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
				msgbox: urls.msgbox,
				find: urls.find,
				chatLog: urls.chatLog,

			});
			/*
			 * .chat({ name: "张三", type: "朋友", avatar:
			 * "https://i.loli.net/2018/12/10/5c0de4003a282.png", id: -2 });
			 */
			window.layim = layim;
		});

		var im = {
			connect: connect,
			_socket: null
		};

		// 连接 ws 服务器
		function connect(url) {
			// var socket = new SockJS(url);
			// var stom = webstomp.over(sock);
			// var stom = webstomp.client(url);
			var ws = new WebSocket(url);
			var stom = webstomp.over(ws);
			im._stom = stom;

			// return stom.send("/im", "hello");
			stom.connect("a", "123", function(e) {
				console.log(e);
			});

		}
		return im;
	}
	return initIM;

})($, _);