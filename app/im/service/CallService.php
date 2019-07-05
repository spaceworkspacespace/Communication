<?php

namespace app\im\service;

use app\im\model\RedisModel;
use app\im\model\ModelFactory;
use app\im\exception\OperationFailureException;
use think\Cache;

class CallService implements ICallService {
    
    public function callFinish($userId, $sign) {
        /**
         * @var \app\im\util\RedisCacheDriverImpl $cache
         */
        $data = $this->getCallDetail(["sign"=>$sign]);
        println("通话结束", $data);
        // 通话没有信息, 可能是还没有建立.
        if (is_null($data) || !$data) {
            return [];
        }
        
        $callDetailField = RedisModel::getKeyName("cache_calling_communication_info_hash_key");
        $callingUserIdList =RedisModel::getKeyName("im_calling_id_list_key") ;
        $callingUserIdHash = RedisModel::getKeyName("calling_h");
        
        $cache = Cache::store("redis");
        
        // 与当前用户断开连接的用户 id
        $finishIds = [];
        
        if (is_array($data) && isset($data["groupid"])) {
            // 清除群聊中的用户聊天信息
            $groupId = $data["groupid"];
            $callGroupField = RedisModel::getKeyName("im_calling_comm_group_hash", ["groupId"=>$groupId]);
            $callUserField = RedisModel::getKeyName("im_calling_comm_user_hash", ["userId"=>$userId]);

            $groupIds = RedisModel::hkeys($callGroupField);
            // 获取群聊中所有正在聊天的成员, 并过滤除了自己。
            $finishIds = $otherIds = array_filter($groupIds, function($value) use ($userId) {
                return $userId != $value && is_numeric($value);
            });
            // 通话状态离线处理
            if (!$cache->lock($callingUserIdList)
                || !$cache->lock($callingUserIdHash)) {
                return false;
            }
            RedisModel::lrem($callingUserIdList, $userId);
            RedisModel::hdel($callingUserIdHash, $userId);
            $cache->unlock($callingUserIdList);
            $cache->unlock($callingUserIdHash);
            
            // 断开当前用户与群聊其他人的连接
            if (count($otherIds) === 0) {
                // 群聊除了自己没其其它人了
                RedisModel::del($callGroupField);
                // 清除通话详情
                RedisModel::hdel($callDetailField, $sign);
            } else {
                println("断开 ", $userId, " 和 ", $otherIds, " 的连接");
                // 断开当前用户与群聊其他用户的通话
                array_for_each($otherIds, function($otherId) use ($userId) {
                    RedisModel::hdel($otherId, $userId);
                });
                // 清除自己在群聊中的信息
               RedisModel::hdel($callGroupField, $userId);
            }
            // 删除用户通话信息
            RedisModel::del($callUserField);
        } else {
            // 清除用户聊天信息
            $callUserField = RedisModel::getKeyName("im_calling_comm_user_hash", ["userId"=>$data["userid"]]);
            $callUserField2 = RedisModel::getKeyName("im_calling_comm_user_hash", ["userId"=>$data["ruserid"]]);
            RedisModel::del($callUserField);
            RedisModel::del($callUserField2);
            // 进行通话状态处理, 取消正在通话的状态
            // 避免与 GatewayWokerman 那边冲突, 加锁.
            if (!$cache->lock($callingUserIdList)
                || !$cache->lock($callingUserIdHash)) {
                im_log("error", implode(" ", "锁失败", $callingUserIdList, $callingUserIdHash));
                return false;
            }
            RedisModel::lrem($callingUserIdList, $data["userid"]);
            RedisModel::lrem($callingUserIdList, $data["ruserid"]);
            RedisModel::hdel($callingUserIdHash, $data["userid"]);
            RedisModel::hdel($callingUserIdHash, $data["ruserid"]);
            $cache->unlock($callingUserIdList);
            $cache->unlock($callingUserIdHash);
            // 清除通话详情
            RedisModel::hdel($callDetailField, $sign);
            
            array_push($finishIds, $userId !== $data["ruserid"]? $data["ruserid"]: $data["userid"]);
        }
        return $finishIds;
    }
   
