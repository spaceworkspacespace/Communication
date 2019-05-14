<?php
namespace app\im\service;

require_once implode([
    __DIR__,
    DIRECTORY_SEPARATOR,
    "IIMService.php"
]);

use GatewayClient\Gateway;
use app\im\controller\ContactController;
use app\im\exception\OperationFailureException;
use think\Db;
use think\db\Query;

class IMServiceImpl implements IIMService
{
    private static $instance;
    private static  $query;
    public const USER_FIELD = "id, avatar, user_nickname AS username, signature AS sign";

    /**
     * 
     * @return IIMService | ChatService
     */
    public static function getInstance() : IMServiceImpl{
        if (!static::$instance) {
            static::$instance = new static();
            static::$query  = new Query(Db::connect(array_merge(config("database"), ['prefix'   => ''])));
        }
        return static::$instance;
    }
    
    /**
     * 便利方法
     * @return \think\db\Query
     */
    protected static function getQuery() {
        return static::$query;
    }
    
    public function createFriendGroup($userId, $groupName): array {
       return SingletonServiceFactory::getContactService()->createFriendGroup($userId, $groupName);
    }
    
    public function init($userId)
    {
        $friend = $this->getOwnFriendGroups($userId);
        if (!count($friend)) $this->createFriendGroup($userId, "我的好友"); 
        return [
            "mine"=>$this->getUserById($userId)[0],
            "friend" => $this->findOwnFriends($userId),
            "group" => $this->findOwnGroups($userId)
        ];
    }

    public function findOwnFriends($userId): array
    {
        // return model("user_entire")::contacts($userId);
//         return UserModel::contacts($userId);
//         return model("friends")->getFriendAndGroup($userId);
        return SingletonServiceFactory::getContactService()->getFriendAndGroup($userId);
    }

    
    /**
     * 获取自己的群聊以及成员
     * {@inheritDoc}
     * @see \app\im\service\IIMService::findOwnGroups()
     */
    public function findOwnGroups($id): array
    {
            $list = null;
            $res = null;
            $newList = null;
            
            //查询自己的群聊
            $groupIds = Db::table('im_groups')
                ->where(['user_id' => $id])
                ->column('contact_id');
            
            for ($i = 0; $i < count($groupIds); $i++) {
                //查询群聊信息
                $list = Db::table('im_group')
                    ->where('id', $groupIds[$i])
                    ->field('id,groupname,description,avatar,create_time AS createtime,admin_id AS admin, admin_count AS admincount,member_count AS membercount')
                    ->find();
                
                //查询群聊成员id
                $userIds = Db::table('im_groups')
                    ->where(['contact_id' => $groupIds[$i]])
                    ->column('user_id');
                
                //根据id查询成员信息
                $res = Db::table('cmf_user a,im_groups b')
                    ->where('a.id = b.user_id')
                    ->where(['b.contact_id' => $groupIds[$i]])
                    ->where('a.id', 'in', $userIds)
                    ->field('b.user_alias AS username,a.id,a.avatar,a.signature AS SIGN,a.sex, b.is_admin AS isadmin')
                    ->select()
                    ->toArray();
                
                //如果缓存中存在用户id就改成在线,否则不在线
                $con = new ContactController(request());
                $res = $con->checkOnOrOff($res);
                
                //组合返回数据
                $list['list'] = $res;
                $newList[$i] = $list;
            }
            
            return $newList; 
    }

    public function findFriends($key): array
    {
        try {
            return model("user")->getQuery()
                ->field(self::USER_FIELD)
                ->where("id", "=", $key)
                ->union(function(Query $q) use($key) {
                    $q->table("cmf_user")
                        ->field(self::USER_FIELD)
                        ->where("user_nickname", "LIKE", "%$key%");
                })
                ->select()
                ->toArray();
        } catch(\Exception $e) {
            im_log("error", "查找用户失败. error: ", $e);
            throw new OperationFailureException("查找失败, 请稍后重试~");
        }
    }

    public function findGroups($key, $no, $count): array
    {
        return SingletonServiceFactory::getContactService()->getGroupByCondition($key, $no, $count);
    }
    
    public function getGroupById($id): array
    {
        try {
            return module("group")->getGroupById($id);
        } catch(\Exception $e) {
            im_log("error", "分组查询失败 ! id: $id", $e);
            throw new OperationFailureException("查询失败, 请稍后重试~");
        }
    }
    
    public function getFriendAndGroup($userId): array {
        return SingletonServiceFactory::getContactService()->getFriendAndGroup($userId);
    }
    
