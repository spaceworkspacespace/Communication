<?php
namespace app\im\service;

use app\im\exception\OperationFailureException;
use app\im\model\RedisModel;
use think\Db;
use think\db\Query;


class UserService implements IUserService {
   
    public function updateUser($userId, $data) {
        try {
            // 字段过滤和映射
            array_filter($data, function($m) {
                return is_string($m);
            });
            // 备份将要修改的数据, 作为返回值
            $rawData = $data;
            array_key_replace($data, [
                "sign"=>"signature"
            ]);
            // 没有要修改的数据, 直接为成功
            if (count($data) == 0) {
                return [];
            }
                
            $query = new Query(Db::connect(array_merge(config("database"), ['prefix'   => ''])));
            $affected = $query->table("cmf_user")
                ->where("id=:id")
                ->bind(["id"=>[$userId, \PDO::PARAM_INT]])
                ->update($data);
            im_log("debug", "更新用户信息 $userId, 受影响行数 $affected.");
            // 更新 session 中的值
            $user = cmf_get_current_user();
            array_index_copy($rawData, $user, array_keys($rawData));
            array_key_replace_force($user, [
                "sign"=>"signature"
            ]);
            println($user);
            im_log("debug", $user);
            cmf_update_current_user($user);
            return $rawData;
        } catch(\Exception $e) {
            im_log("error", "更新用户信息失败! $userId, 信息: ", $data, ", error: ", $e);
            throw new OperationFailureException("更新失败, 请稍后重试~");
//             return [];
        }
    }
    
    public function isOnline($userId): bool
    {
        $cache = RedisModel::getRedis();
        if ($cache->rawCommand("SISMEMBER", config("im.cache_chat_online_user_key"), $userId) 
            !== 0) {
            return true;
        } else {
            return false;
        }
    }
}