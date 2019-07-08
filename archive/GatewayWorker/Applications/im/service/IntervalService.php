<?php
namespace app\im\service;

use app\im\Factory;
use GatewayWorker\Lib\Gateway;
use app\im\exception\OperationFailureException;
use Workerman\Lib\Timer;
use app\im\exception\IllegalStateException;

class IntervalService implements  IIntervalService {
    private static $_setup = false;
    
    public static function setup() {
        if (static::$_setup) {
            throw new IllegalStateException("不要重复启动 !");
        }
        
        $intervalService = Factory::getIntervalService();
        Timer::add(config("resend.resend_interval"), [$intervalService, "messageResend"]);
        Timer::add(config("losecall.check_interval"), [$intervalService, "checkLostConnection"]);
        
        static::$_setup = true;
    }
    
    function checkLostConnection() {
        // println("checkLostConnection:");
        $now = time();
        $callingIdList = config("redis_keys.calling_l");
        $callingIdTimeHash = config("redis_keys.calling_h");
        $cache = Factory::getReidsModel();
        
        // println($cache->llen($callingIdList), "  ",  RedisModel::getRedis()->rawCommand("LRANGE", $callingIdList, 0, 100) );
        // 检查是否存在队列
        if ($cache->llen($callingIdList) == 0) {
            // echo "通话队列没有记录\n";
            // println(implode(" ", ["通话队列没有记录", $callingIdList, $callingIdTimeHash]));
            return;
        }
        
        try {
            // 获取队列尾部 (最早加入) 的 id.
            $fristId = $cache->lindex($callingIdList, -1);
            if (!is_numeric($fristId) 
                || !$cache->hexists($callingIdTimeHash, $fristId)) {
                im_log("error", implode(" ", [
                    "字段错误", 
                    $callingIdList, 
                    $fristId, ";", 
                    $callingIdTimeHash, 
                    $cache->hgetJson($callingIdTimeHash, $fristId)]));
                // 删除
                if ($cache->lock($callingIdList)) {
                    $cache->lrem($callingIdList, $fristId);
                }
                $cache->unlock($callingIdList);
                throw new OperationFailureException();
            }
            
            if (!$cache->lock($callingIdList) || !$cache->lock($callingIdTimeHash)) {
                throw new OperationFailureException("lock 失败.");
            }
            // 获取对应时间 (上次 ping 的时间), 如果过期就下线
            $detail = $cache->hgetJson($callingIdTimeHash, $fristId);
            $time = $detail["timestamp"];
            
            $now = time();
            $timeout = config("losecall.timeout");
            
            // 如果指定时间内没有来过通知, 可以让用户掉线了.
            if ($time < $now - $timeout) {
                // 更新数据
                $detail["losecount"] += 1;
                $cache->rpoplpush($callingIdList, $callingIdList);
                $cache->hsetJson($callingIdTimeHash, $fristId, $detail);
                $cache->unlock($callingIdTimeHash);
                $cache->unlock($callingIdList);

                if ($detail["losecount"] > config("losecall.max_check_count")) {
                    im_log("info", $fristId, " 丢失连接, 发送断线处理到 thinkphp.");
                    // 发送 https 请求到 thinkphp 服务器
                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL=>get_url("chat.offlineProcessing"),
                        CURLOPT_RETURNTRANSFER=>true,
                        CURLOPT_HEADER=>true,
                        CURLOPT_POST=>true,
                        CURLOPT_POSTFIELDS=>"userId=$fristId",
                        CURLOPT_SSL_VERIFYPEER=>false,
                        CURLOPT_SSL_VERIFYHOST=>false,
                    ]);
                    curl_exec($curl);
                    // println($response);
                    curl_close($curl);
                }
                
                // 递归, 进行下一个用户的判断
                // 稍微等等, 让下锁
                usleep(200);
                return $this->checkLostConnection();
            }
        } catch(OperationFailureException $e) {
            ;
        } catch(\Exception $e) {
            im_log("error", $e);
        } finally {
            $cache->unlock($callingIdList);
            $cache->unlock($callingIdTimeHash);
        }
    }
    
    public function messageResend() {
        $hashName = config("redis_keys.cache_chat_last_send_time_key");
        $listName = config("redis_keys.cache_chat_resend_list_key");
        $maxResend = config("resend.max_resend_count");
        $minResendTime = config("resend.timeout");
        // $onlineListName = config("redis_keys.cache_chat_online_user_key");
        
        $cache = Factory::getRedis(config("redis.host"));
        
        // 队列不存在, 没有要发送的消息
        if (!$cache->rawCommand("EXISTS", $listName)) {
            // echo "重发队列没有记录\n";
            // im_log("debug", implode(" ", ["重发队列没有记录", $listName, $hashName]));
            return;
        }
        $msgSign = $cache->rawCommand("LINDEX", $listName, "-1");
        // 缓存没有数据
        if (!$msgSign) return;
        // var_dump($msgSign);
        im_log("debug", implode(" ", ["消息重发", $msgSign]));
        list($sign, $time, $count) = explode("-", $msgSign);
        $now = time();
        // 判断是否重发
        if ($now > $time+$minResendTime) {
            // 执行操作, 移除队列数据
            $cache->rawCommand("RPOP", $listName);
            $data = $cache->rawCommand("HGET", $hashName, $sign);
            if ($data) {
                list( "id"=>$id, "data"=>$data) =  json_decode($data, true);
                
                // 发送
                // Gateway::$method($id, $data);
                if (Gateway::isOnline($id)) {
                    Gateway::sendToClient($id, $data);
                    
                    // 没有超过最大次数
                    if (++$count <= $maxResend) {
                        // 更新
                        $cache->rawCommand("LPUSH", $listName, "$sign-$now-$count");
                    } else {
                        // 删除详情数据
                        $cache->rawCommand("HDEL", $hashName, $sign);
                    }
                } else {
                    // 删除详情数据
                    $cache->rawCommand("HDEL", $hashName, $sign);
                }
            }
            return $this->messageResend();
        }
    }
}