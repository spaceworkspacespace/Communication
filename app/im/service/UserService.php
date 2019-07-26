<?php
namespace app\im\service;

use app\im\exception\OperationFailureException;
use app\im\model\RedisModel;
use think\Db;
use think\db\Query;


class UserService implements IUserService {
   
    public function updateUser($userId, $data) {
        try {
            if(!is_null($data["status"])){
                if($data["status"] === "online"){
                    $data["status"] = 1;
                }else if($data["status"] === "offline"){
                    $data["status"] = 2;
                }else{
                    throw new OperationFailureException("请求错误, 请重试!");
                }
            }
            
            if(!is_null($data["sex"])){
                if($data["sex"] === "保密"){
                    $data["sex"] = 0;
                }else if($data["sex"] === "男"){
                    $data["sex"] = 1;
                }else if($data["sex"] === "女"){
                    $data["sex"] = 2;
                }else{
                    throw new OperationFailureException("请求错误, 请重试!");
                }
            }
            
            // 字段过滤和映射
            array_filter($data, function($m) {
                return is_string($m);
            });
            // 备份将要修改的数据, 作为返回值
            $rawData = $data;
            array_key_replace($data, [
                "sign"=>"signature",
                "username" => "user_nickname",
                "useremail" => "user_email",
                "status" => "user_status"
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
        if (is_numeric($userId) 
            && RedisModel::sismember(config("im.cache_chat_online_user_key"), $userId)) {
            return true;
        } else {
            return false;
        }
    }
}