    public function establish($userId, $userId2, $sign, $desc=null, $ice=null) {
        println("建立通话 ", $userId, " 和 ", $userId2);
        $gatewayService = SingletonServiceFactory::getGatewayService();
        $callUserField = RedisModel::getKeyName("im_calling_comm_user_hash", ["userId"=>$userId]);
        $now = time();
        
        // 获取并检查用户信息
        $both = $this->getUserById($sign, $userId, $userId2);
        if (count($both) < 2) {
            throw new OperationFailureException(lang("invalid user"));
        }
        $both[1] = array_map_keys($both[1], function($value, $key) {
            return "r$key";
        });
        // println("用户信息: ", $both);
        // 取回聊天数据
        $chatDetail = $this->getCallDetail(["sign"=>$sign]);
        if (!$chatDetail) {
            throw new OperationFailureException();
        }
        
        // 将要推送的数据
        $data = array_merge($chatDetail, $both[0], $both[1], ["sign"=>$sign]);
        // 推送的类型
        // 这里如果有 desc 或 ice 参数, 将确定推送的类型并在推送数据上加上对应的数据
        $pushType = array_select(["desc"=> $desc, "ice"=> $ice], 
            ["desc"=> function($value, $index) use (&$data) {
                if (is_null($value)) return null;
                $data["description"] = $value;
                return IGatewayService::COMMUNICATION_COMMAND_TYPE;
            }, "ice"=> function($value, $index) use (&$data) {
                if (is_null($value)) return null;
                // 设置数据
                $data[$index] = $value;
                // 返回类型
                return IGatewayService::COMMUNICATION_ICE_TYPE;
            }], false, IGatewayService::COMMUNICATION_EXCHANGE_TYPE);
        
        // 检测一下, 避免重复连接
        // 第一次连接将会设置好 redis 中通话用户信息
        if (RedisModel::hexists($callUserField, $userId2)
            && $pushType === IGatewayService::COMMUNICATION_EXCHANGE_TYPE) {
            throw new OperationFailureException(lang("don't repeated connect"));
        } else if ($pushType === IGatewayService::COMMUNICATION_EXCHANGE_TYPE) {
            // im_log("notice", implode(" ", ["设置用户信息", $callUserField, $userId2]));
            // 设置用户信息
            $userFieldData = [
                "sign"=>$sign,
                "ctype"=>$chatDetail["ctype"],
                "createTime"=>$now
            ];
            RedisModel::hsetJson($callUserField, $userId2, $userFieldData);
            RedisModel::hsetJson(RedisModel::getKeyName("user_h", ["userId"=>$userId2]), $userId, $userFieldData);
        }
        println("建立通话 推送类型: ", $pushType, " 推送数据: ", $data, " ", $userId, " 推送到: ", $userId2);
        // 推送
        $gatewayService->sendToUser($userId2, $data, $pushType);
    }
    
    /**
     * 获取群聊中正在通话的成员
     * @param int $userId
     * @param int $groupId
     */
    public function getMembersByGroupId($userId, $groupId) {
        $groupModel = ModelFactory::getGroupModel();
        $callGroupField = RedisModel::getKeyName("im_calling_comm_group_hash", ["groupId"=>$groupId]);
        $sign = RedisModel::hgetJson($callGroupField, "g")["sign"];
        // 获取当前正在通话的成员的 id
        $userIds = RedisModel::hkeys($callGroupField);
        $userIds = array_filter($userIds, function($value) {
            return is_numeric($value);
        });
        // 排除自己的 Id
        $userIds = array_complementary($userIds, [$userId]);
        // 获取用户信息
        // $users = (new \ReflectionClass($this))->getMethod("getUserById")->invokeArgs($this, array_merge([$sign], $userIds));
        $users = $this->getUserById($sign, ...$userIds);
        $users = array_map_with_index($users, function($value) {
            $user = $value;
            array_key_replace($user, ["userid"=>"id", "useravatar"=>"avatar"]);
            $user["status"] = "online";
            return $user;
        });
        
        $groups = $groupModel->getGroupById($groupId);
        $group = $groups[0];
        $group["list"] = $users;
        
        return $group;
    }
    
