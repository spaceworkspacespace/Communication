<?php 

namespace app\im\controller;

use think\Controller;
use think\Db;
use app\user\controller\AdminUserActionController;

class MsgboxController extends Controller {
    
    /**
     * 跟我接收人id查询消息
     * @param unknown $page 页码
     * @param unknown $receiver_id  接收人id
     * @return string
     */
    public function data($page) {
        
        
        //获取当前登录用户信息
        $this->user = cmf_get_current_user();
        $receiver_id = $this->user["id"];
        
        //查询我的消息
        $mydata = Db::table('im_msg_box')
        ->where('receiver_id = '.$receiver_id)
        ->select()
        ->toArray();
        
        //定义承载外键的数组
        $sender_ids = [];
        //定义循环变量
        $i = 0;
        
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
        ->where('id','in',$sender_ids)
        ->select();
        
        $list = array("code" => 0,"page" => intval($page),"data" => $mydata,"udata" => $udata);
        //转换为json并返回
        return json_encode($list);
        
    }
    
    /**
     * //拒绝接受好友申请
     * @param unknown $sender_id 请求人id
     * @param unknown $receiver_id  接收人id
     * @return number|string
     */
    public function refuse($sender_id,$receiver_id) {
        
        $res = Db::table('im_msg_box')
        ->where([
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id
        ])
        ->update(['agree' => 'n']);
        
        return $res;
    }
    
    /**
     * 同意接受好友申请
     * @param unknown $sender_id 请求人id
     * @param unknown $send_ip 请求人ip地址
     * @param unknown $receiver_id 接收人id
     * @param unknown $group_id_me 接收人设置给请求人的分组id
     * @param unknown $group_id_you 请求人设置给接收人的分组id
     * @return number|string
     */
    public function agreeFriends($sender_id, $send_ip, $receiver_id, $group_id_me, $group_id_you) {
        
        //定义受影响行数
        $res = 0;
        
        //获取接受人ip地址
        $receiver_ip = cmf_get_current_user()["last_login_ip"];
        
        //开启事务
        Db::startTrans();
        try {
            //获取当前时间
            $nowDate = date('Y-m-d h:i:s', time());
            
            
            $groupOfNull = Db::table('im_friend_groups')
            ->where([
                'id' => $group_id_me,
                'user_id' => $sender_id
            ])
            ->select();
            //判断请求人是否有这个分组
            if($groupOfNull){
                
               Db::table('im_friend_groups')
               ->insert([
                   'user_id' => $sender_id,
                   'priority' => 1,
                   'member_count' => 0
               ]);
               
            }
            
            //接受好友申请
            Db::table('im_msg_box')
            ->where([
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id
            ])
            ->update(['agree' => 'y']);
            
            //插入加为好友之后的系统消息并返回主键id
            $last_reads_you = Db::table('im_chat_user')
            ->insertGetId([
                'sender_id' => $sender_id, 
                'send_ip' => $send_ip, 
                'receiver_id' => $receiver_id, 
                'content' => '我们已经成为好友啦，赶快开始聊天吧！'
            ]);
            $last_reads_me = Db::table('im_chat_user')
            ->insertGetId([
                'sender_id' => $receiver_id, 
                'send_ip' => $receiver_ip, 
                'receiver_id' => $sender_id, 
                'content' => '我们已经成为好友啦，赶快开始聊天吧！'
            ]);
            
            //插入两人成为好友的信息
            Db::table('im_friends')
            ->insert([
                'user_id' => $sender_id, 
                'contact_id' => $receiver_id, 
                'group_id' => $group_id_me, 
                'contact_date' => $nowDate, 
                'last_active_time' => $nowDate, 
                'last_send_time' => $nowDate, 
                'last_reads' => $last_reads_you
            ]);
            Db::table('im_friends')
            ->insert([
                'user_id' => $receiver_id, 
                'contact_id' => $sender_id, 
                'group_id' => $group_id_you, 
                'contact_date' => $nowDate, 
                'last_active_time' => $nowDate, 
                'last_send_time' => $nowDate, 
                'last_reads' => $last_reads_me
            ]);
            
            //提交事务
            Db::commit();
            $res = 1;
        } catch (\Exception $e) {
            //回滚事务
            Db::rollback();
            $res = 0;
        }
        
        return $res;
    }
    
    public function agreeGroup($sender_id, $group_id, $send_ip, $sender_nickname, $receiver_id) {
        
        //定义受影响行数
        $res = 0;
        
        //开启事务
        Db::startTrans();
        try {
            
            //获取当前时间
            $nowDate = date('Y-m-d h:i:s', time());
            
            $last_reads = Db::table('im_chat_group')
            ->insertGetId([
                'group_id' => $group_id,
                'sender_id' => $sender_id,
                'send_ip' => $send_ip,
                'content' => '用户'.$sender_nickname.'加入了群聊'
            ]);
            
            Db::table('im_groups')
            ->insert([
                'user_id' => $sender_id,
                'contact_id' => $group_id,
                'contact_date' => $nowDate,
                'last_active_time' => $nowDate,
                'last_send_time' => $nowDate,
                'last_reads' => $last_reads
            ]);
            
            //接受加群申请
            Db::table('im_msg_box')
            ->where([
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id
            ])
            ->update(['agree' => 'y']);
            
            //提交事务
            Db::commit();
            $res = 1;
        } catch (\Exception $e) {
            //回滚事务
            Db::rollback();
            $res = 0;
        }
        
        return $res;
        
    }
    
}