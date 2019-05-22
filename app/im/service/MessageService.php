<?php
namespace app\im\service;

use app\im\exception\OperationFailureException;
use app\im\model\IMessageModel;
use app\im\model\ModelFactory;
use think\Db;

class MessageService implements IMessageService {
    
    /**
     * 根据消息的 type 创建对应的拒绝处理的答复信息
     * @param mixed $userId
     * @param mixed $msgId
     * @param array $args
     * @return array 创建的消息
     */
    private function createNegativeMessage($userId, $msgId, $args) {
        $message = ModelFactory::getMessageModel()->getMessageById($msgId);
        if (!is_array($message) || count($message) < 1) {
            throw new OperationFailureException("无效的消息记录");
        }
        $message = $message[0];
        $mData = null;
        $receiver = [];
        $data = [];
        // 构建消息
        switch($message["type"]) {
            case IMessageModel::TYPE_FRIEND_ASK:
                $mData = [
                    "sender_id"=>0,
                    "send_date"=>time(),
                    "type"=>IMessageModel::TYPE_FRIEND_ASK_REFUSE,
                    "content"=>"",
                    "corr_id"=>$userId
                ];
                array_push($receiver, $message["associated"][0]["id"]);
                break;
            case IMessageModel::TYPE_GROUP_ASK:
                $mData = [
                    "sender_id"=>0,
                    "send_date"=>time(),
                    "type"=>IMessageModel::TYPE_GROUP_ASK_REFUSE,
                    "content"=>"",
                    "corr_id"=>$message["associated"][1]["id"]
                ];
                array_push($receiver, $message["associated"][0]["id"]);
                break;
            case IMessageModel::TYPE_GROUP_INVITE:
                $mData = [
                    "sender_id"=>0,
                    "send_date"=>time(),
                    "type"=>IMessageModel::TYPE_GROUP_INVITE_REFUSE,
                    "content"=>"",
                    "corr_id"=>$userId,
                    "corr_id2"=>$message["associated"][2]["id"]
                ];
                array_push($receiver, $message["associated"][0]["id"]);
                break;
                break;
        }
        // 插入消息
        if ($mData != null && count($receiver) > 0) {
            $data = ModelFactory::getMessageModel()->createMessage($mData, $receiver);
        }
        // 返回数据
        return $data;
    }
    
    private function handlePositiveMessage($userId, $msgId, $args) {
        $message = ModelFactory::getMessageModel()->getOriginMessageById($msgId);
        if (!is_array($message) || count($message) < 1) {
            throw new OperationFailureException("无效的消息记录");            
        }
        $message = $message[0];
        $mData = null;
        $receiver = [];
        $data = [];
        im_log("debug", "开始处理消息 ", $msgId, ", ", $message, ", ", $args);
        switch($message["type"]) {
            case IMessageModel::TYPE_FRIEND_ASK:
                im_log("debug", 1);
                // 执行添加好友的操作
                SingletonServiceFactory::getContactService()->addFriend($message["sender_id"], $message["corr_id"], $userId, $args[0]);
                break;
            case IMessageModel::TYPE_GROUP_ASK:
                // 执行添加群聊的操作
                SingletonServiceFactory::getContactService()->joinGroup($message["sender_id"], $message["corr_id"], $message["content"]);
                break;
            case IMessageModel::TYPE_GROUP_INVITE:
                // 执行添加群聊的操作
                SingletonServiceFactory::getContactService()->joinGroup($userId, $message["corr_id2"]);
                break;
        }
        
        // 插入消息
        if ($mData != null && count($receiver) > 0) {
            $data = ModelFactory::getMessageModel()->createMessage($mData, $receiver);
        }
        // 返回数据
        return $data;
    }
    
    public function getMessage($userId, $pageNo=1, $pageSize=150)  {
        try {
            foreach ([$userId, $pageNo, $pageSize] as $m) {
                if (!is_numeric($m) || $m <1) {
                    throw new OperationFailureException("参数错误 !");
                }
            }
            // 获取数据
            $data = ModelFactory::getMessageModel()->getMessageIdAndTypeByUser($userId, $pageNo, $pageSize);
            im_log("debug", $data);
            $data = ModelFactory::getMessageModel()->fillMessageAssociated($data);
            im_log("debug", $data);
            // 标记以读
            ModelFactory::getMessageModel()->updateMessageReadByUser($userId);
            return $data;
        } catch(OperationFailureException $e) {
                throw new $e;
        } catch(\Exception $e) {
            im_log("error", "查询通知消息失败 ", $e);
            throw new OperationFailureException();
        }
    }
    
    public function negativeHandle($userId, $msgId, $args)  {
        // 简单的参数检测
        foreach ([$userId, $msgId] as $m) {
            if (!is_numeric($m) || $m <1) {
                throw new OperationFailureException("参数错误 !");
            }
        }
        try {
            $model = ModelFactory::getMessageModel();
            // 检查消息是否属于用户
            if (!$model->hasMessage($userId, $msgId)) {
                throw new OperationFailureException();
            }
            Db::startTrans();
            $_SESSION["x_in_transaction"] = true;
            // 更新消息处理结果为拒绝
            $effect = $model->updateMessageById($userId, $msgId, ["treat"=>"1", "result"=>"n"]);
            // 没有任何数据被改变.
            if ($effect == 0) {
                throw new OperationFailureException();
            }
            // 生成答复消息
            $this->createNegativeMessage($userId, $msgId, $args);
            Db::commit();
            // 推送未读
            SingletonServiceFactory::getPushService()->pushMsgBoxNotification($userId);
        } catch(OperationFailureException $e) {
            Db::rollback();
            throw $e;
        } catch(\Exception $e) {
            Db::rollback();
            im_log("error", "消息处理失败.", $e);
            throw new OperationFailureException();
        } finally {
            $_SESSION["x_in_transaction"] = false;
        }
    }
    
    public function positiveHandle($userId, $msgId, $args) {
        // 简单的参数检测
        foreach ([$userId, $msgId] as $m) {
            if (!is_numeric($m) || $m <1) {
                throw new OperationFailureException("参数错误 !");
            }
        }
        try {
            $model = ModelFactory::getMessageModel();
            // 检查消息是否属于用户
            if (!$model->hasMessage($userId, $msgId)) {
                throw new OperationFailureException();
            }
            
            $this->handlePositiveMessage($userId, $msgId, $args);
            
            // 更新消息处理结果为同意
            ModelFactory::getMessageModel()
                ->updateMessageById($userId, $msgId, ["treat"=>"1", "result"=>"y"]);
            
        } catch(OperationFailureException $e) {

            throw $e;
        } catch(\Exception $e) {

            im_log("error", "消息处理失败.", $e);
            throw new OperationFailureException();
        }
    }
    public function deleteIndex($id)
    {
        $model = ModelFactory::getMessageModel();
        Db::startTrans();
        try {
            //删除消息
            $model->deleteIndex($id);
            Db::commit();
        }catch (OperationFailureException $e) {
            Db::rollback();
            throw $e;
        } catch (\Exception $e) {
            Db::rollback();
            throw new OperationFailureException();
        }
    }
    
    public function postFeedBack($id)
    {
        $model = ModelFactory::getMessageModel();
        Db::startTrans();
        try {
            //删除消息
            $model->postFeedBack($id);
            Db::commit();
        }catch (OperationFailureException $e) {
            Db::rollback();
            throw $e;
        } catch (\Exception $e) {
            Db::rollback();
            throw new OperationFailureException();
        }
    }


}