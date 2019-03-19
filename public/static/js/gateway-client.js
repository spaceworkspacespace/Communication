
/**
 * 
 * @param {url: string, interval: integer, pingData?: string, $?} config 
 */
function Gateway(config) {
    if (this.constructor !== Gateway) return new Gateway(config);
    this._pingData = config.pingData || "ping";
    this._interval = config.interval * 1000;
    this._socket = new WebSocket(config.url);
    this._jquery = config.$ || $;
    this._resetHeartbeat();

    var that = this;
    this._socket.onmessage = function(event) {
        that.onmessage && that.onmessage(event);
    }
    this._socket.onopen = function(event) {
        that.onopen && that.onopen(event);
    }
    this._socket.onclose = function(event) {
        that.onclose && that.onclose(event);
    }
    this._socket.onerror = function(event) {
        that.onerror && that.onerror(event);
    }
}

// 重置心跳
Gateway.prototype._resetHeartbeat = function() {
    this._beat && clearInterval(this._beat);
    this._beat = setInterval(this._sendHeartbeat.bind(this), this._interval);
}

// 发送心跳
Gateway.prototype._sendHeartbeat = function() {
    this._socket.send(this._pingData);
}

// 发送数据
Gateway.prototype.send = function(data) {
    this._resetHeartbeat();
    this._socket.send(data);
}

// 使用 ajax 发送 http 访问
Gateway.prototype.ajax = function(settings) {
    this._jquery.ajax(settings);
}