    public function getOwnFriendGroups($userId): array
    {
        try {
            return model("friend_groups")->getQuery()
                ->alias("friend_g")
                ->field("id, group_name as groupname")
                ->where("friend_g.user_id=:id")
                ->bind(["id"=>[$userId, \PDO::PARAM_INT]])
                ->select()
                ->toArray();
        } catch (OperationFailureException $e) {
            throw $e;
        } catch (\Exception $e) {
            im_log("error", "好友分组查询失败. ", $e);
            throw new OperationFailureException("好友分组查询失败, 请稍后重试~");
        }
    }
    
   

    public function getUserById($userId): array
    {
        
        // 查找用户信息, im 扩展信息. 如果不存在, 更新进去.
        //         $model = model("user");
        //         $user = $model->get($userId);
        //         if (is_null($user) || ! count($user = $user->getData())) {
        //             $user = [
        //                 "user_id" => $userId,
        //                 "sign" => ""
        //             ];
        //             $model->save($user);
        //         }
        //         return $user;
        
        try {
            return model("user")->getQuery()
            ->field(self::USER_FIELD)
            ->where("id=:id")
            ->bind(["id"=>[$userId, \PDO::PARAM_INT]])
            ->select()
            ->toArray();
        } catch(\Exception $e) {
            im_log("error", "查询用户失败, 用户的 id: $userId; error: ", $e);
            throw new OperationFailureException("查询失败, 请稍后重试~");
        }
    }
    
    public function createGroup($creator, string $groupName, string $pic, string $desc) {
         SingletonServiceFactory::getContactService()->createGroup($creator, $groupName, $pic, $desc);
    }
    
    public function getGroupByName($name, $exact = true): array
    {
        $group = model("group")->getQuery();
        if ($exact) {
            $operate = "=";
            $condition = $name;
        } else {
            $operate = "LIKE";
            $condition = "%$name%";
        }
        try {
            $res = $group->where("groupname", $operate, $condition)
                ->select();
            return $res->toArray();
        } catch(\ErrorException $e) {
            im_log("error", "查询失败 !", $e);
            throw new OperationFailureException("查询失败了.");
        }
    }
    
    public function linkFriendMsg($sender, $friendGroupId, $receiver, $content, $ip = null): void
    {
        SingletonServiceFactory::getContactService()->addFriendAsk($sender, $friendGroupId, $receiver, $content, $ip);
    }

    public function linkGroupMsg($sender, $groupId, $content, $ip = null): void
    {
        $dateStr = date(self::SQL_DATE_FORMAT);
        $msgBox = model("msg_box")->getQuery();
        $group = model("group")->getQuery();
        $groups = model("groups")->getQuery();
        
        
        try {
            // 判断是否已是群成员.
            if ($groups->where([
                "user_id"=>["=", $sender],
                "contact_id"=>["=", $groupId]
            ])->count()) {
                throw new OperationFailureException("您已经是群聊成员了~");
            }
            
            // 查询群组管理者 id.
            $receiver = $group -> where("id", "=", $groupId)
                ->field("creator_id")
                ->select();
            
            if (!count($receiver) || !isset($receiver[0]["creator_id"])) {
                im_log("error", "加入不存在的群聊 ! 群聊 id: $groupId, 查询结果: ", $receiver);
                throw new OperationFailureException("群聊不存在~");
            }
            // 获取到群聊管理者的 id.
            $receiver = $receiver[0]["creator_id"];
            
            $data = [
                "sender_id"=>$sender,
                "send_date"=>$dateStr,
                "receiver_id"=>$receiver,
                "group_id"=>$groupId,
                "content"=>$content
            ];
            if (!is_null($ip)) $data["send_ip"] = $ip;
            $msgId = $msgBox
                ->insert($data, false, true);
            if (count(Gateway::getClientIdByUid($receiver))) {
                $msg = $msgBox->where("id", "=", $msgId)->select()->toArray();
                // 错误了, 没得信息.
                if (!count($msg)) {
                    im_log("error", "消息盒子信息插入异常. id: $msgId, 内容: ", $msg);
                    throw new OperationFailureException("信息发送异常 !");
                }
                // 发送消息
//                 GatewayServiceImpl::askToUid($receiver, $msg);
                $this->pushMsgBoxNotification($receiver);
            }
        } catch(OperationFailureException $e) {
            throw $e;
        } catch(\Exception $e) {
            im_log("error", "消息插入失败 !", $e);
            throw new OperationFailureException("消息发送失败 !");
        }
    }
    
    public function pushAll($uid): bool {
        return SingletonServiceFactory::getPushService()->pushAll($uid);
    }
    
    public function pushMsgBoxNotification($uid): bool {
        return SingletonServiceFactory::getPushService()->pushMsgBoxNotification($uid);
    }
    
    public function pushUnreadMessage($uid,$contactId=null, $type=IChatService::CHAT_FRIEND): bool {
        return SingletonServiceFactory::getPushService()->pushUnreadMessage($uid, $contactId, $type);
    }
}