    /**
     * 获取通话详情的便利方法
     * @param array $args { sign: string } | { groupId: number } | { userId: number, userId2: number }
     * @return null | array sign 对应的数据, 或者没找到.
     */
    protected function getCallDetail($args) {
        $sign = array_select($args, [
            "sign"=>function($value) {
                return $value;
            },
            "groupId"=>function($value) {
                // 群聊信息 redis key
                $callGroupField = RedisModel::getKeyName("im_calling_comm_group_hash", ["groupId"=>$value]);
                $g = RedisModel::hgetJson($callGroupField, "g");
                // 如果已经设置了 sign, 就返回. 否则返回 null.
                if (is_array($g) && isset($g["sign"])) {
                    return $g["sign"];
                }
                return null;
            },
            "userId"=> function($value, $index, $array) {
                if(!isset($array["userId2"])) {
                    return null;
                }
                $callUserField = RedisModel::getKeyName("im_calling_comm_user_hash", ["userId"=>$array["userId"]]);
                // 获取用户聊天信息
                $u = RedisModel::hgetJson($callUserField, $array["userId2"]);
                if (is_array($u) && isset($u["sign"])) {
                    return $u["sign"];
                }
                return null;
            }
        ], false);
        
        if (is_null($sign)) {
            return null;
        }
        
        // 保存的 redis hash key 名称.
        $callDetailField = RedisModel::getKeyName("cache_calling_communication_info_hash_key");
        $data = RedisModel::hgetJson($callDetailField, $sign);
        return $data;
    }
    
    /**
     * 便利方法
     * @param string $sign 会话标识
     * @param array ...$groupIds
     * @return Array<{groupid: number, groupname: string, groupavatar: string}>
     */
    protected function getGroupById($sign, ...$groupIds) {
        // 先找出缓存中存在的信息
        $groups = array_map(function($groupId) use($sign) {
            $callGroupField = RedisModel::getKeyName("im_calling_comm_group_hash", ["groupId"=>$groupId]);
            // 缓存中存在群聊信息则取出, 否则查询并设置
            if(RedisModel::hexists($callGroupField, "g")) {
                $g = RedisModel::hgetJson($callGroupField, "g");
                $g["groupname"] = $g["name"];
            } else {
                return null;
            }
            $group = [
                "groupid"=>$g["id"],
                "groupname"=>$g["groupname"],
                "groupavatar"=>$g["avatar"],
            ];
            return $group;
        }, $groupIds);
        
        // 筛选出缓存中不存在的信息
        $queryIds = array_reduce_with_index($groups, function($s, $m, $i) use($groupIds) {
            $result = $s;
            if ($m == null) {
                $result = array_merge($result, [$groupIds[$i]]);
            }
            return $result;
        }, []);

        // 全部找齐, 返回
        if (count($queryIds) === 0) {
            return $groups;
        }
        
        // 通过 id 查找
        // 通过反射的方式传递数组作为展开参数
        $modelRef = new \ReflectionClass(ModelFactory::getGroupModel());
        $methodRef = $modelRef->getMethod("getGroupById");
        $queryGroups = $methodRef->invokeArgs(ModelFactory::getGroupModel(), $queryIds);
        println($queryIds, $queryGroups);
        // 整合结果
        $groups = array_map_with_index($groups, function($group, $index) use ($groupIds, $queryIds, $queryGroups, $sign) {

            if ($group == null) {
                // 在查询结果数组中的索引
                $queryIndex = array_index_of($queryIds, $groupIds[$index]);

                // 如果不存在 (无效 id)
                if (!isset($queryGroups[$queryIndex])) {
                    return null;
                }
                
                $g = $queryGroups[$queryIndex];
                
                // 设置到缓存
                $callGroupField = RedisModel::getKeyName("im_calling_comm_group_hash", ["groupId"=>$g["id"]]);
                RedisModel::hsetJson($callGroupField, "g", [
                    "sign"=>$sign,
                    "id"=>$g["id"],
                    "name"=>$g["groupname"],
                    "avatar"=>$g["avatar"],
                ]);
                return [
                    "groupid"=>$g["id"],
                    "groupname"=>$g["groupname"],
                    "groupavatar"=>$g["avatar"],
                ];
            }
            return $group;
        }) ;
        
        return $groups;
    }
    
