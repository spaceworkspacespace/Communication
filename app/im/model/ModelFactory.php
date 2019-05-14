<?php
namespace app\im\model;

use think\Db;
use think\db\Query;
use app\admin\model\UserModel;

class ModelFactory
{
    private static $chatFriendModel = null;
    private static $chatGroupModel = null;
    private static $friendModel = null;
    private static $groupModel = null;
    private static $imodel = null;
    private static $messageModel = null;
    private static $query = null;
    private static $userModel = null;
    
    public static function getChatFriendModel($buildNew = false): IChatFriendModel {
        if ($buildNew) {
            return new ChatUserModel();
        }
        if (ModelFactory::$chatFriendModel == null) {
            ModelFactory::$chatFriendModel = model("chat_user");;
        }
        return ModelFactory::$chatFriendModel;
    }
    
    public static function getChatGroupModel($buildNew = false): IChatGroupModel {
        if ($buildNew) {
            return new ChatGroupModel();
        }
        if (ModelFactory::$chatGroupModel == null) {
            ModelFactory::$chatGroupModel = model("chat_group");;
        }
        return ModelFactory::$chatGroupModel;
    }
    
    public static function getGroupModel($buildNew = false): IGroupModel {
        if ($buildNew) {
            return new FriendsModel();
        }
        if (ModelFactory::$groupModel == null) {
            ModelFactory::$groupModel = model("group");;
        }
        return ModelFactory::$groupModel;
    }
    
    public static function getFriendModel($buildNew = false): IFriendModel {
        if ($buildNew) {
            return new FriendsModel();
        }
        if (ModelFactory::$friendModel == null) {
            ModelFactory::$friendModel = model("friends");;
        }
        return ModelFactory::$friendModel;
    }
    
    public static function getIModel($buildNew = false): IMModel{
        if ($buildNew) {
            return new IMModel();
        }
        if (ModelFactory::$imodel == null) {
            ModelFactory::$imodel = new IMModel();
        }
        return ModelFactory::$imodel;
    }
    
    public static function getMessageModel($buildNew = false): IMessageModel {
        if ($buildNew) {
            return new MsgBoxModel();
        }
        if (ModelFactory::$messageModel == null) {
            ModelFactory::$messageModel = model("msg_box");;
        }
        return ModelFactory::$messageModel;
    }
    
    public static function getQuery($buildNew = false) {
        if ($buildNew) {
            return new Query(Db::connect(array_merge(config("database"), ['prefix'   => ''])));
        }
        if (ModelFactory::$query == null) {
            ModelFactory::$query = new Query(Db::connect(array_merge(config("database"), ['prefix'   => ''])));
        }
        return ModelFactory::$query;
    }
    
    public static function getUserModel($buildNew = false): IUserModel {
        if ($buildNew) {
            return new UserModel();
        }
        if (ModelFactory::$userModel == null) {
            ModelFactory::$userModel = model("user");;
        }
        return ModelFactory::$userModel;
    }
}

