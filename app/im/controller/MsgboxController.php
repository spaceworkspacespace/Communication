<?php
namespace app\im\controller;

use think\Controller;
use think\Db;
use think\Request;
use app\im\service\GatewayServiceImpl;
use Sabberworm\CSS\Property\Charset;
use think\Session;
use app\im\service\IMServiceImpl;
use app\im\exception\OperationFailureException;
use app\im\service\SingletonServiceFactory;

class MsgboxController extends Controller {
    private $service = null;
    private $user = null;
    
    protected $beforeActionList = [
        "checkUserLogin"
    ];
    
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->user = cmf_get_current_user();
        $this->service = IMServiceImpl::getInstance();
    }
    
    protected function checkUserLogin()
    {
        $isLogin = cmf_get_current_user_id();
        im_log("info", "用户登录验证: ", $isLogin);
        
        if (!$isLogin) {
            if ($this->request->isAjax()) {
                $this->success("您尚未登录", cmf_url("user/Login/index"));
            } else {
                $this->redirect(cmf_url("user/Login/index"));
            }
        }
    }
    
    public function getIndex($page = 1, $count=150) {
        $reMsg = "";
        $reData = null;
        $failure = false;
        try {
            $reData = SingletonServiceFactory::getMessageService()->getMessage($this->user["id"], $page, $count);
        } catch(OperationFailureException $e) {
            $reMsg = $e->getMessage();
            $failure = true;
        } finally { 
            if ($failure) {
                $this->success($reMsg, "/", $reData, 0);
            } else {
                $this->error($reMsg, "/", $reData, 0);
            }
        }
//         $imData = Db::table('im_msg_box a,cmf_user b,im_msg_receive c')
//         ->field('a.result,a.id,a.sender_id AS sender,a.content,a.type,a.corr_id AS corrid,a.corr_id2 AS corrid2,a.corr_str AS corrstr,a.corr_str2 AS corrstr2,
//                 b.user_nickname AS username,b.avatar,
//                 c.send_date AS date,c.treat ')
//         ->where('a.sender_id = b.id')
//         ->where('a.id = c.id')
//         ->where('c.receiver_id', $this->user['id'])
//         ->order('a.send_date', 'desc')
//         ->select();
        
//         Session::set('imData', $imData);
        
//         $this->error('成功', '/', $imData, 0);
    }
    
    /**
         * 消息处理
     * @param int $id 消息id
     * @param int $id2 我给发送人设置的分组id
     * @param int $action 0:拒绝||1:同意
     */
    public function postIndex($id, $id2 = null, $_action) {
        $reMsg = "";
        $reData = null;
        $failure = false;
        try {
            switch ($_action) {
                case 0:
                    SingletonServiceFactory::getMessageService()
                        ->negativeHandle($this->user["id"], $id, [$id2]);
                    $reMsg = "处理成功";
                    break;
                case 1:
                    SingletonServiceFactory::getMessageService()
                        ->positiveHandle($this->user["id"], $id, [$id2]);
                    $reMsg = "处理成功";
                    break;
                default:
                    $failure = true;
                    $reMsg = "参数不正确";
                    break;
            }
        } catch(OperationFailureException $e) {
            $reMsg = $e->getMessage();
            $failure = true;
        } finally { 
            if ($failure) {
                $this->success($reMsg, "/", $reData, 0);
            } else {
                $this->error($reMsg, "/", $reData, 0);
            }
        }
//         switch ($this->queryTypeById($id)) {
//             case 1:
//                 //好友申请，为0表示拒绝，1表示同意
//                 if($_action == 0) {
//                     $this->refuse($id);
//                 } else {
//                     $this->agreeFirend($id, $id2);
//                 }
//                 break;
//             case 2:
//                 //申请加入群组，为0表示拒绝，1表示同意
//                 if($_action == 0) {
//                     $this->refuse($id);
//                 } else {
//                     $this->agreeGroup($id);
//                 }
//                 break;
//             case 3:
//                 //邀请加入群组，为0表示拒绝，1表示同意
//                 if($_action == 0) {
//                     $this->refuse($id);
//                 } else {
//                     $this->agreeGroups($id);
//                 }
//                 break;
//             default:
//                 $this->success('噢噢~ 遇到问题了，请稍后重试', '/', null, 0);
//         }
    }
    
    /**
     * 消息删除
     * @param int $id 消息id
     */
    public function deleteIndex($id) {
        //更改im_msg_receive的visible为不可见
        Db::startTrans();
        try {
            Db::table('im_msg_receive')
            ->where(['id' => $id])
            ->update(['visible' => 0]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->success('删除失败', '/', null, 0);
        }
        $this->error('删除成功', '/', null, 0);
    }
    
    /**
     * 消息已读处理
     * @param int $id 消息id
     */
    public function postFeedBack($id) {
        //更改im_msg_receive的read为以读
        Db::startTrans();
        try {
            Db::table('im_msg_receive')
            ->where(['id' => $id])
            ->update(['read' => 1]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->success('噢噢~ 遇到问题了，请稍后重试', '/', null, 0);
        }
        $this->error('成功', '/', null, 0);
    }
    
    /**
         * 根据消息id查询type类型
     * @param int $id 消息id
     */
    protected function queryTypeById($id) {
        return Db::table('im_msg_box')
        ->where('id', $id)
        ->value('type');
    }
    
    /**
         * 拒绝申请
     * @param int $id 消息盒子id
     */
    protected function refuse($id) {
        
        Db::startTrans();
        try {
            
            //拒绝好友申请
            $this->agreeOrRefuse($id, 'n');
            Db::commit();
        } catch (\Exception $e) {
            //回滚事务
            Db::rollback();
            $this->success("噢噢~ 遇到问题了,请稍后重试", "/", null, 0);
        }
        
        $this->error("已拒绝", "/", null, 0);
        
    }
    
    /**
     * 同意接受好友申请
     * @param int $id 消息盒子id
     * @param int $group_id_me 登陆人给发送人设置的分组id
     */
    protected function agreeFirend($id, $group_id_me) {
        
        //获取发送人信息
        $senderinfo = $this->querySenderInfo($id);
        Db::startTrans();
        try {
            //接受申请
            $this->agreeOrRefuse($id, 'y');
            
            //插入加为好友之后的系统消息并返回主键id
            $last_reads_you = $this->addImchatuserInfo($senderinfo['id'],
                time(),
                $senderinfo['send_ip'],
                $this->user['id']);
            $last_reads_me = $this->addImchatuserInfo($this->user['id'],
                time(),
                $this->user['last_login_ip'],
                $senderinfo['id']);
            
            //插入两人成为好友的信息 调用方法
            $this->addImfriendsInfo($senderinfo['id'],
                $this->user['id'],
                $this->user['username'],
                $senderinfo['sender_id'],
                time(),
                time(),
                time(),
                $last_reads_you);
            $this->addImfriendsInfo($this->user['id'],
                $senderinfo['id'],
                $senderinfo['username'],
                $group_id_me,
                time(),
                time(),
                time(),
                $last_reads_me);
            
            //分组人数+1
            $this->addFriendGroupsMembercount($group_id_me, $this->user['id']);
            $this->addFriendGroupsMembercount($senderinfo['sender_id'], $senderinfo['id']);
            
            //回滚事务
            Db::commit();
        } catch (\Exception $e) {
            //回滚事务
            Db::commit();
            $this->success("你们已经是好友了，请勿重复添加哦", "/", $e->getMessage(), 0);
        }
        $this->error("已同意", "/", null, 0);
    }
    
    /**
     * 跟据接收人id查询消息
     * @param mixed $page 页码
     * @param mixed $receiver_id  接收人id
     * @return string
     */
    public function data($page) {
        
        //定义承载外键的数组
        $sender_ids = [];
        
        //定义排序数组
        $sender_ids = [];
        
        //定义循环变量
        $i = 0;
        
        //获取当前登录用户信息
        $this->user = cmf_get_current_user();
        $receiver_id = $this->user['id'];
        
        //查询我的消息
        $mydata = Db::table('im_msg_box')
        ->where('receiver_id = '.$receiver_id)
        ->order('send_date', 'desc')
        ->select()
        ->toArray();
        
        
        //将获取到的时间转换为YMD格式
        $mydata = array_map(function($value) {
            $value['send_date'] = date("Y-m-d",strtotime($value['send_date']));
            return $value;
        }, $mydata);
            
            //获取外键
            foreach ($mydata as $value) {
                $sender_ids[$i] = $value['sender_id'];
                $i++;
            }
            
            //根据外键查询发送人信息
            $udata = Db::table('cmf_user')
            ->field('id,user_nickname,avatar,last_login_ip,signature,user_email')
            ->select();
            
            //初始化承载外键的数组
            $sender_ids = [];
            //初始化循环变量
            $i = 0;
            
            //获取群组id
            foreach ($mydata as $value) {
                $sender_ids[$i] = $value['group_id'];
                $i++;
            }
            
            //根据群组id查询群组信息
            $groups = Db::table('im_group')
            ->where('id','in',$sender_ids)
            ->select();
            
            //排序数据
            $mydata = $this->sortData($mydata);
            
            $list = array("code" => 0, "page" => intval($page), "data" => $mydata, "udata" => $udata, "groups" => $groups);
            //转换为json并返回
            return json_encode($list);
            
    }
    
    
    /**
         * 同意群聊申请(申请)
     * @param int $id 消息id
     */
    protected function agreeGroup($id) {
        
        //从session取出消息数据
        $imData = Session::get('imData');
        //根据id查找相关数据
        for ($i = 0; $i < count($imData); $i++) {
            if($imData[$i]['id'] == $id) {
                $imData = $imData[$i];
                break;
            }
        }
        
        //开启事务
        Db::startTrans();
        try {
            $this->agreeOrRefuse($id, 'y');
            
            $last_reads = Db::table('im_chat_group')
            ->insertGetId([
                'group_id' => $imData['corrid'],
                'sender_id' => 0,
                'send_date' => time(),
                'content' => '用户'.$imData['username'].'加入了群聊'
            ]);
            
            Db::table('im_groups')
            ->insert([
                'user_id' => $imData['sender'],
                'contact_id' => $imData['corrid'],
                'user_alias' => $imData['username'],
                'contact_date' => time(),
                'is_admin' => 0,
                'last_active_time' => time(),
                'last_send_time' => time(),
                'last_reads' => $last_reads
            ]);
            
            //群聊人数+1
            Db::table('im_group')
            ->where('id', $imData['corrid'])
            ->inc('member_count')
            ->update();
            
            //提交事务
            Db::commit();
        } catch (\Exception $e) {
            //回滚事务
            Db::rollback();
            $this->success('您已经在该群了，请勿重复添加哦', '/', $e->getMessage(), 0);
        }
        $this->error('已同意', '/', null, 0);
    }
    
    /**
         * 同意群聊申请(邀请)
     * @param int $id 消息id
     */
    protected function agreeGroups($id) {
        
        //从session取出消息数据
        $imData = Session::get('imData');
        //根据id查找相关数据
        for ($i = 0; $i < count($imData); $i++) {
            if($imData[$i]['id'] == $id) {
                $imData = $imData[$i];
                break;
            }
        }
        
        //开启事务
        Db::startTrans();
        try {
            $this->agreeOrRefuse($id, 'y');
            
            $last_reads = Db::table('im_chat_group')
            ->insertGetId([
                'group_id' => $imData['corrid'],
                'sender_id' => 0,
                'send_date' => time(),
                'content' => '用户'.$this->user['username'].'加入了群聊'
            ]);
            
            Db::table('im_groups')
            ->insert([
                'user_id' => $this->user['id'],
                'contact_id' => $imData['corrid'],
                'user_alias' => $this->user['username'],
                'contact_date' => time(),
                'last_active_time' => time(),
                'last_send_time' => time(),
                'last_reads' => $last_reads
            ]);
            
            //群聊人数+1
            Db::table('im_group')
            ->where('id', $imData['corrid'])
            ->inc('member_count')
            ->update();
            
            //提交事务
            Db::commit();
        } catch (\Exception $e) {
            //回滚事务
            Db::rollback();
            $this->success('噢噢~ 遇到问题了，请稍后重试', '/', $e->getMessage(), 0);
        }
        $this->error('已同意', '/', null, 0);
    }
    
    
    
    
    
    /**
         * 根据发送人id查询发送人的信息
     * @param int $id 消息id
     */
    private function querySenderInfo($id) {
        return Db::table('im_msg_box a,cmf_user b')
        ->where([
            'a.id' => $id
        ])
        ->where('a.sender_id = b.id')
        ->field('b.id,a.sender_id,a.send_ip,b.user_nickname as username')
        ->find();
    }
    
    /**
         * 同意或者拒绝封装方法
     * @param int $id 消息id
     * @param Charset $agree 同意或者拒绝 y|n
     */
    private function agreeOrRefuse($id, $agree) {
        //接受申请
        Db::table('im_msg_box')
        ->where([
            'id' => $id
        ])
        ->update(['result' => $agree]);
        
        Db::table('im_msg_receive')
        ->where([
            'id' => $id
        ])
        ->update([
            'treat' => 1,
            'read' => 1
        ]);
    }
    
    //插入加为好友之后的系统消息并返回主键id
    private function addImchatuserInfo($sender_id, $send_date, $send_ip, $receiver_id) {
        return Db::table('im_chat_user')
        ->insertGetId([
            'sender_id' => $sender_id,
            'send_date' => $send_date,
            'send_ip' => $send_ip,
            'receiver_id' => $receiver_id,
            'content' => '我们已经成为好友啦，赶快开始聊天吧！'
        ]);
    }
    
    /**
     * 插入两人成为好友的信息
     * @param int $user_id 我的id
     * @param int $contact_id 好友的id
     * @param string $contact_alias 好友的备注
     * @param int $group_id 我给好友设置的分组id
     * @param \DateTime $contact_date 
     * @param \DateTime $last_active_time
     * @param \DateTime $last_send_time
     * @param int $last_reads 最后一条发送的消息
     */
    private function addImfriendsInfo($user_id, $contact_id, $contact_alias, $group_id, $contact_date, $last_active_time, $last_send_time, $last_reads) {
        Db::table('im_friends')
        ->insert([
            'user_id' => $user_id,
            'contact_id' => $contact_id,
            'contact_alias' => $contact_alias,
            'group_id' => $group_id,
            'contact_date' => $contact_date,
            'last_active_time' => $last_active_time,
            'last_send_time' => $last_send_time,
            'last_reads' => $last_reads
        ]);
    }
    
    /**
         * 同意好友后分组人数+1
     * @param int $groupid 分组id
     * @param int $id 用户id
     */
    private function addFriendGroupsMembercount($groupid,$id) {
        Db::table('im_friend_groups')
        ->where([
            'id' => $groupid,
            'user_id' => $id
        ])
        ->inc('member_count')
        ->update();;
    }
    
    /**
         * 排序数据
     * @param array $data 需要排序的数组
     * @return array[] 排序后的数组
     */
    private function sortData($data) {
        
        //agree不为空
        $datas = [];
        
        //循环变量
        $i = 0;
        
        //把agree为空的加进新数组
        foreach ($data as $value) {
            if($value['agree'] == null){
                $datas[$i] = $value;
            }
            $i++;
        }
        
        //把agree不为空的加进新数组
        foreach ($data as $value) {
            if($value['agree'] != null){
                $datas[$i] = $value;
            }
            $i++;
        }
        
        return $datas;
    }
    
    public function addToUid($uid, $data) {
        
        GatewayServiceImpl::addToUid($uid, $data);
        
    }
    
    /**
     * 初始化完成. 表示客户端已准备就绪.
     * 初始化接口请求顺序:
     *  init -> bind -> finish
     */
    public function postPull() {
        $userId = cmf_get_current_user_id();
        //         $this->service->pushMsgBoxNotification($userId);
        $this->service->pushAll($userId);
    }
    
    
}