    /**
     * 便利方法
     * @param string $sign 会话标识
     * @param array ...$userId
     * @return Array<{userid: number, username: string, useravatar: string}>
     */
    protected function getUserById($sign, ...$userIds) {
        // 先找出缓存中存在的信息
        $users = array_map(function($userId) use($sign) {
            $callUserField = RedisModel::getKeyName("im_calling_comm_user_hash", ["userId"=>$userId]);
            // 缓存中存在群聊信息则取出, 否则查询并设置
            if(RedisModel::hexists($callUserField, "u")) {
                $u = RedisModel::hgetJson($callUserField, "u");
                $u["username"] = $u["name"];
            } else {
                return null;
            }
            $group = [
                "userid"=>$u["id"],
                "username"=>$u["username"],
                "useravatar"=>$u["avatar"],
            ];
            return $group;
        }, $userIds);

        // 筛选出缓存中不存在的信息
        $queryIds = array_reduce_with_index($users, function($s, $m, $i) use($userIds) {
            $result = $s;
            if ($m == null) {
                $result = array_merge($result, [$userIds[$i]]);
            }
            return $result;
        }, []);
        
        // 全部找齐, 返回
        if (count($queryIds) === 0) {
            return $users;
        }

        // 通过 id 查找
        // 通过反射的方式传递数组作为展开参数
        $modelRef = new \ReflectionClass(ModelFactory::getUserModel());
        $methodRef = $modelRef->getMethod("getUserById");
        $queryGroups = $methodRef->invokeArgs(ModelFactory::getUserModel(), $queryIds);

        // 整合结果
        $users = array_map_with_index($users, function($user, $index) use ($userIds, $queryIds, $queryGroups, $sign) {
            if ($user == null) {
                // 在查询结果数组中的索引
                $queryIndex = array_index_of($queryIds, $userIds[$index]);
                // 如果不存在 (无效 id)
                if (!isset($queryGroups[$queryIndex])) {
                    return null;
                }

                $u = $queryGroups[$queryIndex];
                // 设置到缓存
                $callUserField = RedisModel::getKeyName("im_calling_comm_user_hash", ["userId"=>$u["id"]]);
                RedisModel::hsetJson($callUserField, "u", [
                    "id"=>$u["id"],
                    "name"=>$u["username"],
                    "avatar"=>$u["avatar"],
                ]);
                return [
                    "userid"=>$u["id"],
                    "username"=>$u["username"],
                    "useravatar"=>$u["avatar"],
                ];
            }
            return $user;
       }) ;

       return $users;
    }
    
    public function isCalling($userId)
    {
        $redis = RedisModel::getRedis();
        $redisCallUserField = RedisModel::getKeyName("im_calling_idtime_hash_key");
        return $redis->rawCommand("HEXISTS", $redisCallUserField, $userId);
    }
    
