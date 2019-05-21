<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
// declare(ticks=1);
use GatewayWorker\Lib\Gateway;

$onlineListName = "im_chat_online_user_set";
$cache = new \Redis();
$cache->connect("192.168.0.80");

class MessageType
{

    const ONLINE = "ONLINE";
}

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{

    // public static function onWebSocketConnect(string $client_id, array $data) {
    // Gateway::sendToClient($client_id, strval($data));
    // }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id
     *            连接id
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据
        // 将 client_id 返回
        Gateway::sendToClient($client_id, json_encode([
            "id" => $client_id,
            "type" => "CONNECTED"
        ]));
        // Gateway::sendToClient($client_id, implode([
        // "SEND\n\n",
        // "Hello $client_id\r\n^@"
        // ]));
        // 向所有人发送
        // Gateway::sendToAll("Hello $client_id\r\n");
        // Gateway::sendToAll(implode([
        // "SEND\n\n",
        // "Hello $client_id\r\n^@"
        // ]));
    }

    /**
     * 当客户端发来消息时触发
     *
     * @param int $client_id
     *            连接id
     * @param mixed $message
     *            具体消息
     */
    public static function onMessage($client_id, $message)
    {
        // 向所有人发送
        // Gateway::sendToAll(implode([
        // "SEND\n\n",
        // "$client_id said $message\r\n^@"
        // ]));
        // Gateway::sendToAll("$client_id said $message\r\n");
        // $data = json_decode($message);
        // switch(data.type) {
        // case MessageType::ONLINE:
        // MessageHandler::online($data["data"]["uid"]);
        // break;
        // }
    }

    /**
     * 当用户断开连接时触发
     *
     * @param int $client_id
     *            连接id
     */
    public static function onClose($client_id)
    {
        // 离线处理
        MessageHandler::tryOffline($client_id);
        // 向所有人发送
        // GateWay::sendToAll("$client_id logout\r\n");
    }
}

class MessageHandler
{

    // 在线应当在 tp 服务器做.
    public static function online($uid)
    {
//         global $onlineListName, $cache;
//         $cache->rawCommand();
    }

    /**
     * 判断用户是否存在, 如果不存在就将其离线
     *
     * @param mixed $client_id
     */
    public static function tryOffline($client_id)
    {
        $uid = Gateway::getUidByClientId($client_id);
        if (! Gateway::isUidOnline($uid)) { // 从在线用户列表中移除
            global $onlineListName, $cache;
            $cache->rawCommand("SREM", $onlineListName, $uid);
        }
    }
}
