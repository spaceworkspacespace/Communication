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
			var socket = new WebSocket("ws://"+document.domain+":8080");
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
				tool: [{
				      alias: 'code'
				      ,title: '代码'
				      ,icon: '&#xe64e;'
				    }],
				msgbox: urls.msgbox,
				find: urls.find,
				chatLog: urls.chatLog,
			});
		//监听发送消息
		  layim.on('sendMessage', function(data){
		     $.post('send', {str: data}, function(data){
		        //如果对方离线
		    	 if(data.type == 'friend'){
		    		 if (data.code==0) {
		    			 layim.setChatStatus('<span style="color:#FF5722;">离线</span>');
		    			 layim.setFriendStatus(data.id, 'offline');
		    		 }else{
		    			 layim.setChatStatus('<span style="color:green;">在线</span>');
		    			 layim.setFriendStatus(data.id, 'online'); 
		    		 }
		    	 }
		     }, 'json');
		  });
		  
		  layim.on('ready', function(res){
		       $.post('getoffmessage', {}, function(data){

		     }, 'json');
		    setInterval("myInterval()",10000);//1000为1秒钟
		  });
		  
		  //监听查看群员
		  layim.on('members',function(data){
			  
		  });
		  // 服务端发来消息时
	      socket.onmessage = function(e)
		  {
		       // json数据转换成js对象
	    	  var data = eval("("+e.data+")");
	    	  var type = data.type || '';
	    	  switch(type){
		      // Events.php中返回的init类型的消息，将client_id发给后台进行uid绑定
	    	  	case 'init':
		            // 利用jquery发起ajax请求，将client_id发给后端进行uid绑定
		            $.post('bind', {client_id: data.client_id}, function(data){}, 'json');
		            break;
		       	case 'chatMessage':
		            for(var i=0;i<data.data.length;i++){
	            		var id = document.getElementById('userId').value;
	            		if(id != data.uid){
			            	layim.getMessage({
			                    username: data.data[i].username
			                    ,avatar: data.data[i].avatar
			                    ,id: data.data[i].id
			                    ,type: data.data[i].type
			                    ,content: data.data[i].content
			                    ,timestamp: new Date().getTime()
			                    ,system:data.data[i].mine
			                  });
		            		}
			            }
		            break;
		        case 'ping':   
		        	break;
		        // 当mvc框架调用GatewayClient发消息时直接alert出来
		        default :
		            alert(e.data);
		    }
		    }
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