    public function isAvailable($userId, $groupId=null)
    {
        $redisCallUserField = RedisModel::getKeyName("im_calling_comm_user_hash", ["userId"=>$userId]);
        
        // 如果用户在进行通话
        if (RedisModel::exists($redisCallUserField)) {
            // 判断是否为群聊通话，如果是群聊通话且与需求的群聊相同，则可以进行通话
            if (RedisModel::hexists($redisCallUserField, "g")) {
                $g = RedisModel::hgetJson($redisCallUserField, "g");
                if ($g["id"] == $groupId) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }
    
    public function newChat($userId, $groupId, $ctype, $sign = null) {
        /**
         * @var \app\im\util\RedisCacheDriverImpl $cache
         */
        $data = [];
        $callGroupField = RedisModel::getKeyName("im_calling_comm_group_hash", ["groupId"=>$groupId]);
        $callUserField = RedisModel::getKeyName("im_calling_comm_user_hash", ["userId"=>$userId]);
        $onlineIdsSetField = RedisModel::getKeyName("cache_chat_online_user_key");
        $groupsModel = model("groups");
        $cache = Cache::store("redis");
        $now = time();
        
        // 锁
        if (!$cache->lock($callGroupField) ||
            !$cache->lock($callUserField)) {
            $cache->unlock($callGroupField);
            $cache->unlock($callUserField);
            throw new OperationFailureException(lang("server busy"));
        }
        
        // 通话已经存在
        if (RedisModel::exists($callGroupField)) {
            $cache->unlock($callGroupField);
            throw new OperationFailureException();
        }
        
        // 会话标识
        if (is_null($sign)) $sign = implode("-", [$groupId, $now]);
        // 储存会话信息
        $data = $this->setCallDetail(["sign"=> $sign, "userId"=>$userId, "groupId"=>$groupId, "ctype"=>$ctype]);
        println("群聊创建 ", $data);
        $g = [
            "sign"=>$sign,
            "id"=> $data["groupid"],
            "name"=>$data["groupname"],
            "avatar"=>$data["groupavatar"]
        ];
        RedisModel::hsetJson($callGroupField, "g", $g);
        // 加入聊天
        RedisModel::hsetJson($callGroupField, $userId, ["joinTime"=>$now]);
        
        // 加入群聊信息
        RedisModel::hsetJson($callUserField, "g", $g);
        
        // 解锁
        $cache->unlock($callGroupField);
        $cache->unlock($callUserField);
        
        // 过滤掉自己的 id 和不可用的 id
        $filterId = function_curry('array_filter', 2)(F_P_, 
            function($value) use($userId, $groupId, $onlineIdsSetField) {
                return RedisModel::sismember($onlineIdsSetField, $value) // 在线
                    && $this->isAvailable($value, $groupId) // 可连接
                    && $userId !== $value; // 不是自己
            });
        
        // 呼叫
        $pushCallReq = function_curry('array_for_each', 2)(F_P_, 
            function($value) use($userId, $ctype, $sign, $groupId) {
                $this->pushCallRequest($userId, $value, $ctype, $sign, $groupId);
            });
        
        $logTap = function_tap(function_curry('println', 2)("newChat"));

        // 获取群聊中的用户 id
        $getUserIdInGroup = function($groupId) use ($groupsModel) {
            return $groupsModel->getUserIdInGroup($groupId);
        };
        
        // 获取群聊中所有在线成员, 执行呼叫.
        function_compose(
            $pushCallReq,
            $logTap,
            $filterId,
            $logTap,
            $getUserIdInGroup
       )($groupId);

        // 返回通话详情的数据
        return $data;
    }
    
    
    public function joinChat($userId, $groupId, $ctype, $sign = null) {
        /**
         * @var \app\im\util\RedisCacheDriverImpl $cache
         */
        $callGroupField = RedisModel::getKeyName("im_calling_comm_group_hash", ["groupId"=>$groupId]);
        $callUserField = RedisModel::getKeyName("im_calling_comm_user_hash", ["userId"=>$userId]);
        
        $cache = Cache::store("redis");
        
        $now = time();
        
        println("群聊通信 ", $userId, " 加入了群聊 ", $groupId);
        
        // 锁
        if (!$cache->lock($callGroupField, 5)) {
            throw new OperationFailureException(lang("server busy"));
        }
        // 会话尚未建立
        if (!RedisModel::exists($callGroupField)
            || !RedisModel::hexists($callGroupField, "g")) {
            $cache->unlock($callGroupField);
            // 建立会话
            return $this->newChat($userId, $groupId, $ctype, $sign);
        }
        // 这里会话已经建立
        // 获取会话标识
        $g = RedisModel::hgetJson($callGroupField, "g");
        $sign = $g["sign"];
        
        // 获取群聊中其它已在会话的成员, 并与他们建立连接
        $userIds = RedisModel::hkeys($callGroupField);
        // 加入聊天
        RedisModel::hsetJson($callGroupField, $userId, ["joinTime"=>$now]);
        // 解锁
        $cache->unlock($callGroupField);
        println("在聊用户", $userIds);
        // 筛选在线用户名单, 并建立连接
        $userIds = array_filter($userIds, function($value) use ($userId) {
            // 筛选出可以进行通话的成员
            return is_numeric($value) && $value != $userId;
        });
        
        // 上锁, 连接时将要操纵此字段
        if (!$cache->lock($callUserField)) {
            throw new OperationFailureException(lang("server busy"));
        }
        
        // 进行连接前的预检
        $connectAvailable = array_every($userIds, function($value) use ($callUserField, $cache) {
            if (RedisModel::hexists($callUserField, $value)) {
                return false;
            }
            return true;
        });
        
        if (!$connectAvailable) {
            $cache->unlock($callUserField);
            throw new OperationFailureException(lang("service unavailable"));
        }
        println("建立 ", $userId, " 与 ", $userIds, "的连接");
        // 连接
        array_for_each($userIds, function($value) use ($userId, $sign) {
            $this->establish($userId, $value, $sign);
        });
        
        // 填充群聊信息
        RedisModel::hsetJson($callUserField, "g", $g);
        
        // 解锁
        $cache->unlock($callUserField);
        return $this->getCallDetail(["sign"=>$sign]);
    }
    
    public function pushCallRequest($userId, $userId2, $callType, $sign=null, $groupId=null) {
        if ($sign === null) {
            // 整个会话的标识
            $sign = implode("-", [
                $userId,
                $userId2,
                time()
            ]);
        }
        // 获取用户信息
        $users = $this->getUserById($sign, $userId, $userId2);
        if (count($users) < 2) {
            throw new OperationFailureException(lang("the other user u receive"));
        }
        $users[1] = array_map_keys($users[1], function($value, $key, $array) {
            return "r$key";
        });
        
        // 获取群聊信息
        $group = [];
        if ($groupId !== null) {
            $groups = $this->getGroupById($sign, $groupId);
            if (count($groups) === 0) {
                throw new OperationFailureException(lang("the other user u receive"));
            }
            $group = $groups[0];
        }
        // 推送
        $gatewayService = SingletonServiceFactory::getGatewayService();
        $data = array_merge([
            "sign" => $sign,
            "ctype" => $callType
        ], $users[0], $users[1], $group);
        $gatewayService->sendToUser($userId2, $data, $gatewayService::COMMUNICATION_ASK_TYPE);
        return $data;
    }
    
    /**
     * 设置聊天详情到缓存
     * @param array $args { userId: number, userId2?: number, groupId?: number, sign?: string, ctype: "video" | "voice" }
     * @return array 设置的数据
     */
    protected function setCallDetail($args) {
        println("设置聊天详情", $args);
        // 参数数据
        $sign = null;
        $groupId = null;
        $userId = $args["userId"];
        $userId2 = null;
        $ctype = $args["ctype"];
        // 当前事件, 用来生成 sign
        $now = time();
        // 保存的 redis hash key 名称.
        $callDetailField = RedisModel::getKeyName("cache_calling_communication_info_hash_key");
        
        if (isset($args["groupId"])) {
            $groupId = $args["groupId"];
        }
        
        // 生成 sign
        if (!isset($args["sign"]) 
            || is_null($args["sign"])) {
            if (is_null($groupId)) {
                $sign = implode("-", [
                    $userId,
                    $userId2,
                    $now
                ]);
            } else {
                $sign = implode("-", [$groupId, $now]);
            }
        } else {
            $sign = $args["sign"];
        }

        // 获取群聊信息
        $group = [];
        if ($groupId !== null) {
            $groups = $this->getGroupById($sign, $groupId);
            if (count ($groups) === 0) throw new OperationFailureException(lang("invalid user"));
            $group = $groups[0];
        }
        
        // 获取用户信息
        $user = [];
        if (isset($args["userId2"])) {
            // 有两个用户 id
            $users = $this->getUserById($sign, $userId, $userId2);
            if (count($users) < 2) throw new OperationFailureException(lang("invalid user"));
            // map 另一个用户信息的属性名
            $users[1] = array_map_keys($users[1], function($value, $key) {
               return  "r$key";
            });
            $user = array_merge($users[0], $users[1]);
        } else {
            // 一个用户 id
            $users = $this->getUserById($sign, $userId);
            if (count($users) === 0) throw new OperationFailureException(lang("invalid user"));
            $user = $users[0];
        }
        
        // 组合数据
        $data = array_merge([ "ctype" => $ctype, "sign"=>$sign ], $user, $group);
        
        // 设置到缓存
        RedisModel::hsetJson($callDetailField, $sign, $data);
        
        // 返回数据
        return $data;
    }
    
    public function test($id) {
        return $this->getUserById("xxx", 1, 5,3,6);
    }
}